<?php
declare(strict_types=1);

namespace App\Services;

use App\includes\Logging;

/**
 * Manages SSH public keys for PHPGit users.
 *
 * Responsibilities:
 *  – CRUD operations on the ssh_keys DB table.
 *  – Rebuilding the git system user's authorized_keys file after every change.
 *
 * authorized_keys format per entry:
 *   command="/usr/bin/php /var/www/phpgit/bin/git-shell-wrapper.php {user_id}",\
 *   no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty {public_key}
 */
class SshKeyService
{
    /** Permitted key algorithm prefixes */
    private const ALLOWED_KEY_TYPES = [
        'ssh-ed25519',
        'ssh-rsa',
        'ecdsa-sha2-nistp256',
        'ecdsa-sha2-nistp384',
        'ecdsa-sha2-nistp521',
        'sk-ssh-ed25519@openssh.com',
        'sk-ecdsa-sha2-nistp256@openssh.com',
    ];

    private \PDO $pdo;
    private string $authorizedKeysPath;
    private string $gitShellWrapperPath;

    public function __construct(\PDO $pdo, string $authorizedKeysPath, string $gitShellWrapperPath)
    {
        $this->pdo = $pdo;
        $this->authorizedKeysPath = $authorizedKeysPath;
        $this->gitShellWrapperPath = $gitShellWrapperPath;
        $this->ensureTableExists();
    }

