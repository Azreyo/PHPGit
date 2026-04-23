<?php
declare(strict_types=1);

use App\includes\Logging;

Logging::loggingToFile('Page not found: ' . $_SERVER['REQUEST_URI'], 2);
?>
<main>
    <div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 70vh;">
        <div class="text-center">
            <div class="error-code text-primary mb-2">404</div>
            <h2 class="fw-bold mb-2">Page Not Found</h2>
            <p class="text-secondary mb-4">The page you're looking for doesn't exist or has been moved.</p>
            <div class="d-flex gap-3 justify-content-center">
                <a class="btn btn-primary" href="/home">Go Home</a>
                <a class="btn btn-outline-secondary" href="/explore">Explore</a>
            </div>
        </div>
    </div>
</main>