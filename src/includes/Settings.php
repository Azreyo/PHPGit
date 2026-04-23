<?php
declare(strict_types=1);

namespace App\includes;

class Settings
{
    private bool $is_logged_in;
    private string $username;
    private string $current_tab;

    private const array ALLOWED_TABS = ['profile', 'security', 'appearance', 'ssh-keys'];
    private const  string TAB_DIR = __DIR__ . '/../pages/tab/';

    /**
     * @param array<string, mixed> $session
     * @param array<string, mixed> $get
     */
    public function __construct(array $session, array $get)
    {
        $this->is_logged_in = ! empty($session['is_logged_in']);
        $this->username = isset($session['username']) && is_string($session['username']) ? $session['username'] : '';
        $security = new Security();
        $tabParam = $get['tab'] ?? 'profile';
        if (! is_string($tabParam)) {
            $tabParam = 'profile';
        }
        $tabParam = $security->sanitizeTab($tabParam);
        if (! in_array($tabParam, self::ALLOWED_TABS, true)) {
            $tabParam = 'profile';
        }
        $this->current_tab = $tabParam;
    }

    private function renderGuest(): void
    {
        http_response_code(403);
        ?>
        <main>
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-12 col-sm-8 col-md-5">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning mx-auto mb-4"
                                 style="width: 72px; height: 72px; font-size: 1.8rem;">
                                <i class="bi bi-lock-fill"></i>
                            </div>
                            <h1 class="fw-bold mb-2 h4">Access Restricted</h1>
                            <p class="text-secondary mb-4">You need to be signed in to view your settings.</p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a class="btn btn-primary px-4" href="/login">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                                </a>
                                <a class="btn btn-outline-secondary px-4" href="/home">Go Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php
    }

    private function renderTabs(): void
    {
        $tabMeta = [
            'profile' => [
                    'label' => 'Profile',
                'description' => 'Display name, bio & website',
                    'icon' => 'bi-person-circle',
                    'group' => 'Account',
            ],
            'security' => [
                    'label' => 'Security',
                'description' => 'Password & 2FA protection',
                    'icon' => 'bi-shield-lock-fill',
                    'group' => 'Account',
            ],
                'appearance' => [
                        'label' => 'Appearance',
                        'description' => 'Theme & display options',
                        'icon' => 'bi-palette',
                        'group' => 'Preferences',
                ],
                'ssh-keys' => [
                        'label' => 'SSH Keys',
                        'description' => 'Manage authentication keys',
                        'icon' => 'bi-key',
                        'group' => 'Developer',
            ],
        ];
        $initials = htmlspecialchars(strtoupper(substr($this->username, 0, 2)), ENT_QUOTES, 'UTF-8');
        $username = htmlspecialchars($this->username, ENT_QUOTES, 'UTF-8');
        ?>
        <aside class="card border-0 shadow-sm rounded-4 overflow-hidden sticky-xl-top" style="top: 1.25rem;">
            <div class="p-4 border-bottom border-secondary-subtle"
                 style="background: linear-gradient(135deg, var(--brand-muted) 0%, transparent 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 40px; height: 40px; font-size: .85rem; letter-spacing: -.02em;">
                        <?php echo $initials; ?>
                    </div>
                    <div class="overflow-hidden">
                        <div class="fw-bold text-truncate" style="font-size: .95rem;"><?php echo $username; ?></div>
                        <small class="text-secondary">Personal Account</small>
                    </div>
                </div>
            </div>
            <nav class="p-3">
                <?php
                $renderedGroups = [];
        foreach (self::ALLOWED_TABS as $tab):
            $isActive = $this->current_tab === $tab;
            $group = $tabMeta[$tab]['group'];
            if (! in_array($group, $renderedGroups, true)):
                $extraTop = $renderedGroups !== [] ? 'mt-2' : '';
                $renderedGroups[] = $group;
                ?>
                        <p class="text-secondary text-uppercase fw-bold px-2 pt-3 mb-2 <?php echo $extraTop; ?>"
                           style="font-size: .65rem; letter-spacing: .12em;"><?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <a class="d-flex align-items-center gap-3 px-3 py-3 rounded-3 text-decoration-none mb-2
                       <?php echo $isActive ? 'bg-primary text-white shadow-sm' : 'text-body-emphasis'; ?>"
                   href="/settings?tab=<?php echo htmlspecialchars($tab, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0
                            <?php echo $isActive ? 'bg-white bg-opacity-25 text-white' : 'bg-primary-subtle text-primary'; ?>"
                         style="width: 34px; height: 34px; font-size: .95rem;">
                        <i class="bi <?php echo htmlspecialchars($tabMeta[$tab]['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="fw-semibold" style="font-size: .9rem;">
                            <?php echo htmlspecialchars($tabMeta[$tab]['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <small class="<?php echo $isActive ? 'text-white text-opacity-75' : 'text-secondary'; ?>">
                            <?php echo htmlspecialchars($tabMeta[$tab]['description'], ENT_QUOTES, 'UTF-8'); ?>
                        </small>
                    </div>
                    <?php if ($isActive): ?>
                    <i class="bi bi-chevron-right ms-auto opacity-75" style="font-size: .7rem;"></i>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </nav>
            <div class="p-3">
                <div class="border-top border-secondary-subtle pt-3">
                    <a href="/home"
                       class="d-flex align-items-center gap-2 text-secondary text-decoration-none p-2 rounded-3"
                       style="font-size: .85rem;">
                        <i class="bi bi-house"></i>
                        <span>Back to Home</span>
                    </a>
                </div>
            </div>
        </aside>
        <?php
    }

    private function renderTabContent(): void
    {
        $path = self::TAB_DIR . $this->current_tab . '.php';
        $username = $this->username;
        ?>
        <section class="card border-0 shadow-sm rounded-4">
            <div class="card-body" style="padding: 2.5rem;">
            <?php
            if (file_exists($path)) {
                include $path;
            }
        ?>
            </div>
        </section>
        <?php
    }

    public function render(): void
    {
        if (! $this->is_logged_in) {
            $this->renderGuest();

            return;
        }
        ?>
        <main>
            <div class="container py-4 py-lg-5">
                <div class="mb-4 mb-lg-5">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                             style="width: 34px; height: 34px; font-size: .9rem;">
                            <i class="bi bi-sliders"></i>
                        </div>
                        <div>
                            <p class="section-label mb-0">Account Settings</p>
                            <h5 class="fw-bold mb-0" style="letter-spacing: -0.02em;">Manage your account</h5>
                        </div>
                    </div>
                    <p class="text-secondary mb-0 ms-1">Control how your profile looks and keep your account secure.</p>
                </div>
                <div class="row g-4 align-items-start">
                    <div class="col-12 col-xl-4">
                        <?php $this->renderTabs(); ?>
                    </div>
                    <div class="col-12 col-xl-8">
                        <?php $this->renderTabContent(); ?>
                    </div>
                </div>
            </div>
        </main>
        <?php
    }
}
