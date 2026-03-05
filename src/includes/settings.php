<?php
declare(strict_types=1);
?>

<?php if (!$is_logged_in): ?>
    <main>
        <div class="container d-flex flex-column align-items-center">
            <div class="logout-icon mb-4">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h1 class="fw-bold mb-2">Warning!</h1>
            <p class="text-secondary mb-4">You're not logged in.</p>
            <div class="d-flex gap-3 justify-content-center">
                <a class="btn btn-primary" href="/index.php?page=home">Go Home</a>
                <a class="btn btn-outline-secondary" href="/index.php?page=login">Login</a>
            </div>
        </div>
    </main>
<?php http_response_code(403); ?>
<?php else:?>
    <main>
        <div class="container d-flex flex-column align-items-start">
            <h3 class="m-4">Welcome, <?php echo htmlspecialchars($_SESSION['username'], ENT_SUBSTITUTE | ENT_QUOTES, 'UTF-8');?></h3>
            <div class="border border-secondary rounded p-4 w-100" style="max-width: 200px;">
                <ul class="list-unstyled">
                    <li>
                        <a class='btn btn-secondary w-100' href="/index.php?page=profile_settings">Profile Settings</a>
                    </li>
                </ul>
            </div>
        </div>

<?php endif;?>
