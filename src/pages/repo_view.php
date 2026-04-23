<?php
declare(strict_types=1);

use App\Controllers\RepoViewController;
use App\includes\Assets;

/** @var bool $is_logged_in */
/** @var string $role */
$rv = new RepoViewController();
if (! $rv->handle($is_logged_in, $role)) {
    return;
}
$rawSlug = $rv->rawSlug;
$currentBranch = $rv->currentBranch;
$currentPath = $rv->currentPath;
$viewMode = $rv->viewMode;
$isEmpty = $rv->isEmpty;
$isOwner = $rv->isOwner;
$isAdmin = $rv->isAdmin;
$branches = $rv->branches;
$treeEntries = $rv->treeEntries;
$commitMap = $rv->commitMap;
$latestCommit = $rv->latestCommit;
$commitCount = $rv->commitCount;
$langBreakdown = $rv->langBreakdown;
$readmeContent = $rv->readmeContent;
$subEntries = $rv->subEntries;
$subCommitMap = $rv->subCommitMap;
$pathLatestCommit = $rv->pathLatestCommit;
$fileData = $rv->fileData;
$fullFileTree = $rv->fullFileTree;
$breadcrumbs = $rv->breadcrumbs;
$rName = $rv->rName;
$rDesc = $rv->rDesc;
$rVis = $rv->rVis;
$rBranch = $rv->rBranch;
$rOwner = $rv->rOwner;
$rDisp = $rv->rDisp;
$rSlug = $rv->rSlug;
$rCreated = $rv->rCreated;
$rUpdated = $rv->rUpdated;
$rStars = $rv->rStars;
$rForks = $rv->rForks;
$httpUrl = $rv->httpUrl;
$sshUrl = $rv->sshUrl;
$httpBase = $rv->httpBase;
$csrfToken = $rv->csrfToken;
$issues = $rv->issues;
$pullRequests = $rv->pullRequests;
$openIssuesCount = $rv->openIssuesCount;
$openPullRequestsCount = $rv->openPullRequestsCount;
$tabErrors = $rv->tabErrors;
$tabSuccess = $rv->tabSuccess;
$detailType = $rv->detailType;
$detailItem = $rv->detailItem;

$repoDefaultBranch = (string)($rv->repo['default_branch'] ?? $currentBranch);
$repoDescriptionRaw = (string)($rv->repo['repo_description'] ?? '');
$repoVisibility = (string)($rv->repo['visibility'] ?? 'public');

$isPrivileged = $isOwner || $isAdmin;
$requestedTab = isset($_GET['tab']) ? strtolower(trim((string) $_GET['tab'])) : 'code';
$allowedTabs = ['code', 'issues', 'pulls'];
if ($isPrivileged) {
    $allowedTabs[] = 'settings';
}
$activeTab = in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'code';

