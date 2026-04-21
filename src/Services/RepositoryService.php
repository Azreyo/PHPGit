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
        return (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,98}$/', $name);
    }

    public static function isValidUsername(string $username): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,49}$/', $username);
    }

    /**
     * @return array<string, mixed>
     */
    public function create(
        int    $ownerUserId,
        string $ownerUsername,
        string $repoName,
        string $description = '',
        string $visibility = 'public',
        string $defaultBranch = 'main'
    ): array {
        $repoName = trim($repoName);
        $description = trim($description);
        $visibility = in_array($visibility, ['public', 'private'], true) ? $visibility : 'public';
        $defaultBranch = preg_replace('/[^a-zA-Z0-9._\/-]/', '', trim($defaultBranch)) ?: 'main';

        if (! self::isValidUsername($ownerUsername)) {
            return ['success' => false, 'error' => 'Invalid owner username.', 'path' => null];
        }

        if (! self::isValidRepoName($repoName)) {
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

        $realDataRoot = realpath($this->dataRoot);
        if ($realDataRoot === false) {
            Logging::loggingToFile('DATA_ROOT does not exist or is not accessible: ' . $this->dataRoot, 4);

            return ['success' => false, 'error' => 'Server configuration error.', 'path' => null];
        }

        $repoPath = $realDataRoot . '/' . $ownerUsername . '/' . $repoName;

        if (! str_starts_with($repoPath, $realDataRoot . '/')) {
            Logging::loggingToFile('Path traversal attempt blocked: ' . $repoPath, 3, true);

            return ['success' => false, 'error' => 'Invalid repository path.', 'path' => null];
        }

        // Path has been validated against realDataRoot; untaint for static-analysis tools.
        $repoPath = self::untaintPath($repoPath);

        if (is_dir($repoPath)) {
            return ['success' => false, 'error' => 'Repository directory already exists on disk.', 'path' => null];
        }

        /** @psalm-suppress TaintedFile - path is validated against $realDataRoot via str_starts_with above */
        if (! mkdir($repoPath, 0755, true)) {
            Logging::loggingToFile('Failed to create repo directory: ' . $repoPath, 4);

            return ['success' => false, 'error' => 'Failed to create repository directory.', 'path' => null];
        }

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
        $repoId = (int) $this->pdo->lastInsertId();

        // Add owner as member
        $member = $this->pdo->prepare(
            'INSERT INTO repository_members (repository_id, user_id, permission) VALUES (?, ?, ?)'
        );
        $member->execute([$repoId, $ownerUserId, 'owner']);

        Logging::loggingToFile("Repository created: {$slug} (id={$repoId})", 1);

        return ['success' => true, 'error' => null, 'path' => $repoPath];
    }

    /**
     * Look up a repository by its "owner/repo" slug string.
     * Resolves via the owner username + repo_name join so the query works
     * regardless of what value is stored in the slug column.
     *
     * @return array<string, mixed>|null
     */
    public function getBySlug(string $slug): ?array
    {
        $parts = explode('/', $slug, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }
        [$ownerUsername, $repoName] = $parts;

        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.owner_user_id, r.repo_name, r.slug, r.repo_description, r.visibility,
                    r.default_branch, r.stars, r.forks, r.lang, r.created_at, r.updated_at,
                    u.username AS owner_username, u.display_name AS owner_display_name
             FROM repositories r
             JOIN users u ON u.id = r.owner_user_id
             WHERE u.username = ? AND r.repo_name = ?
             LIMIT 1'
        );
        $stmt->execute([$ownerUsername, $repoName]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
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

    /**
     * Marks a file-system path as safe after validation.
     * The taint-escape annotation tells Psalm's taint analysis that this
     * function is an intentional sanitization point for file paths.
     *
     * @psalm-taint-escape file
     */
    private static function untaintPath(string $path): string
    {
        return $path;
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
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
