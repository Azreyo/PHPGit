<?php
declare(strict_types=1);

namespace App;

use AllowDynamicProperties;
use App\includes\DevPanel;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/includes/error_handler.php';
#[AllowDynamicProperties]
class Index
{
    private const PAGE_TITLES = [
            'home' => 'Home',
            'about' => 'About us',
            'contact' => 'Contact',
            'explore' => 'Explore',
            'login' => 'Login',
            'register' => 'Register',
            'logout' => 'Logout',
            '404' => 'Page not found',
            '403' => 'Forbidden',
            'terms' => 'Terms of Service',
    ];

    private const RESTRICTED_PAGES = ['env', 'htaccess', 'config'];

    private const AUTHENTICATED_USER_PAGES = [
            'settings' => 'Settings',
    ];

    private string $page;
    private bool $is_logged_in;
    private bool $isDev;
    private array $pageTitles;

    private ?\PDO $pdo;
    private bool $db_current_state;
    private string $host;
    private string $db;
    private string $db_user;
    private string $charset;
    private ?string $pdo_error;
    private Config $config;

    public function __construct(?Config $config = null)
    {
        $this->config = $config ?? Config::getInstance();

        $this->pdo = $this->config->getPdo();
        $this->db_current_state = $this->config->isDbOnline();
        $this->host = $this->config->getHost();
        $this->db = $this->config->getDb();
        $this->db_user = $this->config->getDbUser();
        $this->charset = $this->config->getCharset();
        $this->pdo_error = $this->config->getPdoError();
        $this->isDev = $this->config->isDev();

        $this->startSession();
        $this->is_logged_in = $this->resolveSession();
        $this->pageTitles = $this->buildPageTitles();
        $this->page = $this->resolvePage();
    }


    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    private function resolveSession(): bool
    {
        return (bool)($_SESSION['is_logged_in'] ?? false);
    }


    private function buildPageTitles(): array
    {
        $titles = self::PAGE_TITLES;

        if ($this->isDev) {
            $titles['phpinfo'] = 'phpinfo';
        }

        if ($this->is_logged_in) {
            $titles = array_merge($titles, self::AUTHENTICATED_USER_PAGES);
        }

        return $titles;
    }


    private function resolvePage(): string
    {
        $rawPage = $_GET['page'] ?? 'home';
        if (!is_string($rawPage)) {
            $rawPage = 'home';
        }

        $page = preg_replace('/[^a-z0-9_]/', '', strtolower($rawPage)) ?: 'home';
        if ($page === '') {
            $page = 'home';
        }
        if (!preg_match('/^[a-z0-9_]+$/', $page)) {
            return '403';
        }

        if (in_array($page, self::RESTRICTED_PAGES, true)) {
            return '403';
        }

        if (!array_key_exists($page, $this->pageTitles)) {
            return '404';
        }

        return $page;
    }


    public function run(): void
    {
        $page = $this->page;
        $is_logged_in = $this->is_logged_in;
        $is_dev = $this->isDev;
        $pageTitles = $this->pageTitles;
        $pdo = $this->pdo;
        $config = $this->config;
        $page_title = htmlspecialchars($pageTitles[$page] ?? 'PHPGit', ENT_QUOTES, 'UTF-8');

        ?>
        <!DOCTYPE html>
        <html lang="en" data-bs-theme="dark">
        <head>
            <script>(function () {
                    let t = localStorage.getItem('theme') || 'dark';
                    document.documentElement.setAttribute('data-bs-theme', t);
                })();</script>
            <meta charset="UTF-8">
            <title><?= $page_title ?></title>
            <meta name="description" content="PHPGit">
            <meta name="keywords" content="git, php">
            <meta name="author" content="Azreyo">

            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
                  integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
                  crossorigin="anonymous">
            <link rel="stylesheet"
                  href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <link rel="stylesheet" href="/assets/style.css">
            <?php if ($is_dev): ?>
                <link rel="stylesheet" href="/assets/dev.css">
            <?php endif; ?>
        </head>
        <body>

        <?php
        include __DIR__ . '/includes/header.php';
        include __DIR__ . '/pages/' . htmlspecialchars($page, ENT_QUOTES, 'UTF-8') . '.php';
        include __DIR__ . '/includes/footer.php';
        ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
                integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
                crossorigin="anonymous"></script>
        <script src="/scripts/theme.js"></script>
        <?php if ($is_dev): ?>
            <?php
            (new DevPanel(
                    $this->pdo,
                    $this->db_current_state,
                    $this->host,
                    $this->db,
                    $this->db_user,
                    $this->charset,
                    $this->pdo_error
            ))->render();
            ?>
        <?php endif; ?>

        </body>
        </html>
        <?php
    }
}

(new Index())->run();
