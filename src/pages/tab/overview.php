<?php

use App\Services\SystemService;

$cpu_usage = SystemService::getCPUUsage();
?>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="admin-metric-card h-100">
            <p class="admin-metric-label">Total Users</p>
            <h2 class="admin-metric-value">1,284</h2>
            <div class="admin-metric-meta text-success">
                <i class="bi bi-arrow-up-right"></i>
                <span>+12.5% this month</span>
            </div>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="admin-metric-card h-100">
            <p class="admin-metric-label">Repositories</p>
            <h2 class="admin-metric-value">452</h2>
            <div class="admin-metric-meta text-primary">
                <i class="bi bi-folder2-open"></i>
                <span>+5 created today</span>
            </div>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="admin-metric-card h-100">
            <p class="admin-metric-label">Security Events</p>
            <h2 class="admin-metric-value">24</h2>
            <div class="admin-metric-meta text-warning-emphasis">
                <i class="bi bi-shield-exclamation"></i>
                <span>2 critical alerts</span>
            </div>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="admin-metric-card h-100">
            <p class="admin-metric-label">Server Load</p>
            <h2 class="admin-metric-value">14%</h2>
            <div class="admin-metric-meta text-info-emphasis">
                <i class="bi bi-speedometer2"></i>
                <span>Avg. latency 45ms</span>
            </div>
        </article>
    </div>
</div>

<section class="admin-panel p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <p class="admin-panel-kicker mb-1">Quick Actions</p>
            <h5 class="fw-bold mb-1">One-click Maintenance</h5>
            <p class="text-secondary small mb-0">Use these shortcuts to run common operational tasks.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary rounded-pill px-3">Scan Security</button>
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3">Clear Cache</button>
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3">Restart Services</button>
        </div>
    </div>
</section>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <section class="admin-panel overflow-hidden h-100">
            <header class="admin-section-head">
                <div>
                    <h5 class="mb-1 fw-bold">Recent Activity</h5>
                    <p class="text-secondary small mb-0">Latest changes across users and repositories</p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-3">All Events</button>
            </header>
            <div class="admin-activity-list">
                <?php
                $activities = [
                        ['user' => 'admin', 'action' => 'Modified system settings', 'time' => '2 mins ago', 'icon' => 'bi-gear-fill', 'color' => 'primary', 'desc' => 'Updated security policies and firewall rules.'],
                        ['user' => 'john_doe', 'action' => 'Created repository: <span class="text-primary fw-bold">web-app-v2</span>', 'time' => '15 mins ago', 'icon' => 'bi-plus-circle-fill', 'color' => 'success', 'desc' => 'New private repository initialized with MIT license.'],
                        ['user' => 'security_bot', 'action' => 'Blocked IP: 192.168.1.1', 'time' => '1 hour ago', 'icon' => 'bi-shield-slash-fill', 'color' => 'danger', 'desc' => 'Detected multiple failed login attempts from this address.'],
                        ['user' => 'alice', 'action' => 'Pushed to <span class="text-primary fw-bold">phpgit-core</span>', 'time' => '3 hours ago', 'icon' => 'bi-git', 'color' => 'info', 'desc' => 'Committed 12 new changes to the main branch.'],
                ];
                foreach ($activities as $act):
                    ?>
                    <article class="admin-activity-item">
                        <div class="admin-activity-icon text-<?php echo $act['color']; ?> bg-<?php echo $act['color']; ?> bg-opacity-10">
                            <i class="bi <?php echo $act['icon']; ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                                <h6 class="mb-0 fw-semibold">@<?php echo $act['user']; ?></h6>
                                <small class="text-secondary"><?php echo $act['time']; ?></small>
                            </div>
                            <p class="mb-1 text-body-emphasis"><?php echo $act['action']; ?></p>
                            <p class="mb-0 text-secondary small"><?php echo $act['desc']; ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <div class="col-12 col-lg-4">
        <section class="admin-panel p-4 h-100">
            <h5 class="fw-bold mb-4">System Health</h5>

            <div class="admin-health-block mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span>CPU Usage</span>
                    <strong><?php echo $cpu_usage; ?>%</strong>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-primary" style="width: <?php echo $cpu_usage; ?>%" role="progressbar"></div>
                </div>
            </div>

            <div class="admin-health-block mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span>Memory Usage</span>
                    <strong>30%</strong>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-success" style="width: 30%" role="progressbar"></div>
                </div>
            </div>

            <div class="admin-health-block mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span>Disk Space</span>
                    <strong>78%</strong>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-warning" style="width: 78%" role="progressbar"></div>
                </div>
            </div>

            <div class="admin-status-card bg-success-subtle text-success-emphasis border border-success-subtle">
                <small class="text-uppercase fw-bold">Database Cluster</small>
                <h6 class="mb-1 mt-2 fw-bold">Operational</h6>
                <p class="mb-0 small">Uptime: 14d 2h 15m</p>
            </div>
        </section>
    </div>
</div>
