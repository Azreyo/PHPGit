<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class SystemService
{
    public static function getCPUUsage(): float
    {
        $cores = self::getCpuCoreCount();
        $loadAvg = sys_getloadavg();

        if ($cores <= 0 || $loadAvg === false) {
            return 0.0;
        }

        $usage = ($loadAvg[0] / $cores) * 100;
        $usage = max(0.0, min(100.0, $usage));

        return round($usage, 2);
    }

    /**
     * @return array<string, float>
     */
    public static function getMemoryUsage(): array
    {
        $memInfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($memInfo === false) {
            return [
                'usage_percent' => 0.0,
            ];
        }

        $values = [];
        foreach ($memInfo as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches) === 1) {
                $values[$matches[1]] = (int) $matches[2];
            }
        }

        $totalKb = $values['MemTotal'] ?? 0;
        $availableKb = $values['MemAvailable'] ?? ($values['MemFree'] ?? 0);
        $usedKb = max(0, $totalKb - $availableKb);

        if ($totalKb <= 0) {
            return [
                'usage_percent' => 0.0,
            ];
        }

        return [
            'memory_usage_percent' => round(($usedKb / $totalKb) * 100, 2),
        ];
    }

    private static function getCpuCoreCount(): int
    {
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

    public static function getDiskSpace(): float
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        if ($total === false || $free === false || $total === 0.0) {
            throw new RuntimeException('Unable to read disk space');
        }
        $used = $total - $free;

        return ($used / $total) * 100;
    }
}
