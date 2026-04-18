<?php

declare(strict_types=1);

namespace App\includes;

class DevPanel
{
    private ?\PDO $pdo;
    private ?string $pdo_error;
    private string  $host;
    private string  $db;
    private string  $db_user;
    private string  $charset;
    private bool    $db_current_state;

    public function __construct(
        ?\PDO   $pdo,
        bool    $db_current_state,
        string $host = 'n/a',
        string $db = 'n/a',
        string  $db_user = 'n/a',
        string  $charset = 'utf8mb4',
        ?string $pdo_error = null
    ) {
        $this->pdo = $pdo;
        $this->db_current_state = $db_current_state;
        $this->host = $host;
        $this->db = $db;
        $this->db_user = $db_user;
        $this->charset = $charset;
        $this->pdo_error = $pdo_error;
    }

    private function convertBytes(int $size): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
        $i = $size > 0 ? (int) floor(log($size, 1024)) : 0;

        return round($size / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    private function serviceIndicator(bool $status): string
    {
        $color = $status ? 'success' : 'danger';
        $label = $status ? 'Up' : 'Down';

        return "<span class=\"btn btn-{$color} btn-sm\">{$label}</span>";
    }

    private function checkStatus(bool $status): string
    {
        $color = $status ? 'success' : 'danger';
        $label = $status ? 'Yes' : 'No';

        return "<span class=\"btn btn-{$color} btn-sm\">{$label}</span>";
    }

    private function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function renderHttpStatus(): string
    {
        $code = http_response_code();

        $map = [
            200 => ['success', 'OK'],
            400 => ['danger',  'Bad Request'],
            403 => ['danger',  'Forbidden'],
            404 => ['warning', 'Not Found'],
            414 => ['warning', 'URI too long'],
            500 => ['danger',  'Internal Server Error'],
        ];

        [$color, $text] = $map[$code] ?? ['warning', 'Unknown Status'];

        return '<li>'
             . "<span title=\"HTTP status\" class=\"btn btn-{$color} m-2 p-2\">{$code} {$text}</span>"
             . '</li>';
    }

    private function renderAuthUser(): string
    {
        $username = $this->h((string) ($_SESSION['username'] ?? 'n/a'));

        return "<li><span title=\"Authenticated user\" class=\"btn-dev\">{$username}</span></li>";
    }

    private function renderCurrentPage(): string
    {
        $page = $this->h($_GET['page'] ?? 'n/a');

        return "<li><span title=\"Current page\" class=\"btn-dev\">{$page}</span></li>";
    }

    private function renderRequestTime(): string
    {
        $ms = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2);

        return "<li><span title=\"Request time\" class=\"btn-dev\">{$ms} ms</span></li>";
    }

    private function renderMemoryUsage(): string
    {
        $mem = $this->convertBytes(memory_get_usage(true));

        return "<li><span title=\"Memory usage\" class=\"btn-dev\">{$mem}</span></li>";
    }

    private function renderSessionPopover(): string
    {
        $isLoggedIn = $this->checkStatus((bool) ($_SESSION['is_logged_in'] ?? false));
        $sessionUser = $this->h((string) ($_SESSION['username'] ?? 'n/a'));
        $sessionRole = $this->h((string) ($_SESSION['role'] ?? 'n/a'));
        $sessionLabel = session_status() === PHP_SESSION_ACTIVE ? session_id() : 'no session';

        $content = '<table class="table table-sm table-borderless mb-0">'
                 . "<tr><td><strong>Authorized:</strong></td><td>{$isLoggedIn}</td></tr>"
                 . "<tr><td><strong>Username:</strong></td><td>{$sessionUser}</td></tr>"
                 . "<tr><td><strong>Role:</strong></td><td>{$sessionRole}</td></tr>"
                 . '</table>';

        return $this->popoverItem($sessionLabel, $content);
    }

