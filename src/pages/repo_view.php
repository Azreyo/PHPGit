<?php
declare(strict_types=1);

use App\Config;
use App\Services\GitReaderService;
use App\Services\RepositoryService;
use App\includes\Logging;

/** @var bool $is_logged_in */
/** @var string $role */

$config = Config::getInstance();
$pdo = $config->getPdo();

$rawSlug = trim($_GET['slug'] ?? '');
if (strlen($rawSlug) > 200) {
    http_response_code(414);
    include __DIR__ . '/414.php';
    return;
}
if ($rawSlug === '' || !preg_match('#^[a-zA-Z0-9][a-zA-Z0-9_-]{0,49}/[a-zA-Z0-9][a-zA-Z0-9._-]{0,98}$#', $rawSlug)) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

$repo = null;
if ($pdo !== null) {
    try {
        $service = new RepositoryService($pdo, $config->getDataRoot());
        $repo = $service->getBySlug($rawSlug);
    } catch (PDOException $e) {
        Logging::loggingToFile('repo_view SQL error: ' . $e->getMessage(), 4);
    }
}
if ($repo === null) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$isOwner = $is_logged_in && $sessionUserId === (int)$repo['owner_user_id'];
$isAdmin = $is_logged_in && $role === 'ADMIN';

if ($repo['visibility'] === 'private' && !$isOwner && !$isAdmin) {
    http_response_code(403);
    include __DIR__ . '/403.php';
    return;
}

$dataRoot = $config->getDataRoot();
$repoPath = $dataRoot . '/' . $repo['owner_username'] . '/' . $repo['repo_name'];
$git = new GitReaderService($repoPath);
$isEmpty = $git->isEmpty();

$currentBranch = preg_replace('/[^a-zA-Z0-9._\/-]/', '', $_GET['branch'] ?? $repo['default_branch']);
if ($currentBranch === '') {
    $currentBranch = $repo['default_branch'];
}

$branches = [];
$treeEntries = [];
$commitMap = [];
$latestCommit = null;
$commitCount = 0;
$langBreakdown = [];
$readmeContent = null;

if (!$isEmpty) {
    $branches = $git->getBranches((string)$repo['default_branch']);
    $treeEntries = $git->getTopLevelTree($currentBranch);
    $commitMap = $git->getLastCommitPerEntry($currentBranch);
    $latestCommit = $git->getLatestCommit($currentBranch);
    $commitCount = $git->getCommitCount($currentBranch);
    $langBreakdown = $git->getLanguageBreakdown($currentBranch);
    $readmeContent = $git->getReadme($currentBranch);
    $primaryLang = $langBreakdown[0]['lang'] ?? null;
    if ($pdo !== null && $primaryLang !== null && $primaryLang !== ($repo['lang'] ?? null)) {
        try {
            $pdo->prepare('UPDATE repositories SET lang = ? WHERE id = ?')
                ->execute([$primaryLang, $repo['id']]);
        } catch (PDOException) {
        }
    }
}

$rName = htmlspecialchars($repo['repo_name'], ENT_QUOTES, 'UTF-8');
$rDesc = htmlspecialchars($repo['repo_description'] ?? '', ENT_QUOTES, 'UTF-8');
$rVis = $repo['visibility'];
$rBranch = htmlspecialchars($currentBranch, ENT_QUOTES, 'UTF-8');
$rOwner = htmlspecialchars($repo['owner_username'], ENT_QUOTES, 'UTF-8');
$rDisp = htmlspecialchars($repo['owner_display_name'] ?? $repo['owner_username'], ENT_QUOTES, 'UTF-8');
$rSlug = htmlspecialchars($rawSlug, ENT_QUOTES, 'UTF-8');
$rCreated = date('d M Y', strtotime($repo['created_at']));
$rUpdated = date('d M Y', strtotime($repo['updated_at']));
$rStars = (int)$repo['stars'];
$rForks = (int)$repo['forks'];

