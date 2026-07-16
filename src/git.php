<?php

declare(strict_types=1);

use App\Controllers\GitHttpController;

require __DIR__ . '/../vendor/autoload.php';

new GitHttpController()->run();
