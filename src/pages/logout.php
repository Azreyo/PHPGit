<?php
declare(strict_types=1);

$logged_in = $_SESSION['is_logged_in'] ?? false;
?>

<main>
    <div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 70vh;">
        <div class="text-center">
            <?php if ($logged_in): ?>
                <?php
                    $logged_in = false;
                    $_SESSION = [];
                    session_destroy();
                ?>
            <div class="logout-icon mb-4">
                <i class="bi bi-box-arrow-right"></i>
            </div>
            <h1 class="fw-bold mb-2">See you soon!</h1>
            <p class="text-secondary mb-4">You've been successfully logged out.</p>
            <div class="d-flex gap-3 justify-content-center">
                <a class="btn btn-primary" href="/Index.php?page=home">Go Home</a>
                <a class="btn btn-outline-secondary" href="/Index.php?page=login">Login Again</a>
            </div>
            <?php else: ?>
            <div class="logout-icon mb-4">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h1 class="fw-bold mb-2">Warning!</h1>
            <p class="text-secondary mb-4">You're not logged in.</p>
            <div class="d-flex gap-3 justify-content-center">
                <a class="btn btn-primary" href="/Index.php?page=home">Go Home</a>
                <a class="btn btn-outline-secondary" href="/Index.php?page=login">Login</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>