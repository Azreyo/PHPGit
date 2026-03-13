<?php
declare(strict_types=1);

namespace App\includes;
class Settings
{
    private bool $is_logged_in;
    private string $username;
    private string $current_tab;

    private const array ALLOWED_TABS = ['profile', 'security'];
    private const string TAB_DIR = __DIR__ . '/../pages/tab/';

    public function __construct(array $session, array $get)
    {
        $this->is_logged_in = !empty($session['username']);
        $this->username = $session['username'] ?? '';
        $this->current_tab = $this->sanitizeTab($get['tab'] ?? 'profile');
    }

    private function sanitizeTab(string $tab): string
    {
        $tab = strtolower(preg_replace('/[^a-z0-9_-]/', '', $tab));
        return in_array($tab, self::ALLOWED_TABS, true) ? $tab : 'profile';
    }

    private function renderGuest(): void
    {
        http_response_code(403);
        ?>
        <main>
            <div class="container d-flex flex-column align-items-center">
                <div class="logout-icon mb-4">
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
        ?>
        <div class="nav nav-tabs mb-4 w-100">
            <?php foreach (self::ALLOWED_TABS as $tab): ?>
                <a class="nav-link <?php echo $this->current_tab === $tab ? 'active' : ''; ?>"
                   href="/Index.php?page=settings&tab=<?php echo htmlspecialchars($tab, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars(ucfirst($tab), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function renderTabContent(): void
    {
        $path = self::TAB_DIR . $this->current_tab . '.php';
        ?>
        <div class="border border-secondary rounded p-4 w-100" style="max-width: 600px;">
            <?php
            if (file_exists($path)) {
                include $path;
            }
            ?>
        </div>
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
            <div class="container d-flex flex-column align-items-start">
                <h3 class="m-4">
                    Welcome, <?php echo htmlspecialchars($this->username, ENT_SUBSTITUTE | ENT_QUOTES, 'UTF-8'); ?>
                </h3>
                <?php
                $this->renderTabs();
                $this->renderTabContent();
                ?>
            </div>
        </main>
        <?php
    }
}

new settings($_SESSION, $_GET)->render();
