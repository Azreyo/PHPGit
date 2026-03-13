<?php declare(strict_types=1);
$is_logged_in = $_SESSION['is_logged_in'] ?? false;
?>

<header>
    <nav class="navbar navbar-expand d-flex justify-content-between align-items-center px-3 py-2">
        <a class="navbar-brand" href="Index.php?page=home">PHPGit</a>
        <?php if ($page !== 'logout'): ?>
        <div class="d-flex gap-2">
            <a class="nav-item btn btn-primary text-white" href="/Index.php?page=explore">Explore</a>
            <a class="btn btn-primary text-white" href="/Index.php?page=about">About</a>
            <a class="btn btn-primary text-white" href="/Index.php?page=contact">Contact</a>
        </div>
            <?php if (!$is_logged_in): ?>
                <div class="d-flex gap-2">
                    <a class="btn btn-primary text-white" href="/Index.php?page=login">Login</a>
                    <a class="btn btn-primary text-white" href="/Index.php?page=register">Register</a>
                </div>
            <?php else: ?>
                <div class="d-flex gap-2">
                    <a class="btn btn-primary text-white" href="/Index.php?page=settings">Settings</a>
                    <a class="btn btn-primary text-white" href="/Index.php?page=logout">Logout</a>
                </div>
            <?php endif;?>
        <?php endif; ?>
        <button id="theme-toggle" aria-label="Toggle theme" title="Switch to light mode"><i class="bi bi-sun"></i></button>
    </nav>
</header>