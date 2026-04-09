<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\includes\Logging;
use App\includes\Security;
use App\Services\SystemService;
use App\Config;
use JetBrains\PhpStorm\NoReturn;
use PDO;
use Throwable;

class ApiController extends Controller {

    private ?\PDO $pdo = null;

    public function __construct()
    {
        $this->pdo = Config::getInstance()->getPdo();
    }

    public function getCPU(): void {
        $this->requireMethod('GET');
        $this->requireAdminSession();

        try {
            $cpu = SystemService::getCPUUsage();
            $this->success(['cpu_usage_percent' => $cpu]);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function getMemory(): void {
        $this->requireMethod('GET');
        $this->requireAdminSession();

        try {
            $memory = SystemService::getMemoryUsage();
            $this->success($memory);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function getDisk(): void
    {
        $this->requireMethod('GET');
        $this->requireAdminSession();

        try {
            $disk = SystemService::getDiskSpace();
            $this->success(['disk_space_percent' => $disk]);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }
    #[NoReturn]
    public function getHealth(): void {
        $this->requireMethod('GET');
        $this->success(['status' => 'ok']);
    }

    public function system(string $metric): void {
        $cleanMetric = preg_replace('/[^a-z_]/', '', strtolower($metric)) ?: '';

        match ($cleanMetric) {
            'cpu' => $this->getCPU(),
            'memory' => $this->getMemory(),
            'disk' => $this->getDisk(),
            'health' => $this->getHealth(),
            default => $this->notFound('Unknown system metric endpoint'),
        };
    }

    private function requireLoggedInSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $isLoggedIn = (bool)($_SESSION['is_logged_in'] ?? false);
        if (!$isLoggedIn) {
            $this->error('Unauthorized', 401);
        }
    }
    private function requireAdminSession(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $isLoggedIn = (bool)($_SESSION['is_logged_in'] ?? false);
        $role = (string)($_SESSION['role'] ?? '');

        if (!$isLoggedIn || $role !== 'ADMIN') {
            $this->error('Unauthorized', 401);
        }
    }

    private function getDashboardInfo(): void
    {
        $this->requireMethod('GET');
        $this->requireAdminSession();

        if ($this->pdo === null) {
            $this->error('Database unavailable', 503);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    (SELECT COUNT(id) FROM users) AS total_users,
                    (SELECT COUNT(id) FROM repositories) AS total_repos,
                    (SELECT COUNT(id) FROM log WHERE security = 1) AS total_security_logs
            ");
            $stmt->execute();
            $dashboardInfo = $stmt->fetch();
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
        $this->success($dashboardInfo);
    }

    private function getDatabaseInfo(): void
    {
        $this->requireMethod('GET');
        $this->requireAdminSession();

        if ($this->pdo === null) {
            $this->error('Database unavailable', 503);
        }

        try {
            $stmt = $this->pdo->prepare("SHOW GLOBAL STATUS LIKE 'Uptime'");
            $stmt->execute();
            $databaseInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$databaseInfo) {
                $this->error('Could not fetch database uptime');
            }

            $uptimeSeconds = (int)$databaseInfo['Value'];

            $this->success([
                'uptime' => $uptimeSeconds
            ]);

        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function api(string $endpoint): void
    {
        $cleanEndpoint = preg_replace('/[^a-z_]/', '', strtolower($endpoint)) ?: '';

        match ($cleanEndpoint) {
            'getdashboardinfo' => $this->getDashboardInfo(),
            'getdatabaseuptime' => $this->getDatabaseInfo(),
            default => $this->notFound('Unknown API endpoint'),
        };
    }


    public function auth(string $endpoint): void
    {
        $cleanEndpoint = preg_replace('/[^a-z_]/', '', strtolower($endpoint)) ?: '';

        match ($cleanEndpoint) {
            'register' => $this->register(),
            'login' => $this->login(),
            default => $this->notFound('Unknown auth endpoint'),
        };
    }

    private function register(): void
    {
        $this->requireMethod('POST');
        $security = new Security();
        $config = new Config();
        $errors = [];
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'USER';
        $status = $_POST['status'] ?? 'ACTIVE';
        $csrf_token = $_POST['csrf_token'] ?? '';

        if (!$security->validateCsrfToken($csrf_token)) {
            $errors[] = 'Invalid request. Please refresh the page and try again.';
        }
        if (empty($username)) {
            $errors[] = 'Username cannot be empty';
        }

        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if (empty($errors)) {
            if ($config->getPdo() === null) {
                $errors[] = 'Database is currently unavailable. Please try again later.';
                Logging::loggingToFile("Unable to connect to database: " . $config->getDb() . $config->getHost(), 4);
            } else {
                $stmt = $config->getPdo()->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $existingUserId = $stmt->fetchColumn();

                if ($existingUserId !== false) {
                    $errors[] = 'Email already registered';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $config->getPdo()->prepare('INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)');

                    if ($stmt->execute([$username, $email, $hashed_password, $role, $status])) {
                        $success[] = "User created successfully!";
                    } else {
                        $errors[] = 'Registration failed. Please try again.';
                    }
                }
            }
        }
        if (empty($errors)) {
            $this->success(['message' => $success[0] ?? 'ok']);
        } else {
            $this->badRequest(json_encode($errors));
        }
    }

    private function login(): void
    {
        $this->requireMethod('POST');
        $security = new Security();
        $config = new Config();
        $errors = [];
        $success = isset($_GET['success']) && $_GET['success'] === 'registered';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!$security->validateCsrfToken($csrfToken)) {
                $errors[] = 'Invalid or expired form submission. Please try again.';
            } elseif ($security->isRateLimited()) {
                $errors[] = 'Too many login attempts. Please wait 15 minutes and try again.';
                Logging::loggingToFile("Too many login attempts", 2, true);
            } else {
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($email)) {
                    $errors[] = 'Email is required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email format.';
                }

                if (empty($password)) {
                    $errors[] = 'Password is required.';
                }

                if (empty($errors)) {
                    if ($pdo !== null) {
                        $stmt = $pdo->prepare(
                            'SELECT id, username, password, role FROM users WHERE email = ? LIMIT 1'
                        );
                        $stmt->execute([$email]);
                        $user = $stmt->fetch();

                        if ($user === false || !password_verify($password, $user['password'])) {
                            $security->recordFailedAttempt();
                            $errors[] = 'Invalid email or password.';
                        } else {
                            session_regenerate_id(true);
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['is_logged_in'] = true;
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['role'] = $user['role'];

                            unset($_SESSION['csrf_token']);

                            header('Location: index.php?page=home');
                            exit;
                        }
                    } else {
                        $errors[] = 'Database is currently unavailable. Please try again later.';
                        Logging::loggingToFile("Unable to connect to database: " . $config->getDb() . " " . $config->getHost(), 4);
                    }
                }
            }
        }
        if (empty($errors)) {
            $this->success(['message' => $success ? 'Registration successful!' : 'ok']);
        } else {
            $this->badRequest(json_encode($errors));
        }
    }
}