<div class="d-flex align-items-center gap-3 mb-5 pb-4 border-bottom border-secondary-subtle">
    <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
         style="width: 34px; height: 34px; font-size: .9rem;">
        <i class="bi bi-palette"></i>
    </div>
    <div>
        <p class="section-label mb-0">Appearance</p>
        <h6 class="fw-bold mb-0" style="letter-spacing: -0.01em;">Theme & display options</h6>
    </div>
</div>

<form id="appearance-settings-form" action="#" method="post">

    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 30px; height: 30px; font-size: .85rem;">
                <i class="bi bi-moon-stars-fill"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Interface Theme</h6>
                <small class="text-secondary">Choose how PHPGit looks to you.</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-4">
                <input type="radio" class="btn-check" name="theme" id="theme-light" value="light" autocomplete="off">
                <label class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center gap-2 py-3 rounded-3"
                       for="theme-light">
                    <i class="bi bi-sun fs-4"></i>
                    <span class="fw-semibold" style="font-size: .82rem;">Light</span>
                </label>
            </div>
            <div class="col-4">
                <input type="radio" class="btn-check" name="theme" id="theme-dark" value="dark" autocomplete="off"
                       checked>
                <label class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center gap-2 py-3 rounded-3"
                       for="theme-dark">
                    <i class="bi bi-moon-fill fs-4"></i>
                    <span class="fw-semibold" style="font-size: .82rem;">Dark</span>
                </label>
            </div>
            <div class="col-4">
                <input type="radio" class="btn-check" name="theme" id="theme-system" value="system" autocomplete="off">
                <label class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center gap-2 py-3 rounded-3"
                       for="theme-system">
                    <i class="bi bi-display fs-4"></i>
                    <span class="fw-semibold" style="font-size: .82rem;">System</span>
                </label>
            </div>
        </div>
    </div>

    <hr class="border-secondary-subtle my-5">

    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 30px; height: 30px; font-size: .85rem;">
                <i class="bi bi-type"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Code Editor Font</h6>
                <small class="text-secondary">Applies to code views and diffs.</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <label for="appearance-font" class="form-label fw-semibold">Font Family</label>
                <select id="appearance-font" class="form-select rounded-3">
                    <option value="monospace" selected>Monospace (default)</option>
                    <option value="fira-code">Fira Code</option>
                    <option value="jetbrains-mono">JetBrains Mono</option>
                    <option value="source-code-pro">Source Code Pro</option>
                    <option value="cascadia-code">Cascadia Code</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="appearance-font-size" class="form-label fw-semibold">Font Size</label>
                <select id="appearance-font-size" class="form-select rounded-3">
                    <option value="12">12px</option>
                    <option value="13" selected>13px</option>
                    <option value="14">14px</option>
                    <option value="15">15px</option>
                    <option value="16">16px</option>
                </select>
            </div>
        </div>
    </div>

    <hr class="border-secondary-subtle my-5">

    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 30px; height: 30px; font-size: .85rem;">
                <i class="bi bi-layout-three-columns"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">UI Density</h6>
                <small class="text-secondary">Controls spacing throughout the interface.</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-4">
                <input type="radio" class="btn-check" name="density" id="density-compact" value="compact"
                       autocomplete="off">
                <label class="btn btn-outline-secondary w-100 py-2 rounded-3" for="density-compact"
                       style="font-size: .82rem;">
                    <i class="bi bi-arrows-collapse-vertical d-block mb-1 fs-5"></i>
                    Compact
                </label>
            </div>
            <div class="col-4">
                <input type="radio" class="btn-check" name="density" id="density-default" value="default"
                       autocomplete="off" checked>
                <label class="btn btn-outline-secondary w-100 py-2 rounded-3" for="density-default"
                       style="font-size: .82rem;">
                    <i class="bi bi-arrows-expand-vertical d-block mb-1 fs-5"></i>
                    Default
                </label>
            </div>
            <div class="col-4">
                <input type="radio" class="btn-check" name="density" id="density-comfortable" value="comfortable"
                       autocomplete="off">
                <label class="btn btn-outline-secondary w-100 py-2 rounded-3" for="density-comfortable"
                       style="font-size: .82rem;">
                    <i class="bi bi-distribute-vertical d-block mb-1 fs-5"></i>
                    Spacious
                </label>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-end gap-3 mt-5 pt-4 border-top border-secondary-subtle">
        <button type="reset" id="appearance-reset" class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
        </button>
        <button type="button" id="appearance-save" class="btn btn-primary px-4 d-flex align-items-center gap-2">
            <i class="bi bi-check2-circle"></i> Save Appearance
        </button>
    </div>
</form>
