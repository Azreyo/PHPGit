<?php

declare(strict_types=1);

use App\Config;
use App\Services\RepositoryService;
use App\includes\Logging;

/** @var bool $is_logged_in */
/** @var string $role */

$config = Config::getInstance();
$pdo = $config->getPdo();

$rawSlug = trim($_GET['slug'] ?? '');
if (strlen($rawSlug) > 200) {
    http_response_code(414);
    include __DIR__ . '/414.php';
    return;
}

if ($rawSlug === '' || !preg_match('#^[a-zA-Z0-9][a-zA-Z0-9_-]{0,49}/[a-zA-Z0-9][a-zA-Z0-9._-]{0,98}$#', $rawSlug)) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

$repo = null;
if ($pdo !== null) {
    try {
        $service = new RepositoryService($pdo, $config->getDataRoot());
        $repo = $service->getBySlug($rawSlug);
    } catch (PDOException $e) {
        Logging::loggingToFile('repo_view SQL error: ' . $e->getMessage(), 4);
    }
}

if ($repo === null) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$isOwner = $is_logged_in && $sessionUserId === (int)$repo['owner_user_id'];
$isAdmin = $is_logged_in && $role === 'ADMIN';

if ($repo['visibility'] === 'private' && !$isOwner && !$isAdmin) {
    http_response_code(403);
    include __DIR__ . '/403.php';
    return;
}

$programming_languages = [
    'PHP' => '#4F5D95',
    'HTML' => '#E34C26',
    'CSS' => '#264DE4',
    'JAVASCRIPT' => '#F7DF1E',
    'TYPESCRIPT' => '#3178C6',
    'PYTHON' => '#3776AB',
    'JAVA' => '#B07219',
    'C' => '#555555',
    'C++' => '#F34B7D',
    'C#' => '#178600',
    'GO' => '#00ADD8',
    'RUBY' => '#CC342D',
    'SWIFT' => '#FA7343',
    'KOTLIN' => '#A97BFF',
    'RUST' => '#DEA584',
    'DART' => '#00B4AB',
    'SCALA' => '#DC322F',
    'SHELL' => '#89E051',
    'POWERSHELL' => '#012456',
    'R' => '#198CE7',
];

$rName = htmlspecialchars($repo['repo_name'], ENT_QUOTES, 'UTF-8');
$rDesc = htmlspecialchars($repo['repo_description'] ?? '', ENT_QUOTES, 'UTF-8');
$rVis = $repo['visibility'];
$rBranch = htmlspecialchars($repo['default_branch'], ENT_QUOTES, 'UTF-8');
$rLang = htmlspecialchars($repo['lang'] ?? '', ENT_QUOTES, 'UTF-8');
$rLangColor = $programming_languages[strtoupper(trim($repo['lang'] ?? ''))] ?? '#6c757d';
$rStars = (int)$repo['stars'];
$rForks = (int)$repo['forks'];
$rOwner = htmlspecialchars($repo['owner_username'], ENT_QUOTES, 'UTF-8');
$rDisplayName = htmlspecialchars($repo['owner_display_name'] ?? $repo['owner_username'], ENT_QUOTES, 'UTF-8');
$rCreated = date('d M Y', strtotime($repo['created_at']));
$rUpdated = date('d M Y', strtotime($repo['updated_at']));
$rSlug = htmlspecialchars($rawSlug, ENT_QUOTES, 'UTF-8');
?>
<main class="container py-5">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="index.php?page=explore"><i class="bi bi-compass me-1"></i>Explore</a>
            </li>
            <li class="breadcrumb-item">
                <a href="index.php?page=explore"><?php echo $rOwner; ?></a>
            </li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $rName; ?></li>
        </ol>
    </nav>

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="fs-3 mb-1">
                <i class="bi bi-folder2 me-2 text-secondary"></i>
                <a href="index.php?page=explore" class="text-decoration-none text-secondary"><?php echo $rOwner; ?></a>
                <span class="text-secondary mx-1">/</span>
                <a href="/<?php echo $rSlug; ?>" class="text-decoration-none"><strong><?php echo $rName; ?></strong></a>
                <span class="badge ms-2 <?php echo $rVis === 'private' ? 'bg-secondary' : 'bg-primary-subtle text-primary-emphasis border border-primary-subtle'; ?> fw-normal small align-middle">
                    <?php echo $rVis === 'private' ? '<i class="bi bi-lock-fill me-1"></i>Private' : '<i class="bi bi-globe me-1"></i>Public'; ?>
                </span>
            </h1>
            <?php if ($rDesc !== ''): ?>
                <p class="text-secondary mb-0"><?php echo $rDesc; ?></p>
            <?php endif; ?>
        </div>
        <?php if ($isOwner || $isAdmin): ?>
            <div class="d-flex gap-2">
                <a href="index.php?page=repos" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-collection me-1"></i>Your repos
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="d-flex flex-wrap gap-4 mb-4 border-bottom pb-3 text-secondary small">
        <span class="d-flex align-items-center gap-1">
            <i class="bi bi-star-fill text-warning"></i>
            <strong class="text-body"><?php echo number_format($rStars); ?></strong> stars
        </span>
        <span class="d-flex align-items-center gap-1">
            <i class="bi bi-git"></i>
            <strong class="text-body"><?php echo number_format($rForks); ?></strong> forks
        </span>
        <span class="d-flex align-items-center gap-1">
            <i class="bi bi-diagram-2"></i>
            Default branch: <code class="ms-1"><?php echo $rBranch; ?></code>
        </span>
        <?php if ($rLang !== ''): ?>
            <span class="d-flex align-items-center gap-1">
                <span style="width:.75rem;height:.75rem;border-radius:50%;background:<?php echo htmlspecialchars($rLangColor, ENT_QUOTES, 'UTF-8'); ?>;display:inline-block;"></span>
                <?php echo $rLang; ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-secondary"><i class="bi bi-person me-1"></i>Owner</h6>
                    <p class="card-text fw-semibold mb-0"><?php echo $rDisplayName; ?></p>
                    <small class="text-secondary">@<?php echo $rOwner; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-secondary"><i class="bi bi-calendar-event me-1"></i>Created</h6>
                    <p class="card-text fw-semibold mb-0"><?php echo $rCreated; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-secondary"><i class="bi bi-clock-history me-1"></i>Last updated
                    </h6>
                    <p class="card-text fw-semibold mb-0"><?php echo $rUpdated; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-body-tertiary d-flex align-items-center gap-2">
            <i class="bi bi-code-square"></i>
            <span class="fw-semibold"><?php echo $rBranch; ?></span>
            <span class="text-secondary ms-auto small">No files to display yet</span>
        </div>
        <div class="card-body text-center py-5 text-secondary">
            <i class="bi bi-folder2-open fs-1 d-block mb-3"></i>
            <p class="mb-1">This repository is empty or file browsing is not yet available.</p>
            <small>Clone URL: <code>git clone git@phpgit.local:<?php echo $rSlug; ?>.git</code></small>
        </div>
    </div>

</main>

