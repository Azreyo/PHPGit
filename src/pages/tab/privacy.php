<div class="d-flex align-items-center gap-3 mb-5 pb-4 border-bottom border-secondary-subtle">
    <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
         style="width: 34px; height: 34px; font-size: .9rem;">
        <i class="bi bi-eye-slash-fill"></i>
    </div>
    <div>
        <p class="section-label mb-0">Privacy</p>
        <h6 class="fw-bold mb-0" style="letter-spacing: -0.01em;">Visibility & data control</h6>
    </div>
</div>

<form action="#" method="post">

    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 30px; height: 30px; font-size: .85rem;">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Profile Visibility</h6>
                <small class="text-secondary">Control who can view your public profile.</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-6">
                <input type="radio" class="btn-check" name="profile-visibility" id="vis-public" value="public"
                       autocomplete="off" checked>
                <label class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center gap-2 py-3 rounded-3"
                       for="vis-public">
                    <i class="bi bi-globe2 fs-4 text-success"></i>
                    <div class="text-center">
                        <div class="fw-semibold" style="font-size: .85rem;">Public</div>
                        <small class="text-secondary">Anyone can view</small>
                    </div>
                </label>
            </div>
            <div class="col-6">
                <input type="radio" class="btn-check" name="profile-visibility" id="vis-private" value="private"
                       autocomplete="off">
                <label class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center gap-2 py-3 rounded-3"
                       for="vis-private">
                    <i class="bi bi-lock-fill fs-4 text-warning"></i>
                    <div class="text-center">
                        <div class="fw-semibold" style="font-size: .85rem;">Private</div>
                        <small class="text-secondary">Only you can view</small>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <hr class="border-secondary-subtle my-5">

    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 30px; height: 30px; font-size: .85rem;">
                <i class="bi bi-card-list"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Visible Information</h6>
                <small class="text-secondary">Choose what others can see on your profile.</small>
            </div>
        </div>

        <div class="d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
                <div>
                    <div class="fw-semibold" style="font-size: .9rem;">
                        <i class="bi bi-envelope text-primary me-2"></i>Email address
                    </div>
                    <small class="text-secondary">Show your email on your public profile.</small>
                </div>
                <div class="form-check form-switch m-0 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="priv-show-email"
                           style="width: 2.4em; height: 1.25em; cursor: pointer;">
                    <label class="visually-hidden" for="priv-show-email">Show email</label>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
                <div>
                    <div class="fw-semibold" style="font-size: .9rem;">
                        <i class="bi bi-activity text-success me-2"></i>Activity feed
                    </div>
                    <small class="text-secondary">Make your commit & repo activity public.</small>
                </div>
                <div class="form-check form-switch m-0 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="priv-show-activity"
                           style="width: 2.4em; height: 1.25em; cursor: pointer;" checked>
                    <label class="visually-hidden" for="priv-show-activity">Show activity</label>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
                <div>
                    <div class="fw-semibold" style="font-size: .9rem;">
                        <i class="bi bi-star text-warning me-2"></i>Starred repositories
                    </div>
                    <small class="text-secondary">Show your starred repos to other users.</small>
                </div>
                <div class="form-check form-switch m-0 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="priv-show-stars"
                           style="width: 2.4em; height: 1.25em; cursor: pointer;" checked>
                    <label class="visually-hidden" for="priv-show-stars">Show stars</label>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary-subtle bg-body-secondary">
                <div>
                    <div class="fw-semibold" style="font-size: .9rem;">
                        <i class="bi bi-clock-history text-secondary me-2"></i>Last seen / online status
                    </div>
                    <small class="text-secondary">Let others see when you were last active.</small>
                </div>
                <div class="form-check form-switch m-0 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="priv-show-lastseen"
                           style="width: 2.4em; height: 1.25em; cursor: pointer;">
                    <label class="visually-hidden" for="priv-show-lastseen">Show last seen</label>
                </div>
            </div>
        </div>
    </div>

    <hr class="border-secondary-subtle my-5">

    <div class="mb-2">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 30px; height: 30px; font-size: .85rem;">
                <i class="bi bi-download"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Your Data</h6>
                <small class="text-secondary">Export or review everything PHPGit holds about you.</small>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-3 border border-secondary-subtle">
            <div>
                <div class="fw-semibold" style="font-size: .9rem;">Export account data</div>
                <small class="text-secondary">Download a ZIP of your profile, repos, and activity.</small>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 flex-shrink-0">
                <i class="bi bi-box-arrow-down me-1"></i> Export
            </button>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-end gap-3 mt-5 pt-4 border-top border-secondary-subtle">
        <button type="reset" class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
        </button>
        <button type="button" class="btn btn-primary px-4 d-flex align-items-center gap-2">
            <i class="bi bi-check2-circle"></i> Save Privacy
        </button>
    </div>
</form>
