<?php

declare(strict_types=1);

namespace App\Services;

use App\includes\Logging;
use RuntimeException;
use Throwable;

final class RepositoryDeletionService
{
    public function __construct(private readonly \PDO $pdo, private readonly string $dataRoot)
    {
    }

    /** @param array<string, mixed> $repo */
    public function delete(array $repo): void
    {
        $path = (new RepositoryLocator($this->pdo, $this->dataRoot))->resolvePath(
            (string)$repo['owner_username'],
            (string)$repo['repo_name']
        );
        $root = realpath($this->dataRoot);
        if ($root === false) {
            throw new RuntimeException('DATA_ROOT is unavailable.');
        }
        $trash = $root . '/.trash';
        if (!is_dir($trash) && !mkdir($trash, 0700) && !is_dir($trash)) {
            throw new RuntimeException('Cannot create repository quarantine.');
        }
        $quarantined = $trash . '/' . (int)$repo['id'] . '-' . bin2hex(random_bytes(8));
        if (!rename($path, $quarantined)) {
            throw new RuntimeException('Cannot quarantine repository.');
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('DELETE FROM repositories WHERE id = ?');
            $stmt->execute([(int)$repo['id']]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Repository metadata was not deleted.');
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            @rename($quarantined, $path);

            throw $e;
        }

        try {
            $this->removeTree($quarantined);
        } catch (Throwable $e) {
            Logging::loggingToFile('Quarantined repository cleanup failed: ' . $e->getMessage(), 3);
        }
    }

    private function removeTree(string $directory): void
    {
        $items = scandir($directory);
        if ($items === false) {
            throw new RuntimeException('Cannot read quarantined repository.');
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory . '/' . $item;
            if (is_link($path) || is_file($path)) {
                if (!unlink($path)) {
                    throw new RuntimeException('Cannot remove repository file.');
                }
            } elseif (is_dir($path)) {
                $this->removeTree($path);
            }
        }
        if (!rmdir($directory)) {
            throw new RuntimeException('Cannot remove repository directory.');
        }
    }
}
