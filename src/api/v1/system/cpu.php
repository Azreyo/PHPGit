<?php

declare(strict_types=1);

use App\Controllers\ApiController;

require __DIR__ . '/../../../../vendor/autoload.php';

(new ApiController())->system('cpu');
