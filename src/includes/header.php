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
                    <form method="POST" action="/Index.php?page=logout" class="d-flex">
                        <input type="hidden" name="csrf_token"
                               value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary text-white">Logout</button>
                    </form>
                </div>
            <?php endif;?>
            <?php if ($role === "ADMIN"): ?>
                <div class="d-flex gap-2">
                    <a class="btn btn-primary text-white" href="/Index.php?page=dashboard">Dashboard</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <button id="theme-toggle" aria-label="Toggle theme" title="Switch to light mode"><i class="bi bi-sun"></i></button>
    </nav>
</header>