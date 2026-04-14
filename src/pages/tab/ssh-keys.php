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

    <form action="#" method="post">
        <div class="row g-3">
            <div class="col-md-5">
                <label for="ssh-key-title" class="form-label fw-semibold">Key Title</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3">
                        <i class="bi bi-tag"></i>
                    </span>
                    <input type="text" id="ssh-key-title" class="form-control rounded-end-3"
                           placeholder="e.g. Work laptop">
                </div>
                <div class="form-text">A label to identify this key.</div>
            </div>
            <div class="col-md-7">
                <label for="ssh-key-type" class="form-label fw-semibold">Key Type</label>
                <select id="ssh-key-type" class="form-select rounded-3">
                    <option value="authentication" selected>Authentication Key</option>
                    <option value="signing">Signing Key</option>
                </select>
                <div class="form-text">Authentication keys are used for SSH Git operations.</div>
            </div>
            <div class="col-12">
                <label for="ssh-key-value" class="form-label fw-semibold">Public Key</label>
                <textarea id="ssh-key-value" class="form-control font-monospace rounded-3"
                          rows="4"
                          placeholder="Begins with 'ssh-ed25519', 'ssh-rsa', 'ecdsa-sha2-nistp256', …"
                          style="font-size: .8rem; resize: vertical;"></textarea>
                <div class="form-text">
                    <i class="bi bi-info-circle me-1"></i>
                    Paste the contents of <code>~/.ssh/id_ed25519.pub</code> or your chosen public key file.
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-success px-4 d-flex align-items-center gap-2">
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

    <div class="text-center py-5 px-3 rounded-3 border border-secondary-subtle bg-body-secondary">
        <div class="d-flex align-items-center justify-content-center rounded-circle bg-body border border-secondary-subtle mx-auto mb-3"
             style="width: 52px; height: 52px; font-size: 1.3rem;">
            <i class="bi bi-key text-secondary"></i>
        </div>
        <div class="fw-semibold mb-1" style="font-size: .9rem;">No SSH keys yet</div>
        <small class="text-secondary">Add a key above to start authenticating with SSH.</small>
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
          style="font-size: .7rem;">
        Coming soon
    </span>
</div>