$sshHost = htmlspecialchars($_ENV['SSH_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'phpgit.local', ENT_QUOTES, 'UTF-8');
$gitUser = htmlspecialchars($_ENV['GIT_SYSTEM_USER'] ?? 'git', ENT_QUOTES, 'UTF-8');
$httpBase = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'phpgit.local');
$httpUrl = htmlspecialchars("{$httpBase}/{$rawSlug}.git", ENT_QUOTES, 'UTF-8');
$sshUrl = htmlspecialchars("{$gitUser}@{$sshHost}:{$rawSlug}.git", ENT_QUOTES, 'UTF-8');

/** Render a relative-time string as a <time> element */
function rv_time(string $iso, string $rel): string
{
    $safe = htmlspecialchars($iso, ENT_QUOTES, 'UTF-8');
    $safeRel = htmlspecialchars($rel, ENT_QUOTES, 'UTF-8');
    return "<time datetime=\"{$safe}\" title=\"{$safe}\">{$safeRel}</time>";
}

/** Escape for HTML output */
function rv_e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>

<style>
    .rv-header-tabs .nav-link {
        font-size: .875rem;
        padding: .4rem .75rem;
        border-radius: .375rem .375rem 0 0;
        color: var(--bs-secondary-color);
        border: 1px solid transparent;
        border-bottom: none;
    }

    .rv-header-tabs .nav-link.active {
        color: var(--bs-body-color);
        background: var(--bs-body-bg);
        border-color: var(--bs-border-color);
        font-weight: 600;
    }

    .rv-header-tabs {
        border-bottom: 1px solid var(--bs-border-color);
    }

    .rv-file-table td, .rv-file-table th {
        padding: .45rem .75rem;
        font-size: .875rem;
        vertical-align: middle;
        border-color: var(--bs-border-color);
    }

    .rv-file-table .rv-file-link {
        color: var(--bs-body-color);
        text-decoration: none;
        font-weight: 500;
    }

    .rv-file-table .rv-file-link:hover {
        color: var(--brand);
        text-decoration: underline;
    }

    .rv-commit-subject {
        color: var(--bs-secondary-color);
        font-size: .8rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 260px;
    }

    .rv-latest-commit {
        background: var(--bs-secondary-bg);
        border-radius: 0;
        border-top: none;
    }

    .rv-lang-bar {
        height: 8px;
        border-radius: 6px;
        overflow: hidden;
        display: flex;
    }

    .rv-lang-bar-seg {
        height: 100%;
        transition: width .4s ease;
    }

    .rv-clone-url {
        font-family: monospace;
        font-size: .8rem;
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .375rem;
        padding: .3rem .6rem;
        word-break: break-all;
    }

    .rv-empty-code {
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .5rem;
        padding: 1rem 1.25rem;
        font-size: .8rem;
        line-height: 1.7;
    }

    .rv-empty-code code {
        display: block;
    }

    .rv-readme {
        font-size: .9rem;
        line-height: 1.75;
    }

    .rv-readme h1, .rv-readme h2 {
        border-bottom: 1px solid var(--bs-border-color);
        padding-bottom: .3rem;
        margin-top: 1.25rem;
    }

    .rv-readme pre {
        background: var(--bs-tertiary-bg);
        border-radius: .4rem;
        padding: .75rem 1rem;
        overflow-x: auto;
    }

    .rv-readme code {
        font-size: .82em;
    }

    .rv-readme blockquote {
        border-left: 4px solid var(--bs-border-color);
        padding-left: 1rem;
        color: var(--bs-secondary-color);
    }

    .rv-sidebar-section {
        font-size: .875rem;
    }

    .rv-sidebar-section + .rv-sidebar-section {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--bs-border-color);
    }
</style>

