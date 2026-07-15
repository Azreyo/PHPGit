<?php

declare(strict_types=1);

namespace App\Services;

use App\includes\Logging;
use Throwable;

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
     * @return array{
     *     success: bool,
     *     error: string|null,
     *     path: string|null
     * }
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
        $defaultBranch = trim($defaultBranch);
        if (!self::isValidBranchName($defaultBranch)) {
            $defaultBranch = 'main';
        }
        $defaultBranch = self::untaintShellArgument($defaultBranch);

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

        [$exitCode, $gitOutput] = $this->initializeBareRepository($repoPath, $defaultBranch);

        if ($exitCode !== 0) {
            $this->removeDirectory($repoPath);
            Logging::loggingToFile('git init failed for ' . $repoPath . ': ' . $gitOutput, 4);

            return ['success' => false, 'error' => 'Failed to initialise git repository.', 'path' => null];
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                'INSERT INTO repositories (owner_user_id, repo_name, slug, repo_description, visibility, default_branch)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$ownerUserId, $repoName, $slug, $description ?: null, $visibility, $defaultBranch]);
            $repoId = (int)$this->pdo->lastInsertId();

            $member = $this->pdo->prepare(
                'INSERT INTO repository_members (repository_id, user_id, permission) VALUES (?, ?, ?)'
            );
            $member->execute([$repoId, $ownerUserId, 'owner']);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->removeDirectory($repoPath);
            Logging::loggingToFile('Repository database creation failed for ' . $slug . ': ' . $e->getMessage(), 4);

            return ['success' => false, 'error' => 'Failed to save repository.', 'path' => null];
        }

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

        return array_values($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * Marks a file-system path as safe after validation.
     * The taint-escape annotation tells Psalm's taint analysis that this
     * function is an intentional sanitization point for file paths.
     *
     * @psalm-taint-escape file
     * @psalm-taint-escape shell
     */
    private static function untaintPath(string $path): string
    {
        return $path;
    }

    /**
     * Marks a Git argument as safe after strict branch-name validation.
     *
     * @psalm-taint-escape shell
     */
    private static function untaintShellArgument(string $argument): string
    {
        return $argument;
    }

    private static function isValidBranchName(string $branch): bool
    {
        return $branch !== ''
            && strlen($branch) <= 255
            && preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/', $branch) === 1
            && !str_contains($branch, '..')
            && !str_contains($branch, '//')
            && !str_contains($branch, '@{')
            && !str_ends_with($branch, '/')
            && !str_ends_with($branch, '.')
            && !str_ends_with($branch, '.lock');
    }

    /** @return array{int, string} */
    private function initializeBareRepository(string $path, string $defaultBranch): array
    {
        $process = proc_open(
            ['git', 'init', '--bare', '-b', $defaultBranch, '--', $path],
            [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            [
                'GIT_CONFIG_NOSYSTEM' => '1',
                'GIT_TERMINAL_PROMPT' => '0',
                'PATH' => '/usr/bin:/bin',
            ],
            ['bypass_shell' => true]
        );
        if (!is_resource($process)) {
            return [1, 'Unable to start git.'];
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), trim((string)$stdout . "\n" . (string)$stderr)];
    }

    private function removeDirectory(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }
        if (! is_dir($path)) {
            return;
        }
        $items = new \FilesystemIterator(
            $path,
            \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO
        );
        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }
            $itemPath = $item->getPathname();
            if ($item->isLink() || !$item->isDir()) {
                @unlink($itemPath);
            } else {
                $this->removeDirectory($itemPath);
            }
        }
        @rmdir($path);
    }
}
