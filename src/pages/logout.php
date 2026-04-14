<?php
declare(strict_types=1);

use App\includes\Logging;
use App\includes\Security;

$security = new Security();
$render_logout = false;
$csrf_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$security->validateCsrfToken($csrf_token)) {
        Logging::loggingToFile('Invalid or expired form submission', 4, true);
        $csrf_error = true;
    } elseif ($is_logged_in) {
        $is_logged_in = false;
        $render_logout = true;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
            );
        }
        session_destroy();
    } else {
        Logging::loggingToFile('User is not logged in', 4, true);
    }
} else {
    Logging::loggingToFile('Request method is not allowed: ' . $_SERVER['REQUEST_METHOD'], 4, true);
}

?>
<main>
    <div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 70vh;">
        <div class="text-center">
            <?php if ($render_logout): ?>
                <div class="mb-4 d-flex align-items-center justify-content-center bg-primary-subtle border border-primary-subtle rounded-circle text-primary mx-auto"
                     style="width: 80px; height: 80px; font-size: 2rem;">
                <i class="bi bi-box-arrow-right"></i>
            </div>
            <h1 class="fw-bold mb-2">See you soon!</h1>
            <p class="text-secondary mb-4">You've been successfully logged out.</p>
            <div class="d-flex gap-3 justify-content-center">
                <a class="btn btn-primary" href="/index.php?page=home">Go Home</a>
                <a class="btn btn-outline-secondary" href="/index.php?page=login">Login Again</a>
            </div>
            <?php elseif ($csrf_error): ?>
                <div class="mb-4 d-flex align-items-center justify-content-center bg-danger-subtle border border-danger-subtle rounded-circle text-danger mx-auto"
                     style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="bi bi-shield-exclamation"></i>
                </div>
                <h1 class="fw-bold mb-2">Invalid Session</h1>
                <p class="text-secondary mb-4">Your logout request could not be verified. Please try again.</p>
                <div class="d-flex gap-3 justify-content-center">
                    <form method="POST" action="/index.php?page=logout">
                        <input type="hidden" name="csrf_token"
                               value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary">Retry Logout</button>
                    </form>
                    <a class="btn btn-outline-secondary" href="/index.php?page=home">Cancel</a>
                </div>
            <?php else: ?>
                <div class="mb-4 d-flex align-items-center justify-content-center bg-primary-subtle border border-primary-subtle rounded-circle text-primary mx-auto"
                     style="width: 80px; height: 80px; font-size: 2rem;">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h1 class="fw-bold mb-2">Warning!</h1>
            <p class="text-secondary mb-4">You're not logged in.</p>
            <div class="d-flex gap-3 justify-content-center">
                <a class="btn btn-primary" href="/index.php?page=home">Go Home</a>
                <a class="btn btn-outline-secondary" href="/index.php?page=login">Login</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
