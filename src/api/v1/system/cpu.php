<?php

use App\app\Controllers\System;

header('Content-Type: application/json');
echo json_encode(['cpu_usage' => System::getCPUUsage()]);
exit;