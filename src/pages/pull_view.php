<?php
declare(strict_types=1);

use App\Config;
use App\includes\Security;
use App\Services\GitCommandRunner;
use App\Services\RepositoryAccessPolicy;
use App\Services\RepositoryLocator;

$security = new Security();
$_GET['detail'] = 'pr_' . ($_GET['item'] ?? '');
$is_logged_in = $_SESSION['is_logged_in'] ?? false;
$role = $_SESSION['role'] ?? '';

$slug = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';
$itemId = (int) ($_GET['item'] ?? 0);

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
    $repo = (new RepositoryLocator($pdo, $config->getDataRoot()))->find((string)$slug);
} catch (\Exception $e) {
    $repo = null;
}

if ($repo === null) {
    http_response_code(404);
    echo '<main class="container py-5"><div class="alert alert-warning">Repository not found.</div></main>';

    return;
}

$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$isOwner = $is_logged_in && $sessionUserId === (int)$repo['owner_user_id'];
$isAdmin = $is_logged_in && $role === 'ADMIN';
$isPrivileged = $isOwner || $isAdmin;

if (!(new RepositoryAccessPolicy($pdo))->canRead($repo, $sessionUserId, (string)$role)) {
    http_response_code(403);
    echo '<main class="container py-5"><div class="alert alert-danger">You do not have permission to view this repository.</div></main>';

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
$prStmt->execute([$itemId, (int) $repo['id']]);
$pr = $prStmt->fetch(\PDO::FETCH_ASSOC);

if ($pr === false) {
    http_response_code(404);
    echo '<main class="container py-5"><div class="alert alert-warning">Pull request not found.</div></main>';

    return;
}

$prId = (int) $pr['id'];
$prTitle = (string) $pr['title'];
$prBody = trim((string) $pr['body']);
$prStatus = (string) $pr['status'];
$prFromBranch = (string) $pr['from_branch_name'];
$prToBranch = (string) $pr['to_branch_name'];
$prStatusClass = match ($prStatus) {
    'merged' => 'bg-primary-subtle text-primary border border-primary-subtle',
    'archived' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
    default => 'bg-success-subtle text-success border border-success-subtle',
};
$prAuthorUsername = (string) $pr['author_username'];
$prAuthorDisplay = trim((string) $pr['author_display_name']);
$prAuthorLabel = $prAuthorDisplay !== ''
        ? $prAuthorDisplay . ' (@' . $prAuthorUsername . ')'
        : $prAuthorUsername;
$prCreatedAt = (string) $pr['created_at'];
$prMergedAt = (string) ($pr['merged_at'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isPrivileged && $prStatus !== 'merged') {
    $token = $_POST['csrf_token'] ?? '';
    if (! $security->validateCsrfToken($token)) {
        echo '<main class="container py-5"><div class="alert alert-danger">Invalid token. Please try again.</div></main>';

        return;
    }
    $action = $_POST['repo_action'] ?? '';
    if ($action === 'merge_pull') {
        $mergeSucceeded = false;

        try {
            $repoPath = (string)$repo['path'];
            $runner = new GitCommandRunner($repoPath);
            $fromBranch = $prFromBranch;
            $toBranch = $prToBranch;
            $returnCode = 0;
            $output = $runner->run(['fetch', 'origin'], $returnCode);
            if ($returnCode !== 0) {
                error_log('Fetch failed: ' . $output);
            }
            $output = $runner->run(['checkout', $toBranch], $returnCode);
            if ($returnCode !== 0) {
                error_log('Checkout failed: ' . $output);
            }
            $output = $runner->run(['merge', '--no-ff', '--no-commit', 'origin/' . $fromBranch], $returnCode);
            if ($returnCode !== 0) {
                $runner->run(['merge', '--abort'], $returnCode);
                error_log('Merge failed: ' . $output);
            } else {
                $commitMessage = 'Merge pull request #' . $prId . ': ' . $prTitle;
                $output = $runner->run(['commit', '-m', $commitMessage], $returnCode);
                if ($returnCode === 0) {
                    $output = $runner->run(['push', 'origin', $toBranch], $returnCode);
                    if ($returnCode !== 0) {
                        error_log('Push failed: ' . $output);
                    } else {
                        $mergeSucceeded = true;
                    }
                } else {
                    error_log('Merge commit failed: ' . $output);
                }
            }
        } catch (\Exception $e) {
            error_log('Merge exception: ' . $e->getMessage());
        }
        if ($mergeSucceeded) {
            $stmt = $pdo->prepare('UPDATE pull_requests SET status = ?, merged_at = NOW() WHERE id = ? AND repository_id = ?');
            $stmt->execute(['merged', $prId, (int)$repo['id']]);
        }
        echo '<script>window.location.href="/' . htmlspecialchars($slug) . '/pulls/' . $prId . '";</script>';
        exit;
    }
}

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
            <?php if ($isPrivileged): ?>
                <?php try {
                    $csrfToken = $security->generateCsrfToken();
                } catch (\Exception $e) {
                    $csrfToken = '';
                } ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="item_id" value="<?= $prId ?>">
                    <?php if ($prStatus !== 'merged'): ?>
                    <button type="submit" name="repo_action" value="merge_pull" class="btn btn-sm btn-success"
                            onclick="return confirm('Are you sure you want to merge this pull request?')">
                        <i class="bi bi-git me-1"></i>Merge pull request
                    </button>
                    <?php endif; ?>
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
                        on <?= date('d M Y', (int) strtotime($prCreatedAt)) ?>
                    <?php endif; ?>
                </span>
                <?php if ($prStatus === 'merged' && $prMergedAt !== ''): ?>
                    <span class="text-secondary">
                        · merged on <?= date('d M Y', (int) strtotime($prMergedAt)) ?>
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
