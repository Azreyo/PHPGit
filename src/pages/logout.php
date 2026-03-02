<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION['is_logged_in'] = false;

// Optionally clear all session data before destroying the session.
$_SESSION = [];
session_destroy();
?>

<main>
    <div class="container text-center">
        <p>You were successfully logged out!</p>
        <a class="btn btn-primary" href="index.php?page=home">Go home</a>
    </div>
</main>