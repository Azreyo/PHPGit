<?php
declare(strict_types=1);

use App\Config;
use App\includes\Security;
use App\Services\RepositoryService;

$security = new Security();
$_GET['detail'] = 'issue_' . ($_GET['item'] ?? '');
$is_logged_in = $_SESSION['is_logged_in'] ?? false;
$role = $_SESSION['role'] ?? '';

$slug = $_GET['slug'] ?? '';
$itemId = (int) ($_GET['item'] ?? 0);

if ($slug === '' || $itemId <= 0) {
    http_response_code(404);
    echo '<main class="container py-5"><div class="alert alert-warning">Invalid issue URL.</div></main>';

    return;
}

$config = Config::getInstance();
$pdo = $config->getPDO();

if ($pdo === null) {
    http_response_code(500);
    echo '<main class="container py-5"><div class="alert alert-danger">Database unavailable.</div></main>';

    return;
}

try {
    $repoService = new RepositoryService($pdo, $config->getDataRoot());
    $repo = $repoService->getBySlug($slug);
} catch (\Exception $e) {
    $repo = null;
}

if ($repo === null) {
    http_response_code(404);
    echo '<main class="container py-5"><div class="alert alert-warning">Repository not found.</div></main>';

    return;
}

$issueStmt = $pdo->prepare(
    'SELECT i.id, i.author_user_id, i.title, i.body, i.status, i.created_at, i.closed_at,
            u.username AS author_username, u.email AS author_email,
            COALESCE(u.display_name, \'\') AS author_display_name,
            a.username AS assignee_username,
            COALESCE(a.display_name, \'\') AS assignee_display_name
     FROM issues i
     JOIN users u ON u.id = i.author_user_id
     LEFT JOIN users a ON a.id = i.assignee_user_id
     WHERE i.id = ? AND i.repository_id = ?'
);
$issueStmt->execute([$itemId, (int) $repo['id']]);
$issue = $issueStmt->fetch(\PDO::FETCH_ASSOC);
if ($issue === false) {
    http_response_code(404);
    echo '<main class="container py-5"><div class="alert alert-warning">Issue not found.</div></main>';

    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (! $security->validateCsrfToken($token)) {
        echo '<main class="container py-5"><div class="alert alert-danger">Invalid token. Please try again.</div></main>';

        return;
    }
    $action = $_POST['repo_action'] ?? '';
    if ($action === 'close_issue') {
        $stmt = $pdo->prepare('UPDATE issues SET status = ?, closed_at = NOW() WHERE id = ? AND repository_id = ?');
        $stmt->execute(['closed', $issue['id'], (int) $repo['id']]);
        echo '<script>window.location.href="/' . htmlspecialchars($slug) . '/issues/' . $issue['id'] . '";</script>';
        exit;
    }
    if ($action === 'reopen_issue') {
        $stmt = $pdo->prepare('UPDATE issues SET status = ?, closed_at = NULL WHERE id = ? AND repository_id = ?');
        $stmt->execute(['open', $issue['id'], (int) $repo['id']]);
        echo '<script>window.location.href="/' . htmlspecialchars($slug) . '/issues/' . $issue['id'] . '";</script>';
        exit;
    }
}

$issueId = (int) $issue['id'];
$issueTitle = (string) $issue['title'];
$issueBody = trim((string) $issue['body']);
$issueStatus = (string) $issue['status'];
$issueStatusClass = $issueStatus === 'open'
        ? 'bg-success-subtle text-success border border-success-subtle'
        : 'bg-secondary-subtle text-secondary border border-secondary-subtle';
$issueAuthorUsername = (string) $issue['author_username'];
$issueAuthorDisplay = trim((string) $issue['author_display_name']);
$issueAuthorLabel = $issueAuthorDisplay !== ''
        ? $issueAuthorDisplay . ' (@' . $issueAuthorUsername . ')'
        : $issueAuthorUsername;
$issueAssigneeUsername = (string) ($issue['assignee_username'] ?? '');
$issueAssigneeDisplay = trim((string) ($issue['assignee_display_name'] ?? ''));
$issueCreatedAt = (string) $issue['created_at'];

$sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
$isOwner = $is_logged_in && $sessionUserId === (int) $repo['owner_user_id'];
$isAdmin = $is_logged_in && $role === 'ADMIN';
$isPrivileged = $isOwner || $isAdmin;
$canModifyIssue = $isPrivileged || $sessionUserId === (int) $issue['author_user_id'] || $sessionUserId === (int) ($issue['assignee_user_id'] ?? 0);

$page_title = 'Issue #' . $issueId . ' - ' . $issueTitle;
?>
<main class="container-fluid px-4 px-xl-5 py-5" style="max-width:900px;margin:0 auto;">
    <div class="mb-3">
        <a href="/<?= htmlspecialchars($slug) ?>?tab=issues"
           class="text-decoration-none d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> Back to issues
        </a>
    </div>

    <div class="border rounded-3 overflow-hidden">
        <div class="px-4 py-3 border-bottom bg-body-secondary d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div>
                <h4 class="mb-0 d-flex align-items-center gap-2">
                    <span class="text-secondary">#<?= $issueId ?></span>
                    <?= htmlspecialchars($issueTitle) ?>
                </h4>
            </div>
            <?php if ($canModifyIssue): ?>
                <?php try {
                    $csrfToken = $security->generateCsrfToken();
                } catch (\Exception $e) {
                    $csrfToken = '';
                } ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="item_id" value="<?= $issueId ?>">
                    <?php if ($issueStatus === 'open'): ?>
                        <button type="submit" name="repo_action" value="close_issue"
                                class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Close issue
                        </button>
                    <?php else: ?>
                        <button type="submit" name="repo_action" value="reopen_issue" class="btn btn-sm btn-success">
                            <i class="bi bi-arrow-repeat me-1"></i>Reopen issue
                        </button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
        <div class="p-4">
            <div class="d-flex align-items-center gap-3 mb-4 flex-wrap" style="font-size:.9rem;">
                <span class="badge <?= $issueStatusClass ?>"><?= strtoupper($issueStatus) ?></span>
                <span class="text-secondary">
                    opened by <strong><?= htmlspecialchars($issueAuthorLabel) ?></strong>
                    <?php if ($issueCreatedAt !== ''): ?>
                        on <?= date('d M Y', (int) strtotime($issueCreatedAt)) ?>
                    <?php endif; ?>
                </span>
                <?php if ($issueAssigneeUsername !== ''): ?>
                    <span class="text-secondary">
                        · assigned to <strong><?= htmlspecialchars($issueAssigneeDisplay ?: $issueAssigneeUsername) ?></strong>
                    </span>
                <?php endif; ?>
            </div>
            <?php if ($issueBody !== ''): ?>
                <div class="py-2" style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($issueBody)) ?></div>
            <?php else: ?>
                <p class="text-secondary mb-0">No description provided.</p>
            <?php endif; ?>
        </div>
    </div>
</main>