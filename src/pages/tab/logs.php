<?php
declare(strict_types=1);

use App\Config;
use App\includes\Logging;

$logs = [];
try {
    $config = new Config();
    $pdo = $config->getPDO();
    $stmt = $pdo->prepare('SELECT l.log_time AS time,lv.level, l.message AS msg FROM log AS l INNER JOIN level AS lv ON l.level_id = lv.id ORDER BY l.log_time DESC LIMIT 100');
    $stmt->execute();
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    Logging::loggingToFile("Cannot execute SQL Query: " . $e->getMessage(), 4);
}

?>

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
            foreach ($logs as $log):

                $color = match ($log['level']) {
                    'CRITICAL', 'ERROR' => 'text-danger',
                    'WARNING' => 'text-warning',
                    'SUCCESS' => 'text-success',
                    'DEBUG' => 'text-secondary',
                    default => 'text-info',
                }
                ?>
                <tr>
                    <td class="ps-4 text-secondary"><?php echo $log['time']; ?></td>
                    <td>
                        <span class="admin-log-badge <?php echo $color; ?>"><?php echo $log['level']; ?></span>
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
