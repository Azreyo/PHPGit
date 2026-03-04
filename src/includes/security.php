<?php
declare(strict_types=1);
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function isRateLimited(): bool
{
    $maxAttempts = 5;
    $windowSeconds = 900;
    $now = time();

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts']     = 0;
        $_SESSION['login_attempt_time'] = $now;
    }

    if (($now - $_SESSION['login_attempt_time']) > $windowSeconds) {
        $_SESSION['login_attempts']     = 0;
        $_SESSION['login_attempt_time'] = $now;
    }

    return $_SESSION['login_attempts'] >= $maxAttempts;
}

function recordFailedAttempt(): void
{
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
}
