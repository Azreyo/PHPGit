<?php

declare(strict_types=1);

use App\includes\Logging;
use App\includes\Security;
use Random\RandomException;

$security = new Security();
$contact_success = isset($_GET['success']) && $_GET['success'] === 'sent';
$contact_errors = $_SESSION['contact_errors'] ?? [];
$prefill = $_SESSION['contact_prefill'] ?? [];
unset($_SESSION['contact_errors'], $_SESSION['contact_prefill']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_errors = [];
    if (!$security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $post_errors[] = 'Session expired, please refresh the page and try again.';
    } else {
        $contact_name = trim($_POST['contact_name'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_subject = trim($_POST['contact_subject'] ?? '');
        $contact_message = trim($_POST['contact_message'] ?? '');

        if (empty($contact_name)) {
            $post_errors[] = 'Name is required.';
        }
        if (empty($contact_email)) {
            $post_errors[] = 'Email is required.';
        } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            $post_errors[] = 'Invalid email format.';
        }
        if (empty($contact_subject)) {
            $post_errors[] = 'Subject is required.';
        }
        if (empty($contact_message)) {
            $post_errors[] = 'Message is required.';
        }

        if (empty($post_errors)) {
            // TODO: implement mail sending
            header('Location: index.php?page=contact&success=sent');
            exit;
        }
    }

    $_SESSION['contact_errors'] = $post_errors;
    $_SESSION['contact_prefill'] = [
            'contact_name' => htmlspecialchars(trim($_POST['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'contact_email' => htmlspecialchars(trim($_POST['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'contact_subject' => htmlspecialchars(trim($_POST['contact_subject'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'contact_message' => htmlspecialchars(trim($_POST['contact_message'] ?? ''), ENT_QUOTES, 'UTF-8'),
    ];
    header('Location: index.php?page=contact');
    exit;
}

try {
    $csrf_token = $security->generateCsrfToken();
} catch (RandomException $e) {
    Logging::loggingToFile('Cannot generate csrf token: ' . $e->getMessage(), 4);
    $csrf_token = '';
}
?>
<main>
    <div class="container">
        <section class="about-hero text-center">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <span class="section-label">Contact</span>
                    <h1 class="hero-title mt-2 mb-3">Get in <span class="text-primary">Touch</span></h1>
                    <p class="hero-subtitle text-secondary">Have a question, suggestion, or just want to say hi? We'd love to hear from you.</p>
                </div>
            </div>
        </section>

        <section class="section border-top">
            <div class="contact-wrapper">
                <?php if ($contact_success): ?>
                    <div class="alert alert-success text-center" role="alert">
                        <h5 class="mb-1">Message sent</h5>
                        <p class="mb-0">Thanks for reaching out. We'll get back to you within 24 hours.</p>
                    </div>
                <?php else: ?>
                    <?php if (!empty($contact_errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($contact_errors as $e): ?>
                                    <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="POST" novalidate>
                        <input type="hidden" name="csrf_token"
                               value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="contact_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="contact_name" name="contact_name"
                                       value="<?php echo $prefill['contact_name'] ?? ''; ?>"
                                    placeholder="Jane Doe" required autocomplete="name">
                            </div>
                            <div class="col-sm-6">
                                <label for="contact_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email"
                                       value="<?php echo $prefill['contact_email'] ?? ''; ?>"
                                    placeholder="jane@example.com" required autocomplete="email">
                            </div>
                            <div class="col-12">
                                <label for="contact_subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="contact_subject" name="contact_subject"
                                       value="<?php echo $prefill['contact_subject'] ?? ''; ?>"
                                    placeholder="How can we help?" required>
                            </div>
                            <div class="col-12">
                                <label for="contact_message" class="form-label">Message</label>
                                <textarea class="form-control" id="contact_message" name="contact_message"
                                          rows="6" placeholder="Write your message here..."
                                          required><?php echo $prefill['contact_message'] ?? ''; ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">Send Message</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <section class="section border-top">
            <div class="row g-4 justify-content-center text-center">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="icon-wrap mx-auto"><i class="bi bi-envelope"></i></div>
                        <h6 class="fw-semibold">Email</h6>
                        <p class="text-secondary mb-0">support@phpgit.dev</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="icon-wrap mx-auto"><i class="bi bi-chat-dots"></i></div>
                        <h6 class="fw-semibold">Discord</h6>
                        <p class="text-secondary mb-0">discord.gg/phpgit</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="icon-wrap mx-auto"><i class="bi bi-github"></i></div>
                        <h6 class="fw-semibold">GitHub</h6>
                        <p class="text-secondary mb-0">github.com/phpgit</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>