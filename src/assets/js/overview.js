let has_run = false;

async function updateCPUUsage() {
    try {
        const response = await fetch("/api/v1/system/cpu");
        if (response.ok) {
            const data = await response.json();
            const pct = Math.round(data.cpu_usage_percent) + "%";
            document.getElementById("cpu-usage").textContent = pct;
            document.getElementById("cpu-progress-bar").style.width = pct;
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