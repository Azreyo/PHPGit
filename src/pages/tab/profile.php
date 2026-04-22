<?php
declare(strict_types=1);

use App\Config;
use App\includes\Logging;
use App\includes\Security;
use Random\RandomException;

/** @var string $username */

$security = new Security();
$csrf_token = '';
$errors = [];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';

        if (! $security->validateCsrfToken($csrfToken)) {
            $errors[] = 'Invalid or expired form submission. Please try again.';
        } elseif ($security->isRateLimited()) {
            $errors[] = 'Too many login attempts. Please wait 15 minutes and try again.';
            Logging::loggingToFile('Too many login attempts', 2, true);
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email)) {
                $errors[] = 'Email is required.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format.';
            }

            if (empty($password)) {
                $errors[] = 'Password is required.';
            }

            if (empty($errors)) {
                $config = new Config();
                $pdo = $config->getPDO();
                if ($pdo !== null) {
                    //TODO: implement user update
                } else {
                    $errors[] = 'Database is currently unavailable. Please try again later.';
                    Logging::loggingToFile('Unable to connect to database: ' . $config->getDb() . ' ' . $config->getHost(), 4);
                }
            }
        }
    }
} catch (RandomException $e) {
    Logging::loggingToFile('Error during form processing: ' . $e->getMessage(), 4);
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
        <i class="bi bi-person-circle"></i>
    </div>
    <div>
        <p class="section-label mb-0">Profile</p>
        <h6 class="fw-bold mb-0" style="letter-spacing: -0.01em;">Public account information</h6>
    </div>
</div>

<div class="d-flex align-items-center gap-3 mb-4">
    <div class="position-relative flex-shrink-0">
        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center"
             style="width: 56px; height: 56px; font-size: 1.1rem; letter-spacing: -.02em;">
            <?php echo htmlspecialchars(strtoupper(substr($username, 0, 2)), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <button type="button"
                class="btn btn-sm btn-outline-secondary position-absolute bottom-0 end-0 p-0 d-flex align-items-center justify-content-center rounded-circle bg-body border-2"
                style="width: 24px; height: 24px; font-size: .65rem;" title="Change avatar">
            <i class="bi bi-camera"></i>
        </button>
    </div>
    <div>
        <div class="fw-semibold fs-6"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></div>
        <small class="text-secondary">Profile photo &amp; identity</small>
    </div>
</div>

<form method="post">
    <div class="row g-4">
        <div class="col-12">
            <label for="profile-username" class="form-label fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-at text-primary"></i> Username
            </label>
            <input type="text" id="profile-username" class="form-control rounded-3"
                   value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-text">Your unique handle across PHPGit.</div>
        </div>

        <div class="col-12">
            <label for="profile-display-name" class="form-label fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-person text-primary"></i> Display Name
            </label>
            <input type="text" id="profile-display-name" class="form-control rounded-3"
                   placeholder="Your display name">
            <div class="form-text">The name shown on your public profile page.</div>
        </div>

        <div class="col-12">
            <label for="profile-bio" class="form-label fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-card-text text-primary"></i> Bio
            </label>
            <textarea id="profile-bio" class="form-control rounded-3"
                      rows="4" placeholder="Tell us a bit about yourself"
                      style="min-height: 110px; resize: vertical;"></textarea>
            <div class="form-text">Short introduction visible on your public profile.</div>
        </div>

        <div class="col-12">
            <label for="profile-website" class="form-label fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-link-45deg text-primary"></i> Website
            </label>
            <div class="input-group">
                <span class="input-group-text text-secondary rounded-start-3">
                    <i class="bi bi-globe2"></i>
                </span>
                <input type="url" id="profile-website" class="form-control rounded-end-3"
                       placeholder="https://yourwebsite.com">
            </div>
            <div class="form-text">Optional portfolio or personal website link.</div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-end gap-3 mt-5 pt-4 border-top border-secondary-subtle">
        <button type="reset" class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
        </button>
        <button type="button" class="btn btn-primary px-4 d-flex align-items-center gap-2">
            <i class="bi bi-check2-circle"></i> Save Profile
        </button>
    </div>
    <input type="hidden" name="action" value="update_profile">
    <input type="hidden" name="csrf_token"
           value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

</form>
