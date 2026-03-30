<?php
declare(strict_types=1);

use App\includes\Logging;
use App\includes\Security;
use Random\RandomException;
$security = new Security();
try {
    $csrf_token = $security->generateCsrfToken();
} catch (RandomException $e) {
    Logging::loggingToFile("Cannot generate csrf token: " . $e->getMessage(), 4);
}
?>
<header>
    <nav class="navbar navbar-expand-lg px-3 py-2 border-bottom">
        <div class="container-fluid px-0">
            <a class="navbar-brand fw-bold me-4" href="/Index.php?page=home">PHPGit</a>

            <?php if ($page !== 'logout'): ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                        aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNavbar">
                    <div class="navbar-nav me-auto mb-3 mb-lg-0 mt-3 mt-lg-0 gap-1">
                        <a class="nav-link px-3 py-2 rounded-2 fw-medium" href="/Index.php?page=explore">Explore</a>
                        <a class="nav-link px-3 py-2 rounded-2 fw-medium" href="/Index.php?page=about">About</a>
                        <a class="nav-link px-3 py-2 rounded-2 fw-medium" href="/Index.php?page=contact">Contact</a>
                        <?php if ($role === "ADMIN"): ?>
                            <a class="nav-link px-3 py-2 rounded-2 fw-medium" href="/Index.php?page=dashboard">Dashboard</a>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <?php if (!$is_logged_in): ?>
                            <a class="btn btn-outline-secondary btn-sm px-3" href="/Index.php?page=login">Sign in</a>
                            <a class="btn btn-primary btn-sm px-3" href="/Index.php?page=register">Sign up</a>
                        <?php else: ?>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm p-1 lh-1 fs-5" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open account menu">
                                    <i class="bi bi-list"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <a class="dropdown-item" href="/Index.php?page=settings">Settings</a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="/Index.php?page=logout">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="dropdown-item text-danger">Logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        <?php endif;?>
                        <button id="theme-toggle" class="btn btn-outline-secondary btn-sm" aria-label="Toggle theme" title="Switch to light mode">
                            <i class="bi bi-sun"></i>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="ms-auto">
                    <button id="theme-toggle" class="btn btn-outline-secondary btn-sm" aria-label="Toggle theme" title="Switch to light mode">
                        <i class="bi bi-sun"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </nav>
</header>
