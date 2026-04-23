<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use App\Config;
use Faker\Factory;

$pdo = (new Config())->getPDO();
$faker= Factory::create();
function genInboxData(int $user) : void
{
    GLOBAL $pdo;
    GLOBAL $faker;
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
}

function genLogData(int $user) : void {
    GLOBAL $pdo;
    GLOBAL $faker;
    try {
        for ($i = 0; $i < $user; $i++) {
            $message = $faker->paragraph(1);
            $level = $faker->randomElement(['Debug','Info','Warning','Error','Critical','Unknown']);
            $security = (int) $faker->boolean();
            $ip = $faker->ipv4();
            if ($pdo !== null) {
                $stmt = $pdo->prepare('INSERT INTO log (level, message, security, ip) VALUES (?, ?, ?, ?)');
                $stmt->execute([$level, $message, $security, $ip]);
            }
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        echo "Fake data generation completed.\n";
    }
}
echo "Welcome to the Fake Data Generator!\n" .
     "0. Exit\n" .
     "1. Generate fake inbox data\n" .
     "2. Generate fake log data\n".
     "Enter the menu number: ";
$input = strtolower(trim((string)fgets(STDIN)));

echo "Enter the number of fake logs to generate: ";
$user = strtolower(trim((string)fgets(STDIN)));
$user = (int)$user;
if ($user <= 0) {
    echo "Invalid log count. Please provide a positive integer.\n";
    exit(1);
}
echo "Generating fake data...\n";

switch ($input) {
    case '1':
        genInboxData($user);
        break;
    case '2':
        genLogData($user);
    break;
    case '0':
        exit(0);
    default:
        echo "Invalid option. Please enter a valid number.\n";
        exit(1);
}