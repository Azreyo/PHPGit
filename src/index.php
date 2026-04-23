<?php

declare(strict_types=1);

use App\Controllers\PageController;
use App\Controllers\RawController;
use App\includes\ErrorHandler;

require __DIR__ . '/../vendor/autoload.php';

new ErrorHandler()->register();

if (isset($_GET['raw'])) {
    (new RawController())->run();
}

(new PageController())->run();
