<?php
declare(strict_types=1);

$validTabs = ['profile', 'security'];
$tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_SPECIAL_CHARS) ?? $validTabs[0];

if (!in_array($tab, $validTabs, true)) {
    include __DIR__ . '/../pages/404.php';
    die();
}

require __DIR__ . "/../includes/Settings.php";
