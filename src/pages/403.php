<?php
declare(strict_types=1);

use App\includes\Logging;

function easterEgg($percent): bool
{
    return mt_rand(1, 100) <= $percent;
}

Logging::loggingToFile('Unauthorized attempt: ' . $_SERVER['REQUEST_URI'], 3, true);
?>
<main>
    <div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 70vh;">
        <div class="text-center">
            <div class="error-code text-danger mb-2">403</div>
            <h2 class="fw-bold mb-2">Access Forbidden</h2>
            <p class="text-secondary mb-4">You don't have permission to access this page.</p>
            <a class="btn btn-primary" href="/index.php?page=home">Back to Home</a>
        </div>
        <?php if (easterEgg(1)): ?>
            <link rel="stylesheet" href="/assets/css/easter.css">
            <div id="overlay">
                <svg id="wheel" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="300" cy="300" r="170"
                            fill="none"
                            stroke="#c8a84b"
                            stroke-width="20"/>

                    <circle cx="300" cy="300" r="40"
                            fill="#c8a84b"/>

                    <g stroke="#c8a84b" stroke-width="12" stroke-linecap="round">
                        <line x1="300" y1="300" x2="300" y2="130"/>
                        <line x1="300" y1="300" x2="420.21" y2="179.79"/>
                        <line x1="300" y1="300" x2="470" y2="300"/>
                        <line x1="300" y1="300" x2="420.21" y2="420.21"/>
                        <line x1="300" y1="300" x2="300" y2="470"/>
                        <line x1="300" y1="300" x2="179.79" y2="420.21"/>
                        <line x1="300" y1="300" x2="130" y2="300"/>
                        <line x1="300" y1="300" x2="179.79" y2="179.79"/>
                    </g>

                    <g stroke="#c8a84b" stroke-width="10" stroke-linecap="round">
                        <line x1="300" y1="110" x2="300" y2="80"/>
                        <line x1="434.35" y1="165.65" x2="455" y2="145"/>
                        <line x1="490" y1="300" x2="520" y2="300"/>
                        <line x1="434.35" y1="434.35" x2="455" y2="455"/>
                        <line x1="300" y1="490" x2="300" y2="520"/>
                        <line x1="165.65" y1="434.35" x2="145" y2="455"/>
                        <line x1="110" y1="300" x2="80" y2="300"/>
                        <line x1="165.65" y1="165.65" x2="145" y2="145"/>
                    </g>

                    <g fill="#c8a84b">
                        <circle cx="300" cy="60" r="25"/>
                        <circle cx="470" cy="130" r="25"/>
                        <circle cx="540" cy="300" r="25"/>
                        <circle cx="470" cy="470" r="25"/>
                        <circle cx="300" cy="540" r="25"/>
                        <circle cx="130" cy="470" r="25"/>
                        <circle cx="60" cy="300" r="25"/>
                        <circle cx="130" cy="130" r="25"/>
                    </g>

                </svg>
            </div>
            <script src="/assets/js/easter.js"></script>
        <?php endif; ?>
    </div>
</main>