let has_run = false;
const recentSecurityLogLimit = 5;
const recentSecurityLogList = (
    document.getElementById("recent-security-log-list")
);
const clearCacheButton = (
    document.getElementById("overview-clear-cache-btn")
);
const restartServicesButton = (
    document.getElementById("overview-restart-services-btn")
);
const actionStatus = (
    document.getElementById("overview-action-status")
);
const serverLoadValue = (
    document.getElementById("server-load")
);
const serverLatencyValue = (
    document.getElementById("server-latency")
);

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function resolveLogPresentation(level) {
    const loweredLevel = String(level || "").toLowerCase();

    if (loweredLevel === "critical" || loweredLevel === "error") {
        return {
            color: "danger",
            icon: "bi-exclamation-octagon-fill"
        };
    }

    if (loweredLevel === "warning") {
        return {
            color: "warning",
            icon: "bi-exclamation-triangle-fill"
        };
    }

    if (loweredLevel === "info") {
        return {
            color: "info",
            icon: "bi-shield-check"
        };
    }

    return {
        color: "secondary",
        icon: "bi-info-circle-fill"
    };
}

function showActionStatus(message, type) {
    if (!actionStatus) {
        return;
    }

    actionStatus.classList.remove(
        "d-none",
        "alert-success",
        "alert-danger",
        "alert-info"
    );
    actionStatus.classList.add(
        type === "success"
            ? "alert-success"
            : type === "error"
                ? "alert-danger"
                : "alert-info"
    );
    actionStatus.textContent = message;
}

function renderRecentSecurityLogs(logs) {
    if (!recentSecurityLogList) {
        return;
    }

    if (!Array.isArray(logs) || logs.length === 0) {
        recentSecurityLogList.innerHTML = `
          <p class="mb-0 text-secondary small px-4 py-4">
            No recent security events were found.
          </p>
        `;
        return;
    }

    const entries = logs.map(function (log) {
        const style = resolveLogPresentation(log.level);
        const level = escapeHtml(log.level || "Info");
        const time = escapeHtml(log.time || "Just now");
        const message = escapeHtml(log.msg || "No message provided.");

        return "<article class=\"admin-activity-item\">" +
            "<div class=\"admin-activity-icon text-" +
            style.color +
            " bg-" +
            style.color +
            " bg-opacity-10\">" +
            "<i class=\"bi " + style.icon + "\"></i>" +
            "</div>" +
            "<div class=\"flex-grow-1\">" +
            "<div class=\"d-flex justify-content-between " +
            "align-items-start mb-1 gap-2\">" +
            "<h6 class=\"mb-0 fw-semibold\">" + level + "</h6>" +
            "<small class=\"text-secondary\">" + time + "</small>" +
            "</div>" +
            "<p class=\"mb-0 text-secondary small\">" + message + "</p>" +
            "</div>" +
            "</article>";
    });

    recentSecurityLogList.innerHTML = entries.join("");
}

async function getRecentSecurityLogs() {
    try {
        const response = await fetch("/api/v1/getLogs.php?limit=" + (
            recentSecurityLogLimit + "&security=1")
        );
        const data = await response.json();

        if (!response.ok) {
            const message = data?.error || "Could not fetch security logs";
            return Promise.reject(new Error(message));
        }

        renderRecentSecurityLogs(data.logs || []);
    } catch (error) {
        console.error("Fetch error:", error);
        renderRecentSecurityLogs([]);
    }
}

async function runMaintenanceAction(
    endpoint, loadingMessage, successFallbackMessage
) {
    if (clearCacheButton) {
        clearCacheButton.disabled = true;
    }
    if (restartServicesButton) {
        restartServicesButton.disabled = true;
    }

    showActionStatus(loadingMessage, "info");

    try {
        const response = await fetch(endpoint, {
            body: "{}",
            headers: {
                "Content-Type": "application/json"
            },
            method: "POST"
        });
        const data = await response.json();

        if (!response.ok) {
            const message = "Could not perform maintenance action";
            const errorMessage = data?.error || message;
            return Promise.reject(new Error(errorMessage));
        }

        showActionStatus(data.message || successFallbackMessage, "success");
        await Promise.all([
            getDashboardData(),
            getRecentSecurityLogs()
        ]);
    } catch (error) {
        console.error("Maintenance action error:", error);
        const messageAct = error?.message ?? "Maintenance action failed";

        showActionStatus(messageAct, "error");
    } finally {
        if (clearCacheButton) {
            clearCacheButton.disabled = false;
        }
        if (restartServicesButton) {
            restartServicesButton.disabled = false;
        }
    }
}

