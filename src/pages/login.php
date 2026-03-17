<?php

require __DIR__ . '/../includes/security.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

        if (empty($errors && $pdo === null)) {
            $stmt = $pdo->prepare(
                'SELECT id, username, password, role FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user === false || !password_verify($password, $user['password'])) {
                recordFailedAttempt();
                $errors[] = 'Invalid email or password.';
            } else {
                session_regenerate_id(true);
                $_SESSION['login_attempts'] = 0;
                $_SESSION['is_logged_in']   = true;
                $_SESSION['user_id']        = $user['id'];
                $_SESSION['username']       = $user['username'];
                $_SESSION['role']           = $user['role'];

                unset($_SESSION['csrf_token']);

                header('Location: Index.php?page=home');
                exit;
            }
        } else {
            $errors[] = 'Database is currently unavailable. Please try again later.';
        }
    }
}


if ( $is_dev && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'reset_rate_limit') {
        session_destroy();
    }
}

$csrf_token = generateCsrfToken();

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
                Don't have an account? <a href="Index.php?page=register">Register</a>
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

