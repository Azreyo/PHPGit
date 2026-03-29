<div class="mb-4">
    <p class="text-primary fw-bold text-uppercase mb-2" style="font-size: .78rem; letter-spacing: .12em;">Security</p>
    <h4 class="mb-1" style="letter-spacing: -0.01em;">Protect your account</h4>
    <p class="text-secondary mb-0">Change your credentials and account protection settings.</p>
</div>

<form action="#" method="post">
    <div class="p-3 border border-secondary-subtle rounded-3 bg-body-tertiary bg-opacity-10 mb-4">
        <label for="security-current-password" class="form-label fw-semibold">Current Password</label>
        <input type="password" id="security-current-password" class="form-control rounded-3" style="min-height: 44px;"
               placeholder="Current password">
        <small class="text-secondary d-block mt-2">Required before changing your password.</small>
    </div>

    <div class="p-3 border border-secondary-subtle rounded-3 bg-body-tertiary bg-opacity-10 mb-4">
        <label for="security-new-password" class="form-label fw-semibold">New Password</label>
        <input type="password" id="security-new-password" class="form-control rounded-3" style="min-height: 44px;"
               placeholder="New password">
        <small class="text-secondary d-block mt-2">Use at least 12 characters with symbols and numbers.</small>
    </div>

    <div class="p-3 border border-secondary-subtle rounded-3 bg-body-tertiary bg-opacity-10 mb-4">
        <label for="security-confirm-password" class="form-label fw-semibold">Confirm New Password</label>
        <input type="password" id="security-confirm-password" class="form-control rounded-3" style="min-height: 44px;"
               placeholder="Confirm new password">
    </div>

    <div class="p-3 border border-secondary-subtle rounded-3 bg-body-tertiary bg-opacity-10 mb-4">
        <div class="form-check form-switch m-0">
            <input class="form-check-input" type="checkbox" role="switch" id="security-2fa">
            <label class="form-check-label fw-semibold" for="security-2fa">
                Enable two-factor authentication
            </label>
        </div>
        <small class="text-secondary d-block mt-2">Adds an extra layer of login verification.</small>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-2">
        <small class="text-secondary">Changes are preview-only until backend save is connected.</small>
        <button type="button" class="btn btn-primary px-4">Save Security</button>
    </div>
</form>
