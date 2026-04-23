<?php
declare(strict_types=1);

use App\Config;
use App\Services\SshKeyService;

/** @var bool $is_logged_in */

$config = Config::getInstance();
$pdo = $config->getPDO();
$userId = (int) ($_SESSION['user_id'] ?? 0);

$sshKeys = [];
$sshError = null;

if ($pdo !== null && $userId > 0) {
    $authorizedKeys = rtrim($_ENV['AUTHORIZED_KEYS_PATH'] ?? '', '/') ?: '/home/git/.ssh/authorized_keys';
    $gitShellWrapper = rtrim($_ENV['GIT_SHELL_WRAPPER'] ?? '', '/') ?: dirname(__DIR__, 2) . '/bin/git-shell-wrapper.php';
    $sshService = new SshKeyService($pdo, $authorizedKeys, $gitShellWrapper);
    $sshKeys = $sshService->listKeys($userId);
} elseif ($pdo === null) {
    $sshError = 'Database unavailable. Cannot load SSH keys.';
}

$sshHost = htmlspecialchars($_ENV['SSH_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'phpgit.local', ENT_QUOTES, 'UTF-8');
$gitSysUser = htmlspecialchars($_ENV['GIT_SYSTEM_USER'] ?? 'git', ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex align-items-center gap-3 mb-5 pb-4 border-bottom border-secondary-subtle">
    <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
         style="width: 34px; height: 34px; font-size: .9rem;">
        <i class="bi bi-key-fill"></i>
    </div>
    <div>
        <p class="section-label mb-0">SSH Keys</p>
        <h6 class="fw-bold mb-0" style="letter-spacing: -0.01em;">Manage authentication keys</h6>
    </div>
</div>

<?php if ($sshError !== null): ?>
    <div class="alert alert-warning"><i
                class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($sshError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="mb-5">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success flex-shrink-0"
             style="width: 30px; height: 30px; font-size: .85rem;">
            <i class="bi bi-plus-circle-fill"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0">Add New SSH Key</h6>
            <small class="text-secondary">Paste your public key to authenticate Git over SSH.</small>
        </div>
    </div>

    <div id="ssh-add-alert" class="alert d-none" role="alert"></div>

    <form id="ssh-add-form" novalidate>
        <div class="row g-3">
            <div class="col-md-5">
                <label for="ssh-key-title" class="form-label fw-semibold">Key Title</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3"><i class="bi bi-tag"></i></span>
                    <input type="text" id="ssh-key-title" name="title" class="form-control rounded-end-3"
                           placeholder="e.g. Work laptop" maxlength="100" required>
                </div>
                <div class="form-text">A label to identify this key.</div>
            </div>
        </div>
        <div class="row g-3 mt-0">
            <div class="col-12">
                <label for="ssh-key-value" class="form-label fw-semibold">Public Key</label>
                <textarea id="ssh-key-value" name="public_key" class="form-control font-monospace rounded-3"
                          rows="4" placeholder="Begins with 'ssh-ed25519', 'ssh-rsa', 'ecdsa-sha2-nistp256', …"
                          style="font-size: .8rem; resize: vertical;" required></textarea>
                <div class="form-text">
                    <i class="bi bi-info-circle me-1"></i>
                    Paste the contents of <code>~/.ssh/id_ed25519.pub</code> or your chosen public key file.
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button type="submit" id="ssh-add-btn" class="btn btn-success px-4 d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Add SSH Key
            </button>
        </div>
    </form>
</div>

<hr class="border-secondary-subtle my-5">

<div>
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
             style="width: 30px; height: 30px; font-size: .85rem;">
            <i class="bi bi-list-ul"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0">Your SSH Keys</h6>
            <small class="text-secondary">Keys currently linked to your account.</small>
        </div>
    </div>

    <div id="ssh-keys-list">
        <?php if (empty($sshKeys)): ?>
            <div id="ssh-empty-state"
                 class="text-center py-5 px-3 rounded-3 border border-secondary-subtle bg-body-secondary">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-body border border-secondary-subtle mx-auto mb-3"
                     style="width: 52px; height: 52px; font-size: 1.3rem;">
                    <i class="bi bi-key text-secondary"></i>
                </div>
                <div class="fw-semibold mb-1" style="font-size: .9rem;">No SSH keys yet</div>
                <small class="text-secondary">Add a key above to start authenticating with SSH.</small>
            </div>
        <?php else: ?>
            <?php foreach ($sshKeys as $key): ?>
                <div class="ssh-key-row d-flex align-items-start justify-content-between gap-3 p-3 mb-2 rounded-3 border border-secondary-subtle"
                     data-key-id="<?= (int) $key['id'] ?>">
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars($key['title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <small class="text-secondary font-monospace"><?= htmlspecialchars($key['fingerprint'], ENT_QUOTES, 'UTF-8') ?></small>
                        <div class="mt-1">
                            <span class="badge bg-body-secondary text-secondary border border-secondary-subtle"
                                  style="font-size:.7rem;">
                                <?= htmlspecialchars($key['key_type'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <small class="text-secondary ms-2">Added <?= htmlspecialchars(date('d M Y', strtotime($key['created_at'])), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger ssh-delete-btn flex-shrink-0"
                            data-key-id="<?= (int) $key['id'] ?>"
                            data-key-title="<?= htmlspecialchars($key['title'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<hr class="border-secondary-subtle my-5">

<div class="p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
    <div class="fw-semibold mb-2" style="font-size: .9rem;"><i class="bi bi-terminal me-1"></i> SSH Clone URL format
    </div>
    <code class="text-body-secondary" style="font-size: .8rem;"><?= $gitSysUser ?>@<?= $sshHost ?>
        :username/repository.git</code>
    <div class="form-text mt-1">Replace <em>username</em> and <em>repository</em> with the actual owner and repo name.
    </div>
</div>

<hr class="border-secondary-subtle my-5">

<div class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-3 border border-secondary-subtle">
    <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center justify-content-center rounded-3 bg-warning-subtle text-warning flex-shrink-0"
             style="width: 30px; height: 30px; font-size: .85rem;">
            <i class="bi bi-patch-check-fill"></i>
        </div>
        <div>
            <div class="fw-semibold" style="font-size: .9rem;">GPG / Signing Keys</div>
            <small class="text-secondary">Verify your commits with a GPG or SSH signing key.</small>
        </div>
    </div>
    <span class="badge bg-body-secondary-subtle text-secondary border border-secondary-subtle"
          style="font-size: .7rem;">Coming soon</span>
</div>

<script>
    (function () {
        const form = document.getElementById('ssh-add-form');
        const alertEl = document.getElementById('ssh-add-alert');
        const keyList = document.getElementById('ssh-keys-list');
        const addBtn = document.getElementById('ssh-add-btn');

        function showAlert(msg, type) {
            alertEl.className = 'alert alert-' + type;
            alertEl.textContent = msg;
            alertEl.classList.remove('d-none');
            setTimeout(() => alertEl.classList.add('d-none'), 6000);
        }

        function esc(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function buildKeyRow(key) {
            const date = new Date(key.created_at).toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
            const div = document.createElement('div');
            div.className = 'ssh-key-row d-flex align-items-start justify-content-between gap-3 p-3 mb-2 rounded-3 border border-secondary-subtle';
            div.dataset.keyId = key.id;
            div.innerHTML = `
            <div>
                <div class="fw-semibold">${esc(key.title)}</div>
                <small class="text-secondary font-monospace">${esc(key.fingerprint)}</small>
                <div class="mt-1">
                    <span class="badge bg-body-secondary text-secondary border border-secondary-subtle" style="font-size:.7rem;">${esc(key.key_type)}</span>
                    <small class="text-secondary ms-2">Added ${date}</small>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger ssh-delete-btn flex-shrink-0"
                    data-key-id="${key.id}" data-key-title="${esc(key.title)}">
                <i class="bi bi-trash"></i>
            </button>`;
            return div;
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const title = document.getElementById('ssh-key-title').value.trim();
            const pubKey = document.getElementById('ssh-key-value').value.trim();
            if (!title || !pubKey) {
                showAlert('Title and public key are required.', 'warning');
                return;
            }

            addBtn.disabled = true;
            addBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Adding…';

            try {
                const res = await fetch('/api/v1/addSshKey.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({title, public_key: pubKey}),
                });
                const data = await res.json();
                if (res.ok && data.key) {
                    document.getElementById('ssh-key-title').value = '';
                    document.getElementById('ssh-key-value').value = '';
                    showAlert('SSH key added successfully.', 'success');
                    const emptyState = document.getElementById('ssh-empty-state');
                    if (emptyState) emptyState.remove();
                    const row = buildKeyRow(data.key);
                    keyList.prepend(row);
                    bindDeleteBtn(row.querySelector('.ssh-delete-btn'));
                } else {
                    showAlert(data.error || 'Failed to add key.', 'danger');
                }
            } catch {
                showAlert('Network error. Please try again.', 'danger');
            } finally {
                addBtn.disabled = false;
                addBtn.innerHTML = '<i class="bi bi-plus-lg"></i> Add SSH Key';
            }
        });

        function bindDeleteBtn(btn) {
            btn.addEventListener('click', async function () {
                const keyId = parseInt(this.dataset.keyId);
                const keyTitle = this.dataset.keyTitle;
                if (!confirm(`Remove SSH key "${keyTitle}"? This cannot be undone.`)) return;
                this.disabled = true;
                try {
                    const res = await fetch('/api/v1/deleteSshKey.php', {
                        method: 'DELETE', headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: keyId}),
                    });
                    const data = await res.json();
                    if (res.ok && data.deleted) {
                        const row = keyList.querySelector(`[data-key-id="${keyId}"]`);
                        if (row) row.remove();
                        if (!keyList.querySelector('.ssh-key-row')) {
                            keyList.innerHTML = `<div id="ssh-empty-state" class="text-center py-5 px-3 rounded-3 border border-secondary-subtle bg-body-secondary">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-body border border-secondary-subtle mx-auto mb-3" style="width:52px;height:52px;font-size:1.3rem;"><i class="bi bi-key text-secondary"></i></div>
                            <div class="fw-semibold mb-1" style="font-size:.9rem;">No SSH keys yet</div>
                            <small class="text-secondary">Add a key above to start authenticating with SSH.</small></div>`;
                        }
                        showAlert('SSH key removed.', 'success');
                    } else {
                        showAlert(data.error || 'Failed to delete key.', 'danger');
                        this.disabled = false;
                    }
                } catch {
                    showAlert('Network error. Please try again.', 'danger');
                    this.disabled = false;
                }
            });
        }

        document.querySelectorAll('.ssh-delete-btn').forEach(bindDeleteBtn);
    })();
</script>
