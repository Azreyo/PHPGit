<?php
declare(strict_types=1);

use App\Config;
use App\includes\Logging;

$users = [];
try {
    $config = new Config();
    $pdo = $config->getPDO();
    $stmt = $pdo->prepare('SELECT username, email, role, status, created_at AS joined FROM users ORDER BY created_at DESC LIMIT 10');
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    Logging::loggingToFile("Cannot execute SQL Query: " . $e->getMessage(), 4);
}

?>

<section class="admin-panel p-4 mb-4">
    <div class="row g-3 align-items-center">
        <div class="col-12 col-md-8">
            <p class="admin-panel-kicker mb-1">User Directory</p>
            <h5 class="mb-1 fw-bold">Identity and Access</h5>
            <p class="text-secondary small mb-0">Manage accounts, roles, and moderation status from one table.</p>
        </div>
        <div class="col-12 col-md-4 text-md-end">
            <button type="button" class="btn btn-primary rounded-3 px-4">
                <i class="bi bi-person-plus me-2"></i>Create User
            </button>
        </div>
    </div>
</section>

<div class="admin-panel overflow-hidden">
    <div class="admin-table-toolbar">
        <div class="input-group" style="max-width: 360px;">
            <span class="input-group-text bg-body border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control border-start-0" placeholder="Search by name, email, role...">
        </div>
        <button type="button" class="btn btn-outline-secondary rounded-3 px-3">
            <i class="bi bi-funnel me-2"></i>Filter
        </button>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="admin-table-head">
            <tr>
                <th class="ps-4">User</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th class="pe-4 text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($users as $u):
                $roleClass = match ($u['role']) {
                    'ADMIN' => 'text-bg-danger',
                    default => 'text-bg-secondary'
                };
                $statusClass = match ($u['status']) {
                    'ACTIVE' => 'success',
                    'INACTIVE' => 'secondary',
                    'SUSPENDED' => 'danger',
                    default => 'warning'
                };
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar-circle" style="width: 42px; height: 42px; font-size: 0.85rem;">
                                <?php echo strtoupper(substr($u['username'], 0, 2)); ?>
                            </span>
                            <div>
                                <p class="mb-0 fw-semibold"><?php echo $u['username']; ?></p>
                                <small class="text-secondary">@<?php echo $u['username']; ?>
                                    • <?php echo $u['email']; ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?php echo $roleClass; ?> rounded-pill px-3"><?php echo $u['role']; ?></span>
                    </td>
                    <td>
                        <span class="admin-status-dot bg-<?php echo $statusClass; ?>"></span>
                        <small class="fw-semibold text-<?php echo $statusClass; ?>"><?php echo $u['status']; ?></small>
                    </td>
                    <td>
                        <small class="text-secondary"><?php echo $u['joined']; ?></small>
                    </td>
                    <td class="pe-4 text-end">
                        <div class="d-inline-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3"><i
                                        class="bi bi-pencil"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3"><i
                                        class="bi bi-three-dots"></i></button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-table-foot">
        <small class="text-secondary">Showing 1-6 of 1,284 users</small>
        <div class="btn-group btn-group-sm" role="group" aria-label="Pagination">
            <button type="button" class="btn btn-outline-secondary">Prev</button>
            <button type="button" class="btn btn-primary">1</button>
            <button type="button" class="btn btn-outline-secondary">2</button>
            <button type="button" class="btn btn-outline-secondary">Next</button>
        </div>
    </div>
</div>
