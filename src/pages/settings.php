<?php
declare(strict_types=1);

use App\includes\Settings;

$validTabs = ['profile', 'security'];
$rawTab = $_GET['tab'] ?? 'profile';
if (!is_string($rawTab)) {
    $rawTab = 'profile';
}
$tab = preg_replace('/[^a-z0-9_-]/', '', strtolower($rawTab));

if (!in_array($tab, $validTabs, true)) {
    include __DIR__ . '/../pages/404.php';
    die();
}

(new Settings($_SESSION, $_GET))->render();
