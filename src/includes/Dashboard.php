<?php
declare(strict_types=1);

namespace App\includes;

class Dashboard
{
    private bool $is_logged_in;
    private string $username;
    private string $role;
    private string $current_tab;

    private const array ALLOWED_TABS = ['overview', 'users', 'logs', 'inbox'];
    private const string TAB_DIR = __DIR__ . '/../pages/tab/';

    /**
     * @param array<string, mixed> $session
     * @param array<string, mixed> $get
     */
    public function __construct(array $session, array $get)
    {
        $this->is_logged_in = ! empty($session['is_logged_in']);
        $this->username = $session['username'] ?? '';
        $this->role = $session['role'] ?? '';

        $tabParam = $get['tab'] ?? 'overview';
        if (! is_string($tabParam)) {
            $tabParam = 'overview';
        }
        $this->current_tab = $this->sanitizeTab($tabParam);
    }

    private function sanitizeTab(string $tab): string
    {
        $tab = strtolower(preg_replace('/[^a-z0-9_-]/', '', $tab));

        return in_array($tab, self::ALLOWED_TABS, true) ? $tab : 'overview';
    }

    private function renderForbidden(): void
    {
        http_response_code(403);
        ?>
        <main>
            <div class="container d-flex flex-column align-items-center justify-content-center"
                 style="min-height: 70vh;">
                <div class="text-center">
                    <div class="mb-4 d-flex align-items-center justify-content-center bg-danger-subtle border border-danger-subtle rounded-circle text-danger mx-auto"
                         style="width: 80px; height: 80px; font-size: 2rem;">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h1 class="fw-bold mb-2">Access Denied</h1>
                    <p class="text-secondary mb-4">You do not have administrative privileges to access this area.</p>
                    <div class="d-flex gap-3 justify-content-center">
                        <a class="btn btn-primary" href="/home">Go Home</a>
                        <a class="btn btn-outline-secondary" href="/settings">Settings</a>
                    </div>
                </div>
            </div>
        </main>
        <?php
    }

    private function renderTabs(): void
    {
        $tabMeta = [
                'overview' => [
                        'label' => 'Overview',
                        'description' => 'Platform health and trends',
                        'icon' => 'bi-grid-1x2',
                ],
                'users' => [
                        'label' => 'Users',
                        'description' => 'Accounts and permissions',
                        'icon' => 'bi-people',
                ],
                'logs' => [
                        'label' => 'Logs',
                        'description' => 'Security and audit trail',
                        'icon' => 'bi-journal-text',
                ],
                'inbox' => [
                        'label' => 'Inbox',
                        'description' => 'User messages and feedback',
                        'icon' => 'bi-inbox',
                ],
        ];
        ?>
        <aside class="admin-sidebar sticky-xl-top" style="top: 1.25rem;">
            <div class="admin-panel mb-4">
                <div class="admin-panel-head">
                    <div class="admin-panel-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div>
                        <p class="admin-panel-kicker">Access</p>
                        <h6 class="mb-0 fw-bold">Administrator Session</h6>
                    </div>
                </div>
                <nav class="nav flex-column gap-2 mt-3" aria-label="Dashboard sections">
                    <?php foreach (self::ALLOWED_TABS as $tab):
                        $isActive = $this->current_tab === $tab;
                        ?>
                        <a class="admin-tab-link <?php echo $isActive ? 'is-active' : ''; ?>"
                           href="/dashboard?tab=<?php echo htmlspecialchars($tab, ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="admin-tab-icon"><i
                                        class="bi <?php echo htmlspecialchars($tabMeta[$tab]['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                            <span class="flex-grow-1">
                            <strong><?php echo htmlspecialchars($tabMeta[$tab]['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small><?php echo htmlspecialchars($tabMeta[$tab]['description'], ENT_QUOTES, 'UTF-8'); ?></small>
                        </span>
                            <?php if ($isActive): ?>
                                <i class="bi bi-arrow-right-short admin-tab-arrow"></i>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <div class="admin-panel-foot mt-4">
                    <span class="badge text-bg-light border">v1.2.0</span>
                    <span class="admin-panel-dot"></span>
                    <small>Console Online</small>
                </div>
            </div>

            <div class="admin-tip-card">
                <p class="admin-panel-kicker">Productivity</p>
                <h6 class="fw-bold mb-2">Batch actions are enabled</h6>
                <p class="small text-secondary mb-3">You can process multiple users and export filtered logs in one
                    pass.</p>
                <a href="#" class="btn btn-sm btn-primary rounded-pill px-3">Open Docs</a>
            </div>
        </aside>
        <?php
    }

    private function renderTabContent(): void
    {
        $path = self::TAB_DIR . $this->current_tab . '.php';
        ?>
        <section class="admin-content-wrap">
            <?php
            if (file_exists($path)) {
                include $path;
            } else {
                echo '<div class="admin-panel p-4 text-center"><p class="text-secondary mb-0">Tab content not found.</p></div>';
            }
        ?>
        </section>
        <?php
    }

    public function render(): void
    {
        if (! $this->is_logged_in || $this->role !== 'ADMIN') {
            $this->renderForbidden();

            return;
        }
        ?>
        <main>
            <div class="container-fluid px-lg-5 py-4 py-lg-5 position-relative">
                <section class="admin-hero mb-4 mb-lg-5">
                    <div class="row g-4 align-items-end">
                        <div class="col-12 col-lg-8">
                            <p class="admin-panel-kicker mb-2">Management</p>
                            <h1 class="admin-hero-title mb-2">
                                <?php echo match ($this->current_tab) {
                                    'overview' => 'Operations Overview',
                                    'users' => 'User Administration',
                                    'logs' => 'Security Event Stream',
                                    'inbox' => 'Contact Messages',
                                    default => 'Dashboard'
                                }; ?>
                            </h1>
                            <p class="text-secondary mb-0">Signed in as <span
                                        class="fw-semibold text-body-emphasis">@<?php echo htmlspecialchars($this->username, ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>
                        </div>
                    </div>
                </section>

                <div class="row g-4 align-items-start">
                    <div class="col-12 col-xl-3">
                        <?php $this->renderTabs(); ?>
                    </div>
                    <div class="col-12 col-xl-9">
                        <?php $this->renderTabContent(); ?>
                    </div>
                </div>
            </div>
        </main>
        <?php
    }
}
