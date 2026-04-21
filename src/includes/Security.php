<?php

declare(strict_types=1);

namespace App\includes;

use Random\RandomException;

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

    /**
     * @throws RandomException
     */
    public function generateCsrfToken(): string
    {
        if ($this->csrf_token === 'n/a') {
            $this->csrf_token = bin2hex(random_bytes(32));
        }
        $_SESSION['csrf_token'] = $this->csrf_token;

        return $this->csrf_token;
    }

    public function validateCsrfToken(string $token): bool
    {
        if ($this->csrf_token === 'n/a' || ! hash_equals($this->csrf_token, $token)) {
            return false;
        }

        return true;
    }

    public function isRateLimited(): bool
    {
        $maxAttempts = 5;
        $windowSeconds = 900;
        $now = time();

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
        $this->login_attempts = $this->login_attempts + 1;
        $_SESSION['login_attempts'] = $this->login_attempts;
        $_SESSION['login_attempt_time'] = $this->login_attempt_time;
    }

    public function sanitizeInput(string $input): string
    {
        $input = trim($input);

        return htmlspecialchars(str_replace(['<', '>', '//', '\\\\'], '', $input));
    }

    public function sanitizeTab(string $tab): string
    {
        return strtolower(preg_replace('/[^a-z0-9_-]/', '', $tab));
    }

    public static function sanitizeShellInput(string $input): string
    {
        $input = trim($input);

        return preg_replace('/[^a-zA-Z0-9._:\/\- ]/', '', $input);
    }
}