<main class="container-xl py-4">

    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
        <i class="bi bi-folder2 text-secondary" style="font-size:1.1rem;"></i>
        <h1 class="mb-0 fs-5 fw-normal">
            <a href="/<?= $rSlug ?>" class="text-decoration-none"><?= $rOwner ?></a>
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
            <a class="nav-link active d-flex align-items-center gap-1" href="/<?= $rSlug ?>">
                <i class="bi bi-code-square"></i> Code
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-1 text-secondary" href="#">
                <i class="bi bi-exclamation-circle"></i> Issues <span
                        class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.7rem;">0</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-1 text-secondary" href="#">
                <i class="bi bi-git"></i> Pull Requests <span class="badge bg-secondary-subtle text-secondary ms-1"
                                                              style="font-size:.7rem;">0</span>
            </a>
        </li>
        <?php if ($isOwner || $isAdmin): ?>
            <li class="nav-item ms-auto">
                <a class="nav-link d-flex align-items-center gap-1 text-secondary" href="#">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="row g-4 mt-0 pt-3 border-top" style="border-color:var(--bs-border-color)!important;">

        <div class="col-lg-9">

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
                            <!-- New repo instructions -->
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
                            <!-- Existing repo instructions -->
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
                <!-- ══ FILE BROWSER ══════════════════════════════════════════ -->

                <!-- Toolbar: branch selector + stats + clone button -->
                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <!-- Branch dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1"
                                type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-git"></i> <?= $rBranch ?>
                        </button>
                        <ul class="dropdown-menu shadow-sm">
                            <li><h6 class="dropdown-header">Branches</h6></li>
                            <?php foreach ($branches as $b): ?>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 <?= $b === $currentBranch ? 'fw-bold' : '' ?>"
                                       href="/<?= $rSlug ?>?branch=<?= rv_e($b) ?>">
                                        <?php if ($b === $currentBranch): ?><i
                                                class="bi bi-check2 text-success"></i><?php else: ?><i
                                                class="bi bi-git text-secondary"></i><?php endif; ?>
                                        <?= rv_e($b) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <span class="text-secondary small">
                    <strong class="text-body"><?= number_format($commitCount) ?></strong> commit<?= $commitCount !== 1 ? 's' : '' ?>
                </span>

                    <div class="ms-auto d-flex gap-2">
                        <!-- Clone dropdown -->
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
                                           style="font-size:.75rem;"
                                           value="<?= $httpUrl ?>" readonly>
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
                </div>

                <!-- Latest commit bar -->
                <?php if ($latestCommit !== null): ?>
                    <div class="border rounded-top-3 rv-latest-commit px-3 py-2 d-flex align-items-center gap-2 flex-wrap">
                        <img src="https://www.gravatar.com/avatar/<?= md5(strtolower(trim($latestCommit['author']))) ?>?s=24&d=identicon"
                             class="rounded-circle" width="20" height="20" alt="" loading="lazy">
                        <span class="fw-semibold" style="font-size:.85rem;"><?= rv_e($latestCommit['author']) ?></span>
                        <span class="text-secondary"
                              style="font-size:.85rem; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    <?= rv_e($latestCommit['subject']) ?>
                </span>
                        <span class="text-secondary" style="font-size:.8rem; white-space:nowrap;">
                    <a href="#" class="text-secondary text-decoration-none font-monospace"
                       title="<?= rv_e($latestCommit['hash']) ?>"><?= rv_e($latestCommit['short']) ?></a>
                    &middot; <?= rv_time($latestCommit['time'], $latestCommit['rel']) ?>
                </span>
                    </div>
                <?php endif; ?>

                <!-- File table -->
                <div class="border <?= $latestCommit !== null ? 'border-top-0 rounded-bottom-3' : 'rounded-3' ?> overflow-hidden">
                    <table class="table table-hover table-sm rv-file-table mb-0">
                        <tbody>
                        <?php foreach ($treeEntries as $entry): ?>
                            <?php
                            $isDir = $entry['type'] === 'tree';
                            $eName = rv_e($entry['name']);
                            $icon = $isDir ? '<i class="bi bi-folder-fill text-warning"></i>' : fileIcon($entry['name']);
                            $cmt = $commitMap[$entry['name']] ?? null;
                            ?>
                            <tr>
                                <td style="width:2rem;"><?= $icon ?></td>
                                <td>
                                    <a class="rv-file-link"
                                       href="/<?= $rSlug ?>?branch=<?= $rBranch ?>&path=<?= urlencode($entry['name']) ?>"><?= $eName ?></a>
                                </td>
                                <td class="rv-commit-subject d-none d-md-table-cell">
                                    <?= $cmt !== null ? rv_e($cmt['subject']) : '' ?>
                                </td>
                                <td class="text-secondary text-end text-nowrap" style="font-size:.78rem;">
                                    <?= $cmt !== null ? rv_time($cmt['time'], $cmt['rel']) : '' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($treeEntries)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-4">Empty tree on this branch.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- README -->
                <?php if ($readmeContent !== null): ?>
                    <div class="border rounded-3 mt-3">
                        <div class="border-bottom px-3 py-2 d-flex align-items-center gap-2 bg-body-secondary rounded-top-3">
                            <i class="bi bi-book text-secondary"></i>
                            <span class="fw-semibold" style="font-size:.875rem;">README.md</span>
                </div>
                        <div class="rv-readme px-4 py-3" id="readme-body">
                            <?= renderMarkdown($readmeContent) ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; /* !isEmpty */ ?>
        </div><!-- /col-lg-9 -->

        <!-- Right sidebar -->
        <div class="col-lg-3">

            <!-- About -->
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

            <!-- Language bar -->
            <?php if (!empty($langBreakdown)): ?>
                <div class="rv-sidebar-section">
                    <h6 class="fw-bold mb-3">Languages</h6>
                    <!-- Bar -->
                    <div class="rv-lang-bar mb-3">
                        <?php foreach ($langBreakdown as $lang): ?>
                            <div class="rv-lang-bar-seg" title="<?= rv_e($lang['lang']) ?> <?= $lang['pct'] ?>%"
                                 style="width:<?= $lang['pct'] ?>%;background:<?= rv_e($lang['color']) ?>;"></div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Legend -->
                    <div class="d-flex flex-column gap-1">
                        <?php foreach ($langBreakdown as $lang): ?>
                            <div class="d-flex align-items-center justify-content-between gap-2"
                                 style="font-size:.825rem;">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="width:.7rem;height:.7rem;border-radius:50%;background:<?= rv_e($lang['color']) ?>;display:inline-block;flex-shrink:0;"></span>
                                    <span class="fw-medium"><?= rv_e($lang['lang']) ?></span>
                                </div>
                                <span class="text-secondary"><?= $lang['pct'] ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Clone URLs -->
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
                                    onclick="navigator.clipboard.writeText('<?= $httpUrl ?>')" title="Copy HTTPS URL"><i
                                        class="bi bi-clipboard"></i></button>
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
                                    onclick="navigator.clipboard.writeText('<?= $sshUrl ?>')" title="Copy SSH URL"><i
                                        class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Repo meta -->
            <div class="rv-sidebar-section">
                <div class="d-flex flex-column gap-2 text-secondary" style="font-size:.82rem;">
                    <div><i class="bi bi-person me-1"></i>
                        <a href="#" class="text-decoration-none text-secondary"><?= $rDisp ?></a>
                        <span class="text-secondary">(@<?= $rOwner ?>)</span>
                    </div>
                    <div><i class="bi bi-calendar me-1"></i> Created <?= $rCreated ?></div>
                    <div><i class="bi bi-git me-1"></i> Default branch: <code><?= $rBranch ?></code></div>
                    <?php if (!$isEmpty): ?>
                        <div><i class="bi bi-clock-history me-1"></i> <?= number_format($commitCount) ?>
                            commit<?= $commitCount !== 1 ? 's' : '' ?></div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /col-lg-3 -->
    </div><!-- /row -->
