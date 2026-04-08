<?php
declare(strict_types=1);

use App\includes\Logging;

Logging::loggingToFile("URI request too long: " . $_SERVER['REQUEST_URI'], 3, true);
?>
<main>
    <div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 70vh;">
        <div class="text-center">
            <div class="error-code text-warning mb-2">414</div>
            <h2 class="fw-bold mb-2">URI request too long</h2>
            <p class="text-secondary mb-4">Sorry but your request is too long.</p>
            <a class="btn btn-primary" href="/Index.php?page=home">Back to Home</a>
        </div>
    </div>
</main>