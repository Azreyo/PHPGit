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
        const mem_usage = data.mem_usage_percent;

        document.getElementById('mem-usage').textContent = Math.round(mem_usage) + '%';
        document.getElementById('mem-progress-bar').style.width = Math.round(mem_usage) + '%';
    } catch (error) {
        console.error('Fetch error:', error);
    }
}
updateCPUUsage();
setInterval(updateCPUUsage, 60000);