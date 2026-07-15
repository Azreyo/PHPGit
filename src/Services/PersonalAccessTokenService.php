<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class PersonalAccessTokenService
{
    private const string PREFIX = 'phpgit_pat_';

    public function __construct(private readonly \PDO $pdo)
    {
    }

    /** @return array{token: string, id: int, prefix: string} */
    public function create(int $userId, string $name, string $scope, ?DateTimeImmutable $expiresAt): array
    {
        $name = trim($name);
        if ($userId <= 0 || $name === '' || mb_strlen($name) > 100) {
            throw new InvalidArgumentException('Token name must be between 1 and 100 characters.');
        }
        if (!in_array($scope, ['read', 'write'], true)) {
            throw new InvalidArgumentException('Invalid token scope.');
        }
        if ($expiresAt !== null && $expiresAt <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
            throw new InvalidArgumentException('Token expiry must be in the future.');
        }

        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $token = self::PREFIX . $secret;
        $visiblePrefix = substr($token, 0, 20);
        $stmt = $this->pdo->prepare(
            'INSERT INTO personal_access_tokens (user_id, name, token_prefix, token_hash, scope, expires_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $name,
            $visiblePrefix,
            hash('sha256', $token),
            $scope,
            $expiresAt?->format('Y-m-d H:i:s'),
        ]);

        return ['token' => $token, 'id' => (int)$this->pdo->lastInsertId(), 'prefix' => $visiblePrefix];
    }

    /** @return array{id:int,user_id:int,username:string,role:string,scope:string}|null */
    public function authenticate(string $username, string $token, bool $write): ?array
    {
        if (!str_starts_with($token, self::PREFIX) || strlen($token) > 128) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT t.id, t.user_id, t.token_hash, t.scope, u.username, u.role
               FROM personal_access_tokens t
               JOIN users u ON u.id = t.user_id
              WHERE t.token_prefix = ? AND u.username = ? AND u.status = 'ACTIVE'
                AND t.revoked_at IS NULL AND (t.expires_at IS NULL OR t.expires_at > CURRENT_TIMESTAMP)
              LIMIT 10"
        );
        $stmt->execute([substr($token, 0, 20), $username]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            if (hash_equals((string)$row['token_hash'], hash('sha256', $token))) {
                if ($write && (string)$row['scope'] !== 'write') {
                    return null;
                }
                $this->pdo->prepare('UPDATE personal_access_tokens SET last_used_at = CURRENT_TIMESTAMP WHERE id = ?')
                    ->execute([(int)$row['id']]);

                return [
                    'id' => (int)$row['id'],
                    'user_id' => (int)$row['user_id'],
                    'username' => (string)$row['username'],
                    'role' => (string)$row['role'],
                    'scope' => (string)$row['scope'],
                ];
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, token_prefix, scope, expires_at, last_used_at, revoked_at, created_at
               FROM personal_access_tokens WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);

        return array_values($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function revoke(int $userId, int $tokenId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE personal_access_tokens SET revoked_at = CURRENT_TIMESTAMP
              WHERE id = ? AND user_id = ? AND revoked_at IS NULL'
        );
        $stmt->execute([$tokenId, $userId]);

        return $stmt->rowCount() === 1;
    }
}
