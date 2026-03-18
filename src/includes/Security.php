<?php
declare(strict_types=1);
namespace App\includes;

class Security
{

    private string $csrf_token;
    private int $login_attempts;
    private int $login_attempt_time;
    public function __construct()
    {
        $this->csrf_token = $_SESSION['csrf_token'] ?? 'n/a';
        $this->login_attempts = $_SESSION['login_attempts'] ?? 0;
        $this->login_attempt_time = isset($_SESSION['login_attempt_time'])
            ? (int) $_SESSION['login_attempt_time']
            : time();

    }
    public function generateCsrfToken(): string
    {
        if (empty($this->csrf_token)) {
            $this->csrf_token = bin2hex(random_bytes(32));
        }

        return $this->csrf_token;
    }

    public function validateCsrfToken(string $token): bool
    {
        echo $this->csrf_token;
        return isset($this->csrf_token) && hash_equals($this->csrf_token, $token);
    }

    public function isRateLimited(): bool
    {
        $maxAttempts = 5;
        $windowSeconds = 900;
        $now = time();

        if (!isset($this->login_attempts)) {
            $this->login_attempts = 0;
            $this->login_attempt_time = $now;
            $_SESSION['login_attempt_time'] = $this->login_attempt_time;
            $_SESSION['login_attempts'] = $this->login_attempts;
        }

        if (($now - $this->login_attempt_time) > $windowSeconds) {
            $this->login_attempts = 0;
            $this->login_attempt_time = $now;
            $_SESSION['login_attempt_time'] = $this->login_attempt_time;
            $_SESSION['login_attempts'] = $this->login_attempts;
        }

        return $this->login_attempts >= $maxAttempts;
    }

    public function recordFailedAttempt(): void
    {
        $this->login_attempts = ($this->login_attempts ?? 0) + 1;
        $_SESSION['login_attempts'] = $this->login_attempts;
        $_SESSION['login_attempt_time'] = $this->login_attempt_time;
    }

}