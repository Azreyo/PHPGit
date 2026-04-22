<?php

declare(strict_types=1);

use App\includes\Logging;

/** @var PDO|null $pdo */
/** @var bool $is_logged_in */

$requestedUsername = trim((string)($_GET['user'] ?? $_GET['username'] ?? ''));
$requestedUsername = preg_replace('/[^a-zA-Z0-9._-]/', '', $requestedUsername) ?? '';

$profile = null;
$repositories = [];
$error = '';

if ($requestedUsername === '') {
    $error = 'Please provide a valid username.';
} elseif ($pdo === null) {
    $error = 'Database is currently unavailable. Please try again later.';
} else {
    try {
        $stmt = $pdo->prepare(
                'SELECT id, username, display_name, bio, website, role, created_at
             FROM users
             WHERE username = ?
             LIMIT 1'
        );
        $stmt->execute([$requestedUsername]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($profile === false) {
            $error = 'Profile not found.';
            $profile = null;
        } else {
            $sessionUsername = (string)($_SESSION['username'] ?? '');
            $canSeePrivate = $is_logged_in && $sessionUsername !== '' && strcasecmp($sessionUsername, (string)$profile['username']) === 0;

            $repoSql = 'SELECT repo_name, repo_description, visibility, lang, stars, forks, updated_at
                        FROM repositories
                        WHERE owner_user_id = ?';

            if (!$canSeePrivate) {
                $repoSql .= ' AND visibility = \'public\'';
            }

            $repoSql .= ' ORDER BY updated_at DESC LIMIT 30';

            $repoStmt = $pdo->prepare($repoSql);
            $repoStmt->execute([(int)$profile['id']]);
            $repositories = $repoStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        Logging::loggingToFile('Profile view query failed: ' . $e->getMessage(), 4);
        $error = 'Unable to load profile right now. Please try again later.';
        $profile = null;
        $repositories = [];
    }
}

$profileUsername = $profile !== null ? (string)$profile['username'] : '';
$displayName = $profile !== null ? trim((string)($profile['display_name'] ?? '')) : '';
$headlineName = $displayName !== '' ? $displayName : $profileUsername;
$bio = $profile !== null ? trim((string)($profile['bio'] ?? '')) : '';
$website = $profile !== null ? trim((string)($profile['website'] ?? '')) : '';
$websiteUrl = '';
if ($website !== '' && filter_var($website, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $website) === 1) {
    $websiteUrl = $website;
}

$joinedAt = $profile !== null ? strtotime((string)($profile['created_at'] ?? '')) : false;
$joinedLabel = $joinedAt !== false ? date('M j, Y', $joinedAt) : (string)($profile['created_at'] ?? '');

$initialSeed = preg_replace('/\s+/', '', $headlineName) ?? '';
$initials = strtoupper(substr($initialSeed, 0, 2));
if ($initials === '') {
    $initials = 'U';
}

$repoCount = count($repositories);

$recentRepositories = [];
if ($repoCount > 0) {
    $recentRepositories = array_slice($repositories, 0, 3);
}

$profilePathUsername = rawurlencode($profileUsername);

?>

<main class="container py-4 py-lg-5">
    <?php if ($error !== ''): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    <?php elseif ($profile !== null): ?>
        <div class="row g-4">
            <aside class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm sticky-lg-top" style="top: 1rem;">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-bold mb-3"
                             style="width: 88px; height: 88px; font-size: 1.5rem; letter-spacing: -.03em;">
                            <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
                        </div>

                        <h1 class="h4 mb-0"><?php echo htmlspecialchars($headlineName, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="text-secondary mb-3">
                            @<?php echo htmlspecialchars($profileUsername, ENT_QUOTES, 'UTF-8'); ?></p>

                        <?php if ($bio !== ''): ?>
                            <p class="mb-3"><?php echo nl2br(htmlspecialchars($bio, ENT_QUOTES, 'UTF-8')); ?></p>
                        <?php else: ?>
                            <p class="mb-3 text-secondary">This user has not added a bio yet.</p>
                        <?php endif; ?>

                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2 d-flex align-items-center gap-2 text-secondary">
                                <i class="bi bi-calendar3"></i>
                                <span>Joined <?php echo htmlspecialchars($joinedLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                            <li class="mb-2 d-flex align-items-center gap-2 text-secondary">
                                <i class="bi bi-shield-check"></i>
                                <span><?php echo htmlspecialchars((string)$profile['role'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                            <?php if ($websiteUrl !== ''): ?>
                                <li class="d-flex align-items-center gap-2">
                                    <i class="bi bi-link-45deg text-secondary"></i>
                                    <a href="<?php echo htmlspecialchars($websiteUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="text-decoration-none text-truncate"><?php echo htmlspecialchars($websiteUrl, ENT_QUOTES, 'UTF-8'); ?></a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </aside>

            <section class="col-12 col-lg-8">
                <nav class="nav nav-tabs mb-4" role="navigation" aria-label="Profile navigation">
                    <a class="nav-link active" href="#overview">
                        <i class="bi bi-grid-3x3-gap me-1"></i>Overview
                    </a>
                    <a class="nav-link" href="#repositories">
                        <i class="bi bi-book me-1"></i>Repositories
                        <span class="badge rounded-pill text-bg-secondary ms-1"><?php echo $repoCount; ?></span>
                    </a>
                </nav>

                <div id="overview" class="mb-4">
                    <h2 class="h5 mb-3">Overview</h2>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <p class="mb-2 text-secondary">Public repositories</p>
                            <h3 class="h2 mb-0"><?php echo $repoCount; ?></h3>
                            <?php if (!empty($recentRepositories)): ?>
                                <hr>
                                <p class="mb-2 small text-secondary">Recent</p>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($recentRepositories as $repo): ?>
                                        <?php
                                        $recentRepoName = (string)($repo['repo_name'] ?? '');
                                        $recentRepoUrl = '/' . $profilePathUsername . '/' . rawurlencode($recentRepoName);
                                        ?>
                                        <li class="mb-1">
                                            <a href="<?php echo htmlspecialchars($recentRepoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                               class="text-decoration-none">
                                                <i class="bi bi-bookmark me-1 text-secondary"></i>
                                                <?php echo htmlspecialchars($recentRepoName, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div id="repositories" class="pt-1">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h5 mb-0">Repositories</h2>
                        <?php if ($repoCount > 0): ?>
                            <span class="text-secondary small"><?php echo $repoCount; ?> shown</span>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($repositories)): ?>
                        <div class="alert alert-secondary mb-0" role="alert">
                            <i class="bi bi-info-circle me-1"></i>
                            No repositories available.
                        </div>
                    <?php else: ?>
                        <div class="list-group shadow-sm">
                            <?php foreach ($repositories as $repo): ?>
                                <?php
                                $repoName = (string)($repo['repo_name'] ?? '');
                                $repoDescription = trim((string)($repo['repo_description'] ?? ''));
                                $repoLang = trim((string)($repo['lang'] ?? ''));
                                $repoVisibility = (string)($repo['visibility'] ?? 'public');
                                $repoUpdatedAt = strtotime((string)($repo['updated_at'] ?? ''));
                                $repoUpdatedLabel = $repoUpdatedAt !== false
                                        ? date('M j, Y', $repoUpdatedAt)
                                        : (string)($repo['updated_at'] ?? '');
                                $repoUrl = '/' . $profilePathUsername . '/' . rawurlencode($repoName);
                                ?>
                                <a href="<?php echo htmlspecialchars($repoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                   class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div>
                                            <div class="fw-semibold text-primary mb-1">
                                                <i class="bi bi-bookmark me-1 text-secondary"></i>
                                                <?php echo htmlspecialchars($repoName, ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <?php if ($repoDescription !== ''): ?>
                                                <p class="mb-2 text-secondary small"><?php echo htmlspecialchars($repoDescription, ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php endif; ?>
                                            <div class="d-flex flex-wrap align-items-center gap-3 small text-secondary">
                                                <span>
                                                    <i class="bi <?php echo $repoVisibility === 'private' ? 'bi-lock-fill' : 'bi-globe'; ?> me-1"></i>
                                                    <?php echo htmlspecialchars(ucfirst($repoVisibility), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <?php if ($repoLang !== ''): ?>
                                                    <span><i class="bi bi-circle-fill me-1"
                                                             style="font-size:.5rem;"></i><?php echo htmlspecialchars($repoLang, ENT_QUOTES, 'UTF-8'); ?></span>
                                                <?php endif; ?>
                                                <span><i class="bi bi-star me-1"></i><?php echo (int)($repo['stars'] ?? 0); ?></span>
                                                <span><i class="bi bi-diagram-2 me-1"></i><?php echo (int)($repo['forks'] ?? 0); ?></span>
                                            </div>
                                        </div>
                                        <small class="text-secondary text-nowrap">Updated <?php echo htmlspecialchars($repoUpdatedLabel, ENT_QUOTES, 'UTF-8'); ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    <?php endif; ?>
</main>
