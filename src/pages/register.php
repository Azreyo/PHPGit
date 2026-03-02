<?php
require __DIR__ . "/../config.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $agreeTerms = isset($_POST['agree-terms']);

    $errors = [];
    if (empty($username)) {
        $errors[] = 'Username cannot be empty';
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }

    if (!$agreeTerms) {
        $errors[] = "You must agree to the Terms of Service";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already registered";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

            if ($stmt->execute([$username, $email, $hashedPassword])) {
                header("Location: index.php?page=login&success=registered");
                exit;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>


<main>
    <div class="container d-flex flex-column align-items-end justify-content-center" style="min-height: 80vh;">
        <h1 class="mb-4 text-start">Register</h1>
        <div class="border border-secondary rounded p-4 w-100" style="max-width: 400px;">
            <form method="POST">
                <div class="mb-3">
                    <label for="user" class="form-label">Username</label>
                    <input type="text" class="form-control" id="user" name="username" aria-describedby="user" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" aria-describedby="email" required>
                    <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="agree-terms" name="agree-terms" required>
                    <label class="form-check-label" for="agree-terms"><a href="index.php?page=terms">Terms & Service</a></label>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
</main>