</main>

<?php
/**
 * Return a Bootstrap-Icon HTML for a given filename.
 */
function fileIcon(string $name): string
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $icon = match (true) {
        $ext === 'php' => 'bi-filetype-php text-primary',
        in_array($ext, ['js', 'jsx', 'mjs'], true) => 'bi-filetype-js text-warning',
        in_array($ext, ['ts', 'tsx'], true) => 'bi-filetype-tsx text-primary',
        in_array($ext, ['css', 'scss', 'less'], true) => 'bi-filetype-css text-info',
        in_array($ext, ['html', 'htm'], true) => 'bi-filetype-html text-danger',
        $ext === 'json' => 'bi-filetype-json text-secondary',
        in_array($ext, ['md', 'mdx'], true) => 'bi-filetype-md text-secondary',
        $ext === 'py' => 'bi-filetype-py text-primary',
        in_array($ext, ['sh', 'bash', 'zsh'], true) => 'bi-terminal text-success',
        in_array($ext, ['yml', 'yaml'], true) => 'bi-file-earmark-code text-warning',
        $ext === 'xml' => 'bi-filetype-xml text-secondary',
        $ext === 'sql' => 'bi-database text-info',
        $ext === 'txt' => 'bi-file-text text-secondary',
        in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'], true) => 'bi-file-earmark-image text-success',
        $ext === 'pdf' => 'bi-file-earmark-pdf text-danger',
        in_array($ext, ['zip', 'tar', 'gz'], true) => 'bi-file-zip text-secondary',
        default => 'bi-file-earmark text-secondary',
    };
    return "<i class=\"bi {$icon}\"></i>";
}

/**
 * Minimal Markdown → HTML renderer (headings, bold, code, links, lists, hr).
 * Intentionally simple – no external dependency.
 */
