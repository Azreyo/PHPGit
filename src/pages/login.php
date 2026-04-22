<?php

use App\Config;
use App\includes\Logging;
use App\includes\Security;
use Random\RandomException;

/** @var bool $is_dev */

$config = new Config();
$security = new Security();

$success = isset($_GET['success']) && $_GET['success'] === 'registered';
$csrf_token = null;
$errors = $_SESSION['login_errors'] ?? [];
$prefill_email = $_SESSION['login_prefill_email'] ?? '';
unset($_SESSION['login_errors'], $_SESSION['login_prefill_email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_dev && isset($_POST['action']) && $_POST['action'] === 'reset_rate_limit') {
        session_destroy();
        $_SESSION = [];
        echo '<script>window.location.href="index.php?page=login";</script>';
        exit;
    }

    $post_errors = [];
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (! $security->validateCsrfToken($csrfToken)) {
        $post_errors[] = 'Invalid or expired form submission. Please try again.';
    } elseif ($security->isRateLimited()) {
        $post_errors[] = 'Too many login attempts. Please wait 15 minutes and try again.';
        Logging::loggingToFile('Too many login attempts', 2, true);
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email)) {
            $post_errors[] = 'Email is required.';
        } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $post_errors[] = 'Invalid email format.';
        }

        if (empty($password)) {
            $post_errors[] = 'Password is required.';
        }

        if (empty($post_errors)) {
            $pdo = $config->getPDO();
            if ($pdo !== null) {
                $stmt = $pdo->prepare(
                    'SELECT id, username, password, role FROM users WHERE email = ? LIMIT 1'
                );
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user === false || ! password_verify($password, $user['password'])) {
                    $security->recordFailedAttempt();
                    $post_errors[] = 'Invalid email or password.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['is_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    unset($_SESSION['csrf_token']);
                    echo '<script>window.location.href="index.php?page=home";</script>';
                    exit;
                }
            } else {
                $post_errors[] = 'Database is currently unavailable. Please try again later.';
                Logging::loggingToFile('Unable to connect to database: ' . $config->getDb() . ' ' . $config->getHost(), 4);
            }
        }
    }

    $_SESSION['login_errors'] = $post_errors;
    $_SESSION['login_prefill_email'] = htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $qs = isset($_GET['success']) ? '?page=login&success=registered' : '?page=login';
    echo '<script>window.location.href="index.php' . $qs . '";</script>';
    exit;
}

try {
    $csrf_token = $security->generateCsrfToken();
} catch (RandomException $e) {
    Logging::loggingToFile('Cannot generate csrf token: ' . $e->getMessage(), 4);
}

?>

<main style="position: relative; min-height: 80vh;">

    <div class="d-none d-lg-flex align-items-center justify-content-center"
         style="position: absolute; top: 0; left: 0; bottom: 0; width: 45%; padding: 2rem 1.5rem; z-index: 0;">
        <div class="phpgit-terminal-wrap w-100" style="max-width: 600px;">
            <div class="phpgit-terminal w-100">
                <div class="phpgit-terminal-bar">
                    <span class="t-dot t-dot-r"></span>
                    <span class="t-dot t-dot-y"></span>
                    <span class="t-dot t-dot-g"></span>
                    <span class="t-title">phpgit@unix: ~</span>
                    <i class="bi bi-terminal t-icon"></i>
                </div>
                <div class="phpgit-terminal-body" id="term-serve-output"></div>
            </div>
        </div>
    </div>

    <div class="container d-flex flex-column align-items-end justify-content-center"
         style="min-height: 80vh; position: relative; z-index: 1;">
        <h1 class="mb-4 text-start">Login</h1>
        <div class="border border-secondary rounded p-4 w-100" style="max-width: 400px;">

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    Registration successful! You can now log in.
                </div>
            <?php endif; ?>

            <?php if (! empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token"
                       value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        aria-describedby="emailHelp"
                        value="<?php echo htmlspecialchars($prefill_email, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                        autocomplete="email"
                    >
                    <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <p class="mt-3 text-center">
                Don't have an account? <a href="index.php?page=register">Register</a>
            </p>

            <?php if ($is_dev): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="reset_rate_limit">
                    <button class="btn btn-primary rounded-5" type="submit">
                        Reset Rate Limit
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('term-serve-output')) {
        new PHPGitTerminal('term-serve-output').run(PHPGIT_SERVE_SEQUENCE);
    }
});
</script>

