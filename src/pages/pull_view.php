<?php
declare(strict_types=1);

use App\Config;
use App\Services\RepositoryService;

$_GET['detail'] = 'pr_' . ($_GET['item'] ?? '');
$is_logged_in = $_SESSION['is_logged_in'] ?? false;
$role = $_SESSION['role'] ?? '';

$slug = $_GET['slug'] ?? '';
$itemId = (int)($_GET['item'] ?? 0);

if ($slug === '' || $itemId <= 0) {
    http_response_code(404);
    echo '<main class="container py-5"><div class="alert alert-warning">Invalid pull request URL.</div></main>';
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

$prStmt = $pdo->prepare(
        'SELECT p.id, p.title, p.body, p.status, p.created_at, p.merged_at,
            p.from_branch_name, p.to_branch_name, p.from_head_hash, p.to_head_hash,
            u.username AS author_username, COALESCE(u.display_name, \'\') AS author_display_name
     FROM pull_requests p
     JOIN users u ON u.id = p.author_user_id
     WHERE p.id = ? AND p.repository_id = ?'
);
$prStmt->execute([$itemId, (int)$repo['id']]);
$pr = $prStmt->fetch(\PDO::FETCH_ASSOC);

if ($pr === false) {
    http_response_code(404);
    echo '<main class="container py-5"><div class="alert alert-warning">Pull request not found.</div></main>';
    return;
}

$prId = (int)$pr['id'];
$prTitle = (string)$pr['title'];
$prBody = trim((string)$pr['body']);
$prStatus = (string)$pr['status'];
$prFromBranch = (string)$pr['from_branch_name'];
$prToBranch = (string)$pr['to_branch_name'];
$prStatusClass = match ($prStatus) {
    'merged' => 'bg-primary-subtle text-primary border border-primary-subtle',
    'archived' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
    default => 'bg-success-subtle text-success border border-success-subtle',
};
$prAuthorUsername = (string)$pr['author_username'];
$prAuthorDisplay = trim((string)$pr['author_display_name']);
$prAuthorLabel = $prAuthorDisplay !== ''
        ? $prAuthorDisplay . ' (@' . $prAuthorUsername . ')'
        : $prAuthorUsername;
$prCreatedAt = (string)$pr['created_at'];
$prMergedAt = (string)($pr['merged_at'] ?? '');

$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$isOwner = $is_logged_in && $sessionUserId === (int)$repo['owner_user_id'];
$isAdmin = $is_logged_in && $role === 'ADMIN';
$isPrivileged = $isOwner || $isAdmin;

$page_title = 'Pull Request #' . $prId . ' - ' . $prTitle;
?>
<main class="container-fluid px-4 px-xl-5 py-5" style="max-width:900px;margin:0 auto;">
    <div class="mb-3">
        <a href="/<?= htmlspecialchars($slug) ?>?tab=pulls"
           class="text-decoration-none d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> Back to pull requests
        </a>
    </div>

    <div class="border rounded-3 overflow-hidden">
        <div class="px-4 py-3 border-bottom bg-body-secondary d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div>
                <h4 class="mb-0 d-flex align-items-center gap-2">
                    <span class="text-secondary">#<?= $prId ?></span>
                    <?= htmlspecialchars($prTitle) ?>
                </h4>
            </div>
            <?php if ($isPrivileged && $prStatus === 'open'): ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="item_id" value="<?= $prId ?>">
                    <button type="submit" name="repo_action" value="merge_pull" class="btn btn-sm btn-success"
                            onclick="return confirm('Are you sure you want to merge this pull request?')">
                        <i class="bi bi-git me-1"></i>Merge pull request
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <div class="p-4">
            <div class="d-flex align-items-center gap-3 mb-4 flex-wrap" style="font-size:.9rem;">
                <span class="badge <?= $prStatusClass ?>"><?= strtoupper($prStatus) ?></span>
                <span class="text-secondary">
                    wants to merge <code
                            class="bg-body-tertiary px-1 rounded"><?= htmlspecialchars($prFromBranch) ?></code>
                    into <code class="bg-body-tertiary px-1 rounded"><?= htmlspecialchars($prToBranch) ?></code>
                </span>
            </div>
            <div class="d-flex align-items-center gap-3 mb-4 flex-wrap" style="font-size:.9rem;">
                <span class="text-secondary">
                    opened by <strong><?= htmlspecialchars($prAuthorLabel) ?></strong>
                    <?php if ($prCreatedAt !== ''): ?>
                        on <?= date('d M Y', (int)strtotime($prCreatedAt)) ?>
                    <?php endif; ?>
                </span>
                <?php if ($prStatus === 'merged' && $prMergedAt !== ''): ?>
                    <span class="text-secondary">
                        · merged on <?= date('d M Y', (int)strtotime($prMergedAt)) ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if ($prBody !== ''): ?>
                <div class="py-2" style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($prBody)) ?></div>
            <?php else: ?>
                <p class="text-secondary mb-0">No description provided.</p>
            <?php endif; ?>
        </div>
    </div>
</main>