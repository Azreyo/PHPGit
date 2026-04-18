<?php

declare(strict_types=1);

namespace App\Services;

use App\includes\Logging;

class RepositoryService
{
    private \PDO $pdo;
    private string $dataRoot;

    public function __construct(\PDO $pdo, string $dataRoot)
    {
        $this->pdo = $pdo;
        $this->dataRoot = rtrim($dataRoot, '/');
    }

    public static function isValidRepoName(string $name): bool
    {
        return (bool)preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,98}$/', $name);
    }

    public function create(
        int    $ownerUserId,
        string $ownerUsername,
        string $repoName,
        string $description = '',
        string $visibility = 'public',
        string $defaultBranch = 'main'
    ): array
    {
        $repoName = trim($repoName);
        $description = trim($description);
        $visibility = in_array($visibility, ['public', 'private'], true) ? $visibility : 'public';
        $defaultBranch = preg_replace('/[^a-zA-Z0-9._\/-]/', '', trim($defaultBranch)) ?: 'main';

        if (!self::isValidRepoName($repoName)) {
            return ['success' => false, 'error' => 'Invalid repository name. Use letters, numbers, hyphens, underscores, or dots.', 'path' => null];
        }

        $slug = $ownerUsername . '/' . $repoName;

        $check = $this->pdo->prepare(
            'SELECT id FROM repositories WHERE owner_user_id = ? AND repo_name = ? LIMIT 1'
        );
        $check->execute([$ownerUserId, $repoName]);
        if ($check->fetch()) {
            return ['success' => false, 'error' => 'You already have a repository with that name.', 'path' => null];
        }

        $repoPath = $this->dataRoot . '/' . $ownerUsername . '/' . $repoName;

        if (is_dir($repoPath)) {
            return ['success' => false, 'error' => 'Repository directory already exists on disk.', 'path' => null];
        }

        // Create directory
        if (!mkdir($repoPath, 0755, true)) {
            Logging::loggingToFile('Failed to create repo directory: ' . $repoPath, 4);
            return ['success' => false, 'error' => 'Failed to create repository directory.', 'path' => null];
        }

        // Initialise bare git repository
        $escapedPath = escapeshellarg($repoPath);
        $escapedBranch = escapeshellarg($defaultBranch);
        exec("git init --bare -b {$escapedBranch} {$escapedPath} 2>&1", $output, $exitCode);

        if ($exitCode !== 0) {
            $this->removeDirectory($repoPath);
            $gitOutput = implode("\n", $output);
            Logging::loggingToFile('git init failed for ' . $repoPath . ': ' . $gitOutput, 4);
            return ['success' => false, 'error' => 'Failed to initialise git repository.', 'path' => null];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO repositories (owner_user_id, repo_name, slug, repo_description, visibility, default_branch)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$ownerUserId, $repoName, $slug, $description ?: null, $visibility, $defaultBranch]);
        $repoId = (int)$this->pdo->lastInsertId();

        // Add owner as member
        $member = $this->pdo->prepare(
            'INSERT INTO repository_members (repository_id, user_id, permission) VALUES (?, ?, ?)'
        );
        $member->execute([$repoId, $ownerUserId, 'owner']);

        Logging::loggingToFile("Repository created: {$slug} (id={$repoId})", 1);

        return ['success' => true, 'error' => null, 'path' => $repoPath];
    }

    public function getByOwner(int $ownerUserId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.repo_name, r.slug, r.repo_description, r.visibility,
                    r.default_branch, r.stars, r.forks, r.lang, r.created_at, r.updated_at
             FROM repositories r
             WHERE r.owner_user_id = ?
             ORDER BY r.updated_at DESC'
        );
        $stmt->execute([$ownerUserId]);
        return $stmt->fetchAll();
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($path);
    }
}

