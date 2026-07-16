<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\includes\Logging;
use App\Services\PersonalAccessTokenService;
use App\Services\RepositoryAccessPolicy;
use App\Services\RepositoryLocator;
use App\Services\RepositoryService;
use Throwable;

final class GitHttpController
{
    private const array SAFE_CGI_HEADERS = [
        'content-type' => 'Content-Type',
        'content-length' => 'Content-Length',
        'cache-control' => 'Cache-Control',
        'expires' => 'Expires',
        'pragma' => 'Pragma',
    ];

    public function run(): never
    {
        $config = Config::getInstance();
        $pdo = $config->getPDO();
        if ($pdo === null) {
            $this->abort(503, 'Service unavailable');
        }

        $owner = $this->scalarQuery('_git_user');
        $name = $this->scalarQuery('_git_repo');
        $path = '/' . ltrim($this->scalarQuery('_git_path'), '/');
        $slug = $owner . '/' . $name;
        if (!RepositoryLocator::isValidSlug($slug)) {
            $this->abort(400, 'Bad request');
        }

        try {
            $repo = (new RepositoryLocator($pdo, $config->getDataRoot()))->find($slug);
        } catch (Throwable $e) {
            Logging::loggingToFile('Git HTTP repository resolution failed: ' . $e->getMessage(), 3, true);
            $this->abort(404, 'Repository not found');
        }
        if ($repo === null) {
            $this->abort(404, 'Repository not found');
        }

        [$service, $write] = $this->validateRoute($path);
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST') {
            $expected = 'application/x-' . $service . '-request';
            $contentType = strtolower(trim(explode(';', (string)($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
            if ($contentType !== $expected) {
                $this->abort(415, 'Unsupported media type');
            }
        }

        $userId = 0;
        $role = '';
        $username = null;
        $requiresAuth = $write || (string)$repo['visibility'] === 'private';
        $credentials = $this->credentials();
        if ($credentials !== null) {
            [$username, $password] = $credentials;
            $authenticated = (new PersonalAccessTokenService($pdo))->authenticate($username, $password, $write);
            if ($authenticated !== null) {
                $userId = $authenticated['user_id'];
                $role = $authenticated['role'];
            } elseif ($this->legacyPasswordsEnabled()) {
                $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE username = ? AND status = 'ACTIVE' LIMIT 1");
                $stmt->execute([$username]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row !== false && password_verify($password, (string)$row['password'])) {
                    $userId = (int)$row['id'];
                    $role = (string)$row['role'];
                    Logging::loggingToFile('Deprecated Git HTTP password authentication used by ' . $username, 2, true, true);
                }
            }
        }
        if ($requiresAuth && $userId <= 0) {
            $this->requireAuthentication();
        }

        $policy = new RepositoryAccessPolicy($pdo);
        $allowed = $write
            ? $policy->canWrite($repo, $userId, $role)
            : $policy->canRead($repo, $userId, $role);
        if (!$allowed) {
            if ($userId <= 0) {
                $this->requireAuthentication();
            }
            Logging::loggingToFile('Git HTTP access denied for user_id=' . $userId . ' repo_id=' . $repo['id'], 2, true, true);
            $this->abort(403, 'Access denied');
        }

        $this->runBackend($config->getDataRoot(), $owner, $name, $path, $service, $username);
    }

    /** @return array{string, bool} */
    private function validateRoute(string $path): array
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($path === '/info/refs') {
            if ($method !== 'GET') {
                header('Allow: GET');
                $this->abort(405, 'Method not allowed');
            }
            $service = $this->scalarQuery('service');
            if (!in_array($service, ['git-upload-pack', 'git-receive-pack'], true)) {
                $this->abort(400, 'Invalid Git service');
            }

            return [$service, $service === 'git-receive-pack'];
        }
        if ($method !== 'POST') {
            header('Allow: POST');
            $this->abort(405, 'Method not allowed');
        }
        $service = ltrim($path, '/');
        if (!in_array($service, ['git-upload-pack', 'git-receive-pack'], true)) {
            $this->abort(404, 'Not found');
        }

        return [$service, $service === 'git-receive-pack'];
    }

    private function runBackend(string $root, string $owner, string $name, string $path, string $service, ?string $username): never
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $environment = [
            'GIT_PROJECT_ROOT' => realpath($root) ?: $root,
            'GIT_HTTP_EXPORT_ALL' => '1',
            'GIT_CONFIG_NOSYSTEM' => '1',
            'GIT_TERMINAL_PROMPT' => '0',
            'PATH_INFO' => '/' . $owner . '/' . $name . $path,
            'REQUEST_METHOD' => $method,
            'QUERY_STRING' => $method === 'GET' ? 'service=' . rawurlencode($service) : '',
            'CONTENT_TYPE' => (string)($_SERVER['CONTENT_TYPE'] ?? ''),
            'PATH' => '/usr/bin:/bin',
        ];
        if (isset($_SERVER['CONTENT_LENGTH']) && ctype_digit((string)$_SERVER['CONTENT_LENGTH'])) {
            $environment['CONTENT_LENGTH'] = (string)$_SERVER['CONTENT_LENGTH'];
        }
        $contentEncoding = strtolower((string)($_SERVER['HTTP_CONTENT_ENCODING'] ?? ''));
        if (in_array($contentEncoding, ['gzip', 'deflate'], true)) {
            $environment['HTTP_CONTENT_ENCODING'] = $contentEncoding;
        }
        if ($username !== null) {
            $environment['REMOTE_USER'] = $username;
        }
        $gitProtocol = (string)($_SERVER['HTTP_GIT_PROTOCOL'] ?? '');
        if ($gitProtocol !== '' && strlen($gitProtocol) <= 128 && preg_match('/^[A-Za-z0-9.=:-]+$/', $gitProtocol) === 1) {
            $environment['HTTP_GIT_PROTOCOL'] = $gitProtocol;
        }

        $process = proc_open(
            ['git', 'http-backend'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $environment,
            ['bypass_shell' => true]
        );
        if (!is_resource($process)) {
            $this->abort(500, 'Git backend unavailable');
        }

        $input = fopen('php://input', 'rb');
        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $headerBuffer = '';
        $headersSent = false;
        $stderr = '';
        $inputDone = $method === 'GET' || $input === false;
        $pendingInput = '';
        if ($method === 'GET' && is_resource($input)) {
            fclose($input);
        }
        $started = microtime(true);
        $timeout = max(30, min((int)($_ENV['GIT_HTTP_TIMEOUT'] ?? 300), 1800));

        while (true) {
            if (microtime(true) - $started > $timeout) {
                proc_terminate($process, 9);
                Logging::loggingToFile('Git HTTP backend timed out', 3);
                break;
            }
            $status = proc_get_status($process);
            $read = [$pipes[1], $pipes[2]];
            $write = $inputDone ? [] : [$pipes[0]];
            $except = null;
            @stream_select($read, $write, $except, 0, 200000);

            if ($write !== [] && is_resource($input)) {
                if ($pendingInput === '') {
                    $chunk = fread($input, 65536);
                    if ($chunk === false || $chunk === '') {
                        $inputDone = true;
                        fclose($input);
                    } else {
                        $pendingInput = $chunk;
                    }
                }
                if ($pendingInput !== '') {
                    $written = fwrite($pipes[0], $pendingInput);
                    if ($written === false) {
                        proc_terminate($process, 9);
                        $stderr .= 'Unable to stream request to Git backend.';
                        $inputDone = true;
                    } else {
                        $pendingInput = substr($pendingInput, $written);
                    }
                }
                if ($inputDone && $pendingInput === '' && is_resource($pipes[0])) {
                    fclose($pipes[0]);
                }
            } elseif ($inputDone && is_resource($pipes[0])) {
                fclose($pipes[0]);
            }

            foreach ($read as $pipe) {
                $chunk = fread($pipe, 65536);
                if ($chunk === false || $chunk === '') {
                    continue;
                }
                if ($pipe === $pipes[2]) {
                    $stderr .= substr($chunk, 0, max(0, 65536 - strlen($stderr)));
                    continue;
                }
                if (!$headersSent) {
                    $headerBuffer .= $chunk;
                    if (strlen($headerBuffer) > 65536) {
                        proc_terminate($process, 9);
                        $this->abort(502, 'Invalid Git backend response');
                    }
                    $split = strpos($headerBuffer, "\r\n\r\n");
                    $separatorLength = 4;
                    if ($split === false) {
                        $split = strpos($headerBuffer, "\n\n");
                        $separatorLength = 2;
                    }
                    if ($split !== false) {
                        $this->sendCgiHeaders(substr($headerBuffer, 0, $split));
                        $headersSent = true;
                        echo substr($headerBuffer, $split + $separatorLength);
                        $headerBuffer = '';
                    }
                } else {
                    echo $chunk;
                    flush();
                }
            }

            if (!$status['running'] && feof($pipes[1]) && feof($pipes[2])) {
                break;
            }
        }

        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        $exit = proc_close($process);
        if ($exit !== 0 && $stderr !== '') {
            Logging::loggingToFile('git http-backend failed: ' . trim($stderr), 3);
        }
        if (!$headersSent) {
            $this->abort(502, 'Git backend error');
        }
        exit;
    }

    private function sendCgiHeaders(string $block): void
    {
        foreach (preg_split('/\r?\n/', $block) ?: [] as $line) {
            if (preg_match('/^Status:\s*(\d{3})\b/i', $line, $match) === 1) {
                http_response_code((int)$match[1]);
                continue;
            }
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }
            $name = strtolower(trim(substr($line, 0, $colon)));
            $value = trim(substr($line, $colon + 1));
            if (isset(self::SAFE_CGI_HEADERS[$name]) && preg_match('/[\r\n]/', $value) !== 1) {
                header(self::SAFE_CGI_HEADERS[$name] . ': ' . $value);
            }
        }
        header('X-Content-Type-Options: nosniff');
    }

    /** @return array{string,string}|null */
    private function credentials(): ?array
    {
        $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if ($header === '' && isset($_SERVER['PHP_AUTH_USER'])) {
            $username = (string)$_SERVER['PHP_AUTH_USER'];
            $password = (string)($_SERVER['PHP_AUTH_PW'] ?? '');

            return RepositoryService::isValidUsername($username) && strlen($password) <= 255
                ? [$username, $password]
                : null;
        }
        if (!str_starts_with($header, 'Basic ')) {
            return null;
        }
        $decoded = base64_decode(substr($header, 6), true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return null;
        }
        [$username, $password] = explode(':', $decoded, 2);
        if (!RepositoryService::isValidUsername($username) || strlen($password) > 255) {
            return null;
        }

        return [$username, $password];
    }

    private function legacyPasswordsEnabled(): bool
    {
        return filter_var($_ENV['GIT_HTTP_PASSWORD_AUTH'] ?? 'true', FILTER_VALIDATE_BOOL);
    }

    private function scalarQuery(string $key): string
    {
        $value = $_GET[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    private function requireAuthentication(): never
    {
        header('WWW-Authenticate: Basic realm="PHPGit"');
        $this->abort(401, 'Authentication required');
    }

    private function abort(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');
        echo $message . "\n";
        exit;
    }
}
