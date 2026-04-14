<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Core\Controller;
use App\includes\Logging;
use App\Services\SystemService;
use JetBrains\PhpStorm\NoReturn;
use PDO;
use Throwable;

class ApiController extends Controller
{
    private ?\PDO $pdo = null;

    public function __construct()
    {
        $this->pdo = Config::getInstance()->getPdo();
    }

    public function getCPU(): void
    {
        $this->requireMethod('GET');
        $this->requireAdminSession();

        try {
            $cpu = SystemService::getCPUUsage();
            $this->success(['cpu_usage_percent' => $cpu]);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function getMemory(): void
    {
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
    public function getHealth(): void
    {
        $this->requireMethod('GET');
        $this->success(['status' => 'ok']);
    }

    public function system(string $metric): void
    {
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

    private function requireAdminSession(): void
    {
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
            $stmt = $this->pdo->prepare('
                SELECT 
                    (SELECT COUNT(id) FROM users) AS total_users,
                    (SELECT COUNT(id) FROM repositories) AS total_repos,
                    (SELECT COUNT(id) FROM log WHERE security = 1) AS total_security_logs
            ');
            $stmt->execute();
            $dashboardInfo = $stmt->fetch();
            if (!$dashboardInfo) {
                Logging::loggingToFile('Dashboard info fetch error: No data returned', 4, true, true);
                $this->success([]);
            }
        } catch (Throwable $e) {
            Logging::loggingToFile('Dashboard info fetch error: ' . $e->getMessage(), 4, true, true);
            $this->error('Could not fetch dashboard info');
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
                'uptime' => $uptimeSeconds,
            ]);
        } catch (Throwable $e) {
            Logging::loggingToFile('Database uptime fetch error: ' . $e->getMessage(), 4, true, true);
            $this->error('Could not fetch database uptime');
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
}
