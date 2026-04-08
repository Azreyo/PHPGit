<?php

namespace App\includes;
use App\includes\Security;

class System
{
    static function getCPUUsage(): float
    {
        $load = sys_getloadavg()[0];
        $ncpu = 1;
        if(is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $ncpu = count($matches[0]);
        }

        $cpuPercent = round(($load / $ncpu) * 100, 0);

        return $cpuPercent;
    }
}