<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\includes\AssetManifestBuilder;

new AssetManifestBuilder(dirname(__DIR__) . '/src/')->build(verbose: true);
