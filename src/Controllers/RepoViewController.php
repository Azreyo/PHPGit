<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\includes\Logging;
use App\includes\Security;
use App\Services\GitReaderService;
use App\Services\RepositoryService;
use Random\RandomException;

final class RepoViewController
{
    /** @var array<string, mixed>|null */
    public ?array $repo = null;

    public string $rawSlug = '';
    public string $currentBranch = '';
    public string $currentPath = '';
    public string $viewMode = 'root';
    public bool $isEmpty = false;

    /** @var list<string> */
    public array $branches = [];
    /** @var array<int, array<string, string>> */
    public array $treeEntries = [];
    /** @var array<string, array<string, string>> */
    public array $commitMap = [];
    /** @var array<string, string>|null */
    public ?array $latestCommit = null;
    public int $commitCount = 0;
    /** @var list<array{lang: string, bytes: int, pct: float, color: string}> */
    public array $langBreakdown = [];
    public ?string $readmeContent = null;

    /** @var array<int, array<string, string>> */
    public array $subEntries = [];
    /** @var array<string, array<string, string>> */
    public array $subCommitMap = [];
    /** @var array<string, string>|null */
    public ?array $pathLatestCommit = null;

    /** @var array<string, mixed>|null */
    public ?array $fileData = null;

    /** @var list<array{name:string,type:string,children:array<mixed>}> */
    public array $fullFileTree = [];

    /** @var array<int, array{label:string,url:string|null}> */
    public array $breadcrumbs = [];

    public string $rName = '';
    public string $rDesc = '';
    public string $rVis = '';
    public string $rBranch = '';
    public string $rOwner = '';
    public string $rDisp = '';
    public string $rSlug = '';
    public string $rCreated = '';
    public string $rUpdated = '';
    public int $rStars = 0;
    public int $rForks = 0;
    public string $httpUrl = '';
    public string $sshUrl = '';
    public string $httpBase = '';
    public bool $isOwner = false;
    public bool $isAdmin = false;
    public string $csrfToken = '';

    /** @var list<array<string, mixed>> */
    public array $issues = [];
    /** @var list<array<string, mixed>> */
    public array $pullRequests = [];
    public int $openIssuesCount = 0;
    public int $openPullRequestsCount = 0;

    public string $detailType = '';
    /** @var array<string, mixed>|null */
    public ?array $detailItem = null;

    /** @var list<string> */
    public array $tabErrors = [];
    public ?string $tabSuccess = null;

