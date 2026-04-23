<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\includes\Logging;
use App\Services\GitReaderService;
use App\Services\RepositoryService;

/**
 * Serves raw file content from a git repository — no HTML, no styling,
 * exactly like GitHub's raw.githubusercontent.com viewer.
 *
 * Route: ?page=raw&slug=owner/repo&branch=main&path=src/file.php
 */
final class RawController
{
    public function run(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $rawSlug = trim($_GET['slug'] ?? '');
        if (strlen($rawSlug) > 200) {
            $this->abort(414, '414 URI Too Long');
        }
        if ($rawSlug === '' || ! preg_match('#^[a-zA-Z0-9][a-zA-Z0-9_-]{0,49}/[a-zA-Z0-9][a-zA-Z0-9._-]{0,98}$#', $rawSlug)) {
            $this->abort(404, '404 Not Found');
        }

        $rawPath = trim($_GET['path'] ?? '', '/');
        $segments = [];
        foreach (explode('/', $rawPath) as $seg) {
            if ($seg === '' || $seg === '.' || $seg === '..') {
                continue;
            }
            if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._\- ]*$/', $seg)) {
                $this->abort(400, '400 Bad Request: invalid path');
            }
            $segments[] = $seg;
        }
        if (empty($segments)) {
            $this->abort(400, '400 Bad Request: path is required');
        }
        $filePath = implode('/', $segments);

        $branch = preg_replace('/[^a-zA-Z0-9._\/-]/', '', $_GET['branch'] ?? 'main');
        if ($branch === '') {
            $branch = 'main';
        }

        $config = Config::getInstance();
        $pdo = $config->getPDO();
        $repo = null;

        if ($pdo !== null) {
            try {
                $service = new RepositoryService($pdo, $config->getDataRoot());
                $repo = $service->getBySlug($rawSlug);
            } catch (\PDOException $e) {
                Logging::loggingToFile('RawController SQL error: ' . $e->getMessage(), 4);
            }
        }

        if ($repo === null) {
            $this->abort(404, '404 Not Found');
        }

        $sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
        $isLoggedIn = (bool) ($_SESSION['is_logged_in'] ?? false);
        $role = (string) ($_SESSION['role'] ?? '');
        $isOwner = $isLoggedIn && $sessionUserId === (int) $repo['owner_user_id'];
        $isAdmin = $isLoggedIn && $role === 'ADMIN';

        if ($repo['visibility'] === 'private' && ! $isOwner && ! $isAdmin) {
            $this->abort(403, '403 Forbidden');
        }

        $repoPath = $config->getDataRoot() . '/' . $repo['owner_username'] . '/' . $repo['repo_name'];
        $git = new GitReaderService($repoPath);

        if ($git->isEmpty()) {
            $this->abort(404, '404 Not Found: repository is empty');
        }

        $objType = $git->getObjectType($branch, $filePath);
        if ($objType !== 'blob') {
            $this->abort(404, '404 Not Found: path is not a file');
        }

        $safePath = escapeshellarg($repoPath);
        $safeRef = escapeshellarg($branch . ':' . $filePath);
        $sizeStr = trim((string) shell_exec("git -C {$safePath} cat-file -s {$safeRef} 2>/dev/null"));
        $size = is_numeric($sizeStr) ? (int) $sizeStr : 0;
        $peek = (string) shell_exec("git -C {$safePath} show {$safeRef} 2>/dev/null | head -c 8192");
        $isBinary = str_contains($peek, "\x00");

        header('Content-Type: ' . ($isBinary ? 'application/octet-stream' : 'text/plain; charset=UTF-8'));
        if ($size > 0) {
            header('Content-Length: ' . $size);
        }
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        passthru("git -C {$safePath} show {$safeRef} 2>/dev/null");
        exit;
    }

    private function abort(int $code, string $message): never
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $message;
        exit;
    }
}
