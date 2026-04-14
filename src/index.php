<?php

declare(strict_types=1);

use App\Controllers\PageController;
use App\includes\ErrorHandler;

require __DIR__ . '/../vendor/autoload.php';

new ErrorHandler()->register();

(new PageController())->run();
