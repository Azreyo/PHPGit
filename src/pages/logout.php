<?php
declare(strict_types=1);
$_SESSION['is_logged_in'] = false;
session_destroy();
?>

<main>
    <div class="container text-center">
        <p>You were successfully logged out!</p>
        <a class="btn btn-primary" href="index.php?page=home">Go home</a>
    </div>
</main>