<div class="mb-4">
    <p class="text-primary fw-bold text-uppercase mb-2" style="font-size: .78rem; letter-spacing: .12em;">Profile</p>
    <h4 class="mb-1" style="letter-spacing: -0.01em;">Public account information</h4>
    <p class="text-secondary mb-0">Update how your account appears across PHPGit.</p>
</div>

<form action="#" method="post">
    <div class="p-3 border border-secondary-subtle rounded-3 bg-body-tertiary bg-opacity-10 mb-4">
        <label for="profile-username" class="form-label fw-semibold">Username</label>
        <input
                type="text"
                id="profile-username"
                class="form-control rounded-3"
                style="min-height: 44px;"
                value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
        >
        <small class="text-secondary d-block mt-2">Your unique account handle.</small>
    </div>

    <div class="p-3 border border-secondary-subtle rounded-3 bg-body-tertiary bg-opacity-10 mb-4">
        <label for="profile-display-name" class="form-label fw-semibold">Display Name</label>
        <input type="text" id="profile-display-name" class="form-control rounded-3" style="min-height: 44px;"
               placeholder="Your display name">
        <small class="text-secondary d-block mt-2">This is the name shown on your profile page.</small>
    </div>

    <div class="p-3 border border-secondary-subtle rounded-3 bg-body-tertiary bg-opacity-10 mb-4">
        <label for="profile-bio" class="form-label fw-semibold">Bio</label>
        <textarea id="profile-bio" class="form-control rounded-3" style="min-height: 130px;" rows="4"
                  placeholder="Tell us a bit about yourself"></textarea>
        <small class="text-secondary d-block mt-2">Short introduction for other users.</small>
    </div>

    <div class="p-3 border border-secondary-subtle rounded-3 bg-body-tertiary bg-opacity-10 mb-4">
        <label for="profile-website" class="form-label fw-semibold">Website</label>
        <input type="url" id="profile-website" class="form-control rounded-3" style="min-height: 44px;"
               placeholder="https://example.com">
        <small class="text-secondary d-block mt-2">Optional portfolio or personal website.</small>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-2">
        <small class="text-secondary">Changes are preview-only until backend save is connected.</small>
        <button type="button" class="btn btn-primary px-4">Save Profile</button>
    </div>
</form>
