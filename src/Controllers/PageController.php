<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\includes\DevPanel;

final class PageController
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
        '414' => 'URI too long',
        'terms' => 'Terms of Service',
    ];

    private const RESTRICTED_PAGES = ['env', 'htaccess', 'config'];

    private const AUTHENTICATED_USER_PAGES = [
        'settings' => 'Settings',
        'repos' => 'Your Repositories',
        'new_repo' => 'New Repository',
    ];

    private const ADMIN_PAGES = [
        'dashboard' => 'Dashboard',
    ];

    private string $page;
    private bool $isLoggedIn;
    private bool $isDev;
    /** @var array<string, string> */
    private array $pageTitles;

    private ?\PDO $pdo;
    private bool $dbCurrentState;
    private string $host;
    private string $db;
    private string $dbUser;
    private string $username;
    private string $charset;
    private ?string $pdoError;
    private Config $config;
    private string $role;

    public function __construct(?Config $config = null)
    {
        $this->config = $config ?? Config::getInstance();

        $this->pdo = $this->config->getPdo();
        $this->dbCurrentState = $this->config->isDbOnline();
        $this->host = $this->config->getHost();
        $this->db = $this->config->getDb();
        $this->dbUser = $this->config->getDbUser();
        $this->charset = $this->config->getCharset();
        $this->pdoError = $this->config->getPdoError();
        $this->isDev = $this->config->isDev();

        $this->startSession();
        $this->isLoggedIn = (bool) ($_SESSION['is_logged_in'] ?? false);
        $this->username = (string) ($_SESSION['username'] ?? '');
        $this->role = (string) ($_SESSION['role'] ?? '');
        $this->pageTitles = $this->buildPageTitles();
        $this->page = $this->resolvePage();
    }

    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $cookieLifetime = 30 * 24 * 60 * 60;
            session_set_cookie_params([
                'lifetime' => $cookieLifetime,
                'path' => '/',
                'domain' => 'phpgit.local',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            ini_set('session.gc_maxlifetime', (string) $cookieLifetime);
            session_start();

            if (! isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
        }
    }

    /** @return array<string, string> */
    private function buildPageTitles(): array
    {
        /** @var array<string, string> $titles */
        $titles = self::PAGE_TITLES;

        if ($this->isDev) {
            $titles['phpinfo'] = 'phpinfo';
        }

        if ($this->isLoggedIn) {
            $titles += self::AUTHENTICATED_USER_PAGES;
            if ($this->role === 'ADMIN') {
                $titles += self::ADMIN_PAGES;
            }
        }

        return $titles;
    }

    private function resolvePage(): string
    {
        $rawPage = $_GET['page'] ?? 'home';
        if (! is_string($rawPage)) {
            $rawPage = 'home';
        }

        $page = preg_replace('/[^a-z0-9_]/', '', strtolower($rawPage)) ?: 'home';

        if (! preg_match('/^[a-z0-9_]+$/', $page)) {
            return '403';
        }

        if (in_array($page, self::RESTRICTED_PAGES, true)) {
            return '403';
        }

        if (! array_key_exists($page, $this->pageTitles)) {
            return '404';
        }

        return $page;
    }

    public function run(): void
    {
        $page = $this->page;
        $this->setHttpStatusCode($page);

        $is_logged_in = $this->isLoggedIn;
        $is_dev = $this->isDev;
        $pageTitles = $this->pageTitles;
        $pdo = $this->pdo;
        $config = $this->config;
        $username = $this->username;
        $role = $this->role;
        $page_title = htmlspecialchars($pageTitles[$page] ?? 'PHPGit', ENT_QUOTES, 'UTF-8');

        include __DIR__ . '/../views/layout.php';
    }

    private function setHttpStatusCode(string $page): void
    {
        $statusCode = match ($page) {
            '403' => 403,
            '404' => 404,
            '414' => 414,
            default => 200,
        };

        http_response_code($statusCode);
    }

    public function renderDevPanel(): void
    {
        if (! $this->isDev) {
            return;
        }

        (new DevPanel(
            $this->pdo,
            $this->dbCurrentState,
            $this->host,
            $this->db,
            $this->dbUser,
            $this->charset,
            $this->pdoError
        ))->render();
    }
}
