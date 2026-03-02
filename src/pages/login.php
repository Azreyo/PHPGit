<?php

declare(strict_types=1);

require __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function isRateLimited(): bool
{
    $maxAttempts = 5;
    $windowSeconds = 900;
    $now = time();

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts']     = 0;
        $_SESSION['login_attempt_time'] = $now;
    }

    if (($now - $_SESSION['login_attempt_time']) > $windowSeconds) {
        $_SESSION['login_attempts']     = 0;
        $_SESSION['login_attempt_time'] = $now;
    }

    return $_SESSION['login_attempts'] >= $maxAttempts;
}

function recordFailedAttempt(): void
{
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
}

$errors  = [];
$success = isset($_GET['success']) && $_GET['success'] === 'registered';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    } elseif (isRateLimited()) {
        $errors[] = 'Too many login attempts. Please wait 15 minutes and try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'SELECT id, username, password FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT role FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $userRole = $stmt->fetch();
            if ($user === false || !password_verify($password, $user['password'])) {
                recordFailedAttempt();
                // Generic message to prevent user enumeration
                $errors[] = 'Invalid email or password.';
            } else {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                $_SESSION['login_attempts'] = 0;
                $_SESSION['isLoggedIn']     = true;
                $_SESSION['user_id']        = $user['id'];
                $_SESSION['username']       = $user['username'];
                $_SESSION['role']            = $userRole['role'];

                // Rotate CSRF token after successful login
                unset($_SESSION['csrf_token']);

                header('Location: index.php?page=home');
                exit;
            }
        }
    }
}

$csrfToken = generateCsrfToken();

?>

<main>
    <div class="container d-flex flex-column align-items-end justify-content-center" style="min-height: 80vh;">
        <h1 class="mb-4 text-start">Login</h1>
        <div class="border border-secondary rounded p-4 w-100" style="max-width: 400px;">

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    Registration successful! You can now log in.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        aria-describedby="emailHelp"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
        </div>
    </div>
</main>

