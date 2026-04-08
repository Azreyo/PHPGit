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
        const response = await fetch("api/v1/system/memory");
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
        const response = await fetch("api/v1/system/disk");
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        const mem_usage = data.disk_space_percent;

        document.getElementById('disk-space').textContent = Math.round(mem_usage) + '%';
        document.getElementById('disk-progress-bar').style.width = Math.round(mem_usage) + '%';
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

async function Update() {
    updateCPUUsage();
    updateMemoryUsage();
    updateDiskUsage();
}

async function getDashboardData() {
    const response = await fetch("api/v1/getDashboardInfo");
    if (!response.ok) throw new Error('Network response was not ok');
    const data = await response.json();
    const total_users = data.total_users;
    const total_repositories = data.total_repos;
    const total_services = data.total_security_logs;
    document.getElementById('total-users').textContent = total_users;
    document.getElementById('total-repositories').textContent = total_repositories;
    document.getElementById('total-security-events').textContent = total_services;
}

function runOnce() {
    if (!has_run) {
        has_run = true;
        getDashboardData();
    }
}

runOnce();
Update();
setInterval(Update, 20000);