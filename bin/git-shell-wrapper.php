#!/usr/bin/env php
<?php
/**
 * PHPGit SSH shell wrapper
 *
 * This script is set as the forced command in ~git/.ssh/authorized_keys:
 *
 *   command="/usr/bin/php /var/www/phpgit/bin/git-shell-wrapper.php 42",\
 *   no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty \
 *   ssh-ed25519 AAAA... user@host
 *
 * Where "42" is the PHPGit user ID associated with the SSH key.
 *
 * The script:
 *  1. Validates the user ID and account status.
 *  2. Parses SSH_ORIGINAL_COMMAND (git-upload-pack / git-receive-pack).
 *  3. Checks repository existence and access permissions.
 *  4. Execs the appropriate git binary against the bare repo on disk.
 */
declare(strict_types=1);

// ── Bootstrap ──────────────────────────────────────────────────────────────
chdir(dirname(__DIR__));
require __DIR__ . '/../vendor/autoload.php';

use App\Config;
use App\includes\Logging;

function die_err(string $msg): never
{
    fwrite(STDERR, "PHPGit: {$msg}\n");
    exit(128);
}

// ── User ID from argv ──────────────────────────────────────────────────────
$userId = isset($argv[1]) ? (int)$argv[1] : 0;
if ($userId <= 0) {
    die_err('Invalid user.');
}

// ── Connect to DB ──────────────────────────────────────────────────────────
$config = Config::getInstance();
$pdo = $config->getPdo();
if ($pdo === null) {
    die_err('Database unavailable. Contact administrator.');
}

// ── Validate account ───────────────────────────────────────────────────────
$userStmt = $pdo->prepare("SELECT id, username, status FROM users WHERE id = ? LIMIT 1");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if ($user === false || $user['status'] !== 'ACTIVE') {
    Logging::loggingToFile("SSH access denied for user_id={$userId}: account inactive/missing", 2, true);
    die_err('Account is inactive or does not exist.');
}

// ── Parse SSH_ORIGINAL_COMMAND ─────────────────────────────────────────────
$originalCmd = getenv('SSH_ORIGINAL_COMMAND') ?: ($_SERVER['SSH_ORIGINAL_COMMAND'] ?? '');

if ($originalCmd === '') {
    // Interactive login attempt — greet the user, just like GitHub does.
    $name = (string)$user['username'];
    echo "PTY allocation request failed on channel 0\n";
    echo "Hi {$name}! You've successfully authenticated, but PHPGit does not provide shell access.\n";
    exit(0);
}

// Expected format: git-upload-pack 'owner/repo.git'
//              or: git-receive-pack 'owner/repo.git'
//              or: git-upload-archive 'owner/repo.git'
if (!preg_match(
        '/^(git-upload-pack|git-receive-pack|git-upload-archive)\s+\'([a-zA-Z0-9][a-zA-Z0-9_\/-]{0,149}(?:\.git)?)\'$/',
        $originalCmd,
        $m
)) {
    Logging::loggingToFile("SSH invalid command for user_id={$userId}: {$originalCmd}", 2, true);
    die_err('Invalid git command.');
}

$gitCmd = $m[1];
$rawSlug = $m[2];

// ── Normalise repo slug (strip trailing .git) ──────────────────────────────
$slug = preg_replace('/\.git$/', '', $rawSlug);
$parts = explode('/', $slug, 2);

if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
    die_err('Invalid repository path.');
}

[$ownerUsername, $repoName] = $parts;

// ── Look up repository ─────────────────────────────────────────────────────
$repoStmt = $pdo->prepare(
        'SELECT r.id, r.visibility
       FROM repositories r
       JOIN users u ON u.id = r.owner_user_id
      WHERE u.username = ? AND r.repo_name = ?
      LIMIT 1'
);
$repoStmt->execute([$ownerUsername, $repoName]);
$repo = $repoStmt->fetch();

if ($repo === false) {
    die_err('Repository not found.');
}

// ── Check permissions ──────────────────────────────────────────────────────
$isWriteOp = ($gitCmd === 'git-receive-pack');

if ($isWriteOp || $repo['visibility'] === 'private') {
    $permStmt = $pdo->prepare(
            'SELECT permission
           FROM repository_members
          WHERE repository_id = ? AND user_id = ?
          LIMIT 1'
    );
    $permStmt->execute([$repo['id'], $userId]);
    $member = $permStmt->fetch();

    if ($member === false) {
        Logging::loggingToFile(
                "SSH access denied: user_id={$userId} has no membership in repo_id={$repo['id']}",
                2,
                true
        );
        die_err('Access denied.');
    }

    if ($isWriteOp && !in_array($member['permission'], ['owner', 'maintainer', 'write'], true)) {
        Logging::loggingToFile(
                "SSH write access denied: user_id={$userId} permission={$member['permission']} repo_id={$repo['id']}",
                2,
                true
        );
        die_err('Write access denied.');
    }
}

// ── Resolve repo path safely ───────────────────────────────────────────────
$dataRoot = $config->getDataRoot();
$repoRelPath = $ownerUsername . '/' . $repoName;
$repoPath = $dataRoot . '/' . $repoRelPath;
$realRepo = realpath($repoPath);
$realData = realpath($dataRoot);

if ($realRepo === false || $realData === false || !str_starts_with($realRepo, $realData . '/')) {
    Logging::loggingToFile("SSH path validation failed: {$repoPath}", 3);
    die_err('Repository not accessible.');
}

// ── Exec git command ───────────────────────────────────────────────────────
$gitBin = trim((string)shell_exec('which git')) ?: '/usr/bin/git';

if (!is_executable($gitBin)) {
    die_err('git binary not found on this server.');
}

// pcntl_exec replaces current process – no return on success
pcntl_exec($gitBin, [$gitCmd, $realRepo]);

// Only reached if exec failed
Logging::loggingToFile("pcntl_exec failed for git cmd={$gitCmd} path={$realRepo}", 4);
die_err('Failed to execute git command.');


