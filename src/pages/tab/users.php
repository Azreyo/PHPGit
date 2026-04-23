<?php
declare(strict_types=1);

use App\Config;
use App\includes\Assets;
use App\includes\Logging;
use App\includes\Security;
use Random\RandomException;

$config = new Config();
$security = new Security();
$csrf_token = '';
$errors = [];
$users = [];
$pdo = $config->getPDO();
$all_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (! $security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    }
    switch ($action) {
        case 'create_user':
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'USER';
            $status = $_POST['status'] ?? 'ACTIVE';

            if (empty($username)) {
                $errors[] = 'Username is required.';
            }
            if (empty($email)) {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format.';
            }
            if (empty($password)) {
                $errors[] = 'Password is required.';
            } elseif (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long.';
            }
            if (!in_array($role, ['USER', 'ADMIN'], true)) {
                $errors[] = 'Invalid role selected.';
            }
            if (!in_array($status, ['ACTIVE', 'INACTIVE', 'SUSPENDED'], true)) {
                $errors[] = 'Invalid status selected.';
            }
            try {
                if ($pdo !== null) {
                    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE email = ?');
                    $stmt->execute([$email]);
                    if ($stmt->fetchColumn() > 0) {
                        $errors[] = 'Email is already in use.';
                    }
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
                    $stmt->execute([$username]);
                    if ($stmt->fetchColumn() > 0) {
                        $errors[] = 'Username is already taken.';
                    }
                } else {
                    $errors[] = 'Database connection is not available. Please try again later.';
                    Logging::loggingToFile('Database connection is not available: ' . $config->getDb() . ' ' . $config->getHost(), 4);
                }
            } catch (PDOException $e) {
                Logging::loggingToFile('Error checking existing email: ' . $e->getMessage(), 4);
                $errors[] = 'An error occurred while validating the email. Please try again.';
            }

            if (empty($errors)) {
                try {
                    $pdo = $config->getPDO();
                    if ($pdo !== null) {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare(
                                'INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$username, $email, $hashedPassword, $role, $status]);
                        echo '<script>window.location.href="/dashboard?tab=users&success=created";</script>';
                        exit;
                    } else {
                        $errors[] = 'Database connection is not available. Please try again later.';
                    }
                } catch (PDOException $e) {
                    Logging::loggingToFile('Error creating user: ' . $e->getMessage(), 4);
                    $errors[] = 'An error occurred while creating the user. Please try again.';
                }
            }
            break;
        case 'update_user':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $display_name = trim($_POST['display_name'] ?? '');
            $role = $_POST['role'] ?? 'USER';
            $status = $_POST['status'] ?? 'ACTIVE';
            $bio = $_POST['bio'] ?? '';

            if ($user_id <= 0) {
                $errors[] = 'Invalid user ID.';
            }
            if (empty($username)) {
                $errors[] = 'Username is required.';
            }
            if (empty($email)) {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format.';
            }

            if (empty($errors)) {
                try {
                    if ($pdo !== null) {
                        // Check uniqueness
                        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (email = ? OR username = ?) AND id != ?');
                        $stmt->execute([$email, $username, $user_id]);
                        if ($stmt->fetchColumn() > 0) {
                            $errors[] = 'Username or Email is already in use by another account.';
                        } else {
                            $stmt = $pdo->prepare(
                                'UPDATE users SET username = ?, email = ?, display_name = ?, role = ?, status = ?, bio = ? WHERE id = ?'
                            );
                            $stmt->execute([$username, $email, $display_name, $role, $status, $bio, $user_id]);
                            echo '<script>window.location.href="/dashboard?tab=users&success=updated";</script>';
                            exit;
                        }
                    }
                } catch (PDOException $e) {
                    Logging::loggingToFile('Error updating user: ' . $e->getMessage(), 4);
                    $errors[] = 'An error occurred while updating the user.';
                }
            }
            break;
        case 'delete_user':
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) {
                $errors[] = 'Invalid user ID.';
            }
            
            if (empty($errors)) {
                try {
                    if ($pdo !== null) {
                        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                        $stmt->execute([$user_id]);
                        echo '<script>window.location.href="/dashboard?tab=users&success=deleted";</script>';
                        exit;
                    }
                } catch (PDOException $e) {
                    Logging::loggingToFile('Error deleting user: ' . $e->getMessage(), 4);
                    $errors[] = 'An error occurred while deleting the user.';
                }
            }
            break;
        default:
            Logging::loggingToFile('Unknown form action: ' . ($_POST['action'] ?? ''), 4);
    }
}

try {
    if ($pdo !== null) {
        $stmt = $pdo->prepare('SELECT id, username, email, display_name, role, status, bio, created_at AS joined FROM users ORDER BY created_at DESC LIMIT 10');
        $stmt->execute();
        $users = $stmt->fetchAll();
        $stmt = $pdo->prepare('SELECT COUNT(*) as all_count FROM users');
        $stmt->execute();
        $all_count = $stmt->fetchColumn();
    } else {
        $users = [];
        Logging::loggingToFile('Database connection is not available: ' . $config->getDb() . ' ' . $config->getHost(), 4);
    }
} catch (PDOException $e) {
    Logging::loggingToFile('Cannot execute SQL Query: ' . $e->getMessage(), 4);
}

