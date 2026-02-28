document.addEventListener('DOMContentLoaded', function () {
    const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
    popovers.forEach(el => new bootstrap.Popover(el, {
        sanitize: false
    }));
});