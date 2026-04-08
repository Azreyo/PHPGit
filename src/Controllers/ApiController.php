<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\SystemService;
use Throwable;

class ApiController extends Controller {

    public function getCPU(): void {
        $this->requireMethod('GET');

        try {
            $cpu = SystemService::getCPUUsage();
            $this->success(['cpu_usage_percent' => $cpu]);
        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    public function getMemory(): void {
        $this->requireMethod('GET');

        try {
            $memory = SystemService::getMemoryUsage();
            $this->success($memory);
        } catch (Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    public function system(string $metric): void {
        $cleanMetric = preg_replace('/[^a-z_]/', '', strtolower($metric)) ?: '';

        match ($cleanMetric) {
            'cpu' => $this->getCPU(),
            'memory' => $this->getMemory(),
            default => $this->notFound('Unknown system metric endpoint'),
        };
    }

}