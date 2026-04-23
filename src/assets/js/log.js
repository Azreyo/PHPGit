document.addEventListener('DOMContentLoaded', function () {
    const filterButtons = document.querySelectorAll('.log-level-filter');
    const clearFiltersButton = document.getElementById('log-clear-filters');
    const logRows = document.querySelectorAll('.admin-log-table tbody tr');

    if (filterButtons.length === 0 || logRows.length === 0) {
        return;
    }

    let activeLevel = '';

    const applyFilter = function (level) {
        logRows.forEach(function (row) {
            const rowLevel = row.dataset.logLevel || '';
            row.style.display = level === '' || rowLevel === level ? '' : 'none';
        });

        filterButtons.forEach(function (button) {
            const isActive = (button.dataset.level || '').toLowerCase() === level;
            button.classList.toggle('btn-outline-light', !isActive);
            button.classList.toggle('btn-light', isActive);
            button.classList.toggle('text-dark', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const selectedLevel = (button.dataset.level || '').toLowerCase();
            activeLevel = activeLevel === selectedLevel ? '' : selectedLevel;
            applyFilter(activeLevel);
        });
    });

    if (clearFiltersButton) {
        clearFiltersButton.addEventListener('click', function () {
            activeLevel = '';
            applyFilter(activeLevel);
        });
    }
});