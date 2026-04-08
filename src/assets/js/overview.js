let has_run = false;
async function updateCPUUsage() {
    try {
        const response = await fetch("/api/v1/system/cpu");
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        const cpu_usage = data.cpu_usage_percent;
        document.getElementById('cpu-usage').textContent = Math.round(cpu_usage) + '%';
        document.getElementById('cpu-progress-bar').style.width = Math.round(cpu_usage) + '%';
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

async function updateMemoryUsage() {
    try {
        const response = await fetch("/api/v1/system/memory");
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        const mem_usage = data.memory_usage_percent;
        document.getElementById('mem-usage').textContent = Math.round(mem_usage) + '%';
        document.getElementById('mem-progress-bar').style.width = Math.round(mem_usage) + '%';
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

async function updateDiskUsage() {
    try {
        const response = await fetch("/api/v1/system/disk");
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        const disk_usage = data.disk_space_percent;
        document.getElementById('disk-space').textContent = Math.round(disk_usage) + '%';
        document.getElementById('disk-progress-bar').style.width = Math.round(disk_usage) + '%';
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

async function Update() {
    try {
        await Promise.all([
            updateCPUUsage(),
            updateMemoryUsage(),
            updateDiskUsage()
        ]);
    } catch (error) {
        console.error('Update error:', error);
    }
}

async function getDashboardData() {
    try {
        const response = await fetch("/api/v1/getDashboardInfo");
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        document.getElementById('total-users').textContent = data.total_users;
        document.getElementById('total-repositories').textContent = data.total_repos;
        document.getElementById('total-security-events').textContent = data.total_security_logs;
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

async function getDatabaseUptime() {
    try {
        const response = await fetch("/api/v1/getDatabaseUptime");
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        const uptime = data.uptime;
        const minutes = Math.floor(uptime / 60);
        const hours = Math.floor(minutes / 60);
        document.getElementById('database-uptime').textContent = `${hours}h ${minutes % 60}m`;
    } catch (error) {
        console.error('Fetch error:', error);
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
    await Update();
    setTimeout(runUpdateLoop, 20000);
}

async function runDatabaseUptimeLoop() {
    await getDatabaseUptime();
    setTimeout(runDatabaseUptimeLoop, 60000);
}

runUpdateLoop();
runDatabaseUptimeLoop();