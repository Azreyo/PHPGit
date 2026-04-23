<?php use App\includes\Assets; ?>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="admin-metric-card h-100">
            <p class="admin-metric-label bi bi-person">Total Users</p>
            <h2 class="admin-metric-value" id="total-users">0</h2>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="admin-metric-card h-100">
            <p class="admin-metric-label">Repositories</p>
            <h2 class="admin-metric-value" id="total-repositories">0</h2>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="admin-metric-card h-100">
            <p class="admin-metric-label">Security Events</p>
            <h2 class="admin-metric-value" id="total-security-events">0</h2>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="admin-metric-card h-100">
            <p class="admin-metric-label">Server Load</p>
            <h2 class="admin-metric-value" id="server-load">0%</h2>
            <div class="admin-metric-meta text-info-emphasis">
                <i class="bi bi-speedometer2"></i>
                <span id="server-latency">Avg. latency --ms</span>
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
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" id="overview-clear-cache-btn">
                Clear Cache
            </button>
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3"
                    id="overview-restart-services-btn">Restart Services
            </button>
            <div class="alert d-none mb-0 py-2 px-3" id="overview-action-status" role="status"></div>
        </div>
    </div>
</section>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <section class="admin-panel overflow-hidden h-100">
            <header class="admin-section-head">
                <div>
                    <h5 class="mb-1 fw-bold">Recent Security Log</h5>
                    <p class="text-secondary small mb-0">Latest alerts and operational security events</p>
                </div>
                <a href="/dashboard?tab=logs" class="btn btn-sm btn-outline-secondary rounded-3">All Events</a>
            </header>
            <div class="admin-activity-list" id="recent-security-log-list">
                <p class="mb-0 text-secondary small px-4 py-4" id="recent-security-log-placeholder">
                    Loading recent security logs...
                </p>
            </div>
        </section>
    </div>
    <div class="col-12 col-lg-4">
        <section class="admin-panel p-4 h-100">
            <h5 class="fw-bold mb-4">System Health</h5>

            <div class="admin-health-block mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span>CPU Usage</span>
                    <strong id="cpu-usage"> 100%</strong>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-primary" id="cpu-progress-bar" style="width: 100%" role="progressbar"></div>
                </div>
            </div>

            <div class="admin-health-block mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span>Memory Usage</span>
                    <strong id="mem-usage">100%</strong>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-success" id="mem-progress-bar" style="width: 100%" role="progressbar"></div>
                </div>
            </div>

            <div class="admin-health-block mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span>Disk Space</span>
                    <strong id="disk-space">100%</strong>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-warning" id="disk-progress-bar" style="width: 100%" role="progressbar"></div>
                </div>
            </div>

            <div class="admin-status-card bg-success-subtle text-success-emphasis border border-success-subtle">
                <small class="text-uppercase fw-bold">Database Cluster</small>
                <h6 class="mb-1 mt-2 fw-bold">Operational</h6>
                <p class="mb-0 small" id="database-uptime">Uptime: 0d 0h 0m</p>
            </div>
        </section>
    </div>
</div>
<script src="<?= Assets::url('/assets/js/overview.js') ?>"></script>