function renderMarkdown(string $md): string
{
    $lines = explode("\n", $md);
    $html = '';
    $inPre = false;
    $inUl = false;
    $inOl = false;
    $preBuf = '';

    $closeList = function () use (&$inUl, &$inOl, &$html): void {
        if ($inUl) {
            $html .= "</ul>\n";
            $inUl = false;
        }  // @phpstan-ignore if.alwaysFalse
        if ($inOl) {
            $html .= "</ol>\n";
            $inOl = false;
        }  // @phpstan-ignore if.alwaysFalse
    };

    foreach ($lines as $line) {
        // Fenced code block
        if (preg_match('/^```/', $line)) {
            if (!$inPre) {
                $closeList();
                $lang = trim(substr($line, 3));
                $inPre = true;
                $preBuf = '';
                $html .= '<pre><code' . ($lang ? " class=\"language-{$lang}\"" : '') . '>';
            } else {
                $html .= htmlspecialchars($preBuf, ENT_QUOTES, 'UTF-8') . '</code></pre>' . "\n";
                $inPre = false;
                $preBuf = '';
            }
            continue;
        }
        if ($inPre) {
            $preBuf .= $line . "\n";
            continue;
        }

        // Headings
        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
            $closeList();
            $lvl = strlen($m[1]);
            $text = inlineMarkdown($m[2]);
            $id = preg_replace('/[^a-z0-9]+/', '-', strtolower(strip_tags($text)));
            $html .= "<h{$lvl} id=\"{$id}\">{$text}</h{$lvl}>\n";
            continue;
        }

        // HR
        if (preg_match('/^[-*_]{3,}$/', trim($line))) {
            $closeList();
            $html .= "<hr>\n";
            continue;
        }

        // Blockquote
        if (str_starts_with($line, '> ')) {
            $closeList();
            $html .= '<blockquote><p>' . inlineMarkdown(substr($line, 2)) . "</p></blockquote>\n";
            continue;
        }

        // Unordered list
        if (preg_match('/^[-*+]\s+(.+)$/', $line, $m)) {
            if ($inOl) {
                $html .= "</ol>\n";
                $inOl = false;
            }
            if (!$inUl) {
                $html .= "<ul>\n";
                $inUl = true;
            }
            $html .= '<li>' . inlineMarkdown($m[1]) . "</li>\n";
            continue;
        }

        // Ordered list
        if (preg_match('/^\d+\.\s+(.+)$/', $line, $m)) {
            if ($inUl) {
                $html .= "</ul>\n";
                $inUl = false;
            }
            if (!$inOl) {
                $html .= "<ol>\n";
                $inOl = true;
            }
            $html .= '<li>' . inlineMarkdown($m[1]) . "</li>\n";
            continue;
        }

        $closeList();

        // Empty line
        if (trim($line) === '') {
            $html .= "\n";
            continue;
        }

        // Normal paragraph line
        $html .= '<p>' . inlineMarkdown($line) . "</p>\n";
    }

    if ($inPre) {
        $html .= htmlspecialchars($preBuf, ENT_QUOTES, 'UTF-8') . '</code></pre>';
    }
    if ($inUl) {
        $html .= '</ul>';
    }
    if ($inOl) {
        $html .= '</ol>';
    }

    return $html;
}

function inlineMarkdown(string $text): string
{
    // Escape HTML first
    $s = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    // Inline code
    $s = preg_replace('/`([^`]+)`/', '<code>$1</code>', $s) ?? $s;
    // Bold + italic
    $s = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $s) ?? $s;
    $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s) ?? $s;
    $s = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $s) ?? $s;
    $s = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $s) ?? $s;
    $s = preg_replace('/_(.+?)_/', '<em>$1</em>', $s) ?? $s;
    // Links [text](url)
    $s = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" rel="noopener noreferrer">$1</a>', $s) ?? $s;
    // Strikethrough
    $s = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $s) ?? $s;
    return $s;
}
?>

<script>
    const HTTP_URL = '<?= $httpUrl ?>';
    const SSH_URL = '<?= $sshUrl ?>';

    function setCloneUrl(proto) {
        const url = proto === 'ssh' ? SSH_URL : HTTP_URL;
        const el = document.getElementById('cloneUrlDisplay');
        if (el) el.textContent = url;
        document.querySelectorAll('.clone-url-inline').forEach(e => e.textContent = url);
    }

    function copyCloneUrl(btn) {
        const el = document.getElementById('cloneUrlDisplay');
        if (!el) return;
        navigator.clipboard.writeText(el.textContent.trim()).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
            setTimeout(() => btn.innerHTML = orig, 1500);
        });
    }

    function switchCloneTab(proto, tabEl) {
        const url = proto === 'ssh' ? SSH_URL : HTTP_URL;
        const inp = document.getElementById('cloneUrlInput');
        if (inp) inp.value = url;
        tabEl.closest('.nav').querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        tabEl.classList.add('active');
    }

    function copyCloneInput() {
        const inp = document.getElementById('cloneUrlInput');
        if (!inp) return;
        navigator.clipboard.writeText(inp.value);
    }
</script>
