<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

final class RepositoryLocator
{
    public function __construct(private readonly \PDO $pdo, private readonly string $dataRoot)
    {
    }

    public static function isValidSlug(string $slug): bool
    {
        return preg_match('#^[A-Za-z0-9][A-Za-z0-9_-]{0,49}/[A-Za-z0-9][A-Za-z0-9._-]{0,98}$#D', $slug) === 1;
    }

    /** @return array<string, mixed>|null */
    public function find(string $slug, bool $requireDisk = true): ?array
    {
        if (!self::isValidSlug($slug)) {
            throw new InvalidArgumentException('Invalid repository slug.');
        }

        [$owner, $name] = explode('/', $slug, 2);
        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.owner_user_id, r.repo_name, r.slug, r.repo_description, r.visibility,
                    r.default_branch, r.stars, r.forks, r.lang, r.created_at, r.updated_at,
                    u.username AS owner_username, u.display_name AS owner_display_name
               FROM repositories r
               JOIN users u ON u.id = r.owner_user_id
              WHERE u.username = ? AND r.repo_name = ?
              LIMIT 1'
        );
        $stmt->execute([$owner, $name]);
        $repo = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($repo === false) {
            return null;
        }

        if ($requireDisk) {
            $repo['path'] = $this->resolvePath($owner, $name);
        }

        return $repo;
    }

    public function resolvePath(string $owner, string $name): string
    {
        if (!RepositoryService::isValidUsername($owner) || !RepositoryService::isValidRepoName($name)) {
            throw new InvalidArgumentException('Invalid repository path components.');
        }

        $root = realpath($this->dataRoot);
        $repo = realpath(rtrim($this->dataRoot, '/') . '/' . $owner . '/' . $name);
        if ($root === false) {
            throw new RuntimeException('DATA_ROOT is unavailable.');
        }
        if ($repo === false || !str_starts_with($repo, $root . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Repository path is unavailable or outside DATA_ROOT.');
        }
        if (!is_dir($repo) || !is_file($repo . '/HEAD') || !is_dir($repo . '/objects')) {
            throw new RuntimeException('Repository is not a bare Git repository.');
        }

        return $repo;
    }
}