    private function renderDbPopover(): string
    {
        $dbStatus = isset($this->pdo)
            ? '<span class="text-success">✔ Connected</span>'
            : '<span class="text-danger">✘ ' . $this->h($this->pdo_error ?? 'Not connected') . '</span>';

        $dbLabel = isset($this->pdo) ? 'DB connected' : 'DB not connected';

        $content = '<table class="table table-sm table-borderless mb-0">'
                 . "<tr><td><strong>Status:</strong></td><td>{$dbStatus}</td></tr>"
                 . "<tr><td><strong>Host:</strong></td><td>{$this->h($this->host)}</td></tr>"
                 . "<tr><td><strong>Database:</strong></td><td>{$this->h($this->db)}</td></tr>"
                 . "<tr><td><strong>User:</strong></td><td>{$this->h($this->db_user)}</td></tr>"
                 . "<tr><td><strong>Charset:</strong></td><td>{$this->h($this->charset)}</td></tr>"
                 . '</table>';

        return $this->popoverItem($dbLabel, $content);
    }

    private function renderPhpPopover(): string
    {
        $apiStatus = false;

        $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443 ? 'https' : 'http';
        $url = "{$scheme}://phpgit.local/api/v1/health";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FAILONERROR => false,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            Logging::loggingToFile("cURL error: {$error}", 4, true, true);
            error_log("cURL error: {$error}");
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $apiResponse = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Logging::loggingToFile('Failed to decode API response: ' . json_last_error_msg(), 4, true, true);
                error_log('JSON error: ' . json_last_error_msg());
            } elseif ($httpCode >= 400) {
                Logging::loggingToFile("API request failed with status code: {$httpCode}", 4, true, true);
            } else {
                $apiStatus = ($apiResponse['status'] ?? 'unknown') === 'ok';
            }
        }
        curl_close($ch);
        $opcacheState = function_exists('opcache_get_status') && opcache_get_status() !== false;
        $mailStatus = function_exists('mail');

        $isDbRunning = $this->serviceIndicator($this->db_current_state);
        $isOpcacheRunning = $this->serviceIndicator($opcacheState);
        $isMailRunning = $this->serviceIndicator($mailStatus);
        $isApiRunning = $this->serviceIndicator($apiStatus);

        $content = '<table class="table table-sm table-borderless mb-0">'
                 . '<tr><td><strong>PHP version:</strong></td><td>' . PHP_VERSION . '</td></tr>'
                 . '<tr><td><strong>PHP SAPI:</strong></td><td>' . php_sapi_name() . '</td></tr>'
            . "<tr><td><strong>API:</strong></td><td>{$isApiRunning}</td></tr>"
                 . "<tr><td><strong>Database:</strong></td><td>{$isDbRunning}</td></tr>"
                 . "<tr><td><strong>Opcache:</strong></td><td>{$isOpcacheRunning}</td></tr>"
                 . "<tr><td><strong>Mail:</strong></td><td>{$isMailRunning}</td></tr>"
                 . '</table>';

        return $this->popoverItem('version', $content);
    }

    private function popoverItem(string $label, string $content): string
    {
        $safeContent = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        return '<li>'
             . '<span class="btn-dev db-info"'
             . ' data-bs-toggle="popover"'
             . ' data-bs-trigger="hover"'
             . ' data-bs-placement="top"'
             . ' data-bs-html="true"'
             . " data-bs-content=\"{$safeContent}\">"
             . $label
             . '</span>'
             . '</li>';
    }

    public function render(): void
    {
        echo '<div class="fixed-bottom bg-dark text-light border border-dark border-2">';
        echo '<ul class="d-flex align-items-center gap-3 mb-0 list-unstyled">';

        echo $this->renderHttpStatus();
        echo $this->renderAuthUser();
        echo $this->renderCurrentPage();
        echo $this->renderRequestTime();
        echo $this->renderMemoryUsage();

        echo '<div class="ms-auto d-flex gap-3">';
        echo $this->renderSessionPopover();
        echo $this->renderDbPopover();
        echo $this->renderPhpPopover();
        echo '<li><a class="btn btn-dev" href="/index.php?page=phpinfo">phpinfo</a></li>';
        echo '</div>';

        echo '</ul>';
        echo '</div>';
        echo '<script src="/assets/js/dev.js"></script>';
    }
}
