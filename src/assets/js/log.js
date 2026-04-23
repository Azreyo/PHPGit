document.addEventListener('DOMContentLoaded', function () {
    const logShell = document.querySelector('.admin-log-shell');
    if (!logShell) {
        return;
    }

    const endpoint = logShell.dataset.logsEndpoint || '';
    const limitForm = document.getElementById('log-limit-form');
    const limitInput = document.getElementById('log-search-by-int');
    const tableBody = document.getElementById('log-table-body');
    const footerCount = document.getElementById('log-footer-count');
    const fetchError = document.getElementById('log-fetch-error');
    const filterButtons = document.querySelectorAll('.log-level-filter');
    const clearFiltersButton = document.getElementById('log-clear-filters');

    if (!tableBody || endpoint === '') {
        return;
    }

    let activeLevel = '';

    const getLevelClass = function (level) {
        switch (level) {
            case 'Critical':
            case 'Error':
                return 'text-danger';
            case 'Warning':
                return 'text-warning';
            case 'Success':
                return 'text-success';
            case 'Debug':
                return 'text-secondary';
            default:
                return 'text-info';
        }
    };

    const showFetchError = function (message) {
        if (!fetchError) {
            return;
        }

        if (message === '') {
            fetchError.classList.add('d-none');
            fetchError.textContent = '';
            return;
        }

        fetchError.textContent = message;
        fetchError.classList.remove('d-none');
    };

    const applyFilter = function (level) {
        const logRows = tableBody.querySelectorAll('tr');

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

    const updateCounters = function (counters) {
        const critical = document.getElementById('log-count-critical');
        const error = document.getElementById('log-count-error');
        const warning = document.getElementById('log-count-warning');
        const info = document.getElementById('log-count-info');

        if (critical) {
            critical.textContent = String(counters.critical || 0);
        }
        if (error) {
            error.textContent = String(counters.error || 0);
        }
        if (warning) {
            warning.textContent = String(counters.warning || 0);
        }
        if (info) {
            info.textContent = String(counters.info || 0);
        }
    };

    const renderLogs = function (logs) {
        tableBody.innerHTML = '';

        logs.forEach(function (log) {
            const row = document.createElement('tr');
            row.dataset.logLevel = String(log.level || '').toLowerCase();

            const timeCell = document.createElement('td');
            timeCell.className = 'ps-4 text-secondary';
            timeCell.textContent = String(log.time || '');

            const levelCell = document.createElement('td');
            const levelBadge = document.createElement('span');
            levelBadge.className = 'admin-log-badge ' + getLevelClass(String(log.level || ''));
            levelBadge.textContent = String(log.level || '');
            levelCell.appendChild(levelBadge);

            const messageCell = document.createElement('td');
            messageCell.className = 'pe-4 text-light';
            messageCell.textContent = String(log.msg || '');

            row.appendChild(timeCell);
            row.appendChild(levelCell);
            row.appendChild(messageCell);
            tableBody.appendChild(row);
        });
    };

    const updateFooterCount = function (count) {
        if (!footerCount) {
            return;
        }

        footerCount.textContent = 'Showing latest ' + count + ' entries';
    };

    const fetchLogs = function (limit) {
        const params = new URLSearchParams();
        params.set('limit', String(limit));

        showFetchError('');

        return fetch(endpoint + '?' + params.toString(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                return response.json().catch(function () {
                    return null;
                }).then(function (payload) {
                    return {response: response, payload: payload};
                });
            })
            .then(function (result) {
                const response = result.response;
                const payload = result.payload;

                if (!response.ok || !payload || !Array.isArray(payload.logs)) {
                    const message = payload && payload.error
                        ? String(payload.error)
                        : 'Failed to fetch logs.';
                    throw new Error(message);
                }

                renderLogs(payload.logs);
                updateCounters(payload.counters || {});
                updateFooterCount(Number(payload.count || payload.logs.length));
                applyFilter(activeLevel);

                if (limitInput && payload.limit) {
                    limitInput.value = String(payload.limit);
                }
            })
            .catch(function (error) {
                const message = error instanceof Error
                    ? error.message
                    : 'Failed to fetch logs.';
                showFetchError(message);
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

    if (limitForm) {
        limitForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const parsedLimit = Number.parseInt(limitInput ? limitInput.value : '', 10);
            const validLimit = Number.isNaN(parsedLimit)
                ? 100
                : Math.min(Math.max(parsedLimit, 1), 1000);

            fetchLogs(validLimit);
        });
    }

    const initialLimit = Number.parseInt(limitInput ? limitInput.value : '', 10);
    const startupLimit = Number.isNaN(initialLimit)
        ? 100
        : Math.min(Math.max(initialLimit, 1), 1000);
    fetchLogs(startupLimit);
});