<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Core\Controller;
use App\includes\Logging;
use App\Services\SshKeyService;
use App\Services\SystemService;
use PDO;
use Throwable;

class ApiController extends Controller
{
    private ?\PDO $pdo = null;

    public function __construct()
    {
        $this->pdo = Config::getInstance()->getPDO();
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

        $isLoggedIn = (bool) ($_SESSION['is_logged_in'] ?? false);
        if (! $isLoggedIn) {
            $this->error('Unauthorized', 401);
        }
    }

    private function requireAdminSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $isLoggedIn = (bool) ($_SESSION['is_logged_in'] ?? false);
        $role = (string) ($_SESSION['role'] ?? '');

        if (! $isLoggedIn || $role !== 'ADMIN') {
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

        $dashboardInfo = null;

        try {
            $stmt = $this->pdo->prepare('
                SELECT 
                    (SELECT COUNT(id) FROM users) AS total_users,
                    (SELECT COUNT(id) FROM repositories) AS total_repos,
                    (SELECT COUNT(id) FROM log WHERE security = 1) AS total_security_logs
            ');
            $stmt->execute();
            $dashboardInfo = $stmt->fetch();
            if (! $dashboardInfo) {
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

            if (! $databaseInfo) {
                $this->error('Could not fetch database uptime');
            }

            $uptimeSeconds = (int) $databaseInfo['Value'];

            $this->success([
                'uptime' => $uptimeSeconds,
            ]);
        } catch (Throwable $e) {
            Logging::loggingToFile('Database uptime fetch error: ' . $e->getMessage(), 4, true, true);
            $this->error('Could not fetch database uptime');
        }
    }

    private function markInboxRead(): void
    {
        $this->requireMethod('POST');
        $this->requireAdminSession();

        if ($this->pdo === null) {
            Logging::loggingToFile('markInboxRead error: Database connection is null', 4, true, true);
            $this->error('Database unavailable', 503);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $ids = $body['ids'] ?? [];

        if (! is_array($ids) || $ids === []) {
            $this->error('No IDs provided', 400);
        }
        $ids = array_values(array_filter(array_map('intval', $ids), fn (int $id) => $id > 0));

        if ($ids === []) {
            $this->error('No valid IDs provided', 400);
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->pdo->prepare(
                "UPDATE inbox SET unread = 0 WHERE id IN ({$placeholders}) AND unread = 1"
            );
            $stmt->execute($ids);
            $this->success(['updated' => $stmt->rowCount()]);
        } catch (Throwable $e) {
            Logging::loggingToFile('markInboxRead error: ' . $e->getMessage(), 4, true, true);
            $this->error('Could not update inbox');
        }
    }

    public function addSshKey(): void
    {
        $this->requireMethod('POST');
        $this->requireLoggedInSession();

        if ($this->pdo === null) {
            $this->error('Database unavailable', 503);
        }

        $body = json_decode((string)file_get_contents('php://input'), true);
        $title = trim((string)($body['title'] ?? ''));
        $publicKey = trim((string)($body['public_key'] ?? ''));

        if ($title === '' || $publicKey === '') {
            $this->error('Title and public_key are required', 400);
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $service = $this->buildSshKeyService();

        $result = $service->addKey($userId, $title, $publicKey);

        if (!$result['success']) {
            $this->error($result['error'] ?? 'Failed to add key', 400);
        }

        $this->success(['key' => $result['key']]);
    }

    public function deleteSshKey(): void
    {
        $this->requireMethod('DELETE');
        $this->requireLoggedInSession();

        if ($this->pdo === null) {
            $this->error('Database unavailable', 503);
        }

        $body = json_decode((string)file_get_contents('php://input'), true);
        $keyId = (int)($body['id'] ?? 0);

        if ($keyId <= 0) {
            $this->error('Invalid key ID', 400);
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $service = $this->buildSshKeyService();

        if (!$service->deleteKey($keyId, $userId)) {
            $this->error('Key not found or not owned by you', 404);
        }

        $this->success(['deleted' => $keyId]);
    }

    public function listSshKeys(): void
    {
        $this->requireMethod('GET');
        $this->requireLoggedInSession();

        if ($this->pdo === null) {
            $this->error('Database unavailable', 503);
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $service = $this->buildSshKeyService();

        $this->success(['keys' => $service->listKeys($userId)]);
    }

    private function buildSshKeyService(): SshKeyService
    {
        $pdo = $this->pdo;
        if ($pdo === null) {
            $this->error('Database unavailable', 503);
        }

        $authorizedKeys = rtrim($_ENV['AUTHORIZED_KEYS_PATH'] ?? '', '/');
        $gitShellWrapper = rtrim($_ENV['GIT_SHELL_WRAPPER'] ?? '', '/');

        if ($authorizedKeys === '') {
            $authorizedKeys = '/home/git/.ssh/authorized_keys';
        }
        if ($gitShellWrapper === '') {
            $gitShellWrapper = dirname(__DIR__, 2) . '/bin/git-shell-wrapper.php';
        }

        return new SshKeyService($pdo, $authorizedKeys, $gitShellWrapper);
    }


    public function api(string $endpoint): void
    {
        $cleanEndpoint = preg_replace('/[^a-z_]/', '', strtolower($endpoint)) ?: '';

        match ($cleanEndpoint) {
            'getdashboardinfo' => $this->getDashboardInfo(),
            'getdatabaseuptime' => $this->getDatabaseInfo(),
            'markinboxread' => $this->markInboxRead(),
            'addsshkey' => $this->addSshKey(),
            'deletesshkey' => $this->deleteSshKey(),
            'listsshkeys' => $this->listSshKeys(),
            default => $this->notFound('Unknown API endpoint'),
        };
    }
}
