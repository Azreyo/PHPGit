<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\SystemService;

class ApiController extends Controller {

    public function getCPU(): void {
        try {
            $cpu = SystemService::getCPUUsage();
            $this->json(['cpu_usage_percent' => $cpu]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    public function getMemory(): void {
        try {
            $memory = SystemService::getMemoryUsage();
            $this->json($memory);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    // Add more system endpoints here, e.g., disk, network
}