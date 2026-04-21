<?php
/**
 * PHPGit – Git HTTP Smart Protocol handler
 *
 * Rewrite rules in .htaccess route requests like:
 *   GET  /{user}/{repo}.git/info/refs?service=git-upload-pack
 *   POST /{user}/{repo}.git/git-upload-pack
 *   POST /{user}/{repo}.git/git-receive-pack
 *
 * to this file with query params:
 *   _git_user, _git_repo, _git_path
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config;
use App\includes\Logging;

function requireAuth(): never
{
    header('WWW-Authenticate: Basic realm="PHPGit"');
    http_response_code(401);
    exit("Authentication required\n");
}

$gitUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['_git_user'] ?? '');
$gitRepo = preg_replace('/[^a-zA-Z0-9._-]/', '', $_GET['_git_repo'] ?? '');
$gitPath = $_GET['_git_path'] ?? '/';

if ($gitPath === '' || $gitPath[0] !== '/') {
    $gitPath = '/' . $gitPath;
}

if ($gitUser === '' || $gitRepo === '') {
    http_response_code(400);
    exit("Bad request\n");
}

$service = $_GET['service'] ?? '';
$isWriteOp = ($service === 'git-receive-pack')
    || str_ends_with($gitPath, '/git-receive-pack');
$config = Config::getInstance();
$pdo = $config->getPdo();

if ($pdo === null) {
    http_response_code(503);
    exit("Service unavailable\n");
}

$repoStmt = $pdo->prepare(
    'SELECT r.id, r.visibility, r.owner_user_id
       FROM repositories r
       JOIN users u ON u.id = r.owner_user_id
      WHERE u.username = ? AND r.repo_name = ?
      LIMIT 1'
);
$repoStmt->execute([$gitUser, $gitRepo]);
$repo = $repoStmt->fetch();

if ($repo === false) {
    http_response_code(404);
    exit("Repository not found\n");
}

$authedUser = null;
$authedUserId = null;
$requiresAuth = ($repo['visibility'] === 'private') || $isWriteOp;

$authHeader = $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    ?? '';
if ($authHeader === '' && isset($_SERVER['PHP_AUTH_USER'])) {
    $authLogin = $_SERVER['PHP_AUTH_USER'];
    $authPass = $_SERVER['PHP_AUTH_PW'] ?? '';
} elseif (str_starts_with($authHeader, 'Basic ')) {
    $decoded = base64_decode(substr($authHeader, 6), true);
    if ($decoded === false) {
        requireAuth();
    }
    [$authLogin, $authPass] = array_pad(explode(':', $decoded, 2), 2, '');
} else {
    $authLogin = '';
    $authPass = '';
}

if ($requiresAuth) {
    if ($authLogin === '') {
        requireAuth();
    }

    $credStmt = $pdo->prepare(
        "SELECT id, password
           FROM users
          WHERE username = ? AND status = 'ACTIVE'
          LIMIT 1"
    );
    $credStmt->execute([$authLogin]);
    $authRow = $credStmt->fetch();

    if ($authRow === false || !password_verify($authPass, $authRow['password'])) {
        Logging::loggingToFile("Git HTTP auth failure for user: {$authLogin}", 2, true);
        requireAuth();
    }

    $authedUserId = (int)$authRow['id'];
    $authedUser = $authLogin;

    if ($isWriteOp) {
        $permStmt = $pdo->prepare(
            "SELECT permission
               FROM repository_members
              WHERE repository_id = ? AND user_id = ?
              LIMIT 1"
        );
        $permStmt->execute([$repo['id'], $authedUserId]);
        $member = $permStmt->fetch();

        if ($member === false || !in_array($member['permission'], ['owner', 'maintainer', 'write'], true)) {
            http_response_code(403);
            exit("Write access denied\n");
        }
    }
}

$dataRoot = $config->getDataRoot();
$pathInfo = '/' . $gitUser . '/' . $gitRepo . $gitPath;

$env = [
    'GIT_PROJECT_ROOT' => $dataRoot,
    'GIT_HTTP_EXPORT_ALL' => '1',
    'PATH_INFO' => $pathInfo,
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? '',
    'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? '',
    'PATH' => getenv('PATH') ?: '/usr/bin:/bin:/usr/local/bin',
    'HOME' => getenv('HOME') ?: '/tmp',
];

if ($authedUser !== null) {
    $env['REMOTE_USER'] = $authedUser;
}

if (!empty($_SERVER['HTTP_CONTENT_ENCODING'])) {
    $env['HTTP_CONTENT_ENCODING'] = $_SERVER['HTTP_CONTENT_ENCODING'];
}

if (!empty($_SERVER['CONTENT_LENGTH'])) {
    $env['CONTENT_LENGTH'] = (string)(int)$_SERVER['CONTENT_LENGTH'];
}

$descriptors = [
    0 => ['pipe', 'r'],   // stdin  – request body
    1 => ['pipe', 'w'],   // stdout – CGI response
    2 => ['pipe', 'w'],   // stderr – errors
];

$process = proc_open('git http-backend', $descriptors, $pipes, null, $env);

if (!is_resource($process)) {
    http_response_code(500);
    exit("Failed to start git http-backend\n");
}

$input = fopen('php://input', 'rb');
if ($input !== false) {
    stream_copy_to_stream($input, $pipes[0]);
    fclose($input);
}
fclose($pipes[0]);

$rawOutput = stream_get_contents($pipes[1]);
$errorOut = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

if ($exitCode !== 0 || $rawOutput === false || $rawOutput === '') {
    if ($errorOut !== '') {
        Logging::loggingToFile("git http-backend stderr: {$errorOut}", 3);
    }
    http_response_code(500);
    exit("Git backend error\n");
}

$crlfPos = strpos($rawOutput, "\r\n\r\n");
$lfPos = strpos($rawOutput, "\n\n");

if ($crlfPos !== false && ($lfPos === false || $crlfPos <= $lfPos)) {
    $headerBlock = substr($rawOutput, 0, $crlfPos);
    $body = substr($rawOutput, $crlfPos + 4);
    $lineSep = "\r\n";
} elseif ($lfPos !== false) {
    $headerBlock = substr($rawOutput, 0, $lfPos);
    $body = substr($rawOutput, $lfPos + 2);
    $lineSep = "\n";
} else {
    http_response_code(500);
    exit("Malformed response from git backend\n");
}

foreach (explode($lineSep, $headerBlock) as $line) {
    if (preg_match('/^Status:\s*(\d+)/i', $line, $m)) {
        http_response_code((int)$m[1]);
    } elseif (trim($line) !== '') {
        header($line);
    }
}

echo $body;