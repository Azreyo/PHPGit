<?php

declare(strict_types=1);

use App\includes\Dashboard;

(new Dashboard($_SESSION, $_GET))->render();