    /**
     * Create the ssh_keys table if it does not exist yet.
     * Allows the feature to work even if the installer hasn't been re-run.
     */
    private function ensureTableExists(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS ssh_keys (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id     INT UNSIGNED NOT NULL,
                title       VARCHAR(100) NOT NULL,
                key_type    VARCHAR(50)  NOT NULL,
                public_key  TEXT         NOT NULL,
                fingerprint VARCHAR(100) NOT NULL,
                created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY ux_ssh_keys_fingerprint (fingerprint),
                INDEX ix_ssh_keys_user (user_id),
                CONSTRAINT fk_ssh_keys_user FOREIGN KEY (user_id)
                    REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * @return array{success: bool, error: ?string, key?: array<string,mixed>}
     */
    public function addKey(int $userId, string $title, string $publicKey): array
    {
        $title = trim($title);
        $publicKey = trim($publicKey);

        if ($title === '' || strlen($title) > 100) {
            return ['success' => false, 'error' => 'Title must be between 1 and 100 characters.'];
        }

        $parsed = $this->parsePublicKey($publicKey);
        if ($parsed === null) {
            return ['success' => false, 'error' => 'Invalid public key format. Supported types: ' . implode(', ', self::ALLOWED_KEY_TYPES) . '.'];
        }

        $fingerprint = $this->computeFingerprint($publicKey);
        if ($fingerprint === null) {
            return ['success' => false, 'error' => 'Could not compute key fingerprint. Ensure ssh-keygen is available.'];
        }

        // Uniqueness check
        $check = $this->pdo->prepare('SELECT id FROM ssh_keys WHERE fingerprint = ? LIMIT 1');
        $check->execute([$fingerprint]);
        if ($check->fetch() !== false) {
            return ['success' => false, 'error' => 'This key is already registered (duplicate fingerprint).'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ssh_keys (user_id, title, key_type, public_key, fingerprint)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $parsed['type'], $parsed['normalized'], $fingerprint]);
        $keyId = (int)$this->pdo->lastInsertId();

        Logging::loggingToFile("SSH key added: key_id={$keyId} user_id={$userId} fingerprint={$fingerprint}", 1);

        $this->rebuildAuthorizedKeys();

        return [
            'success' => true,
            'error' => null,
            'key' => [
                'id' => $keyId,
                'title' => $title,
                'key_type' => $parsed['type'],
                'fingerprint' => $fingerprint,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Delete a key owned by the given user.
     */
    public function deleteKey(int $keyId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM ssh_keys WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$keyId, $userId]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        Logging::loggingToFile("SSH key deleted: key_id={$keyId} user_id={$userId}", 1);
        $this->rebuildAuthorizedKeys();

        return true;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listKeys(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, key_type, fingerprint, created_at
               FROM ssh_keys
              WHERE user_id = ?
              ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    // ── authorized_keys management ────────────────────────────────────────

    /**
     * Regenerate the authorized_keys file for the git system user.
     * Called automatically after every add/delete.
     */
    public function rebuildAuthorizedKeys(): void
    {
        $dir = dirname($this->authorizedKeysPath);
        if (!is_dir($dir)) {
            Logging::loggingToFile("authorized_keys directory missing: {$dir}", 3);
            return;
        }

        $phpBin = PHP_BINARY;
        $wrapperEsc = escapeshellarg($this->gitShellWrapperPath);

        // Fetch all active keys joined with their user ID
        $stmt = $this->pdo->prepare(
            "SELECT k.public_key, k.user_id
               FROM ssh_keys k
               JOIN users u ON u.id = k.user_id
              WHERE u.status = 'ACTIVE'
              ORDER BY k.user_id, k.id"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $lines = [];
        $lines[] = '# PHPGit authorized_keys – auto-generated, do not edit manually';
        $lines[] = '# Generated: ' . date('Y-m-d H:i:s T');
        $lines[] = '';

        foreach ($rows as $row) {
            $userId = (int)$row['user_id'];
            $publicKey = trim((string)$row['public_key']);
            // Use a shell entry script so PHP startup warnings (OPcache) can be
            // suppressed without losing real git progress output on stderr.
            $entryScript = dirname($this->gitShellWrapperPath) . '/git-ssh-entry.sh';
            $cmdBin = is_executable($entryScript)
                ? $entryScript
                : $phpBin . ' -d opcache.enable=0 -d opcache.enable_cli=0 ' . $this->gitShellWrapperPath;

            $options = sprintf(
                'command="%s %d",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty',
                str_replace('"', '\\"', $cmdBin),
                $userId
            );
            $lines[] = $options . ' ' . $publicKey;
        }

        $content = implode("\n", $lines) . "\n";

        // Write atomically via a temp file in /tmp (avoids needing write access to .ssh dir)
        $tmp = sys_get_temp_dir() . '/phpgit_authkeys_' . getmypid() . '.tmp';
        if (file_put_contents($tmp, $content, LOCK_EX) === false) {
            Logging::loggingToFile("Failed to write temporary authorized_keys: {$tmp}", 4);
            return;
        }
        chmod($tmp, 0600);

        // Move into place using a shell copy (preserves target ownership/ACL)
        $escaped = escapeshellarg($this->authorizedKeysPath);
        $escapedTmp = escapeshellarg($tmp);
        exec("cp {$escapedTmp} {$escaped} && rm -f {$escapedTmp}", $out, $code);
        if ($code !== 0) {
            Logging::loggingToFile("Failed to install authorized_keys from {$tmp}", 4);
            @unlink($tmp);
        }
    }

    // ── Internal helpers ──────────────────────────────────────────────────

    /**
     * Parse and validate a public key string.
     *
     * @return array{type: string, normalized: string}|null
     */
    private function parsePublicKey(string $key): ?array
    {
        // Collapse whitespace / strip accidental newlines inside the key
        $key = preg_replace('/\s+/', ' ', trim($key)) ?? '';

        foreach (self::ALLOWED_KEY_TYPES as $type) {
            if (str_starts_with($key, $type . ' ')) {
                $parts = explode(' ', $key, 3); // type, base64, [comment]
                if (count($parts) < 2) {
                    return null;
                }
                // Validate base64 blob
                $decoded = base64_decode($parts[1], true);
                if ($decoded === false || strlen($decoded) < 16) {
                    return null;
                }
                return [
                    'type' => $type,
                    'normalized' => implode(' ', $parts), // keep comment if present
                ];
            }
        }

        return null;
    }

    /**
     * Compute SHA-256 fingerprint.
     * Tries ssh-keygen first; falls back to pure-PHP calculation.
     */
    private function computeFingerprint(string $key): ?string
    {
        // ── Try ssh-keygen (most accurate) ────────────────────────────────
        $tmp = tempnam(sys_get_temp_dir(), 'phpgit_key_');
        if ($tmp !== false) {
            try {
                file_put_contents($tmp, trim($key) . "\n");
                $cmd = 'ssh-keygen -l -E sha256 -f ' . escapeshellarg($tmp) . ' 2>/dev/null';
                $output = shell_exec($cmd);
                if ($output !== null && $output !== '') {
                    if (preg_match('/SHA256:[A-Za-z0-9+\/=]+/', $output, $m)) {
                        return $m[0];
                    }
                }
            } finally {
                @unlink($tmp);
            }
        }

        // ── Pure-PHP fallback ─────────────────────────────────────────────
        // RFC 4716: fingerprint = SHA-256 of the raw decoded key blob
        $parts = explode(' ', trim($key), 3);
        if (count($parts) < 2) {
            return null;
        }
        $blob = base64_decode($parts[1], true);
        if ($blob === false || strlen($blob) < 16) {
            return null;
        }
        $hash = hash('sha256', $blob, true);
        return 'SHA256:' . rtrim(base64_encode($hash), '=');
    }
}

