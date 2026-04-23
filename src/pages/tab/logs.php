<?php
declare(strict_types=1);

use App\Config;
use App\includes\Assets;
use App\includes\Logging;
use App\includes\Security;
use Random\RandomException;

$security = new Security();
$config = new Config();
$pdo = $config->getPDO();
$logs = [];
$csrf_token = null;
$default_log_limit = 100;
$logs_api_endpoint = '/api/v1/getLogs.php';

try {
    if ($pdo === null) {
        throw new PDOException('Database connection is not available. Please try again later.');
    }
    $stmt = $pdo->prepare('SELECT log_time AS time, level, message AS msg FROM log ORDER BY log_time DESC LIMIT ?');
    $stmt->execute([$default_log_limit]);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    Logging::loggingToFile('Cannot execute SQL Query: ' . $e->getMessage(), 4);
}

$count = count($logs);

$critical_count = 0;
$error_count = 0;
$warning_count = 0;
$info_count = 0;
foreach ($logs as $l) {
    switch ($l['level']) {
        case 'Critical':
            $critical_count++;
            break;
        case 'Error':
            $error_count++;
            break;
        case 'Warning':
            $warning_count++;
            break;
        case 'Info':
            $info_count++;
            break;
        default:
            break;
    }
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (! $security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('DELETE FROM log');
            $stmt->execute();
            $pdo->commit();
            echo '<script>window.location.href="/dashboard?tab=logs";</script>';
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logging::loggingToFile('Cannot execute SQL Query: ' . $e->getMessage(), 4);
            $errors[] = 'An error occurred while wiping logs. Please try again later.';
        }
    }
}

try {
    $csrf_token = $security->generateCsrfToken();
} catch (RandomException $e) {
    Logging::loggingToFile('Cannot generate csrf token: ' . $e->getMessage(), 4);
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
        <?php if (! empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="action" value="wipe_logs">
            <input type="hidden" name="csrf_token"
                   value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-outline-danger rounded-3"
                        onclick="return confirm('Are you sure? This action cannot be undone.')">Wipe All Logs
                </button>
            </div>
        </form>
    </div>
</section>

<section class="admin-log-shell"
         data-logs-endpoint="<?php echo htmlspecialchars($logs_api_endpoint, ENT_QUOTES, 'UTF-8'); ?>">
    <header class="admin-log-toolbar">
        <form id="log-limit-form" class="d-flex align-items-center gap-2" method="get"
              action="<?php echo htmlspecialchars($logs_api_endpoint, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="log-search-by-int" class="visually-hidden">Log limit</label>
            <div class="input-group input-group-sm" style="max-width: 185px;">
                <input type="number" class="form-control bg-transparent border-light border-opacity-25 text-light"
                       id="log-search-by-int" name="limit" min="1" max="1000" value="<?php echo $default_log_limit; ?>">
                <button type="submit" class="btn btn-sm btn-outline-light">Fetch</button>
            </div>
        </form>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-light rounded-pill log-level-filter" data-level="Critical"
                    aria-pressed="false">Critical (<span id="log-count-critical"><?php echo $critical_count; ?></span>)
            </button>
            <button type="button" class="btn btn-sm btn-outline-light rounded-pill log-level-filter" data-level="Error"
                    aria-pressed="false">Error (<span id="log-count-error"><?php echo $error_count; ?></span>)
            </button>
            <button type="button" class="btn btn-sm btn-outline-light rounded-pill log-level-filter" data-level="Warning"
                    aria-pressed="false">Warning (<span id="log-count-warning"><?php echo $warning_count; ?></span>)
            </button>
            <button type="button" class="btn btn-sm btn-outline-light rounded-pill log-level-filter" data-level="Info"
                    aria-pressed="false">Info (<span id="log-count-info"><?php echo $info_count; ?></span>)
            </button>
            <button type="button" class="btn btn-sm btn-secondary rounded-pill" id="log-clear-filters">Clear Filters</button>
        </div>
        <div class="alert alert-danger py-2 px-3 mb-0 d-none" id="log-fetch-error" role="alert"></div>
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
            <tbody id="log-table-body">
            <?php
            foreach ($logs as $log):

                $color = match ($log['level']) {
                    'Critical', 'Error' => 'text-danger',
                    'Warning' => 'text-warning',
                    'Success' => 'text-success',
                    'Debug' => 'text-secondary',
                    default => 'text-info',
                };
                ?>
                <tr data-log-level="<?php echo strtolower($log['level']); ?>">
                    <td class="ps-4 text-secondary"><?php echo $log['time']; ?></td>
                    <td>
                        <span class="admin-log-badge <?php echo $color; ?>"><?php echo $log['level']; ?></span>
                    </td>
                    <td class="pe-4 text-light"><?php echo $security->sanitizeInput($log['msg']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <footer class="admin-log-footer">
        <small id="log-footer-count">Showing latest <?php echo $count; ?> entries</small>
    </footer>
</section>

<script src="<?= Assets::url('/assets/js/log.js'); ?>"></script>
