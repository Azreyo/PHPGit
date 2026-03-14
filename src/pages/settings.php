<?php
declare(strict_types=1);

use App\includes\Settings;

$validTabs = ['profile', 'security'];
$tab = preg_replace('/[^a-z0-9_-]/', '', strtolower($_GET['tab'] ?? 'profile'));

if (!in_array($tab, $validTabs, true)) {
    http_response_code(404);
    include __DIR__ . '/../pages/404.php';
    return;
}

(new Settings($_SESSION, $_GET))->render();
