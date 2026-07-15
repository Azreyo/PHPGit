<?php

declare(strict_types=1);

use App\Services\RepositoryAccessPolicy;
use App\Services\RepositoryLocator;
use PHPUnit\Framework\TestCase;

final class RepositorySecurityTest extends TestCase
{
    private PDO $pdo;

    private function setUpDatabase(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('pdo_sqlite is required for database-backed permission tests.');
        }
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE repository_members (repository_id INTEGER, user_id INTEGER, permission TEXT)');
        $this->pdo->exec("INSERT INTO repository_members VALUES (10, 2, 'read'), (10, 3, 'write')");
    }

    public function testSlugValidationIsStrict(): void
    {
        self::assertTrue(RepositoryLocator::isValidSlug('alice/project.git'));
        self::assertFalse(RepositoryLocator::isValidSlug('../alice/project'));
        self::assertFalse(RepositoryLocator::isValidSlug('alice/project/extra'));
        self::assertFalse(RepositoryLocator::isValidSlug('alice/<script>'));
    }

    public function testPermissionsAreConsistent(): void
    {
        $this->setUpDatabase();
        $policy = new RepositoryAccessPolicy($this->pdo);
        $private = ['id' => 10, 'owner_user_id' => 1, 'visibility' => 'private'];
        self::assertFalse($policy->canRead($private));
        self::assertTrue($policy->canRead($private, 2));
        self::assertFalse($policy->canWrite($private, 2));
        self::assertTrue($policy->canWrite($private, 3));
        self::assertTrue($policy->canManage($private, 1));
        self::assertFalse($policy->canManage($private, 3));
        self::assertTrue($policy->canManage($private, 9, 'ADMIN'));
    }
}
