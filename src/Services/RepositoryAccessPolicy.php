<?php

declare(strict_types=1);

namespace App\Services;

final class RepositoryAccessPolicy
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function permission(int $repositoryId, int $userId): ?string
    {
        if ($userId <= 0) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT permission FROM repository_members WHERE repository_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$repositoryId, $userId]);
        $permission = $stmt->fetchColumn();

        return is_string($permission) ? $permission : null;
    }

    /** @param array<string, mixed> $repo */
    public function canRead(array $repo, int $userId = 0, string $role = ''): bool
    {
        if ($role === 'ADMIN' || (int)$repo['owner_user_id'] === $userId) {
            return true;
        }
        if ((string)$repo['visibility'] === 'public') {
            return true;
        }

        return $this->permission((int)$repo['id'], $userId) !== null;
    }

    /** @param array<string, mixed> $repo */
    public function canWrite(array $repo, int $userId, string $role = ''): bool
    {
        if ($role === 'ADMIN' || (int)$repo['owner_user_id'] === $userId) {
            return true;
        }

        return in_array($this->permission((int)$repo['id'], $userId), ['owner', 'maintainer', 'write'], true);
    }

    /** @param array<string, mixed> $repo */
    public function canManage(array $repo, int $userId, string $role = ''): bool
    {
        return $role === 'ADMIN' || (int)$repo['owner_user_id'] === $userId;
    }
}
