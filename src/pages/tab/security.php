<?php

use App\Config;
use App\includes\Logging;
use App\includes\Security;
use Random\RandomException;

$security = new Security();
$csrf_token = null;

$error = $_SESSION['security_errors'] ?? [];
$success = $_SESSION['security_success'] ?? [];
unset($_SESSION['security_errors'], $_SESSION['security_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token_post = $_POST['csrf-token'] ?? '';
    $action = $_POST['action'] ?? '';
    $post_errors = [];
    $post_success = [];

    if (! $security->validateCsrfToken($csrf_token_post)) {
        $post_errors[] = 'Invalid or expired form submission. Please try again.';
        Logging::loggingToFile('Invalid or expired form submission', 4, true);
    } else {
        $config = new Config();
        if ($pdo = $config->getPDO()) {
            $userId = $_SESSION['user_id'];
            switch ($action) {
                case 'change_password':
                    try {
                        $currentPassword = $_POST['current_password'] ?? '';
                        $newPassword = $_POST['new_password'] ?? '';
                        $confirmPassword = $_POST['confirm_password'] ?? '';
                        if (! $currentPassword || ! $newPassword || ! $confirmPassword) {
                            $post_errors[] = 'All fields are required.';
                        } elseif ($newPassword !== $confirmPassword) {
                            $post_errors[] = 'New passwords do not match.';
                        } elseif (strlen($newPassword) < 12) {
                            $post_errors[] = 'Password must be at least 12 characters.';
                        } elseif (! preg_match('/\d/', $newPassword)) {
                            $post_errors[] = 'Password must contain at least one number.';
                        } elseif (! preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
                            $post_errors[] = 'Password must contain at least one special character.';
                        } else {
                            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
                            $stmt->execute([$userId]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            if (! $user) {
                                $post_errors[] = 'User not found.';
                            } elseif (! password_verify($currentPassword, $user['password'])) {
                                $post_errors[] = 'Current password is incorrect.';
                            } else {
                                $pdo->beginTransaction();
                                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                                $stmt->execute([$newPasswordHash, $userId]);
                                $pdo->commit();
                                $post_success[] = 'Password changed successfully.';
                            }
                        }
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $post_errors[] = 'Failed to change password. Please try again.';
                        Logging::loggingToFile('Failed to change password: ' . $e->getMessage(), 4, true);
                    }
                    break;
                case 'change_email':
                    try {
                        $currentEmail = $_POST['current_email'] ?? '';
                        $newEmail = trim($_POST['new_email'] ?? '');
                        $confirmEmail = trim($_POST['confirm_email'] ?? '');
                        $stmt = $pdo->prepare('SELECT id, email FROM users WHERE id = ?');
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (! $user) {
                            $post_errors[] = 'User not found.';
                        } elseif ($currentEmail !== $user['email']) {
                            $post_errors[] = 'Current email is incorrect.';
                        } elseif ($newEmail !== $confirmEmail) {
                            $post_errors[] = 'New emails do not match.';
                        } elseif (! filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                            $post_errors[] = 'New email address is not valid.';
                        } else {
                            $pdo->beginTransaction();
                            $stmt = $pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
                            $stmt->execute([$newEmail, $userId]);
                            $_SESSION['email'] = $newEmail;
                            $pdo->commit();
                            $post_success[] = 'Email address updated successfully.';
                        }
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $post_errors[] = 'Failed to change email. Please try again.';
                        Logging::loggingToFile('Failed to change email: ' . $e->getMessage(), 4, true);
                    }
                    Logging::loggingToFile('Email change requested', 3);
                    break;
                case 'delete_account':
                    try {
                        $pdo->beginTransaction();

                        $stmt = $pdo->prepare('DELETE FROM issues WHERE author_user_id = ?');
                        $stmt->execute([$userId]);

                        $stmt = $pdo->prepare('DELETE FROM repositories WHERE owner_user_id = ?');
                        $stmt->execute([$userId]);

                        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                        $stmt->execute([$userId]);

                        $pdo->commit();
                        Logging::loggingToFile("Account deleted for user ID: {$userId}", 3);
                        session_destroy();
                        echo '<script>window.location.href="/home";</script>';
                        exit;
                    } catch (PDOException $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $post_errors[] = 'Failed to delete account. Please try again.';
                        Logging::loggingToFile('Failed to delete user: ' . $e->getMessage(), 4, true);
                    }
                    break;
            }
        }
    }
    $_SESSION['security_errors'] = $post_errors;
    $_SESSION['security_success'] = $post_success;
    echo '<script>window.location.href="/settings?tab=security";</script>';
    exit;
}

try {
    $csrf_token = $security->generateCsrfToken();
} catch (RandomException $e) {
    Logging::loggingToFile('Cannot generate csrf token: ' . $e->getMessage(), 4);
}
?>
<div class="d-flex align-items-center gap-3 mb-5 pb-4 border-bottom border-secondary-subtle">
    <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
         style="width: 34px; height: 34px; font-size: .9rem;">
        <i class="bi bi-shield-lock-fill"></i>
    </div>
    <div>
        <p class="section-label mb-0">Security</p>
        <h6 class="fw-bold mb-0" style="letter-spacing: -0.01em;">Protect your account</h6>
    </div>
</div>
<?php if (! empty($success)) : ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php foreach ($success as $msg) : ?>
            <p class="mb-0"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (! empty($error)) : ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php foreach ($error as $err) : ?>
            <p class="mb-0"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form method="post">
    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 36px; height: 36px; font-size: 1rem;">
                <i class="bi bi-key-fill"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Change Password</h6>
                <small class="text-secondary">Use a strong password with symbols and numbers.</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <label for="security-current-password" class="form-label fw-semibold">Current Password</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" id="security-current-password"
                           name="current_password"
                           class="form-control rounded-end-3"
                           placeholder="Enter current password">
                </div>
                <div class="form-text">Required to authorize any password change.</div>
            </div>

            <div class="col-md-6">
                <label for="security-new-password" class="form-label fw-semibold">New Password</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" id="security-new-password"
                           name="new_password"
                           class="form-control rounded-end-3"
                           placeholder="New password">
                </div>
                <div class="form-text">Minimum 12 characters with symbols &amp; numbers.</div>
            </div>

            <div class="col-md-6">
                <label for="security-confirm-password" class="form-label fw-semibold">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" id="security-confirm-password"
                           name="confirm_password"
                           class="form-control rounded-end-3"
                           placeholder="Confirm new password">
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="action" value="change_password">
    <div class="d-flex align-items-center justify-content-end gap-3 mt-5 pt-4 border-top border-secondary-subtle">
        <button type="reset" class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
        </button>
        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2">
            <i class="bi bi-shield-check"></i> Save Security
        </button>
    </div>
</form>

<hr class="border-secondary-subtle my-5">

<form method="post">

    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 36px; height: 36px; font-size: 1rem;">
                <i class="bi bi-envelope-fill"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Change Email</h6>
                <small class="text-secondary">Update the email address associated with your account.</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <label for="security-current-email" class="form-label fw-semibold">Current Email</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" id="security-current-email" name="current_email"
                           class="form-control rounded-end-3"
                           placeholder="Your current email address">
                </div>
                <div class="form-text">Enter your current email to confirm your identity.</div>
            </div>

            <div class="col-md-6">
                <label for="security-new-email" class="form-label fw-semibold">New Email</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3">
                        <i class="bi bi-envelope-at"></i>
                    </span>
                    <input type="email" id="security-new-email" name="new_email"
                           class="form-control rounded-end-3"
                           placeholder="New email address">
                </div>
            </div>

            <div class="col-md-6">
                <label for="security-confirm-email" class="form-label fw-semibold">Confirm New Email</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3">
                        <i class="bi bi-envelope-check"></i>
                    </span>
                    <input type="email" id="security-confirm-email" name="confirm_email"
                           class="form-control rounded-end-3"
                           placeholder="Confirm new email address">
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="csrf-token" value="<?= $csrf_token ?>">
    <input type="hidden" name="action" value="change_email">
    <div class="d-flex align-items-center justify-content-end gap-3 mt-5 pt-4 border-top border-secondary-subtle">
        <button type="reset" class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
        </button>
        <button type="submit" name="action" value="change_email"
                class="btn btn-primary px-4 d-flex align-items-center gap-2">
            <i class="bi bi-envelope-check"></i> Update Email
        </button>
    </div>
</form>

<hr class="border-secondary-subtle my-5">

<div>
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center justify-content-center rounded-3 bg-danger-subtle text-danger flex-shrink-0"
             style="width: 36px; height: 36px; font-size: 1rem;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0 text-danger">Danger Zone</h6>
            <small class="text-secondary">Irreversible actions — proceed with caution.</small>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-3 border border-danger-subtle">
        <div>
            <div class="fw-semibold">Delete Account</div>
            <small class="text-secondary">Permanently remove your account and all associated data.</small>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="delete_account">
            <input type="hidden" name="csrf-token" value="<?= $csrf_token ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm px-3 flex-shrink-0"
                    onclick="return confirm('Are you sure? This action cannot be undone.')">
                <i class="bi bi-trash3 me-1"></i> Delete
            </button>
        </form>
    </div>
</div>
