<?php

declare(strict_types=1);

use App\Services\PersonalAccessTokenService;
use PHPUnit\Framework\TestCase;

final class PersonalAccessTokenServiceTest extends TestCase
{
    private PDO $pdo;
    private PersonalAccessTokenService $service;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('pdo_sqlite is required for token service tests.');
        }
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, status TEXT, role TEXT)');
        $this->pdo->exec("INSERT INTO users VALUES (1, 'alice', 'ACTIVE', 'USER')");
        $this->pdo->exec('CREATE TABLE personal_access_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, name TEXT, token_prefix TEXT,
            token_hash TEXT, scope TEXT, expires_at TEXT, last_used_at TEXT, revoked_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        $this->service = new PersonalAccessTokenService($this->pdo);
    }

    public function testTokenIsShownOnceAndStoredAsHash(): void
    {
        $created = $this->service->create(1, 'Laptop', 'read', new DateTimeImmutable('+1 day'));
        self::assertStringStartsWith('phpgit_pat_', $created['token']);
        $stored = $this->pdo->query('SELECT token_hash FROM personal_access_tokens')->fetchColumn();
        self::assertSame(hash('sha256', $created['token']), $stored);
        self::assertNotSame($created['token'], $stored);
        self::assertNotNull($this->service->authenticate('alice', $created['token'], false));
        self::assertNull($this->service->authenticate('alice', $created['token'], true));
    }

    public function testWriteScopeAndRevocation(): void
    {
        $created = $this->service->create(1, 'CI', 'write', null);
        self::assertNotNull($this->service->authenticate('alice', $created['token'], true));
        self::assertTrue($this->service->revoke(1, $created['id']));
        self::assertNull($this->service->authenticate('alice', $created['token'], false));
    }
}
