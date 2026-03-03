<?php

declare(strict_types=1);

http_response_code(403);
?>
<main>
    <div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 70vh;">
        <div class="text-center">
            <div class="error-code text-danger mb-2">403</div>
            <h2 class="fw-bold mb-2">Access Forbidden</h2>
            <p class="text-secondary mb-4">You don't have permission to access this page.</p>
            <a class="btn btn-primary" href="index.php?page=home">Back to Home</a>
        </div>
    </div>
</main>