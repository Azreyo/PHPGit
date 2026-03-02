<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$is_dev = $_ENV['APP_ENV'] === 'dev';

$host    = $_ENV['DB_HOST'] ?? 'n/a';
$db      = $_ENV['DB_NAME'] ?? 'n/a';
$user    = $_ENV['DB_USER'] ?? 'n/a';
$pass    = $_ENV['DB_PASS'];
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    if ($is_dev) {
        $db_current_state = true;
    }
} catch (\PDOException $e) {
    if (isset($_ENV['APP_ENV']) && $is_dev) {
        $pdo            = null;
        $pdo_error = $e->getMessage();
        $db_current_state = false;
    } else {
        throw new \PDOException($e->getMessage(), (int) $e->getCode());
    }
}