try {
    $csrf_token = $security->generateCsrfToken();
} catch (RandomException $e) {
    Logging::loggingToFile('Cannot generate csrf token: ' . $e->getMessage(), 4);
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
            <?php if (! empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <button type="button"
                    class="btn btn-primary rounded-3 px-4"
                    data-bs-toggle="modal"
                    data-bs-target="#createUserModal">
                <i class="bi bi-person-plus me-2"></i>Create User
            </button>
        </div>
    </div>
</section>
<div class="create-user-dim-overlay" id="createUserDimOverlay" aria-hidden="true"></div>

<div class="modal"
     id="createUserModal"
     tabindex="-1"
     aria-labelledby="createUserModalLabel"
     aria-hidden="true"
     data-bs-backdrop="false"
     style="z-index: 1;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="createUserModalLabel">
                    <i class="bi bi-person-vcard me-2"></i>Create User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="post">
                <input type="hidden" name="action" value="create_user">
                <input type="hidden" name="csrf_token"
                       value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="username" class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-at"></i></span>
                                <input type="text" class="form-control" id="username" name="username"
                                       placeholder="johndoe" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="name@example.com"
                                       required>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Enter password"
                                       required>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <label for="role" class="form-label fw-semibold">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="USER" selected>USER</option>
                                <option value="ADMIN">ADMIN</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="ACTIVE" selected>ACTIVE</option>
                                <option value="INACTIVE">INACTIVE</option>
                                <option value="SUSPENDED">SUSPENDED</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">
                        <i class="bi bi-check2-circle me-1"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal"
     id="editUserModal"
     tabindex="-1"
     aria-labelledby="editUserModalLabel"
     aria-hidden="true"
     data-bs-backdrop="false"
     style="z-index: 1;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="editUserModalLabel">
                    <i class="bi bi-pencil-square me-2 text-primary"></i>Edit User Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="post">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="csrf_token"
                       value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="user_id" value="">

                <div class="modal-body pt-3">
                    <p class="text-secondary small mb-4">Modify account details and permissions for this user. Changes take effect immediately.</p>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="edit_username" class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-end-0"><i class="bi bi-at"></i></span>
                                <input type="text" class="form-control border-start-0" id="edit_username" name="username"
                                       placeholder="johndoe" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="edit_email" class="form-label fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control border-start-0" id="edit_email" name="email"
                                       placeholder="name@example.com"
                                       required>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="edit_display_name" class="form-label fw-semibold">Display Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-end-0"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control border-start-0" id="edit_display_name" name="display_name"
                                       placeholder="John Doe">
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <label for="edit_role" class="form-label fw-semibold">Role</label>
                            <select class="form-select" id="edit_role" name="role">
                                <option value="USER">USER</option>
                                <option value="ADMIN">ADMIN</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label for="edit_status" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="INACTIVE">INACTIVE</option>
                                <option value="SUSPENDED">SUSPENDED</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="edit_bio" class="form-label fw-semibold">Bio</label>
                            <textarea class="form-control" id="edit_bio" name="bio" rows="3" placeholder="Tell something about this user..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">
                        <i class="bi bi-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="admin-panel overflow-hidden">
    <div class="admin-table-toolbar">
        <div class="input-group" style="max-width: 360px;">
            <span class="input-group-text bg-body border-end-0"><i class="bi bi-search"></i></span>
            <label>
                <input type="text" class="form-control border-start-0" placeholder="Search by name, email, role...">
            </label>
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
                            <form method="post">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <input type="hidden" name="csrf_token"
                                       value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-3"
                                        style="z-index: 1;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal"
                                        data-bs-id="<?php echo $u['id']; ?>"
                                        data-bs-username="<?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-bs-email="<?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-bs-display-name="<?php echo htmlspecialchars($u['display_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-bs-role="<?php echo htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-bs-status="<?php echo htmlspecialchars($u['status'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-bs-bio="<?php echo htmlspecialchars($u['bio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3"
                                        onclick="return confirm('Are you sure? This action cannot be undone.')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-table-foot">
        <small class="text-secondary">Showing <?php echo count($users); ?> of <?php echo $all_count; ?> users</small>
        <div class="btn-group btn-group-sm" role="group" aria-label="Pagination">
            <button type="button" class="btn btn-outline-secondary">Prev</button>
            <button type="button" class="btn btn-primary">1</button>
            <button type="button" class="btn btn-outline-secondary">2</button>
            <button type="button" class="btn btn-outline-secondary">Next</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= Assets::url('/assets/css/users.css') ?>">
<script src="<?= Assets::url('/assets/js/users.js') ?>"></script>