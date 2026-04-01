<section class="admin-panel p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <p class="admin-panel-kicker mb-1">Monitoring</p>
            <h5 class="mb-1 fw-bold">System Log Stream</h5>
            <p class="text-secondary small mb-0">Track security events, diagnostics, and operator actions in real
                time.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary rounded-3">Export</button>
            <button type="button" class="btn btn-outline-danger rounded-3">Wipe Logs</button>
        </div>
    </div>
</section>

<section class="admin-log-shell">
    <header class="admin-log-toolbar">
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-light rounded-pill">Critical (2)</button>
            <button type="button" class="btn btn-sm btn-outline-light rounded-pill">Warning (5)</button>
            <button type="button" class="btn btn-sm btn-outline-light rounded-pill">Info (128)</button>
            <button type="button" class="btn btn-sm btn-secondary rounded-pill">Clear Filters</button>
        </div>
        <div class="input-group input-group-sm" style="max-width: 340px;">
            <span class="input-group-text bg-transparent border-light border-opacity-25 text-light"><i
                        class="bi bi-search"></i></span>
            <input type="text" class="form-control bg-transparent border-light border-opacity-25 text-light"
                   placeholder="Search logs...">
        </div>
    </header>

    <div class="table-responsive" style="max-height: 640px;">
        <table class="table table-dark align-middle mb-0 admin-log-table">
            <thead>
            <tr>
                <th class="ps-4">Timestamp</th>
                <th>Level</th>
                <th class="pe-4">Message</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $logs = [
                    ['time' => '2024-03-29 13:05:42', 'level' => 'INFO', 'msg' => 'Admin dashboard accessed by <span class="text-info fw-bold">@admin</span>', 'color' => 'text-info', 'bg' => ''],
                    ['time' => '2024-03-29 12:40:01', 'level' => 'CRITICAL', 'msg' => 'Database connection timeout on <span class="text-danger fw-bold">cluster-01</span>. Automatic failover initiated.', 'color' => 'text-danger', 'bg' => 'bg-danger bg-opacity-10'],
                    ['time' => '2024-03-29 12:38:45', 'level' => 'WARNING', 'msg' => 'High memory usage detected (<span class="text-warning fw-bold">88%</span>) - Triggered auto-scale routine.', 'color' => 'text-warning', 'bg' => ''],
                    ['time' => '2024-03-29 12:35:20', 'level' => 'INFO', 'msg' => 'User <span class="text-info fw-bold">@admin</span> updated global system security policy from <span class="text-secondary">v1.2</span> to <span class="text-success fw-bold">v1.3</span>', 'color' => 'text-info', 'bg' => ''],
                    ['time' => '2024-03-29 12:30:12', 'level' => 'DEBUG', 'msg' => 'CSRF Token validated for session <span class="text-secondary opacity-75">8f2...9a1</span>', 'color' => 'text-secondary', 'bg' => ''],
                    ['time' => '2024-03-29 12:28:55', 'level' => 'CRITICAL', 'msg' => 'Unauthorized access attempt from <span class="text-danger fw-bold">185.12.33.4</span> (Blocked by firewall)', 'color' => 'text-danger', 'bg' => 'bg-danger bg-opacity-10'],
                    ['time' => '2024-03-29 12:25:30', 'level' => 'SUCCESS', 'msg' => 'System backup completed successfully (<span class="text-success fw-bold">4.2GB</span>) in 45s.', 'color' => 'text-success', 'bg' => ''],
                    ['time' => '2024-03-29 12:20:00', 'level' => 'INFO', 'msg' => 'New user <span class="text-info fw-bold">@dev_test</span> registered via invitation code <span class="text-secondary">#INV-2024</span>', 'color' => 'text-info', 'bg' => ''],
                    ['time' => '2024-03-29 12:15:10', 'level' => 'INFO', 'msg' => 'Mail server connection established with <span class="text-secondary">smtp.phpgit.dev</span>', 'color' => 'text-info', 'bg' => ''],
                    ['time' => '2024-03-29 12:10:05', 'level' => 'WARNING', 'msg' => 'SSL Certificate expires in <span class="text-warning fw-bold">5 days</span>. Auto-renewal scheduled.', 'color' => 'text-warning', 'bg' => ''],
            ];
            foreach ($logs as $log):
                ?>
                <tr class="<?php echo $log['bg']; ?>">
                    <td class="ps-4 text-secondary"><?php echo $log['time']; ?></td>
                    <td>
                        <span class="admin-log-badge <?php echo $log['color']; ?>"><?php echo $log['level']; ?></span>
                    </td>
                    <td class="pe-4 text-light"><?php echo $log['msg']; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <footer class="admin-log-footer">
        <small>Showing latest 100 entries</small>
        <button type="button" class="btn btn-link text-info text-decoration-none p-0">Fetch older entries</button>
    </footer>
</section>
