<?php

/** @var PDO|null $pdo */

$requestedUsername = trim((string)($_GET['user'] ?? $_GET['username'] ?? ''));
$requestedUsername = preg_replace('/[^a-zA-Z0-9._-]/', '', $requestedUsername) ?? '';
$profile = null;
$error = '';

if ($requestedUsername === '') {
    $error = 'Please provide a valid username.';
} elseif ($pdo === null) {
    $error = 'Database is currently unavailable. Please try again later.';
} else {
    $stmt = $pdo->prepare('SELECT username, role, created_at FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$requestedUsername]);
    $profile = $stmt->fetch();

    if ($profile === false) {
        $error = 'Profile not found.';
        $profile = null;
    }
}

?>

<main class="container py-4" style="max-width: 760px;">
    <div class="mb-4">
        <h1 class="h3 mb-1">Profile Viewer</h1>
        <p class="text-secondary mb-0">View a public profile by username.</p>
    </div>

    <form method="get" class="row g-3 mb-4">
        <input type="hidden" name="page" value="profile_viewer">
        <div class="col-12 col-md-9">
            <label for="user" class="form-label">Username</label>
            <input
                    type="text"
                    class="form-control"
                    id="user"
                    name="user"
                    maxlength="64"
                    value="<?php echo htmlspecialchars($requestedUsername, ENT_QUOTES, 'UTF-8'); ?>"
                    required
            >
        </div>
        <div class="col-12 col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">View</button>
        </div>
    </form>

    <?php if ($error !== ''): ?>
        <div class="alert alert-warning" role="alert">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php elseif ($profile !== null): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 card-title mb-3">Public Profile</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Username</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars((string)$profile['username'], ENT_QUOTES, 'UTF-8'); ?></dd>

                    <dt class="col-sm-4">Role</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars((string)$profile['role'], ENT_QUOTES, 'UTF-8'); ?></dd>

                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars((string)$profile['created_at'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </dl>
            </div>
        </div>
    <?php endif; ?>
</main>
