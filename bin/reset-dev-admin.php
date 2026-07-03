<?php

declare(strict_types=1);

use App\Config;

require __DIR__ . '/../vendor/autoload.php';

$config = Config::getInstance();

if (!$config->isDev()) {
    fwrite(STDERR, "Refusing to reset the development admin outside APP_ENV=dev.\n");
    exit(1);
}

$pdo = $config->getPDO();
if ($pdo === null) {
    fwrite(STDERR, "Database connection is not available. Check src/.env DB_* values.\n");
    exit(1);
}

$email = 'admin@phpgit.dev';
$password = 'Admin1234!';
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    'UPDATE users SET password = ?, role = ?, status = ? WHERE email = ?'
);
$stmt->execute([$hash, 'ADMIN', 'ACTIVE', $email]);

if ($stmt->rowCount() === 0) {
    fwrite(STDERR, "No admin user found for {$email}. Load seed data first.\n");
    exit(1);
}

echo "Development admin reset: {$email} / {$password}\n";
