<?php

declare(strict_types=1);

use App\Config;
use App\includes\Logging;
use App\includes\Security;
use App\Services\PersonalAccessTokenService;

$pdo = Config::getInstance()->getPDO();
$security = new Security();
$userId = (int)($_SESSION['user_id'] ?? 0);
$errors = $_SESSION['token_errors'] ?? [];
$plainToken = $_SESSION['new_access_token'] ?? null;
unset($_SESSION['token_errors'], $_SESSION['new_access_token']);
$tokens = [];
$csrfToken = '';

if ($pdo !== null && $userId > 0) {
    $service = new PersonalAccessTokenService($pdo);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $csrf = (string)($_POST['csrf_token'] ?? '');
        $action = (string)($_POST['token_action'] ?? '');
        $postErrors = [];
        if (!$security->validateCsrfToken($csrf)) {
            $postErrors[] = 'Invalid or expired form submission.';
        } elseif ($action === 'revoke') {
            $service->revoke($userId, (int)($_POST['token_id'] ?? 0));
        } elseif ($action === 'create') {
            $password = (string)($_POST['current_password'] ?? '');
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? AND status = ? LIMIT 1');
            $stmt->execute([$userId, 'ACTIVE']);
            $passwordHash = $stmt->fetchColumn();
            if (!is_string($passwordHash) || !password_verify($password, $passwordHash)) {
                $postErrors[] = 'Current password is incorrect.';
            } else {
                $expiryChoice = (string)($_POST['expires'] ?? '90');
                $expiresAt = match ($expiryChoice) {
                    '30' => new DateTimeImmutable('+30 days', new DateTimeZone('UTC')),
                    '365' => new DateTimeImmutable('+365 days', new DateTimeZone('UTC')),
                    'never' => null,
                    default => new DateTimeImmutable('+90 days', new DateTimeZone('UTC')),
                };

                try {
                    $created = $service->create(
                            $userId,
                            (string)($_POST['name'] ?? ''),
                            (string)($_POST['scope'] ?? 'read'),
                            $expiresAt
                    );
                    $_SESSION['new_access_token'] = $created['token'];
                    Logging::loggingToFile('Personal access token created for user_id=' . $userId, 1, true);
                } catch (Throwable $e) {
                    $postErrors[] = $e->getMessage();
                }
            }
        } else {
            $postErrors[] = 'Unknown token action.';
        }
        $_SESSION['token_errors'] = $postErrors;
        header('Location: /settings?tab=access-tokens', true, 303);
        exit;
    }
    $tokens = $service->listForUser($userId);
}

try {
    $csrfToken = $security->generateCsrfToken();
} catch (Throwable $e) {
    Logging::loggingToFile('Cannot generate token CSRF token: ' . $e->getMessage(), 4);
}

$e = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<div class="d-flex align-items-center gap-3 mb-4 pb-4 border-bottom border-secondary-subtle">
    <div class="rounded-3 bg-primary-subtle text-primary p-2"><i class="bi bi-shield-key"></i></div>
    <div><p class="section-label mb-0">Personal access tokens</p><h6 class="fw-bold mb-0">Git over HTTPS</h6></div>
</div>

<?php foreach ((array)$errors as $error): ?>
    <div class="alert alert-danger"><?= $e((string)$error) ?></div>
<?php endforeach; ?>
<?php if (is_string($plainToken) && $plainToken !== ''): ?>
    <div class="alert alert-success">
        <strong>Copy this token now. It will not be shown again.</strong>
        <code class="d-block mt-2 user-select-all text-break"><?= $e($plainToken) ?></code>
    </div>
<?php endif; ?>

<form method="post" class="border rounded-3 p-3 mb-4">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <input type="hidden" name="token_action" value="create">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="token-name">Name</label><input class="form-control"
                                                                                            id="token-name" name="name"
                                                                                            maxlength="100" required>
        </div>
        <div class="col-md-6"><label class="form-label" for="token-password">Current password</label><input
                    class="form-control" id="token-password" name="current_password" type="password"
                    autocomplete="current-password" required></div>
        <div class="col-md-6"><label class="form-label" for="token-scope">Scope</label><select class="form-select"
                                                                                               id="token-scope"
                                                                                               name="scope">
                <option value="read">Read repositories</option>
                <option value="write">Read and write repositories</option>
            </select></div>
        <div class="col-md-6"><label class="form-label" for="token-expiry">Expires</label><select class="form-select"
                                                                                                  id="token-expiry"
                                                                                                  name="expires">
                <option value="30">30 days</option>
                <option value="90" selected>90 days</option>
                <option value="365">365 days</option>
                <option value="never">Never</option>
            </select></div>
    </div>
    <button class="btn btn-primary mt-3" type="submit">Generate token</button>
</form>

<div class="list-group">
    <?php if ($tokens === []): ?>
        <div class="text-secondary text-center py-4">No access tokens.</div><?php endif; ?>
    <?php foreach ($tokens as $token): ?>
        <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
            <div><strong><?= $e((string)$token['name']) ?></strong> <span
                        class="badge text-bg-secondary"><?= $e((string)$token['scope']) ?></span><br><code><?= $e((string)$token['token_prefix']) ?>
                    …</code><br><small
                        class="text-secondary">Created <?= $e((string)$token['created_at']) ?><?= $token['expires_at'] ? ' · expires ' . $e((string)$token['expires_at']) : ' · no expiry' ?></small>
            </div>
            <?php if ($token['revoked_at'] === null): ?>
                <form method="post"><input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input
                        type="hidden" name="token_action" value="revoke"><input type="hidden" name="token_id"
                                                                                value="<?= (int)$token['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Revoke</button></form><?php else: ?><span
                    class="badge text-bg-danger">Revoked</span><?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
