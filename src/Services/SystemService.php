<?php

namespace App\Services;

final class SystemService {

    public static function getCPUUsage(): float {
        $cores = self::getCpuCoreCount();
        $loadAvg = sys_getloadavg();

        if ($cores <= 0 || $loadAvg === false || !isset($loadAvg[0])) {
            return 0.0;
        }

        $usage = ($loadAvg[0] / $cores) * 100;
        $usage = max(0.0, min(100.0, $usage));

        return round($usage, 2);
    }

    public static function getMemoryUsage(): array {
        $memInfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($memInfo === false) {
            return [
                'total_mb' => 0.0,
                'used_mb' => 0.0,
                'free_mb' => 0.0,
                'usage_percent' => 0.0,
            ];
        }

        $values = [];
        foreach ($memInfo as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches) === 1) {
                $values[$matches[1]] = (int)$matches[2];
            }
        }

        $totalKb = $values['MemTotal'] ?? 0;
        $availableKb = $values['MemAvailable'] ?? ($values['MemFree'] ?? 0);
        $usedKb = max(0, $totalKb - $availableKb);

        if ($totalKb <= 0) {
            return [
                'total_mb' => 0.0,
                'used_mb' => 0.0,
                'free_mb' => 0.0,
                'usage_percent' => 0.0,
            ];
        }

        return [
            'total_mb' => round($totalKb / 1024, 2),
            'used_mb' => round($usedKb / 1024, 2),
            'free_mb' => round($availableKb / 1024, 2),
            'usage_percent' => round(($usedKb / $totalKb) * 100, 2),
        ];
    }

    private static function getCpuCoreCount(): int {
        $cpuInfo = @file('/proc/cpuinfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($cpuInfo === false) {
            return 1;
        }

        $cores = 0;
        foreach ($cpuInfo as $line) {
            if (str_starts_with($line, 'processor')) {
                $cores++;
            }
        }

        return max(1, $cores);
    }
}