$codeTabUrl = '/' . $rSlug;
$issuesTabUrl = '/' . $rSlug . '?tab=issues';
$pullsTabUrl = '/' . $rSlug . '?tab=pulls';
$settingsTabUrl = '/' . $rSlug . '?tab=settings';
?>
<link rel="stylesheet" href="<?= Assets::url('assets/css/repo_view.css') ?>">
<main class="container-fluid px-4 px-xl-5 py-5" style="max-width:1600px;margin:0 auto;">
    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
        <i class="bi bi-folder2 text-secondary" style="font-size:1.1rem;"></i>
        <h1 class="mb-0 fs-5 fw-normal">
            <a href="/<?= $rOwner ?>" class="text-decoration-none"><?= $rOwner ?></a>
            <span class="text-secondary mx-1">/</span>
            <a href="/<?= $rSlug ?>" class="fw-bold text-decoration-none"><?= $rName ?></a>
        </h1>
        <span class="badge fw-normal align-middle <?= $rVis === 'private' ? 'bg-secondary' : 'text-secondary border border-secondary-subtle bg-transparent' ?>">
            <?= $rVis === 'private' ? '<i class="bi bi-lock-fill me-1"></i>Private' : '<i class="bi bi-globe me-1"></i>Public' ?>
        </span>
    </div>
    <?php if ($rDesc !== ''): ?>
        <p class="text-secondary mb-2 ms-4" style="font-size:.9rem;"><?= $rDesc ?></p>
    <?php endif; ?>
    <ul class="nav rv-header-tabs mt-3 mb-0">
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-1 <?= $activeTab === 'code' ? 'active' : 'text-secondary' ?>"
               href="<?= RepoViewController::e($codeTabUrl) ?>">
                <i class="bi bi-code-square"></i> Code
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-1 <?= $activeTab === 'issues' ? 'active' : 'text-secondary' ?>"
               href="<?= RepoViewController::e($issuesTabUrl) ?>">
                <i class="bi bi-exclamation-circle"></i> Issues
                <span class="badge <?= $activeTab === 'issues' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' ?> ms-1"
                      style="font-size:.7rem;"><?= number_format($openIssuesCount) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-1 <?= $activeTab === 'pulls' ? 'active' : 'text-secondary' ?>"
               href="<?= RepoViewController::e($pullsTabUrl) ?>">
                <i class="bi bi-git"></i> Pull Requests
                <span class="badge <?= $activeTab === 'pulls' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' ?> ms-1"
                      style="font-size:.7rem;"><?= number_format($openPullRequestsCount) ?></span>
            </a>
        </li>
        <?php if ($isPrivileged): ?>
            <li class="nav-item ms-auto">
                <a class="nav-link d-flex align-items-center gap-1 <?= $activeTab === 'settings' ? 'active' : 'text-secondary' ?>"
                   href="<?= RepoViewController::e($settingsTabUrl) ?>">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <?php if ($activeTab !== 'code'): ?>
        <div class="pt-4 border-top" style="border-color:var(--bs-border-color)!important;">
            <?php if (!empty($tabErrors)): ?>
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0">
                        <?php foreach ($tabErrors as $tabError): ?>
                            <li><?= RepoViewController::e($tabError) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($tabSuccess !== null): ?>
                <div class="alert alert-success mb-3"><?= RepoViewController::e($tabSuccess) ?></div>
            <?php endif; ?>
            <?php if ($activeTab === 'issues'): ?>
                <div class="border rounded-3 overflow-hidden">
                    <div class="px-4 py-3 border-bottom bg-body-secondary d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div>
                            <h5 class="mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-exclamation-circle"></i>
                                Issues
                            </h5>
                            <p class="text-secondary mb-0" style="font-size:.85rem;">Track bugs and discuss tasks
                                for <?= RepoViewController::e($rName) ?>.</p>
                        </div>
                        <?php if ($is_logged_in): ?>
                            <button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#newIssueForm" aria-expanded="false" aria-controls="newIssueForm">
                                <i class="bi bi-plus-lg me-1"></i>New issue
                            </button>
                        <?php else: ?>
                            <a class="btn btn-sm btn-outline-secondary" href="/login">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Sign in to create
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_logged_in): ?>
                        <div id="newIssueForm" class="collapse border-bottom px-4 py-3 bg-body-tertiary">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="csrf_token" value="<?= RepoViewController::e($csrfToken) ?>">
                                <input type="hidden" name="repo_action" value="create_issue">

                                <div class="col-12">
                                    <label for="issueTitle" class="form-label fw-semibold">Title</label>
                                    <input id="issueTitle" type="text" name="issue_title" class="form-control"
                                           maxlength="160" required>
                                </div>
                                <div class="col-12">
                                    <label for="issueBody" class="form-label fw-semibold">Description</label>
                                    <textarea id="issueBody" name="issue_body" class="form-control" rows="4"
                                              maxlength="20000" placeholder="Describe the issue..."></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-send me-1"></i>Create issue
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($issues)): ?>
                        <div class="p-5 text-center">
                            <i class="bi bi-chat-square-text text-secondary" style="font-size:2.4rem;"></i>
                            <h5 class="fw-bold mt-3 mb-1">No issues yet</h5>
                            <p class="text-secondary mb-0">Open the first issue to start discussing bugs and tasks.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($issues as $issue): ?>
                                <?php
                                $issueStatus = (string)($issue['status'] ?? 'open');
                                $issueStatusClass = $issueStatus === 'open'
                                        ? 'bg-success-subtle text-success border border-success-subtle'
                                        : 'bg-secondary-subtle text-secondary border border-secondary-subtle';
                                $issueAuthorUsername = (string)($issue['author_username'] ?? 'unknown');
                                $issueAuthorDisplay = trim((string)($issue['author_display_name'] ?? ''));
                                $issueAuthorLabel = $issueAuthorDisplay !== '' ? $issueAuthorDisplay : $issueAuthorUsername;
                                $issueCreatedAt = (string)($issue['created_at'] ?? '');
                                $issueBody = trim((string)($issue['body'] ?? ''));
                                $issueDetailUrl = '/' . $rSlug . '/issues/' . (int)($issue['id'] ?? 0);
                                ?>
                                <div class="list-group-item px-4 py-3">
                                    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                                        <div>
                                            <h6 class="mb-1 fw-semibold d-flex align-items-center gap-2">
                                                <a href="<?= RepoViewController::e($issueDetailUrl) ?>"
                                                   class="text-decoration-none"><?= RepoViewController::e((string)($issue['title'] ?? 'Untitled issue')) ?></a>
                                                <span class="badge fw-normal <?= $issueStatusClass ?>"><?= RepoViewController::e(strtoupper($issueStatus)) ?></span>
                                            </h6>
                                            <div class="text-secondary" style="font-size:.82rem;">
                                                #<?= (int)($issue['id'] ?? 0) ?> opened by
                                                <strong class="text-body"><?= RepoViewController::e($issueAuthorLabel) ?></strong>
                                                (@<?= RepoViewController::e($issueAuthorUsername) ?>)
                                                <?php if ($issueCreatedAt !== ''): ?>
                                                    &middot;
                                                    <?= RepoViewController::e(date('d M Y H:i', (int)strtotime($issueCreatedAt))) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($issueBody !== ''): ?>
                                        <p class="mb-0 mt-2 text-secondary" style="font-size:.88rem;">
                                            <?= nl2br(RepoViewController::e($issueBody)) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($activeTab === 'pulls'): ?>
                <div class="border rounded-3 overflow-hidden">
                    <div class="px-4 py-3 border-bottom bg-body-secondary d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div>
                            <h5 class="mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-git"></i>
                                Pull Requests
                            </h5>
                            <p class="text-secondary mb-0" style="font-size:.85rem;">Propose and review changes before
                                merging into <?= RepoViewController::e($rBranch) ?>.</p>
                        </div>
                        <?php if ($is_logged_in): ?>
                            <button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#newPullForm" aria-expanded="false" aria-controls="newPullForm">
                                <i class="bi bi-diagram-3 me-1"></i>New pull request
                            </button>
                        <?php else: ?>
                            <a class="btn btn-sm btn-outline-secondary" href="/login">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Sign in to create
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_logged_in): ?>
                        <div id="newPullForm" class="collapse border-bottom px-4 py-3 bg-body-tertiary">
                            <?php $canCreatePull = !empty($branches); ?>
                            <?php if (!$canCreatePull): ?>
                                <div class="alert alert-warning mb-0" style="font-size:.86rem;">
                                    Pull requests are available after the repository has at least one branch with
                                    commits.
                                </div>
                            <?php else: ?>
                                <?php
                                $pullFromDefault = $branches[0];
                                $pullToDefault = $repoDefaultBranch;
                                ?>
                                <form method="POST" class="row g-3">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= RepoViewController::e($csrfToken) ?>">
                                    <input type="hidden" name="repo_action" value="create_pull">

                                    <div class="col-12">
                                        <label for="pullTitle" class="form-label fw-semibold">Title</label>
                                        <input id="pullTitle" type="text" name="pull_title" class="form-control"
                                               maxlength="160" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fromBranch" class="form-label fw-semibold">From branch</label>
                                        <select id="fromBranch" name="from_branch" class="form-select" required>
                                            <?php foreach ($branches as $branch): ?>
                                                <option value="<?= RepoViewController::e($branch) ?>" <?= $branch === $pullFromDefault ? 'selected' : '' ?>>
                                                    <?= RepoViewController::e($branch) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="toBranch" class="form-label fw-semibold">To branch</label>
                                        <select id="toBranch" name="to_branch" class="form-select" required>
                                            <?php foreach ($branches as $branch): ?>
                                                <option value="<?= RepoViewController::e($branch) ?>" <?= $branch === $pullToDefault ? 'selected' : '' ?>>
                                                    <?= RepoViewController::e($branch) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="pullBody" class="form-label fw-semibold">Description</label>
                                        <textarea id="pullBody" name="pull_body" class="form-control" rows="4"
                                                  maxlength="20000"
                                                  placeholder="Describe what this pull request changes..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="bi bi-send me-1"></i>Create pull request
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($pullRequests)): ?>
                        <div class="p-5 text-center">
                            <i class="bi bi-git text-secondary" style="font-size:2.4rem;"></i>
                            <h5 class="fw-bold mt-3 mb-1">No pull requests yet</h5>
                            <p class="text-secondary mb-0">Create a pull request to propose changes between
                                branches.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pullRequests as $pull): ?>
                                <?php
                                $pullStatus = (string)($pull['status'] ?? 'open');
                                $pullStatusClass = match ($pullStatus) {
                                    'merged' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                    'archived' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                    default => 'bg-success-subtle text-success border border-success-subtle',
                                };
                                $pullAuthorUsername = (string)($pull['author_username'] ?? 'unknown');
                                $pullAuthorDisplay = trim((string)($pull['author_display_name'] ?? ''));
                                $pullAuthorLabel = $pullAuthorDisplay !== '' ? $pullAuthorDisplay : $pullAuthorUsername;
                                $pullCreatedAt = (string)($pull['created_at'] ?? '');
                                $pullBody = trim((string)($pull['body'] ?? ''));
                                $pullDetailUrl = '/' . $rSlug . '/pulls/' . (int)($pull['id'] ?? 0);
                                ?>
                                <div class="list-group-item px-4 py-3">
                                    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                                        <div>
                                            <h6 class="mb-1 fw-semibold d-flex align-items-center gap-2">
                                                <a href="<?= RepoViewController::e($pullDetailUrl) ?>"
                                                   class="text-decoration-none"><?= RepoViewController::e((string)($pull['title'] ?? 'Untitled pull request')) ?></a>
                                                <span class="badge fw-normal <?= $pullStatusClass ?>"><?= RepoViewController::e(strtoupper($pullStatus)) ?></span>
                                            </h6>
                                            <div class="text-secondary" style="font-size:.82rem;">
                                                #<?= (int)($pull['id'] ?? 0) ?>
                                                <span class="mx-1">·</span>
                                                <?= RepoViewController::e((string)($pull['from_branch_name'] ?? '')) ?>
                                                <i class="bi bi-arrow-right mx-1"></i>
                                                <?= RepoViewController::e((string)($pull['to_branch_name'] ?? '')) ?>
                                                <span class="mx-1">·</span>
                                                by <strong
                                                        class="text-body"><?= RepoViewController::e($pullAuthorLabel) ?></strong>
                                                (@<?= RepoViewController::e($pullAuthorUsername) ?>)
                                                <?php if ($pullCreatedAt !== ''): ?>
                                                    <span class="mx-1">·</span>
                                                    <?= RepoViewController::e(date('d M Y H:i', (int)strtotime($pullCreatedAt))) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($pullBody !== ''): ?>
                                        <p class="mb-0 mt-2 text-secondary" style="font-size:.88rem;">
                                            <?= nl2br(RepoViewController::e($pullBody)) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($isPrivileged): ?>
                <div class="border rounded-3 overflow-hidden mb-3">
                    <div class="px-4 py-3 border-bottom bg-body-secondary">
                        <h5 class="mb-1 d-flex align-items-center gap-2">
                            <i class="bi bi-gear"></i>
                            Repository settings
                        </h5>
                        <p class="text-secondary mb-0" style="font-size:.85rem;">Visual shell for repository
                            configuration.</p>
                    </div>
                    <div class="p-4">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= RepoViewController::e($csrfToken) ?>">
                            <input type="hidden" name="repo_action" value="update_repo_settings">

                            <div class="col-md-6">
                                <label for="repoNamePreview" class="form-label small fw-semibold text-secondary">Repository
                                    name</label>
                                <input id="repoNamePreview" type="text" class="form-control"
                                       value="<?= RepoViewController::e($rName) ?>"
                                       disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="repoBranchPreview" class="form-label small fw-semibold text-secondary">Default
                                    branch</label>
                                <?php if (!empty($branches)): ?>
                                    <select id="repoBranchPreview" name="default_branch" class="form-select">
                                        <?php foreach ($branches as $branch): ?>
                                            <option value="<?= RepoViewController::e($branch) ?>" <?= $branch === $repoDefaultBranch ? 'selected' : '' ?>>
                                                <?= RepoViewController::e($branch) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input id="repoBranchPreview" type="text" name="default_branch" class="form-control"
                                           value="<?= RepoViewController::e($repoDefaultBranch) ?>">
                                <?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label for="repoDescriptionPreview" class="form-label small fw-semibold text-secondary">Description</label>
                                <textarea id="repoDescriptionPreview" name="repo_description" class="form-control"
                                          rows="3"><?= RepoViewController::e($repoDescriptionRaw) ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary">Visibility</label>
                                <div class="d-flex flex-column flex-sm-row gap-2">
                                    <div class="form-check border rounded-3 p-2 flex-grow-1">
                                        <input class="form-check-input" type="radio" name="visibility"
                                               id="repoVisibilityPublic"
                                               value="public" <?= $repoVisibility === 'public' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="repoVisibilityPublic">
                                            <i class="bi bi-globe me-1"></i>Public
                                        </label>
                                    </div>
                                    <div class="form-check border rounded-3 p-2 flex-grow-1">
                                        <input class="form-check-input" type="radio" name="visibility"
                                               id="repoVisibilityPrivate"
                                               value="private" <?= $repoVisibility === 'private' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="repoVisibilityPrivate">
                                            <i class="bi bi-lock-fill me-1"></i>Private
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-check2-circle me-1"></i>Save changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="border border-danger-subtle rounded-3 overflow-hidden">
                    <div class="px-4 py-3 border-bottom border-danger-subtle bg-danger-subtle">
                        <h6 class="mb-1 fw-bold text-danger d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle"></i>Danger zone
                        </h6>
                        <p class="mb-0 text-danger-emphasis" style="font-size:.82rem;">These actions are intentionally
                            disabled for now.</p>
                    </div>
                    <div class="p-4 d-flex flex-column gap-2">
                        <button class="btn btn-outline-danger text-start" type="button" disabled>Transfer ownership
                        </button>
                        <button class="btn btn-outline-danger text-start" type="button" disabled>Archive this
                            repository
                        </button>
                        <button class="btn btn-danger text-start" type="button" disabled>Delete this repository</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <div class="row g-3 mt-0 pt-4 border-top" style="border-color:var(--bs-border-color)!important;">
        <?php if (! $isEmpty && ! empty($fullFileTree)): ?>
            <div class="col-lg-2 d-none d-lg-block">
                <div class="rv-tree-panel">
                    <div class="rv-tree-panel-header">
                        <i class="bi bi-folder2 text-secondary" style="font-size:.9rem;"></i>
                        <span>Files</span>
                    </div>
                    <div class="rv-tree-scroll">
                        <?php RepoViewController::renderTree($fullFileTree, $rawSlug, $currentBranch, $currentPath); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="<?= ! $isEmpty && ! empty($fullFileTree) ? 'col-lg-8' : 'col-lg-10' ?>">
            <?php if ($isEmpty): ?>
                <div class="border rounded-3 overflow-hidden" style="border-color:var(--bs-border-color)!important;">
                    <div class="p-4 text-center border-bottom" style="background:var(--bs-secondary-bg);">
                        <i class="bi bi-folder2-open" style="font-size:2.5rem;color:var(--brand);"></i>
                        <h4 class="fw-bold mt-3 mb-1">Get started with <?= $rName ?></h4>
                        <p class="text-secondary mb-0" style="font-size:.9rem;">
                            This is an empty repository. Use the instructions below to push your first commit.
                        </p>
                    </div>
                    <div class="p-4">
                        <p class="fw-semibold mb-2">Quick setup — copy a URL to get started</p>
                        <div class="d-flex align-items-center gap-0 mb-4">
                            <div class="btn-group me-2" role="group">
                                <input type="radio" class="btn-check" name="cloneProto" id="protoHTTP" checked>
                                <label class="btn btn-sm btn-outline-secondary" for="protoHTTP"
                                       onclick="setCloneUrl('http')">HTTPS</label>
                                <input type="radio" class="btn-check" name="cloneProto" id="protoSSH">
                                <label class="btn btn-sm btn-outline-secondary" for="protoSSH"
                                       onclick="setCloneUrl('ssh')">SSH</label>
                            </div>
                            <code id="cloneUrlDisplay" class="rv-clone-url flex-grow-1"><?= $httpUrl ?></code>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyCloneUrl(this)"
                                    title="Copy URL">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <p class="fw-semibold mb-2"><i class="bi bi-terminal me-1"></i>Create a new repository
                                    on the command line</p>
                                <div class="rv-empty-code">
                                    <code>echo "# <?= $rName ?>" >> README.md</code>
                                    <code>git init</code>
                                    <code>git add README.md</code>
                                    <code>git commit -m "first commit"</code>
                                    <code>git branch -M <?= $rBranch ?></code>
                                    <code>git remote add origin <span
                                                class="clone-url-inline text-warning"><?= $httpUrl ?></span></code>
                                    <code>git push -u origin <?= $rBranch ?></code>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <p class="fw-semibold mb-2"><i class="bi bi-arrow-up-circle me-1"></i>Push an existing
                                    repository</p>
                                <div class="rv-empty-code">
                                    <code>git remote add origin <span
                                                class="clone-url-inline text-warning"><?= $httpUrl ?></span></code>
                                    <code>git branch -M <?= $rBranch ?></code>
                                    <code>git push -u origin <?= $rBranch ?></code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1"
                                type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-git"></i> <?= $rBranch ?>
                        </button>
                        <ul class="dropdown-menu shadow-sm">
                            <li><h6 class="dropdown-header">Branches</h6></li>
                            <?php foreach ($branches as $b): /** @var string $b */ ?>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 <?= $b === $currentBranch ? 'fw-bold' : '' ?>"
                                       href="/<?= $rSlug ?>?branch=<?= RepoViewController::e($b) ?>">
                                        <?php if ($b === $currentBranch): ?>
                                            <i class="bi bi-check2 text-success"></i>
                                        <?php else: ?>
                                            <i class="bi bi-git text-secondary"></i>
                                        <?php endif; ?>
                                        <?= RepoViewController::e($b) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php if ($currentPath !== ''): ?>
                        <nav class="rv-breadcrumb d-flex align-items-center flex-wrap"
                             style="font-size:.875rem; gap:.1rem;">
                            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                                <?php if ($i > 0): ?><span class="sep">/</span><?php endif; ?>
                                <?php if ($crumb['url'] !== null): ?>
                                    <a href="<?= RepoViewController::e($crumb['url']) ?>"><?= RepoViewController::e($crumb['label']) ?></a>
                                <?php else: ?>
                                    <span class="fw-semibold"><?= RepoViewController::e($crumb['label']) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>
                    <span class="text-secondary small ms-auto">
                        <strong class="text-body"><?= number_format($commitCount) ?></strong>
                        commit<?= $commitCount !== 1 ? 's' : '' ?>
                    </span>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-success dropdown-toggle d-flex align-items-center gap-1"
                                type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-code-slash"></i> Code
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow p-3" style="min-width:320px;">
                            <p class="fw-semibold mb-2" style="font-size:.85rem;">Clone</p>
                            <ul class="nav nav-pills nav-fill mb-2" style="font-size:.78rem;">
                                <li class="nav-item">
                                    <a class="nav-link active py-1" href="#"
                                       onclick="switchCloneTab('http',this);return false;">HTTPS</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1" href="#"
                                       onclick="switchCloneTab('ssh',this);return false;">SSH</a>
                                </li>
                            </ul>
                            <div class="input-group input-group-sm">
                                <input type="text" id="cloneUrlInput" class="form-control font-monospace"
                                       style="font-size:.75rem;" value="<?= $httpUrl ?>" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyCloneInput()"
                                        title="Copy">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <p class="text-secondary mt-2 mb-0" style="font-size:.75rem;">
                                Use Git over HTTPS or SSH with your credentials.
                            </p>
                        </div>
                    </div>
                </div>
                <?php if ($viewMode === 'root' || $viewMode === 'tree'): ?>
                    <?php
                    $dispEntries = ($viewMode === 'root') ? $treeEntries : $subEntries;
                    $dispCommitMap = ($viewMode === 'root') ? $commitMap : $subCommitMap;
                    $dispCommit = ($viewMode === 'root') ? $latestCommit : $pathLatestCommit;
                    $parentUrl = null;
                    if ($viewMode === 'tree') {
                        $parentParts = explode('/', $currentPath);
                        array_pop($parentParts);
                        $parentPathStr = implode('/', $parentParts);
                        $parentUrl = $parentPathStr !== ''
                                ? RepoViewController::pathUrl($rawSlug, $currentBranch, $parentPathStr)
                                : '/' . $rawSlug . '?branch=' . urlencode($currentBranch);
                    }
                    ?>
                    <?php if ($dispCommit !== null): ?>
                        <div class="border rounded-top-3 rv-latest-commit px-3 py-2 d-flex align-items-center gap-2 flex-wrap">
                            <img src="https://www.gravatar.com/avatar/<?= md5(strtolower(trim($_SESSION['username']))) ?>?s=24&d=identicon"
                                 class="rounded-circle" width="20" height="20" alt="" loading="lazy">
                            <span class="fw-semibold"
                                  style="font-size:.85rem;"><?= RepoViewController::e($dispCommit['author']) ?></span>
                            <span class="text-secondary"
                                  style="font-size:.85rem; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= RepoViewController::e($dispCommit['subject']) ?>
                            </span>
                            <span class="text-secondary" style="font-size:.8rem; white-space:nowrap;">
                                <a href="#" class="text-secondary text-decoration-none font-monospace"
                                   title="<?= RepoViewController::e($dispCommit['hash']) ?>"><?= RepoViewController::e($dispCommit['short']) ?></a>
                                &middot; <?= RepoViewController::time($dispCommit['time'], $dispCommit['rel']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="border <?= $dispCommit !== null ? 'border-top-0 rounded-bottom-3' : 'rounded-3' ?> overflow-hidden">
                        <table class="table table-hover table-sm rv-file-table mb-0">
                            <tbody>
                            <?php if ($viewMode === 'tree' && $parentUrl !== null): // @phpstan-ignore notIdentical.alwaysTrue?>
                                <tr class="rv-parent-row">
                                    <td style="width:2rem;"><i class="bi bi-folder-fill text-secondary opacity-50"></i>
                                    </td>
                                    <td colspan="3"><a class="rv-file-link"
                                                       href="<?= RepoViewController::e($parentUrl) ?>">..</a></td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($dispEntries as $entry): ?>
                                <?php
                                $isDir = $entry['type'] === 'tree';
                                $eName = RepoViewController::e($entry['name']);
                                $icon = $isDir ? '<i class="bi bi-folder-fill text-warning"></i>' : RepoViewController::fileIcon($entry['name']);
                                $cmt = $dispCommitMap[$entry['name']] ?? null;
                                $ePath = ($currentPath !== '' ? $currentPath . '/' : '') . $entry['name'];
                                $eUrl = RepoViewController::pathUrl($rawSlug, $currentBranch, $ePath);
                                ?>
                                <tr>
                                    <td style="width:2rem;"><?= $icon ?></td>
                                    <td><a class="rv-file-link"
                                           href="<?= RepoViewController::e($eUrl) ?>"><?= $eName ?></a></td>
                                    <td class="rv-commit-subject d-none d-md-table-cell">
                                        <?= $cmt !== null ? RepoViewController::e($cmt['subject']) : '' ?>
                                    </td>
                                    <td class="text-secondary text-end text-nowrap" style="font-size:.78rem;">
                                        <?= $cmt !== null ? RepoViewController::time($cmt['time'], $cmt['rel']) : '' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($dispEntries)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-4">Empty directory.</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($viewMode === 'root' && $readmeContent !== null): ?>
                        <div class="border rounded-3 mt-3">
                            <div class="border-bottom px-3 py-2 d-flex align-items-center gap-2 bg-body-secondary rounded-top-3">
                                <i class="bi bi-book text-secondary"></i>
                                <span class="fw-semibold" style="font-size:.875rem;">README.md</span>
                            </div>
                            <div class="rv-readme px-4 py-3">
                                <?= RepoViewController::renderMarkdown($readmeContent) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php elseif ($viewMode === 'blob' && $fileData !== null): ?>
                    <?php
                    $fileName = basename($currentPath);
                    $fileSize = $fileData['size'];
                    $isBinary = $fileData['binary'];
                    $isTrunc = $fileData['truncated'];
                    $fileLines = $fileData['lines'];
                    $fileContent = $fileData['content'];
                    $hlClass = 'language-' . RepoViewController::hlLang($fileName);
                    ?>
                    <?php if ($pathLatestCommit !== null): ?>
                        <div class="border rounded-top-3 rv-latest-commit px-3 py-2 d-flex align-items-center gap-2 flex-wrap">
                            <img src="https://www.gravatar.com/avatar/<?= md5(strtolower(trim($pathLatestCommit['author']))) ?>?s=24&d=identicon"
                                 class="rounded-circle" width="20" height="20" alt="" loading="lazy">
                            <span class="fw-semibold"
                                  style="font-size:.85rem;"><?= RepoViewController::e($pathLatestCommit['author']) ?></span>
                            <span class="text-secondary"
                                  style="font-size:.85rem; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= RepoViewController::e($pathLatestCommit['subject']) ?>
                            </span>
                            <span class="text-secondary" style="font-size:.8rem; white-space:nowrap;">
                                <span class="font-monospace"
                                      title="<?= RepoViewController::e($pathLatestCommit['hash']) ?>"><?= RepoViewController::e($pathLatestCommit['short']) ?></span>
                                &middot; <?= RepoViewController::time($pathLatestCommit['time'], $pathLatestCommit['rel']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="border <?= $pathLatestCommit !== null ? 'border-top-0 rounded-bottom-3' : 'rounded-3' ?> overflow-hidden rv-file-viewer">
                        <div class="d-flex align-items-center gap-3 flex-wrap px-3 py-2"
                             style="background:var(--bs-secondary-bg); border-bottom:1px solid var(--bs-border-color); font-size:.82rem;">
                            <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                                <?= RepoViewController::fileIcon($fileName) ?>
                                <span class="fw-semibold"><?= RepoViewController::e($fileName) ?></span>
                                <?php if (! $isBinary && $fileContent !== null): ?>
                                    <span class="text-secondary"><?= number_format($fileLines) ?> line<?= $fileLines !== 1 ? 's' : '' ?></span>
                                    <span class="text-secondary">·</span>
                                <?php endif; ?>
                                <span class="text-secondary"><?= RepoViewController::size($fileSize) ?></span>
                                <?php if ($isTrunc): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"
                                          style="font-size:.68rem;">truncated</span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0">
                                <?php if (! $isBinary && $fileContent !== null): ?>
                                    <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                            onclick="rvCopyFile(this)" title="Copy file content">
                                        <i class="bi bi-clipboard" id="rv-copy-icon"></i>
                                        <span class="d-none d-sm-inline">Copy</span>
                                    </button>
                                <?php endif; ?>
                                <a href="<?= RepoViewController::e('/' . $rawSlug . '?' . http_build_query(['branch' => $currentBranch, 'path' => $currentPath, 'raw' => '1'])) ?>"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span class="d-none d-sm-inline">Raw</span>
                                </a>
                            </div>
                        </div>
                        <?php if ($isBinary): ?>
                            <div class="text-center py-5 text-secondary">
                                <i class="bi bi-file-binary" style="font-size:2rem;"></i>
                                <p class="mt-2 mb-0" style="font-size:.9rem;">Binary file
                                    · <?= RepoViewController::size($fileSize) ?></p>
                            </div>
                        <?php elseif ($fileContent === null): ?>
                            <div class="text-center py-5 text-secondary">
                                <i class="bi bi-exclamation-triangle" style="font-size:2rem;"></i>
                                <p class="mt-2 mb-0" style="font-size:.9rem;">Could not read file content.</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x:auto;">
                                <table class="rv-line-table" id="rv-code-table">
                                    <tbody>
                                    <?php
                                    $codeLines = explode("\n", $fileContent);
                            if (end($codeLines) === '') {
                                array_pop($codeLines);
                            }
                            foreach ($codeLines as $ln => $codeLine): ?>
                                        <tr id="L<?= $ln + 1 ?>" class="rv-line">
                                            <td class="rv-line-num"
                                                onclick="rvToggleLine(<?= $ln + 1 ?>)"><?= $ln + 1 ?></td>
                                            <td class="rv-line-code"><?= htmlspecialchars($codeLine, ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <pre style="display:none;"><code id="rv-hl-src"
                                                             class="<?= RepoViewController::e($hlClass) ?>"><?= htmlspecialchars($fileContent, ENT_QUOTES, 'UTF-8') ?></code></pre>
                            <?php if ($isTrunc): ?>
                                <div class="px-3 py-2 text-secondary text-center"
                                     style="font-size:.8rem; background:var(--bs-secondary-bg); border-top:1px solid var(--bs-border-color);">
                                    <i class="bi bi-scissors me-1"></i>Truncated at 512 KB.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="col-lg-2">
            <div class="rv-sidebar-section">
                <h6 class="fw-bold mb-3">About</h6>
                <?php if ($rDesc !== ''): ?>
                    <p class="text-secondary mb-3" style="font-size:.875rem;"><?= $rDesc ?></p>
                <?php else: ?>
                    <p class="text-secondary mb-3" style="font-size:.875rem; font-style:italic;">No description.</p>
                <?php endif; ?>
                <div class="d-flex flex-column gap-2 text-secondary" style="font-size:.85rem;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-star"></i>
                        <strong class="text-body"><?= number_format($rStars) ?></strong> stars
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-diagram-2"></i>
                        <strong class="text-body"><?= number_format($rForks) ?></strong> forks
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock"></i>
                        Updated <?= $rUpdated ?>
                    </div>
                </div>
            </div>
            <?php if (! empty($langBreakdown)): ?>
                <div class="rv-sidebar-section">
                    <h6 class="fw-bold mb-3">Languages</h6>
                    <div class="rv-lang-bar mb-3">
                        <?php foreach ($langBreakdown as $lang): ?>
                            <div class="rv-lang-bar-seg"
                                 title="<?= RepoViewController::e($lang['lang']) ?> <?= $lang['pct'] ?>%"
                                 style="width:<?= $lang['pct'] ?>%;background:<?= RepoViewController::e($lang['color']) ?>;"></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <?php foreach ($langBreakdown as $lang): ?>
                            <div class="d-flex align-items-center justify-content-between gap-2"
                                 style="font-size:.825rem;">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="width:.7rem;height:.7rem;border-radius:50%;background:<?= RepoViewController::e($lang['color']) ?>;display:inline-block;flex-shrink:0;"></span>
                                    <span class="fw-medium"><?= RepoViewController::e($lang['lang']) ?></span>
                                </div>
                                <span class="text-secondary"><?= $lang['pct'] ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="rv-sidebar-section">
                <h6 class="fw-bold mb-3">Clone</h6>
                <div class="d-flex flex-column gap-2">
                    <div>
                        <div class="text-secondary mb-1"
                             style="font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">
                            HTTPS
                        </div>
                        <div class="d-flex gap-1">
                            <code class="rv-clone-url flex-grow-1 d-block"
                                  style="font-size:.73rem;"><?= $httpUrl ?></code>
                            <button class="btn btn-sm btn-outline-secondary"
                                    onclick="navigator.clipboard.writeText('<?= $httpUrl ?>')" title="Copy HTTPS URL">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <div class="text-secondary mb-1"
                             style="font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">
                            SSH
                        </div>
                        <div class="d-flex gap-1">
                            <code class="rv-clone-url flex-grow-1 d-block"
                                  style="font-size:.73rem;"><?= $sshUrl ?></code>
                            <button class="btn btn-sm btn-outline-secondary"
                                    onclick="navigator.clipboard.writeText('<?= $sshUrl ?>')" title="Copy SSH URL">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="rv-sidebar-section">
                <div class="d-flex flex-column gap-2 text-secondary" style="font-size:.82rem;">
                    <div>
                        <i class="bi bi-person me-1"></i>
                        <a href="#" class="text-decoration-none text-secondary"><?= $rDisp ?></a>
                        <span class="text-secondary">(@<?= $rOwner ?>)</span>
                    </div>
                    <div><i class="bi bi-calendar me-1"></i> Created <?= $rCreated ?></div>
                    <div><i class="bi bi-git me-1"></i> Default branch: <code><?= $rBranch ?></code></div>
                    <?php if (! $isEmpty): ?>
                        <div><i class="bi bi-clock-history me-1"></i> <?= number_format($commitCount) ?>
                            commit<?= $commitCount !== 1 ? 's' : '' ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>
<script>
    const HTTP_URL = '<?= $httpUrl ?>';
    const SSH_URL = '<?= $sshUrl ?>';

    function setCloneUrl(proto) {
        const url = (proto === "ssh" ? SSH_URL : HTTP_URL);
        const el = document.getElementById("cloneUrlDisplay");
        if (el) {
            el.textContent = url;
        }
        document.querySelectorAll(".clone-url-inline")
            .forEach((e) => e.textContent = url);
    }

    function copyCloneUrl(btn) {
        const el = document.getElementById("cloneUrlDisplay");
        if (!el) {
            return;
        }
        navigator.clipboard.writeText(el.textContent.trim()).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = "<i class='bi bi-check2 text-success'></i>";
            setTimeout(() => btn.innerHTML = orig, 1500);
        });
    }

    function switchCloneTab(proto, tabEl) {
        const url = proto === "ssh" ? SSH_URL : HTTP_URL;
        const inp = document.getElementById("cloneUrlInput");
        if (inp) {
            inp.value = url;
        }
        tabEl.closest(".nav")
            .querySelectorAll(".nav-link")
            .forEach((l) => l.classList.remove("active"));
        tabEl.classList.add("active");
    }

    function copyCloneInput() {
        const inp = document.getElementById("cloneUrlInput");
        if (!inp) {
            return;
        }
        navigator.clipboard.writeText(inp.value);
    }

    function rvCopyFile(btn) {
        const src = document.getElementById("rv-hl-src");
        if (!src) {
            return;
        }
        navigator.clipboard.writeText(src.textContent).then(() => {
            const icon = document.getElementById("rv-copy-icon");
            if (icon) {
                icon.className = "bi bi-check2 text-success";
                setTimeout(() => {
                    icon.className = "bi bi-clipboard";
                }, 1800);
            }
        });
    }

    function rvToggleLine(n) {
        const row = document.getElementById("L" + n);
        if (!row) {
            return;
        }
        row.classList.toggle("rv-line-highlighted");
    }

    function rvTreeToggle(el, e) {
        const li = el.parentElement;
        if (!li) {
            return;
        }
        const children = li.querySelector(".rv-tree-children");
        if (!children) {
            return;
        }
        e.preventDefault();
        const isOpen = children.classList.toggle("rv-open");
        const caret = el.querySelector(".rv-tree-toggle i");
        if (caret) {
            caret.className = (
                isOpen ? "bi bi-caret-down-fill" : "bi bi-caret-right-fill"
            );
        }
        window.location.href = el.href;
    }

    document.addEventListener("DOMContentLoaded", function () {
        const src = document.getElementById("rv-hl-src");
        const table = document.getElementById("rv-code-table");
        if (!src || !table || typeof hljs === "undefined") {
            return;
        }

        hljs.highlightElement(src);

        const highlightedLines = src.innerHTML.split("\n");
        const codeCells = table.querySelectorAll(".rv-line-code");
        codeCells.forEach(function (cell, i) {
            cell.innerHTML = (
                highlightedLines[i] !== undefined ? highlightedLines[i] : ""
            );
        });
    });

    (function () {
        const el = document.getElementById("hljs-css");
        if (!el) {
            return;
        }
        const t = document.documentElement.getAttribute("data-bs-theme");
        if (t === "light") {
            el.href =
                "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/" +
                "styles/github.min.css";
        }
    })();


</script>
<?php if ($viewMode === 'blob' && $fileData !== null && ! $fileData['binary'] && $fileData['content'] !== null): ?>
    <link rel="stylesheet" id="hljs-css"
          href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github-dark-dimmed.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>
<?php endif; ?>
