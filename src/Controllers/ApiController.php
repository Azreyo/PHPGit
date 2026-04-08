<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\SystemService;
use JetBrains\PhpStorm\NoReturn;
use Throwable;

class ApiController extends Controller {

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

    public function getHealth(): void {
        $this->requireMethod('GET');
        $this->success(['status' => 'ok']);
    }

    public function system(string $metric): void {
        $cleanMetric = preg_replace('/[^a-z_]/', '', strtolower($metric)) ?: '';

        match ($cleanMetric) {
            'cpu' => $this->getCPU(),
            'memory' => $this->getMemory(),
            default => $this->notFound('Unknown system metric endpoint'),
        };
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

}