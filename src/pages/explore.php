<?php

declare(strict_types=1);

// Sample repository data — replace with DB query when ready
$repos = [
    ['name' => 'phpgit/core',              'desc' => 'The core engine powering PHPGit, including the Git object model and repository management layer.', 'lang' => 'PHP',   'lang_color' => '#4f5d95', 'stars' => 1240, 'forks' => 89,  'updated' => '2 hours ago'],
    ['name' => 'phpgit/ui',                'desc' => 'Frontend interface for PHPGit built with Bootstrap 5. Contributions welcome!',                     'lang' => 'HTML',  'lang_color' => '#e34c26', 'stars' => 430,  'forks' => 51,  'updated' => '5 hours ago'],
    ['name' => 'user/awesome-php',         'desc' => 'A curated list of amazingly awesome PHP libraries, resources and shiny things.',                    'lang' => 'PHP',   'lang_color' => '#4f5d95', 'stars' => 3800, 'forks' => 512, 'updated' => 'Yesterday'],
    ['name' => 'user/rest-api-boilerplate','desc' => 'A clean, opinionated REST API boilerplate written in PHP with Slim and PDO.',                      'lang' => 'PHP',   'lang_color' => '#4f5d95', 'stars' => 820,  'forks' => 134, 'updated' => '3 days ago'],
    ['name' => 'dev/darktheme-kit',        'desc' => 'A Bootstrap 5 dark theme starter kit with CSS variables and utility classes.',                     'lang' => 'CSS',   'lang_color' => '#563d7c', 'stars' => 670,  'forks' => 98,  'updated' => '1 week ago'],
    ['name' => 'team/deploy-scripts',      'desc' => 'Shell and PHP automation scripts for zero-downtime deployments.',                                  'lang' => 'Shell', 'lang_color' => '#89e051', 'stars' => 310,  'forks' => 45,  'updated' => '2 weeks ago'],
];

$query = trim($_GET['q'] ?? '');
if ($query !== '') {
    $repos = array_values(array_filter($repos, fn($r) => stripos($r['name'], $query) !== false || stripos($r['desc'], $query) !== false));
}
?>
<main>
    <div class="container">
        <section class="about-hero">
            <div class="row justify-content-center text-center">
                <div class="col-lg-6">
                    <span class="section-label">Discover</span>
                    <h1 class="hero-title mt-2 mb-3">Explore <span class="text-primary">Repositories</span></h1>
                    <p class="hero-subtitle text-secondary">Discover open-source projects, contribute to repositories, and find inspiration.</p>
                </div>
            </div>
        </section>

        <!-- Search -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8">
                <form method="GET" action="index.php" class="d-flex gap-2">
                    <input type="hidden" name="page" value="explore">
                    <input type="search" name="q" class="form-control" placeholder="Search repositories..."
                        value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn btn-primary px-4">Search</button>
                </form>
            </div>
        </div>

        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link active" href="index.php?page=explore">All</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php?page=explore&lang=php">PHP</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php?page=explore&lang=js">JavaScript</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php?page=explore&lang=css">CSS</a></li>
        </ul>

        <!-- Repo Cards -->
        <div class="row g-4 mb-5">
            <?php if (empty($repos)): ?>
                <div class="col-12 text-center py-5 text-secondary">
                    <p class="fs-5">No repositories found for &ldquo;<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>&rdquo;.</p>
                    <a href="index.php?page=explore" class="btn btn-outline-secondary mt-2">Clear search</a>
                </div>
            <?php else: ?>
                <?php foreach ($repos as $repo): ?>
                <div class="col-md-6">
                    <div class="repo-card h-100">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <a href="#" class="fw-semibold text-primary text-decoration-none">
                                <?php echo htmlspecialchars($repo['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                            <button class="btn btn-sm btn-outline-secondary py-0 d-flex align-items-center gap-1">
                                <i class="bi bi-star"></i> Star
                            </button>
                        </div>
                        <p class="text-secondary small mb-3"><?php echo htmlspecialchars($repo['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="d-flex align-items-center gap-3 repo-meta text-secondary">
                            <span class="d-flex align-items-center gap-1">
                                <span class="lang-dot" style="background-color:<?php echo htmlspecialchars($repo['lang_color'], ENT_QUOTES, 'UTF-8'); ?>;"></span>
                                <?php echo htmlspecialchars($repo['lang'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <span class="d-flex align-items-center gap-1">
                                <i class="bi bi-star-fill text-warning" style="font-size:.75rem;"></i>
                                <?php echo number_format($repo['stars']); ?>
                            </span>
                            <span class="d-flex align-items-center gap-1">
                                <i class="bi bi-git"></i>
                                <?php echo number_format($repo['forks']); ?>
                            </span>
                            <span class="ms-auto">Updated <?php echo htmlspecialchars($repo['updated'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
