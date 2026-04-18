<?php

declare(strict_types=1);

use App\includes\Settings;

(new Settings($_SESSION, $_GET))->render();
