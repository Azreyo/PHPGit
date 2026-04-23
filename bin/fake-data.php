<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use App\Config;
use Faker\Factory;

$pdo = (new Config())->getPDO();
$faker = Factory::create();

$user = strtolower(trim((string)fgets(STDIN)));
$user = (int)$user;

if ($user <= 0) {
    echo "Invalid user count. Please provide a positive integer.\n";
    exit(1);
}

echo "Generating fake data...\n";

try {
    for ($i = 0; $i < $user; $i++) {
        $username = $faker->userName();
        $email = $faker->email();
        $subject = $faker->sentence(2);
        $message = $faker->paragraph(3);
        if ($pdo !== null) {
            $stmt = $pdo->prepare('INSERT INTO inbox (username, email, subject, body) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $email, $subject, $message]);
        }
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    echo "Fake data generation completed.\n";
}