function initMaintenanceButtons() {
    if (clearCacheButton) {
        clearCacheButton.addEventListener("click", function () {
            runMaintenanceAction(
                "/api/v1/clearCache.php",
                "Clearing application caches...",
                "Cache cleared successfully"
            );
        });
    }

    if (restartServicesButton) {
        restartServicesButton.addEventListener("click", function () {
            runMaintenanceAction("/api/v1/restartServices.php",
                "Restarting services...",
                "Service restart request submitted");
        });
    }
}

async function updateCPUUsage() {
    const startedAt = performance.now();

    try {
        const response = await fetch("/api/v1/system/cpu");

        if (response.ok) {
            const data = await response.json();
            const pct = Math.round(data.cpu_usage_percent) + "%";
            const latencyMs = Math.max(
                0,
                Math.round(performance.now() - startedAt)
            );
            document.getElementById("cpu-usage").textContent = pct;
            document.getElementById("cpu-progress-bar").style.width = pct;

            if (serverLoadValue) {
                serverLoadValue.textContent = pct;
            }

            if (serverLatencyValue) {
                serverLatencyValue.textContent = `Avg. latency ${latencyMs}ms`;
            }
        }
    } catch (error) {
        console.error("Fetch error:", error);
    }
}

async function updateMemoryUsage() {
    try {
        const response = await fetch("/api/v1/system/memory");
        if (response.ok) {
            const data = await response.json();
            const pct = Math.round(data.memory_usage_percent) + "%";
            document.getElementById("mem-usage").textContent = pct;
            document.getElementById("mem-progress-bar").style.width = pct;
        }
    } catch (error) {
        console.error("Fetch error:", error);
    }
}

async function updateDiskUsage() {
    try {
        const response = await fetch("/api/v1/system/disk");
        if (response.ok) {
            const data = await response.json();
            const pct = Math.round(data.disk_space_percent) + "%";
            document.getElementById("disk-space").textContent = pct;
            document.getElementById("disk-progress-bar").style.width = pct;
        }
    } catch (error) {
        console.error("Fetch error:", error);
    }
}

async function update() {
    try {
        await Promise.all([
            updateCPUUsage(),
            updateMemoryUsage(),
            updateDiskUsage()
        ]);
    } catch (error) {
        console.error("Update error:", error);
    }
}

async function getDashboardData() {
    try {
        const response = await fetch("/api/v1/getDashboardInfo");
        if (response.ok) {
            const data = await response.json();
            const users = data.total_users;
            const repos = data.total_repos;
            const logs = data.total_security_logs;
            document.getElementById("total-users").textContent = users;
            document.getElementById("total-repositories").textContent = repos;
            document.getElementById("total-security-events").textContent = logs;
        }
    } catch (error) {
        console.error("Fetch error:", error);
    }
}

async function getDatabaseUptime() {
    try {
        const response = await fetch("/api/v1/getDatabaseUptime");
        if (response.ok) {
            const data = await response.json();
            const minutes = Math.floor(data.uptime / 60);
            const hours = Math.floor(minutes / 60);
            const uptimeStr = hours + "h " + (minutes % 60) + "m";
            document.getElementById("database-uptime").textContent = uptimeStr;
        }
    } catch (error) {
        console.error("Fetch error:", error);
    }
}

function runOnce() {
    if (!has_run) {
        has_run = true;
        getDashboardData();
        getRecentSecurityLogs();
        initMaintenanceButtons();
    }
}

runOnce();

async function runUpdateLoop() {
    await update();
    setTimeout(runUpdateLoop, 20000);
}

async function runDatabaseUptimeLoop() {
    await getDatabaseUptime();
    setTimeout(runDatabaseUptimeLoop, 60000);
}

runUpdateLoop();
runDatabaseUptimeLoop();