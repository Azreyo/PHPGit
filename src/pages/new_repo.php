<?php

declare(strict_types=1);

use App\Config;
use App\includes\Logging;
use App\includes\Security;
use App\Services\RepositoryService;
use Random\RandomException;

/** @var bool $is_logged_in */

if (! $is_logged_in) {
    http_response_code(403);
    include __DIR__ . '/403.php';

    return;
}

$config = Config::getInstance();
$security = new Security();

$errors = $_SESSION['repo_errors'] ?? [];
$prefill = $_SESSION['repo_prefill'] ?? [];
unset($_SESSION['repo_errors'], $_SESSION['repo_prefill']);

$userId = (int) ($_SESSION['user_id'] ?? 0);
$username = htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (! $security->validateCsrfToken($csrfToken)) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    } else {
        $repoName = trim($_POST['repo_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $visibility = $_POST['visibility'] ?? 'public';
        $defaultBranch = trim($_POST['default_branch'] ?? 'main');

        if (empty($repoName)) {
            $errors[] = 'Repository name is required.';
        } elseif (! RepositoryService::isValidRepoName($repoName)) {
            $errors[] = 'Invalid repository name. Use letters, numbers, hyphens, underscores, or dots (no leading dot/hyphen).';
        }

        if (strlen($description) > 500) {
            $errors[] = 'Description must be 500 characters or fewer.';
        }

        if (! in_array($visibility, ['public', 'private'], true)) {
            $visibility = 'public';
        }

        if (empty($errors)) {
            $pdoConn = $config->getPdo();
            if ($pdoConn === null) {
                $errors[] = 'Database is currently unavailable. Please try again later.';
            } else {
                $service = new RepositoryService($pdoConn, $config->getDataRoot());

                $result = $service->create($userId, $_SESSION['username'] ?? '', $repoName, $description, $visibility, $defaultBranch ?: 'main');

                if ($result['success']) {
                    $_SESSION['repo_flash'] = 'Repository <strong>' . htmlspecialchars($repoName, ENT_QUOTES, 'UTF-8') . '</strong> created successfully.';
                    echo '<script>window.location.href="index.php?page=repos";</script>';
                    exit;
                }

                $errors[] = $result['error'] ?? 'Unknown error creating repository.';
            }
        }
    }

    $_SESSION['repo_errors'] = $errors;
    $_SESSION['repo_prefill'] = [
        'repo_name' => htmlspecialchars(trim($_POST['repo_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'visibility' => $_POST['visibility'] ?? 'public',
        'default_branch' => htmlspecialchars(trim($_POST['default_branch'] ?? 'main'), ENT_QUOTES, 'UTF-8'),
    ];
    echo '<script>window.location.href="index.php?page=new_repo";</script>';
    exit;
}

try {
    $csrf_token = $security->generateCsrfToken();
} catch (RandomException $e) {
    Logging::loggingToFile('Cannot generate csrf token: ' . $e->getMessage(), 4);
    $csrf_token = '';
}

$f = [
    'repo_name' => $prefill['repo_name'] ?? '',
    'description' => $prefill['description'] ?? '',
    'visibility' => $prefill['visibility'] ?? 'public',
    'default_branch' => $prefill['default_branch'] ?? 'main',
];
?>

<main class="container py-5" style="max-width: 680px;">
    <h1 class="mb-1 fs-3"><i class="bi bi-folder-plus me-2"></i>Create a new repository</h1>
    <p class="text-secondary mb-4">A repository contains all project files, including the revision history.</p>

    <?php if (! empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="mb-3">
            <label class="form-label fw-semibold">Owner</label>
            <input type="text" class="form-control" value="<?php echo $username; ?>" disabled>
        </div>

        <div class="mb-3">
            <label for="repo_name" class="form-label fw-semibold">Repository name <span
                        class="text-danger">*</span></label>
            <input
                    type="text"
                    class="form-control"
                    id="repo_name"
                    name="repo_name"
                    maxlength="100"
                    value="<?php echo htmlspecialchars($f['repo_name'], ENT_QUOTES, 'UTF-8'); ?>"
                    required
                    autocomplete="off"
                    placeholder="e.g. my-awesome-project"
            >
            <div class="form-text">Letters, numbers, hyphens, underscores, dots. No leading dot or hyphen.</div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label fw-semibold">Description <span class="text-secondary fw-normal">(optional)</span></label>
            <input
                    type="text"
                    class="form-control"
                    id="description"
                    name="description"
                    maxlength="500"
                    value="<?php echo htmlspecialchars($f['description'], ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="Short description of this repository"
            >
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold">Visibility</label>
            <div class="d-flex flex-column gap-2">
                <div class="form-check border rounded p-3">
                    <input class="form-check-input" type="radio" name="visibility" id="vis_public" value="public"
                        <?php echo $f['visibility'] === 'public' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="vis_public">
                        <i class="bi bi-globe me-1"></i> <strong>Public</strong>
                        <div class="text-secondary small">Anyone can see this repository.</div>
                    </label>
                </div>
                <div class="form-check border rounded p-3">
                    <input class="form-check-input" type="radio" name="visibility" id="vis_private" value="private"
                        <?php echo $f['visibility'] === 'private' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="vis_private">
                        <i class="bi bi-lock me-1"></i> <strong>Private</strong>
                        <div class="text-secondary small">Only you and explicit collaborators can see it.</div>
                    </label>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <label for="default_branch" class="form-label fw-semibold">Default branch</label>
            <input
                    type="text"
                    class="form-control"
                    id="default_branch"
                    name="default_branch"
                    maxlength="100"
                    value="<?php echo htmlspecialchars($f['default_branch'], ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="main"
            >
        </div>

        <button type="submit" class="btn btn-success px-4">
            <i class="bi bi-folder-plus me-1"></i> Create repository
        </button>
        <a href="index.php?page=repos" class="btn btn-outline-secondary ms-2">Cancel</a>
    </form>
</main>


