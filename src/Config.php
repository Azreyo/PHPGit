<?php

declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

class Config
{
    private static ?self $instance = null;

    private string $host;
    private string $db;
    private string $dbUser;
    private string $dbPass;
    private string $charset;
    private bool $isDev;
    private ?\PDO $pdo = null;
    private ?string $pdoError = null;
    private bool $dbOnline = false;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        $this->isDev = ($_ENV['APP_ENV'] ?? 'prod') === 'dev';
        $this->host = $_ENV['DB_HOST'] ?? 'n/a';
        $this->db = $_ENV['DB_NAME'] ?? 'n/a';
        $this->dbUser = $_ENV['DB_USER'] ?? 'n/a';
        $this->dbPass = $_ENV['DB_PASS'] ?? '';
        $this->charset = 'utf8mb4';

        $this->connect();
    }

    private function connect(): void
    {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new \PDO($dsn, $this->dbUser, $this->dbPass, $options);
            $this->dbOnline = true;
        } catch (\PDOException $e) {
            if ($this->isDev) {
                $this->pdo = null;
                $this->pdoError = $e->getMessage();
                $this->dbOnline = false;
            } else {
                throw new \PDOException($e->getMessage(), (int) $e->getCode());
            }
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getPdo(): ?\PDO
    {
        return $this->pdo;
    }

    public function getPdoError(): ?string
    {
        return $this->pdoError;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getDb(): string
    {
        return $this->db;
    }

    public function getDbUser(): string
    {
        return $this->dbUser;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function isDev(): bool
    {
        return $this->isDev;
    }

    public function isDbOnline(): bool
    {
        return $this->dbOnline;
    }
}
