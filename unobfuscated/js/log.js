document.addEventListener("DOMContentLoaded", function () {
    const logShell = document.querySelector(".admin-log-shell");
    if (!logShell) {
        return;
    }

    const endpoint = logShell.dataset.logsEndpoint || "";
    const limitForm = document.getElementById("log-limit-form");
    const limitInput = document.getElementById("log-search-by-int");
    const tableBody = document.getElementById("log-table-body");
    const footerCount = document.getElementById("log-footer-count");
    const fetchError = document.getElementById("log-fetch-error");
    const filterButtons = document.querySelectorAll(".log-level-filter");
    const clearFiltersButton = document.getElementById("log-clear-filters");

    if (!tableBody || endpoint === "") {
        return;
    }

    let activeLevel = "";

    const getLevelClass = function (level) {
        if (level === "Critical" || level === "Error") {
            return "text-danger";
        }
        if (level === "Warning") {
            return "text-warning";
        }
        if (level === "Success") {
            return "text-success";
        }
        if (level === "Debug") {
            return "text-secondary";
        }

        return "text-info";
    };

    const showFetchError = function (message) {
        if (!fetchError) {
            return;
        }

        if (message === "") {
            fetchError.classList.add("d-none");
            fetchError.textContent = "";
            return;
        }

        fetchError.textContent = message;
        fetchError.classList.remove("d-none");
    };

    const applyFilter = function (level) {
        const logRows = tableBody.querySelectorAll("tr");

        logRows.forEach(function (row) {
            const rowLevel = row.dataset.logLevel || "";
            if (level === "" || rowLevel === level) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });

        filterButtons.forEach(function (button) {
            const buttonLevel = String(button.dataset.level || "");
            const isActive = buttonLevel.toLowerCase() === level;
            button.classList.toggle("btn-outline-light", !isActive);
            button.classList.toggle("btn-light", isActive);
            button.classList.toggle("text-dark", isActive);
            if (isActive) {
                button.setAttribute("aria-pressed", "true");
            } else {
                button.setAttribute("aria-pressed", "false");
            }
        });
    };

    const updateCounters = function (counters) {
        const critical = document.getElementById("log-count-critical");
        const error = document.getElementById("log-count-error");
        const warning = document.getElementById("log-count-warning");
        const info = document.getElementById("log-count-info");

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
        tableBody.innerHTML = "";

        logs.forEach(function (log) {
            const row = document.createElement("tr");
            row.dataset.logLevel = String(log.level || "").toLowerCase();

            const timeCell = document.createElement("td");
            timeCell.className = "ps-4 text-secondary";
            timeCell.textContent = String(log.time || "");

            const levelCell = document.createElement("td");
            const levelBadge = document.createElement("span");
            const levelClass = getLevelClass(String(log.level || ""));
            levelBadge.className = "admin-log-badge " + levelClass;
            levelBadge.textContent = String(log.level || "");
            levelCell.appendChild(levelBadge);

            const messageCell = document.createElement("td");
            messageCell.className = "pe-4 text-light";
            messageCell.textContent = String(log.msg || "");

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

        footerCount.textContent = "Showing latest " + count + " entries";
    };

    const fetchLogs = function (limit) {
        const params = new URLSearchParams();

        params.set("limit", String(limit));

        showFetchError("");

        return fetch(endpoint + "?" + params.toString(), {
            headers: {
                "Accept": "application/json"
            },
            method: "GET"
        }).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (payload) {
                return {
                    payload,
                    response
                };
            });
        }).then(function (result) {
            const response = result.response;
            const payload = result.payload;
            let message = "Failed to fetch logs.";

            if (!response.ok || !payload || !Array.isArray(payload.logs)) {
                if (payload && payload.error) {
                    message = String(payload.error);
                }
                showFetchError(message);
                return;
            }

            renderLogs(payload.logs);
            updateCounters(payload.counters || {});
            updateFooterCount(Number(payload.count || payload.logs.length));
            applyFilter(activeLevel);

            if (limitInput && payload.limit) {
                limitInput.value = String(payload.limit);
            }
        }).catch(function (error) {
            let message = "Failed to fetch logs.";
            if (error && typeof error.message === "string") {
                message = error.message;
            }
            showFetchError(message);
        });
    };

    filterButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const buttonLevel = String(button.dataset.level || "");
            const selectedLevel = buttonLevel.toLowerCase();
            if (activeLevel === selectedLevel) {
                activeLevel = "";
            } else {
                activeLevel = selectedLevel;
            }
            applyFilter(activeLevel);
        });
    });

    if (clearFiltersButton) {
        clearFiltersButton.addEventListener("click", function () {
            activeLevel = "";
            applyFilter(activeLevel);
        });
    }

    if (limitForm) {
        limitForm.addEventListener("submit", function (event) {
            let inputValue = "";
            let validLimit = 100;
            event.preventDefault();
            if (limitInput) {
                inputValue = limitInput.value;
            }

            const parsedLimit = Number.parseInt(inputValue, 10);
            if (!Number.isNaN(parsedLimit)) {
                validLimit = Math.min(Math.max(parsedLimit, 1), 1000);
            }

            fetchLogs(validLimit);
        });
    }

    let initialLimitValue = "";
    let startupLimit = 100;
    if (limitInput) {
        initialLimitValue = limitInput.value;
    }

    const initialLimit = Number.parseInt(initialLimitValue, 10);
    if (!Number.isNaN(initialLimit)) {
        startupLimit = Math.min(Math.max(initialLimit, 1), 1000);
    }

    fetchLogs(startupLimit);
});