<?php
declare(strict_types=1);

namespace App\includes;

use App\includes\Security;

class Settings
{
    private bool $is_logged_in;
    private string $username;
    private string $current_tab;

    private const array ALLOWED_TABS = ['profile', 'security'];
    private const  string TAB_DIR = __DIR__ . '/../pages/tab/';

    public function __construct(array $session, array $get)
    {
        $this->is_logged_in = !empty($session['is_logged_in']);
        $this->username = $session['username'] ?? '';
        $tabParam = $get['tab'] ?? 'profile';
        if (!is_string($tabParam)) {
            $tabParam = 'profile';
        }
        if (!in_array($tabParam, self::ALLOWED_TABS, true)) {
            $tabParam = 'profile';
        }
        $this->current_tab = (new Security)->sanitizeTab($tabParam);
    }

    private function renderGuest(): void
    {
        http_response_code(403);
        ?>
        <main>
            <div class="container d-flex flex-column align-items-center">
                <div class="mb-4 d-flex align-items-center justify-content-center bg-primary-subtle border border-primary-subtle rounded-circle text-primary"
                     style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h1 class="fw-bold mb-2">Warning!</h1>
                <p class="text-secondary mb-4">You're not logged in.</p>
                <div class="d-flex gap-3 justify-content-center">
                    <a class="btn btn-primary" href="/Index.php?page=home">Go Home</a>
                    <a class="btn btn-outline-secondary" href="/Index.php?page=login">Login</a>
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
                        'description' => 'Public profile and account details',
                        'icon' => 'bi-person'
                ],
                'security' => [
                        'label' => 'Security',
                        'description' => 'Password and account protection',
                        'icon' => 'bi-shield-lock'
                ],
        ];
        ?>
        <aside class="card border-secondary-subtle shadow-sm sticky-xl-top" style="top: 1.25rem;">
            <div class="card-body p-0">
                <div class="p-3 border-bottom border-secondary-subtle bg-primary-subtle bg-opacity-10">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-subtle border border-primary border-opacity-25 text-primary fw-bold d-flex align-items-center justify-content-center"
                             style="width: 32px; height: 32px; font-size: 0.8rem;">
                            <?php echo htmlspecialchars(strtoupper(substr($this->username, 0, 2)), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div>
                            <h5 class="mb-1">Settings</h5>
                            <small class="text-secondary">
                                @<?php echo htmlspecialchars($this->username, ENT_QUOTES, 'UTF-8'); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <nav class="nav flex-column p-2 gap-1">
            <?php foreach (self::ALLOWED_TABS as $tab): ?>
                <a class="nav-link rounded-3 p-3 <?php echo $this->current_tab === $tab ? 'bg-primary-subtle text-primary border border-primary border-opacity-25' : 'text-body-emphasis hover-bg-light'; ?>"
                   href="/Index.php?page=settings&tab=<?php echo htmlspecialchars($tab, ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="d-flex align-items-start gap-2">
                            <i class="bi <?php echo htmlspecialchars($tabMeta[$tab]['icon'], ENT_QUOTES, 'UTF-8'); ?> text-primary"></i>
                            <span>
                                <span class="d-block fw-semibold">
                                    <?php echo htmlspecialchars($tabMeta[$tab]['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <small class="text-secondary">
                                    <?php echo htmlspecialchars($tabMeta[$tab]['description'], ENT_QUOTES, 'UTF-8'); ?>
                                </small>
                            </span>
                        </span>
                </a>
            <?php endforeach; ?>
                </nav>
            </div>
        </aside>
        <?php
    }

    private function renderTabContent(): void
    {
        $path = self::TAB_DIR . $this->current_tab . '.php';
        $username = $this->username;
        ?>
        <section class="card border-secondary-subtle shadow">
            <div class="card-body p-4 p-lg-5">
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
        if (!$this->is_logged_in) {
            $this->renderGuest();
            return;
        }
        ?>
        <main>
            <div class="container py-4 py-lg-5">
                <div class="mb-4 mb-lg-5 p-4 border border-secondary-subtle rounded-4 shadow-sm"
                     style="background: radial-gradient(circle at top right, rgba(79, 142, 247, 0.1), transparent 70%);">
                    <p class="text-primary fw-bold text-uppercase mb-2"
                       style="font-size: .78rem; letter-spacing: .12em;">Account Settings</p>
                    <h2 class="fw-extrabold mb-2" style="letter-spacing: -0.02em;">Customize your experience</h2>
                    <p class="text-secondary mb-0">Manage profile details and security preferences in one place.</p>
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
