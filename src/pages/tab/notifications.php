<div class="d-flex align-items-center gap-3 mb-5 pb-4 border-bottom border-secondary-subtle">
    <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
         style="width: 34px; height: 34px; font-size: .9rem;">
        <i class="bi bi-bell-fill"></i>
    </div>
    <div>
        <p class="section-label mb-0">Notifications</p>
        <h6 class="fw-bold mb-0" style="letter-spacing: -0.01em;">Alerts & email digests</h6>
    </div>
</div>

<form action="#" method="post">

    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 30px; height: 30px; font-size: .85rem;">
                <i class="bi bi-envelope-fill"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Email Notifications</h6>
                <small class="text-secondary">Choose what triggers an email to you.</small>
            </div>
        </div>

        <div class="d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
                <div>
                    <div class="fw-semibold" style="font-size: .9rem;">
                        <i class="bi bi-star text-warning me-2"></i>Repository stars
                    </div>
                    <small class="text-secondary">Someone stars one of your repositories.</small>
                </div>
                <div class="form-check form-switch m-0 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="notif-stars"
                           style="width: 2.4em; height: 1.25em; cursor: pointer;" checked>
                    <label class="visually-hidden" for="notif-stars">Repository stars</label>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
                <div>
                    <div class="fw-semibold" style="font-size: .9rem;">
                        <i class="bi bi-git text-primary me-2"></i>Pull requests
                    </div>
                    <small class="text-secondary">New pull requests opened on your repos.</small>
                </div>
                <div class="form-check form-switch m-0 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="notif-prs"
                           style="width: 2.4em; height: 1.25em; cursor: pointer;" checked>
                    <label class="visually-hidden" for="notif-prs">Pull requests</label>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
                <div>
                    <div class="fw-semibold" style="font-size: .9rem;">
                        <i class="bi bi-chat-dots text-success me-2"></i>Comments & mentions
                    </div>
                    <small class="text-secondary">Someone comments on or mentions you.</small>
                </div>
                <div class="form-check form-switch m-0 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="notif-comments"
                           style="width: 2.4em; height: 1.25em; cursor: pointer;" checked>
                    <label class="visually-hidden" for="notif-comments">Comments & mentions</label>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
                <div>
                    <div class="fw-semibold" style="font-size: .9rem;">
                        <i class="bi bi-person-plus text-info me-2"></i>New followers
                    </div>
                    <small class="text-secondary">Someone starts following your account.</small>
                </div>
                <div class="form-check form-switch m-0 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="notif-followers"
                           style="width: 2.4em; height: 1.25em; cursor: pointer;">
                    <label class="visually-hidden" for="notif-followers">New followers</label>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
                <div>
                    <div class="fw-semibold" style="font-size: .9rem;">
                        <i class="bi bi-shield-exclamation text-danger me-2"></i>Security alerts
                    </div>
                    <small class="text-secondary">Vulnerability or account security events.</small>
                </div>
                <div class="form-check form-switch m-0 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="notif-security"
                           style="width: 2.4em; height: 1.25em; cursor: pointer;" checked>
                    <label class="visually-hidden" for="notif-security">Security alerts</label>
                </div>
            </div>
        </div>
    </div>

    <hr class="border-secondary-subtle my-5">

    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 30px; height: 30px; font-size: .85rem;">
                <i class="bi bi-calendar3"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Email Digest</h6>
                <small class="text-secondary">A summary email of your activity.</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label for="notif-digest-freq" class="form-label fw-semibold">Frequency</label>
                <select id="notif-digest-freq" class="form-select rounded-3">
                    <option value="never">Never</option>
                    <option value="daily" selected>Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
                <div class="form-text">How often you receive a digest email.</div>
            </div>
            <div class="col-12 col-md-6">
                <label for="notif-digest-email" class="form-label fw-semibold">Delivery Address</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary rounded-start-3">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" id="notif-digest-email" class="form-control rounded-end-3"
                           placeholder="you@example.com">
                </div>
                <div class="form-text">Leave blank to use your account email.</div>
            </div>
        </div>
    </div>

    <hr class="border-secondary-subtle my-5">

    <div class="mb-2">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 30px; height: 30px; font-size: .85rem;">
                <i class="bi bi-bell"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">In-App Notifications</h6>
                <small class="text-secondary">Alerts shown while you're browsing PHPGit.</small>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
            <div>
                <div class="fw-semibold" style="font-size: .9rem;">
                    <i class="bi bi-bell-slash text-secondary me-2"></i>Mute all in-app alerts
                </div>
                <small class="text-secondary">Disable all notification banners and toasts.</small>
            </div>
            <div class="form-check form-switch m-0 flex-shrink-0">
                <input class="form-check-input" type="checkbox" role="switch" id="notif-mute-all"
                       style="width: 2.4em; height: 1.25em; cursor: pointer;">
                <label class="visually-hidden" for="notif-mute-all">Mute all</label>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-end gap-3 mt-5 pt-4 border-top border-secondary-subtle">
        <button type="reset" class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
        </button>
        <button type="button" class="btn btn-primary px-4 d-flex align-items-center gap-2">
            <i class="bi bi-check2-circle"></i> Save Notifications
        </button>
    </div>
</form>
