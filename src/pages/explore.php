<?php

declare(strict_types=1);

use App\includes\Logging;

$repos = [];

if ($pdo !== null) {
    try {
        $stmt = $pdo->prepare(
            'SELECT CONCAT(COALESCE(repo_name, \'\'), \'/\', COALESCE(slug, \'\')) AS name, repo_description AS descr, stars, forks, lang, updated_at AS updated FROM repositories;'
        );
        $stmt->execute();
        $repos = $stmt->fetchAll();
    } catch (PDOException $e) {
        Logging::loggingToFile('Cannot execute SQL Query: ' . $e->getMessage(), 4);
    }
} else {
    Logging::loggingToFile('Cannot open database', 4);
}

$programming_languages = [
        'PHP' => '#4F5D95',
        'HTML' => '#E34C26',
        'CSS' => '#264DE4',
        'JavaScript' => '#F7DF1E',
        'TypeScript' => '#3178C6',
        'Python' => '#3776AB',
        'Java' => '#B07219',
        'C' => '#555555',
        'C++' => '#F34B7D',
        'C#' => '#178600',
        'Go' => '#00ADD8',
        'Ruby' => '#CC342D',
        'Swift' => '#FA7343',
        'Kotlin' => '#A97BFF',
        'Rust' => '#DEA584',
        'Dart' => '#00B4AB',
        'Scala' => '#DC322F',
        'Shell' => '#89E051',
        'PowerShell' => '#012456',
        'R' => '#198CE7',
];

$search_query = trim($_GET['q'] ?? '');
if (strlen($search_query) > 100) {
    header('Location: /index.php?page=414');
    exit;
}
if ($search_query !== '') {
    $repos = array_values(array_filter($repos, fn ($r) => stripos($r['name'], $search_query) !== false || stripos($r['descr'] ?? '', $search_query) !== false));
}
$programming_languages = array_change_key_case($programming_languages, CASE_UPPER);
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
                           value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
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
                    <p class="fs-5">No repositories found for
                        &ldquo;<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>&rdquo;.</p>
                    <a href="index.php?page=explore" class="btn btn-outline-secondary mt-2">Clear search</a>
                </div>
            <?php else: ?>
                <?php foreach ($repos as $repo):
                    $rawLang = $repo['lang'] ?? '';
                    $repo_lang = (string) $rawLang;
                    $langKey = strtoupper(trim($repo_lang));
                    $color = $programming_languages[$langKey] ?? '[#000000](#000000)';
                    ?>
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
                        <p class="text-secondary small mb-3"><?php echo htmlspecialchars($repo['descr'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="d-flex align-items-center gap-3 repo-meta text-secondary">
                            <span class="d-flex align-items-center gap-1">
                                <span class="lang-dot" style="background-color:<?php
                                echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>;
                                        "></span>
                                <?php echo htmlspecialchars($repo_lang, ENT_QUOTES, 'UTF-8'); ?>
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
