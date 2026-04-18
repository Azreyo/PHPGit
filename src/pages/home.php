<main>
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8 m-5">
                    <?php if (! empty($username)): ?>
                        <p class="welcome-back mb-2">Welcome back, <?php echo htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <h1 class="hero-title mb-3">
                        Code. Collaborate.<br>
                        <span class="text-primary">Ship faster.</span>
                    </h1>
                    <p class="hero-subtitle text-secondary mb-4">
                        PHPGit is a modern platform for hosting, collaborating, and version-controlling your projects. Streamlined tools built for developers.
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <?php if (empty($username)): ?>
                            <a href="index.php?page=register" class="btn btn-primary btn-lg px-4 m-2">Get Started
                                Free</a>
                            <a href="index.php?page=explore" class="btn btn-outline-secondary btn-lg px-4 m-2">Explore
                                Repos</a>
                        <?php else: ?>
                            <a href="index.php?page=explore" class="btn btn-primary btn-lg px-4 m-2">Explore Repos</a>
                            <a href="index.php?page=about" class="btn btn-outline-secondary btn-lg px-4 m-2">About
                                Us</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <div class="row g-0">
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-number">12k+</div>
                    <div class="stat-label">Repositories</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-number">4.8k+</div>
                    <div class="stat-label">Developers</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-number">320k+</div>
                    <div class="stat-label">Commits</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Uptime</div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-label">Features</span>
                <h2 class="section-title mt-2">Everything you need to build better software</h2>
                <p class="section-subtitle text-secondary">Powerful tools designed for modern development teams.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="icon-wrap"><i class="bi bi-archive"></i></div>
                        <h5 class="fw-semibold mb-2">Repository Hosting</h5>
                        <p class="text-secondary mb-0">Store and manage Git repositories with full access control, branch protection, and unlimited history.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="icon-wrap"><i class="bi bi-people"></i></div>
                        <h5 class="fw-semibold mb-2">Collaboration Tools</h5>
                        <p class="text-secondary mb-0">Pull requests, code reviews, and inline comments make team collaboration seamless and efficient.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="icon-wrap"><i class="bi bi-arrow-repeat"></i></div>
                        <h5 class="fw-semibold mb-2">CI/CD Pipelines</h5>
                        <p class="text-secondary mb-0">Automate builds, tests, and deployments with native CI/CD support that integrates into your workflow.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="icon-wrap"><i class="bi bi-shield-check"></i></div>
                        <h5 class="fw-semibold mb-2">Security First</h5>
                        <p class="text-secondary mb-0">Automated vulnerability scanning, secret detection, and dependency audits keep your code secure.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="icon-wrap"><i class="bi bi-bar-chart-line"></i></div>
                        <h5 class="fw-semibold mb-2">Insights &amp; Analytics</h5>
                        <p class="text-secondary mb-0">Track contributions, commit frequency, code churn, and team velocity with rich analytics dashboards.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="icon-wrap"><i class="bi bi-lock"></i></div>
                        <h5 class="fw-semibold mb-2">Public &amp; Private Repos</h5>
                        <p class="text-secondary mb-0">Host open-source projects publicly or keep proprietary code private with fine-grained access control.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if (empty($username)): ?>
    <section class="cta-section">
        <div class="container">
            <div class="cta-box">
                <h2 class="fw-bold mb-2">Ready to get started?</h2>
                <p class="text-secondary mb-4">Join thousands of developers already using PHPGit.</p>
                <a href="index.php?page=register" class="btn btn-primary btn-lg px-5">Create Free Account</a>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>