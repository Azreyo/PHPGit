<?php

use App\includes\Security;
use App\includes\Logging;
use App\Config;

?>
<div class="d-flex align-items-center gap-3 mb-5 pb-4 border-bottom border-secondary-subtle">
    <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
         style="width: 34px; height: 34px; font-size: .9rem;">
        <i class="bi bi-shield-lock-fill"></i>
    </div>
    <div>
        <p class="section-label mb-0">Security</p>
        <h6 class="fw-bold mb-0" style="letter-spacing: -0.01em;">Protect your account</h6>
    </div>
</div>

<form action="#" method="post">
    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 36px; height: 36px; font-size: 1rem;">
                <i class="bi bi-key-fill"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Change Password</h6>
                <small class="text-secondary">Use a strong password with symbols and numbers.</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <label for="security-current-password" class="form-label fw-semibold">Current Password</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" id="security-current-password"
                           class="form-control rounded-end-3"
                           placeholder="Enter current password">
                </div>
                <div class="form-text">Required to authorize any password change.</div>
            </div>

            <div class="col-md-6">
                <label for="security-new-password" class="form-label fw-semibold">New Password</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" id="security-new-password"
                           class="form-control rounded-end-3"
                           placeholder="New password">
                </div>
                <div class="form-text">Minimum 12 characters with symbols &amp; numbers.</div>
            </div>

            <div class="col-md-6">
                <label for="security-confirm-password" class="form-label fw-semibold">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" id="security-confirm-password"
                           class="form-control rounded-end-3"
                           placeholder="Confirm new password">
                </div>
            </div>
        </div>
    </div>

    <hr class="border-secondary-subtle my-5">

    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success flex-shrink-0"
                 style="width: 36px; height: 36px; font-size: 1rem;">
                <i class="bi bi-shield-check"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Two-Factor Authentication</h6>
                <small class="text-secondary">Add an extra layer of security to your login.</small>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
            <div>
                <div class="fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-phone text-secondary"></i> Authenticator App
                </div>
                <small class="text-secondary">Secure your account with a time-based one-time password.</small>
            </div>
            <div class="form-check form-switch m-0 flex-shrink-0">
                <input class="form-check-input" type="checkbox" role="switch" id="security-2fa"
                       style="width: 2.4em; height: 1.25em; cursor: pointer;">
                <label class="visually-hidden" for="security-2fa">Enable two-factor authentication</label>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-end gap-3 mt-5 pt-4 border-top border-secondary-subtle">
        <button type="reset" class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
        </button>
        <button type="button" class="btn btn-primary px-4 d-flex align-items-center gap-2">
            <i class="bi bi-shield-check"></i> Save Security
        </button>
    </div>
</form>

<hr class="border-secondary-subtle my-5">

<div>
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center justify-content-center rounded-3 bg-danger-subtle text-danger flex-shrink-0"
             style="width: 36px; height: 36px; font-size: 1rem;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0 text-danger">Danger Zone</h6>
            <small class="text-secondary">Irreversible actions — proceed with caution.</small>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-3 border border-danger-subtle">
        <div>
            <div class="fw-semibold">Delete Account</div>
            <small class="text-secondary">Permanently remove your account and all associated data.</small>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm px-3 flex-shrink-0"
                onclick="return confirm('Are you sure? This action cannot be undone.')">
            <i class="bi bi-trash3 me-1"></i> Delete
        </button>
    </div>
</div>