    public function handle(bool $isLoggedIn, string $role): bool
    {
        $config = Config::getInstance();
        $pdo = $config->getPDO();

        $rawSlug = trim($_GET['slug'] ?? '');
        if (strlen($rawSlug) > 200) {
            http_response_code(414);
            include __DIR__ . '/../pages/414.php';

            return false;
        }
        if ($rawSlug === '' || ! preg_match('#^[a-zA-Z0-9][a-zA-Z0-9_-]{0,49}/[a-zA-Z0-9][a-zA-Z0-9._-]{0,98}$#', $rawSlug)) {
            http_response_code(404);
            include __DIR__ . '/../pages/404.php';

            return false;
        }

        $repo = null;
        if ($pdo !== null) {
            try {
                $repo = (new RepositoryService($pdo, $config->getDataRoot()))->getBySlug($rawSlug);
            } catch (\PDOException $e) {
                Logging::loggingToFile('RepoViewController SQL error: ' . $e->getMessage(), 4);
            }
        }
        if ($repo === null) {
            http_response_code(404);
            include __DIR__ . '/../pages/404.php';

            return false;
        }

        $sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
        $isOwner = $isLoggedIn && $sessionUserId === (int) $repo['owner_user_id'];
        $isAdmin = $isLoggedIn && $role === 'ADMIN';

        if ($repo['visibility'] === 'private' && ! $isOwner && ! $isAdmin) {
            http_response_code(403);
            include __DIR__ . '/../pages/403.php';

            return false;
        }

        $security = new Security();
        $isPrivileged = $isOwner || $isAdmin;
        $requestedTab = isset($_GET['tab']) ? strtolower(trim((string) $_GET['tab'])) : 'code';
        $allowedTabs = ['code', 'issues', 'pulls'];
        if ($isPrivileged) {
            $allowedTabs[] = 'settings';
        }
        $activeTab = in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'code';

        $tabErrors = $_SESSION['repo_view_errors'] ?? [];
        $tabSuccess = $_SESSION['repo_view_success'] ?? null;
        unset($_SESSION['repo_view_errors'], $_SESSION['repo_view_success']);

        $repoPath = $config->getDataRoot() . '/' . $repo['owner_username'] . '/' . $repo['repo_name'];
        $git = new GitReaderService($repoPath);
        $isEmpty = $git->isEmpty();
        $availableBranches = ! $isEmpty ? $git->getBranches((string) $repo['default_branch']) : [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postErrors = [];
            $postSuccess = null;
            $action = strtolower(trim((string) ($_POST['repo_action'] ?? '')));
            $csrfToken = (string) ($_POST['csrf_token'] ?? '');

            if (! $security->validateCsrfToken($csrfToken)) {
                $postErrors[] = 'Invalid or expired form submission. Please try again.';
            } elseif ($pdo === null) {
                $postErrors[] = 'Database is currently unavailable. Please try again later.';
            } else {
                try {
                    if ($action === 'create_issue') {
                        if (! $isLoggedIn || $sessionUserId <= 0) {
                            $postErrors[] = 'You must be logged in to create an issue.';
                        } else {
                            $title = trim((string) ($_POST['issue_title'] ?? ''));
                            $body = trim((string) ($_POST['issue_body'] ?? ''));

                            if ($title === '') {
                                $postErrors[] = 'Issue title is required.';
                            } elseif (mb_strlen($title) > 160) {
                                $postErrors[] = 'Issue title must be 160 characters or fewer.';
                            }

                            if (mb_strlen($body) > 20000) {
                                $postErrors[] = 'Issue description must be 20000 characters or fewer.';
                            }

                            if ($postErrors === []) {
                                $stmt = $pdo->prepare(
                                    'INSERT INTO issues (repository_id, author_user_id, title, body)
                                     VALUES (?, ?, ?, ?)'
                                );
                                $stmt->execute([(int) $repo['id'], $sessionUserId, $title, $body !== '' ? $body : null]);
                                $postSuccess = 'Issue created successfully.';
                            }
                        }
                    } elseif ($action === 'create_pull') {
                        if (! $isLoggedIn || $sessionUserId <= 0) {
                            $postErrors[] = 'You must be logged in to create a pull request.';
                        } elseif ($isEmpty || $availableBranches === []) {
                            $postErrors[] = 'Pull requests require at least one branch with commits.';
                        } else {
                            $title = trim((string) ($_POST['pull_title'] ?? ''));
                            $body = trim((string) ($_POST['pull_body'] ?? ''));
                            $fromBranch = preg_replace('/[^a-zA-Z0-9._\/-]/', '', (string) ($_POST['from_branch'] ?? '')) ?? '';
                            $toBranch = preg_replace('/[^a-zA-Z0-9._\/-]/', '', (string) ($_POST['to_branch'] ?? '')) ?? '';

                            if ($title === '') {
                                $postErrors[] = 'Pull request title is required.';
                            } elseif (mb_strlen($title) > 160) {
                                $postErrors[] = 'Pull request title must be 160 characters or fewer.';
                            }

                            if ($fromBranch === '' || $toBranch === '') {
                                $postErrors[] = 'Both source and target branches are required.';
                            } elseif ($fromBranch === $toBranch) {
                                $postErrors[] = 'Source and target branches must be different.';
                            }

                            if (! in_array($fromBranch, $availableBranches, true) || ! in_array($toBranch, $availableBranches, true)) {
                                $postErrors[] = 'Selected branches are invalid for this repository.';
                            }

                            if (mb_strlen($body) > 20000) {
                                $postErrors[] = 'Pull request description must be 20000 characters or fewer.';
                            }

                            if ($postErrors === []) {
                                $stmt = $pdo->prepare(
                                    'INSERT INTO pull_requests (repository_id, author_user_id, from_branch_name, to_branch_name, title, body)
                                     VALUES (?, ?, ?, ?, ?, ?)'
                                );
                                $stmt->execute([
                                    (int) $repo['id'],
                                    $sessionUserId,
                                    $fromBranch,
                                    $toBranch,
                                    $title,
                                    $body !== '' ? $body : null,
                                ]);
                                $postSuccess = 'Pull request created successfully.';
                            }
                        }
                    } elseif ($action === 'update_repo_settings') {
                        if (! $isPrivileged) {
                            $postErrors[] = 'You do not have permission to update repository settings.';
                        } else {
                            $description = trim((string) ($_POST['repo_description'] ?? ''));
                            $visibility = (string) ($_POST['visibility'] ?? 'public');
                            $defaultBranch = preg_replace('/[^a-zA-Z0-9._\/-]/', '', (string) ($_POST['default_branch'] ?? '')) ?? '';

                            if ($defaultBranch === '') {
                                $defaultBranch = (string) $repo['default_branch'];
                            }

                            if (mb_strlen($description) > 500) {
                                $postErrors[] = 'Description must be 500 characters or fewer.';
                            }

                            if (! in_array($visibility, ['public', 'private'], true)) {
                                $postErrors[] = 'Invalid repository visibility selected.';
                            }

                            if (! $isEmpty && $availableBranches !== [] && ! in_array($defaultBranch, $availableBranches, true)) {
                                $postErrors[] = 'Default branch must be one of the existing repository branches.';
                            }

                            if ($postErrors === []) {
                                $stmt = $pdo->prepare(
                                    'UPDATE repositories
                                     SET repo_description = ?, visibility = ?, default_branch = ?
                                     WHERE id = ?'
                                );
                                $stmt->execute([
                                    $description !== '' ? $description : null,
                                    $visibility,
                                    $defaultBranch,
                                    (int) $repo['id'],
                                ]);
                                $postSuccess = 'Repository settings updated successfully.';
                            }
                        }
                    } elseif ($action === 'repo_delete') {
                        if (! $isPrivileged) {
                            $postErrors[] = 'You do not have permission to delete this repository.';
                        } else {
                            $stmt = $pdo->prepare('DELETE FROM repositories WHERE id = ?');
                            $stmt->execute([(int) $repo['id']]);

                            $dataRoot = $config->getDataRoot();
                            $bareRepoPath = $dataRoot . '/' . $repo['owner_username'] . '/' . $repo['repo_name'];
                            if (is_dir($bareRepoPath)) {
                                $delFunc = static function (string $dir) use (&$delFunc): void {
                                    $items = scandir($dir);
                                    foreach ($items as $item) {
                                        if ($item === '.' || $item === '..') {
                                            continue;
                                        }
                                        $path = $dir . '/' . $item;
                                        is_dir($path) ? $delFunc($path) : unlink($path);
                                    }
                                    rmdir($dir);
                                };
                                $delFunc($bareRepoPath);
                            }

                            $_SESSION['repo_flash'] = 'Repository <strong>' . htmlspecialchars($repo['repo_name'], ENT_QUOTES, 'UTF-8') . '</strong> deleted successfully.';
                            echo '<script>window.location.href="/' . $repo['owner_username'] . '";</script>';
                            exit;
                        }
                    }
                } catch (\PDOException $e) {
                    Logging::loggingToFile('Repo view tab action failed: ' . $e->getMessage(), 4);
                    $postErrors[] = 'Unable to save changes right now. Please try again later.';
                }
            }

            $_SESSION['repo_view_errors'] = $postErrors;
            if ($postSuccess !== null) {
                $_SESSION['repo_view_success'] = $postSuccess;
            }

            $redir = '/' . htmlspecialchars($rawSlug, ENT_QUOTES, 'UTF-8');
            if ($activeTab !== 'code') {
                $redir .= '?tab=' . urlencode($activeTab);
            }
            echo '<script>window.location.href="' . $redir . '";</script>';
            exit;
        }

        $currentBranch = preg_replace('/[^a-zA-Z0-9._\/-]/', '', $_GET['branch'] ?? $repo['default_branch']);
        if ($currentBranch === '') {
            $currentBranch = (string) $repo['default_branch'];
        }

        $rawPath = trim($_GET['path'] ?? '', '/');
        $cleanSegments = [];
        foreach (explode('/', $rawPath) as $seg) {
            if ($seg === '' || $seg === '.' || $seg === '..') {
                continue;
            }
            if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._\- ]*$/', $seg)) {
                $cleanSegments = [];
                break;
            }
            $cleanSegments[] = $seg;
        }
        $currentPath = implode('/', $cleanSegments);

        $viewMode = 'root';
        $fileData = null;
        $subEntries = [];
        $subCommitMap = [];
        $pathLatestCommit = null;
        $branches = [];
        $treeEntries = [];
        $commitMap = [];
        $latestCommit = null;
        $commitCount = 0;
        $langBreakdown = [];
        $readmeContent = null;
        $fullFileTree = [];
        $issues = [];
        $pullRequests = [];
        $openIssuesCount = 0;
        $openPullRequestsCount = 0;
        $csrfToken = '';

        if (! $isEmpty) {
            $branches = $availableBranches;
            $commitCount = $git->getCommitCount($currentBranch);
            $fullFileTree = $git->getFullFileTree($currentBranch);

            if ($currentPath !== '') {
                $objType = $git->getObjectType($currentBranch, $currentPath);
                if ($objType === null) {
                    http_response_code(404);
                    include __DIR__ . '/../pages/404.php';

                    return false;
                }
                if ($objType === 'blob') {
                    $viewMode = 'blob';
                    $fileData = $git->getFileContent($currentBranch, $currentPath);
                    $pathLatestCommit = $git->getLastCommitForPath($currentBranch, $currentPath);
                } else {
                    $viewMode = 'tree';
                    $subEntries = $git->getTreeAtPath($currentBranch, $currentPath);
                    $subCommitMap = $git->getLastCommitPerEntryAtPath($currentBranch, $currentPath);
                    $pathLatestCommit = $git->getLastCommitForPath($currentBranch, $currentPath);
                }
            } else {
                $treeEntries = $git->getTopLevelTree($currentBranch);
                $commitMap = $git->getLastCommitPerEntry($currentBranch);
                $latestCommit = $git->getLatestCommit($currentBranch);
                $langBreakdown = $git->getLanguageBreakdown($currentBranch);
                $readmeContent = $git->getReadme($currentBranch);
                $primaryLang = $langBreakdown[0]['lang'] ?? null;
                if ($pdo !== null && $primaryLang !== null && $primaryLang !== ($repo['lang'] ?? null)) {
                    try {
                        $pdo->prepare('UPDATE repositories SET lang = ? WHERE id = ?')
                            ->execute([$primaryLang, $repo['id']]);
                    } catch (\PDOException) {
                    }
                }
            }
        }

        $detailType = '';
        $detailItem = null;
        if ($pdo !== null) {
            try {
                $issueCountStmt = $pdo->prepare('SELECT COUNT(*) FROM issues WHERE repository_id = ? AND status = ?');
                $issueCountStmt->execute([(int) $repo['id'], 'open']);
                $openIssuesCount = (int) $issueCountStmt->fetchColumn();

                $pullCountStmt = $pdo->prepare('SELECT COUNT(*) FROM pull_requests WHERE repository_id = ? AND status = ?');
                $pullCountStmt->execute([(int) $repo['id'], 'open']);
                $openPullRequestsCount = (int) $pullCountStmt->fetchColumn();

                $issuesStmt = $pdo->prepare(
                    'SELECT i.id, i.title, i.body, i.status, i.created_at, i.closed_at,
                            u.username AS author_username,
                            COALESCE(u.display_name, \'\') AS author_display_name,
                            a.username AS assignee_username,
                            COALESCE(a.display_name, \'\') AS assignee_display_name
                     FROM issues i
                     JOIN users u ON u.id = i.author_user_id
                     LEFT JOIN users a ON a.id = i.assignee_user_id
                     WHERE i.repository_id = ?
                     ORDER BY (i.status = \'open\') DESC, i.created_at DESC
                     LIMIT 100'
                );
                $issuesStmt->execute([(int) $repo['id']]);
                $issues = $issuesStmt->fetchAll() ?: [];

                $pullStmt = $pdo->prepare(
                    'SELECT p.id, p.title, p.body, p.status, p.created_at, p.merged_at,
                            p.from_branch_name, p.to_branch_name,
                            u.username AS author_username,
                            COALESCE(u.display_name, \'\') AS author_display_name
                     FROM pull_requests p
                     JOIN users u ON u.id = p.author_user_id
                     WHERE p.repository_id = ?
                     ORDER BY (p.status = \'open\') DESC, p.created_at DESC
                     LIMIT 100'
                );
                $pullStmt->execute([(int) $repo['id']]);
                $pullRequests = $pullStmt->fetchAll() ?: [];
            } catch (\PDOException $e) {
                Logging::loggingToFile('Repo view list query failed: ' . $e->getMessage(), 4);
            }

            $detailType = '';
            $detailItem = null;
            $detailTab = isset($_GET['detail']) ? (string) $_GET['detail'] : '';
            if ($detailTab !== '' && preg_match('/^(issue|pr)_(\d+)$/', $detailTab, $m)) {
                $detailType = $m[1];
                $detailId = (int) $m[2];
                if ($detailType === 'issue') {
                    $detailStmt = $pdo->prepare(
                        'SELECT i.id, i.title, i.body, i.status, i.created_at, i.closed_at,
                                u.username AS author_username, u.email AS author_email,
                                COALESCE(u.display_name, \'\') AS author_display_name,
                                a.username AS assignee_username,
                                COALESCE(a.display_name, \'\') AS assignee_display_name
                         FROM issues i
                         JOIN users u ON u.id = i.author_user_id
                         LEFT JOIN users a ON a.id = i.assignee_user_id
                         WHERE i.id = ? AND i.repository_id = ?'
                    );
                    $detailStmt->execute([$detailId, (int) $repo['id']]);
                    $detailItem = $detailStmt->fetch(\PDO::FETCH_ASSOC);
                } elseif ($detailType === 'pr') {
                    $detailStmt = $pdo->prepare(
                        'SELECT p.id, p.title, p.body, p.status, p.created_at, p.merged_at,
                                p.from_branch_name, p.to_branch_name, p.from_head_hash, p.to_head_hash,
                                u.username AS author_username, u.email AS author_email,
                                COALESCE(u.display_name, \'\') AS author_display_name
                         FROM pull_requests p
                         JOIN users u ON u.id = p.author_user_id
                         WHERE p.id = ? AND p.repository_id = ?'
                    );
                    $detailStmt->execute([$detailId, (int) $repo['id']]);
                    $detailItem = $detailStmt->fetch(\PDO::FETCH_ASSOC);
                }
            }
        }

        try {
            $csrfToken = $security->generateCsrfToken();
        } catch (RandomException $e) {
            Logging::loggingToFile('Cannot generate repo view csrf token: ' . $e->getMessage(), 4);
        }

        $breadcrumbs = [
            ['label' => (string) $repo['owner_username'], 'url' => '/'],
            ['label' => (string) $repo['repo_name'], 'url' => '/' . $rawSlug],
        ];
        if ($currentPath !== '') {
            $acc = '';
            $segs = explode('/', $currentPath);
            foreach ($segs as $i => $seg) {
                $acc .= ($acc === '' ? '' : '/') . $seg;
                $isLast = ($i === count($segs) - 1);
                $breadcrumbs[] = [
                    'label' => $seg,
                    'url' => $isLast ? null : self::pathUrl($rawSlug, $currentBranch, $acc),
                ];
            }
        }

        $httpBase = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'phpgit.local');
        $sshHost = $_ENV['SSH_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'phpgit.local';
        $gitUser = $_ENV['GIT_SYSTEM_USER'] ?? 'git';

        $this->repo = $repo;
        $this->rawSlug = $rawSlug;
        $this->currentBranch = $currentBranch;
        $this->currentPath = $currentPath;
        $this->viewMode = $viewMode;
        $this->isEmpty = $isEmpty;
        $this->isOwner = $isOwner;
        $this->isAdmin = $isAdmin;
        $this->branches = $branches;
        $this->treeEntries = $treeEntries;
        $this->commitMap = $commitMap;
        $this->latestCommit = $latestCommit;
        $this->commitCount = $commitCount;
        $this->langBreakdown = $langBreakdown;
        $this->readmeContent = $readmeContent;
        $this->subEntries = $subEntries;
        $this->subCommitMap = $subCommitMap;
        $this->pathLatestCommit = $pathLatestCommit;
        $this->fileData = $fileData;
        $this->fullFileTree = $fullFileTree;
        $this->breadcrumbs = $breadcrumbs;
        $this->httpBase = $httpBase;
        $this->rName = self::e((string) $repo['repo_name']);
        $this->rDesc = self::e((string) ($repo['repo_description'] ?? ''));
        $this->rVis = (string) $repo['visibility'];
        $this->rBranch = self::e($currentBranch);
        $this->rOwner = self::e((string) $repo['owner_username']);
        $this->rDisp = self::e((string) ($repo['owner_display_name'] ?? $repo['owner_username']));
        $this->rSlug = self::e($rawSlug);
        $this->rCreated = date('d M Y', (int) strtotime((string) $repo['created_at']));
        $this->rUpdated = date('d M Y', (int) strtotime((string) $repo['updated_at']));
        $this->rStars = (int) $repo['stars'];
        $this->rForks = (int) $repo['forks'];
        $this->httpUrl = self::e("{$httpBase}/{$rawSlug}.git");
        $this->sshUrl = self::e("{$gitUser}@{$sshHost}:{$rawSlug}.git");
        $this->csrfToken = $csrfToken;
        $this->issues = $issues;
        $this->pullRequests = $pullRequests;
        $this->openIssuesCount = $openIssuesCount;
        $this->openPullRequestsCount = $openPullRequestsCount;
        $this->detailType = $detailType;
        $this->detailItem = $detailItem;
        $this->tabErrors = is_array($tabErrors) ? array_values(array_filter($tabErrors, 'is_string')) : [];
        $this->tabSuccess = is_string($tabSuccess) && $tabSuccess !== '' ? $tabSuccess : null;

        return true;
    }

    public static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    public static function time(string $iso, string $rel): string
    {
        $safe = self::e($iso);
        $safeRel = self::e($rel);

        return "<time datetime=\"{$safe}\" title=\"{$safe}\">{$safeRel}</time>";
    }

    public static function size(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }

    public static function pathUrl(string $slug, string $branch, string $path): string
    {
        return '/' . $slug . '?' . http_build_query(['branch' => $branch, 'path' => $path]);
    }

    public static function fileIcon(string $name): string
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

    public static function hlLang(string $name): string
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return match ($ext) {
            'php' => 'php',
            'js', 'jsx', 'mjs' => 'javascript',
            'ts', 'tsx' => 'typescript',
            'py', 'pyw' => 'python',
            'rb' => 'ruby',
            'java' => 'java',
            'c', 'h' => 'c',
            'cpp', 'cc', 'cxx', 'hpp' => 'cpp',
            'cs' => 'csharp',
            'go' => 'go',
            'rs' => 'rust',
            'swift' => 'swift',
            'kt', 'kts' => 'kotlin',
            'sh', 'bash', 'zsh' => 'bash',
            'ps1' => 'powershell',
            'sql' => 'sql',
            'html', 'htm' => 'xml',
            'css' => 'css',
            'scss' => 'scss',
            'json' => 'json',
            'yml', 'yaml' => 'yaml',
            'toml' => 'ini',
            'xml' => 'xml',
            'md', 'mdx' => 'markdown',
            'lua' => 'lua',
            'r' => 'r',
            'dart' => 'dart',
            'dockerfile' => 'dockerfile',
            default => 'plaintext',
        };
    }

    public static function renderMarkdown(string $md): string
    {
        $lines = explode("\n", $md);
        $html = '';
        $inPre = false;
        $inUl = false;
        $inOl = false;
        $preBuf = '';

        $closeList = function () use (&$inUl, &$inOl, &$html): void {
            // @phpstan-ignore if.alwaysFalse
            if ($inUl) {
                $html .= "</ul>\n";
                $inUl = false;
            }
            // @phpstan-ignore if.alwaysFalse
            if ($inOl) {
                $html .= "</ol>\n";
                $inOl = false;
            }
        };

        foreach ($lines as $line) {
            if (str_starts_with($line, '```')) {
                if (! $inPre) {
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

            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
                $closeList();
                $lvl = strlen($m[1]);
                $text = self::inlineMarkdown($m[2]);
                $id = preg_replace('/[^a-z0-9]+/', '-', strtolower(strip_tags($text)));
                $html .= "<h{$lvl} id=\"{$id}\">{$text}</h{$lvl}>\n";
                continue;
            }
            if (preg_match('/^[-*_]{3,}$/', trim($line))) {
                $closeList();
                $html .= "<hr>\n";
                continue;
            }
            if (str_starts_with($line, '> ')) {
                $closeList();
                $html .= '<blockquote><p>' . self::inlineMarkdown(substr($line, 2)) . "</p></blockquote>\n";
                continue;
            }
            if (preg_match('/^[-*+]\s+(.+)$/', $line, $m)) {
                if ($inOl) {
                    $html .= "</ol>\n";
                    $inOl = false;
                }
                if (! $inUl) {
                    $html .= "<ul>\n";
                    $inUl = true;
                }
                $html .= '<li>' . self::inlineMarkdown($m[1]) . "</li>\n";
                continue;
            }
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $m)) {
                if ($inUl) {
                    $html .= "</ul>\n";
                    $inUl = false;
                }
                if (! $inOl) {
                    $html .= "<ol>\n";
                    $inOl = true;
                }
                $html .= '<li>' . self::inlineMarkdown($m[1]) . "</li>\n";
                continue;
            }

            $closeList();
            if (trim($line) === '') {
                $html .= "\n";
                continue;
            }
            $html .= '<p>' . self::inlineMarkdown($line) . "</p>\n";
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

    public static function inlineMarkdown(string $text): string
    {
        $s = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $s = preg_replace('/`([^`]+)`/', '<code>$1</code>', $s) ?? $s;
        $s = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $s) ?? $s;
        $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $s) ?? $s;
        $s = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/_(.+?)_/', '<em>$1</em>', $s) ?? $s;
        $s = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" rel="noopener noreferrer">$1</a>', $s) ?? $s;
        $s = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $s) ?? $s;

        return $s;
    }

    /**
     * @param list<array{name:string,type:string,children:array<mixed>}> $nodes
     */
    public static function renderTree(
        array  $nodes,
        string $slug,
        string $branch,
        string $activePath,
        string $pathSoFar = '',
        int    $depth = 0
    ): void {
        $indent = ($depth * 1.1 + 0.5) . 'rem';
        echo '<ul class="rv-tree-ul">';
        foreach ($nodes as $node) {
            /** @var array{name:string,type:string,children:array<mixed>} $node */
            $name = self::e($node['name']);
            $isDir = $node['type'] === 'tree';
            $nodePath = $pathSoFar !== '' ? $pathSoFar . '/' . $node['name'] : $node['name'];
            $nodePathEnc = self::e($nodePath);
            $isActive = ($nodePath === $activePath);
            $isAncestor = $activePath !== '' && str_starts_with($activePath . '/', $nodePath . '/');
            $icon = $isDir
                ? '<i class="bi bi-folder-fill text-warning" style="font-size:.8rem;flex-shrink:0;"></i>'
                : '<i class="bi bi-file-earmark text-secondary" style="font-size:.8rem;flex-shrink:0;"></i>';
            $activeClass = $isActive ? ' rv-active' : '';
            $url = '/' . self::e($slug) . '?branch=' . urlencode($branch) . '&path=' . urlencode($nodePath);

            if ($isDir) {
                $childrenOpen = ($isActive || $isAncestor) ? ' rv-open' : '';
                $toggleIcon = ($isActive || $isAncestor)
                    ? '<i class="bi bi-caret-down-fill"></i>'
                    : '<i class="bi bi-caret-right-fill"></i>';
                echo '<li>';
                echo "<a href=\"{$url}\" class=\"rv-tree-item{$activeClass}\" style=\"--rv-indent:{$indent}\" onclick=\"rvTreeToggle(this,event)\">";
                echo "<span class=\"rv-tree-toggle\">{$toggleIcon}</span>{$icon} {$name}";
                echo '</a>';
                /** @var list<array{name:string,type:string,children:array<mixed>}> $children */
                $children = array_values($node['children']);
                if (! empty($children)) {
                    echo "<div class=\"rv-tree-children{$childrenOpen}\" id=\"rv-td-" . md5($nodePathEnc) . '">';
                    self::renderTree($children, $slug, $branch, $activePath, $nodePath, $depth + 1);
                    echo '</div>';
                }
                echo '</li>';
            } else {
                echo "<li><a href=\"{$url}\" class=\"rv-tree-item{$activeClass}\" style=\"--rv-indent:{$indent}\">";
                echo "<span class=\"rv-tree-toggle\"></span>{$icon} {$name}";
                echo '</a></li>';
            }
        }
        echo '</ul>';
    }
}
