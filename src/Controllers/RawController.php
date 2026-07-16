<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\includes\Logging;
use App\Services\GitReaderService;
use App\Services\RepositoryAccessPolicy;
use App\Services\RepositoryLocator;
use Throwable;

final class RawController
{
    public function run(): never
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            header('Allow: GET');
            $this->abort(405, 'Method not allowed');
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $slug = $this->scalarQuery('slug');
        $branch = $this->scalarQuery('branch') ?: 'main';
        $path = trim($this->scalarQuery('path'), '/');
        if (!RepositoryLocator::isValidSlug($slug) || !$this->isValidPath($path)) {
            $this->abort(400, 'Bad request');
        }

        $config = Config::getInstance();
        $pdo = $config->getPDO();
        if ($pdo === null) {
            $this->abort(503, 'Service unavailable');
        }

        try {
            $repo = (new RepositoryLocator($pdo, $config->getDataRoot()))->find($slug);
        } catch (Throwable $e) {
            Logging::loggingToFile('Raw repository resolution failed: ' . $e->getMessage(), 3, true);
            $this->abort(404, 'Not found');
        }
        if ($repo === null) {
            $this->abort(404, 'Not found');
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = (string) ($_SESSION['role'] ?? '');
        if (!(new RepositoryAccessPolicy($pdo))->canRead($repo, $userId, $role)) {
            $this->abort($userId > 0 ? 403 : 401, 'Access denied');
        }

        $git = new GitReaderService((string)$repo['path']);
        $commit = $git->resolveRef($branch);
        if ($commit === null || $git->getObjectType($commit, $path) !== 'blob') {
            $this->abort(404, 'Not found');
        }

        $size = $git->getBlobSize($commit, $path);
        $peek = $git->readBlob($commit, $path, 8192);
        $binary = str_contains($peek, "\0");
        header('Content-Type: ' . ($binary ? 'application/octet-stream' : 'text/plain; charset=UTF-8'));
        if ($size >= 0) {
            header('Content-Length: ' . $size);
        }
        header('Content-Disposition: inline; filename="' . addcslashes(basename($path), '"\\') . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        $git->streamBlob($commit, $path, static function (string $chunk): void {
            echo $chunk;
            flush();
        });
        exit;
    }

    private function isValidPath(string $path): bool
    {
        if ($path === '' || strlen($path) > 4096 || !mb_check_encoding($path, 'UTF-8') || preg_match('/[\x00-\x1f\x7f]/', $path) === 1) {
            return false;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || strlen($segment) > 255) {
                return false;
            }
        }

        return true;
    }

    private function scalarQuery(string $key): string
    {
        $value = $_GET[$key] ?? '';

        return is_string($value) ? $value : '';
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
