<?php

declare(strict_types=1);

use App\Config;
use App\includes\Logging;
use App\includes\Security;
use Random\RandomException;

$config = new Config();
$security = new Security();

$errors = $_SESSION['register_errors'] ?? [];
$prefill = $_SESSION['register_prefill'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_prefill']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $agree_terms = isset($_POST['agree-terms']);
    $role = 'USER';
    $csrf_token = $_POST['csrf_token'] ?? '';

    $post_errors = [];

    if (! $security->validateCsrfToken($csrf_token)) {
        $post_errors[] = 'Invalid request. Please refresh the page and try again.';
    }
    if (empty($username)) {
        $post_errors[] = 'Username cannot be empty';
    } elseif (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,49}$/', $username)) {
        $post_errors[] = 'Username may only contain letters, numbers, hyphens and underscores, and must start with a letter or digit.';
    }

    if (empty($email)) {
        $post_errors[] = 'Email is required';
    } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $post_errors[] = 'Invalid email format';
    }

    if (empty($password)) {
        $post_errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $post_errors[] = 'Password must be at least 8 characters';
    }

    if (! $agree_terms) {
        $post_errors[] = 'You must agree to the Terms of Service';
    }

    if (empty($post_errors) && $pdo !== null) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $existingUserId = $stmt->fetchColumn();

        if ($existingUserId !== false) {
            $post_errors[] = 'Email already registered';
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');

            if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                echo '<script>window.location.href="index.php?page=login&success=registered";</script>';
                exit;
            }
            $post_errors[] = 'Registration failed. Please try again.';
        }
    } elseif ($pdo === null) {
        $post_errors[] = 'Database is currently unavailable. Please try again later.';
        Logging::loggingToFile('Unable to connect to database: ' . $config->getDb() . $config->getHost(), 4);
    } elseif (! empty($post_errors)) {
    } else {
        $post_errors[] = 'Unknown error occurred.';
        Logging::loggingToFile('Unknown error occurred', -1);
    }

    $_SESSION['register_errors'] = $post_errors;
    $_SESSION['register_prefill'] = [
            'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
            'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
    ];
    echo '<script>window.location.href="index.php?page=register";</script>';
    exit;
}

try {
    $csrf_token = $security->generateCsrfToken();
} catch (RandomException $e) {
    Logging::loggingToFile('Cannot generate csrf token: ' . $e->getMessage(), 4);
}
?>
<main style="position: relative; min-height: 80vh;">

    <!-- Terminal: absolutely positioned on the left, desktop only -->
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
                <div class="phpgit-terminal-body" id="term-install-output"></div>
            </div>
        </div>
    </div>

    <!-- Original form: unchanged -->
    <div class="container d-flex flex-column align-items-end justify-content-center"
         style="min-height: 80vh; position: relative; z-index: 1;">
        <h1 class="mb-4 text-start">Register</h1>
        <div class="border border-secondary rounded p-4 w-100" style="max-width: 400px;">

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
                    <label for="user" class="form-label">Username</label>
                    <input
                        type="text"
                        class="form-control"
                        id="user"
                        name="username"
                        aria-describedby="user"
                        value="<?php echo $prefill['username'] ?? ''; ?>"
                        required
                        autocomplete="username"
                    >
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        aria-describedby="emailHelp"
                        value="<?php echo $prefill['email'] ?? ''; ?>"
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
                        autocomplete="new-password"
                    >
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="agree-terms" name="agree-terms" required>
                    <label class="form-check-label" for="agree-terms">
                        <a href="/index.php?page=terms">Terms &amp; Conditions</a>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Submit</button>
            </form>

            <p class="mt-3 text-center">
                Already have an account? <a href="/index.php?page=login">Login</a>
            </p>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('term-install-output')) {
        new PHPGitTerminal('term-install-output').run(PHPGIT_INSTALL_SEQUENCE);
    }
});
</script>