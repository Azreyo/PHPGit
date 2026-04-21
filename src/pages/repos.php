<?php

declare(strict_types=1);

use App\Config;
use App\Services\RepositoryService;

/** @var bool $is_logged_in */


if (! $is_logged_in) {
    http_response_code(403);
    include __DIR__ . '/403.php';

    return;
}

$config = Config::getInstance();
$userId = (int) ($_SESSION['user_id'] ?? 0);
$flash = $_SESSION['repo_flash'] ?? null;
unset($_SESSION['repo_flash']);

$repos = [];
if ($config->getPdo() !== null) {
    $service = new RepositoryService($config->getPdo(), $config->getDataRoot());
    $repos = $service->getByOwner($userId);
}

$profileUsername = htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<main class="container py-5">
    <?php if ($flash !== null): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $flash; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="fs-3 mb-0"><i class="bi bi-collection me-2"></i>Your repositories</h1>
        <a href="index.php?page=new_repo" class="btn btn-success btn-sm">
            <i class="bi bi-folder-plus me-1"></i> New repository
        </a>
    </div>

    <?php if (empty($repos)): ?>
        <div class="text-center py-5 text-secondary">
            <i class="bi bi-folder2-open fs-1 d-block mb-3"></i>
            <p class="mb-3">You don&rsquo;t have any repositories yet.</p>
            <a href="index.php?page=new_repo" class="btn btn-primary">
                <i class="bi bi-folder-plus me-1"></i> Create your first repository
            </a>
        </div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($repos as $repo): ?>
                <?php
                $rName = htmlspecialchars($repo['repo_name'], ENT_QUOTES, 'UTF-8');
                $rDesc = htmlspecialchars($repo['repo_description'] ?? '', ENT_QUOTES, 'UTF-8');
                $rVis = $repo['visibility'];
                $rLang = htmlspecialchars($repo['lang'] ?? '', ENT_QUOTES, 'UTF-8');
                $rUpdated = date('d M Y', strtotime($repo['updated_at']));
                ?>
                <div class="list-group-item list-group-item-action py-3">
                    <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                        <div>
                            <span class="fw-semibold me-2">
                                <i class="bi bi-folder me-1 text-secondary"></i><?php echo $profileUsername; ?> / <strong><?php echo $rName; ?></strong>
                            </span>
                            <span class="badge rounded-pill <?php echo $rVis === 'private' ? 'bg-secondary' : 'bg-primary-subtle text-primary-emphasis border border-primary-subtle'; ?> small">
                                <?php echo $rVis === 'private' ? '<i class="bi bi-lock-fill me-1"></i>Private' : '<i class="bi bi-globe me-1"></i>Public'; ?>
                            </span>
                        </div>
                        <small class="text-secondary text-nowrap">Updated <?php echo $rUpdated; ?></small>
                    </div>
                    <?php if ($rDesc !== ''): ?>
                        <p class="mb-1 mt-1 text-secondary small"><?php echo $rDesc; ?></p>
                    <?php endif; ?>
                    <div class="d-flex gap-3 mt-2 small text-secondary">
                        <?php if ($rLang !== ''): ?>
                            <span><i class="bi bi-circle-fill me-1"
                                     style="font-size:.6rem;"></i><?php echo $rLang; ?></span>
                        <?php endif; ?>
                        <span><i class="bi bi-star me-1"></i><?php echo (int) $repo['stars']; ?></span>
                        <span><i class="bi bi-diagram-2 me-1"></i><?php echo (int) $repo['forks']; ?></span>
                        <span><i class="bi bi-git me-1"></i><?php echo htmlspecialchars($repo['default_branch'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>


