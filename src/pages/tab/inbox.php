<?php

use App\Config;
use App\includes\Assets;
use App\includes\Logging;

$config = new Config();
$pdo = $config->getPDO();

try {
    if ($pdo !== null) {
        $stmt = $pdo->prepare('SELECT id, username as name, email, subject, body, status, created_at AS time, unread FROM inbox ORDER BY created_at DESC;');
        $stmt->execute();
        $messages = $stmt->fetchAll();
    } else {
        throw new Exception('Database connection not established.');
    }
} catch (Exception $e) {
    Logging::loggingToFile('Error loading inbox: ' . $e->getMessage(), 4);
    echo '<div class="alert alert-danger">An error occurred while loading the inbox. Please try again later.</div>';

    return;
}

$unreadCount = count(array_filter($messages, fn ($m) => $m['unread']));

$statusMeta = [
    'new' => ['label' => 'New', 'class' => 'text-bg-primary'],
    'replied' => ['label' => 'Replied', 'class' => 'text-bg-success'],
    'archived' => ['label' => 'Archived', 'class' => 'text-bg-secondary'],
];

$avatarColor = [
    'new' => 'primary',
    'replied' => 'success',
    'archived' => 'secondary',
];

function inboxInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $i = strtoupper(substr($parts[0] ?? '?', 0, 1));
    if (isset($parts[1])) {
        $i .= strtoupper(substr($parts[1], 0, 1));
    }

    return $i;
}

?>

<div class="d-flex align-items-center gap-3 mb-5 pb-4 border-bottom border-secondary-subtle">
    <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
         style="width: 34px; height: 34px; font-size: .9rem;">
        <i class="bi bi-envelope-fill"></i>
    </div>
    <div class="flex-grow-1">
        <p class="section-label mb-0">Inbox</p>
        <h6 class="fw-bold mb-0" style="letter-spacing: -0.01em;">Contact form submissions</h6>
    </div>
    <?php if ($unreadCount > 0): ?>
        <span class="badge rounded-pill bg-primary px-3 py-2" style="font-size: .78rem;">
            <?php echo $unreadCount; ?> unread
        </span>
    <?php endif; ?>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                onclick="inboxMarkAllRead()">
            <i class="bi bi-check2-all me-2"></i>Mark all read
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3">
            <i class="bi bi-archive me-2"></i>Archive read
        </button>
    </div>
    <div class="input-group input-group-sm" style="max-width: 340px;">
        <span class="input-group-text border-secondary-subtle text-secondary rounded-start-3 bg-body-secondary">
            <i class="bi bi-search"></i>
        </span>
        <label for="inboxSearch">
        </label><input type="search" id="inboxSearch"
                                                class="form-control border-secondary-subtle bg-body-secondary rounded-end-3"
                                                placeholder="Search messages by subject…" oninput="inboxFilter()">
    </div>
</div>

<div class="d-flex gap-1 mb-4 p-1 rounded-3 bg-body-secondary border border-secondary-subtle"
     style="width: fit-content;">
    <button type="button" class="inbox-tab btn btn-primary btn-sm rounded-2 px-3 active-tab" data-filter="all"
            style="font-size: .82rem;" onclick="inboxSetTab(this, 'all')">All
    </button>
    <button type="button" class="inbox-tab btn btn-sm rounded-2 px-3 text-secondary" data-filter="new"
            style="font-size: .82rem;" onclick="inboxSetTab(this, 'new')">New
    </button>
    <button type="button" class="inbox-tab btn btn-sm rounded-2 px-3 text-secondary" data-filter="replied"
            style="font-size: .82rem;" onclick="inboxSetTab(this, 'replied')">Replied
    </button>
    <button type="button" class="inbox-tab btn btn-sm rounded-2 px-3 text-secondary" data-filter="archived"
            style="font-size: .82rem;" onclick="inboxSetTab(this, 'archived')">Archived
    </button>
    <button type="button" class="inbox-tab btn btn-sm rounded-2 px-3 text-secondary" data-filter="archived"
            style="font-size: .82rem;" onclick="inboxSetTab(this, 'archived')">Archived
    </button>
</div>

<div id="inboxList" class="d-flex flex-column gap-2">
    <?php foreach ($messages as $msg):
        $initials = inboxInitials($msg['name']);
        $color = $avatarColor[$msg['status']] ?? 'secondary';
        $badge = $statusMeta[$msg['status']] ?? $statusMeta['new'];
        $preview = mb_strimwidth(str_replace("\n", ' ', $msg['body']), 0, 100, '…');
        ?>
        <article class="inbox-msg d-flex align-items-start gap-3 p-3 rounded-3 border
                        <?php echo $msg['unread'] ? 'border-primary border-opacity-25 bg-primary bg-opacity-10' : 'border-secondary-subtle bg-body-secondary'; ?>"
                 style="cursor: pointer; transition: filter 0.15s ease;"
                 onmouseenter="this.style.filter='brightness(1.04)'"
                 onmouseleave="this.style.filter=''"
                 onclick="inboxOpen(this)"
                 data-status="<?php echo htmlspecialchars($msg['status'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-unread="<?php echo $msg['unread'] ? '1' : '0'; ?>"
                 data-id="<?php echo (int) $msg['id']; ?>"
                 data-name="<?php echo htmlspecialchars($msg['name'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-email="<?php echo htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-subject="<?php echo htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-body="<?php echo htmlspecialchars($msg['body'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-time="<?php echo htmlspecialchars($msg['time'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-color="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>"
                 data-initials="<?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-bold flex-shrink-0 bg-<?php echo $color; ?>"
                 style="width: 42px; height: 42px; font-size: .78rem; letter-spacing: -.01em; flex-shrink: 0;">
                <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-1 flex-wrap">
                    <span class="fw-<?php echo $msg['unread'] ? 'bold' : 'semibold'; ?> text-truncate"
                          style="font-size: .9rem; max-width: 75%;">
                        <?php echo htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="text-secondary flex-shrink-0" style="font-size: .78rem;">
                        <?php echo htmlspecialchars($msg['time'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <small class="text-secondary fw-semibold">
                        <?php echo htmlspecialchars($msg['name'], ENT_QUOTES, 'UTF-8'); ?>
                        <span class="text-secondary fw-normal opacity-75">&lt;<?php echo htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8'); ?>&gt;</span>
                    </small>
                </div>
                <small class="text-secondary text-truncate d-block mt-1 opacity-75">
                    <?php echo htmlspecialchars($preview, ENT_QUOTES, 'UTF-8'); ?>
                </small>
            </div>

            <div class="d-flex flex-column align-items-end gap-2 flex-shrink-0">
                <?php if ($msg['unread']): ?>
                    <span class="rounded-circle bg-primary d-block" title="Unread"
                          style="width: 8px; height: 8px; flex-shrink: 0;"></span>
                <?php else: ?>
                    <span style="width: 8px; height: 8px; display: block;"></span>
                <?php endif; ?>
                <span class="badge <?php echo $badge['class']; ?> rounded-pill" style="font-size: .72rem;">
                    <?php echo $badge['label']; ?>
                </span>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<div id="inboxEmpty" class="text-center py-5 d-none">
    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mx-auto mb-3"
         style="width: 56px; height: 56px; font-size: 1.4rem;">
        <i class="bi bi-inbox"></i>
    </div>
    <p class="fw-semibold mb-1">No messages here</p>
    <small class="text-secondary">Nothing matches the current filter.</small>
</div>

<div class="modal fade" id="inboxModal" tabindex="-1" aria-labelledby="inboxModalSubject" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border border-secondary-subtle shadow-lg" style="overflow: hidden;">

            <div class="modal-header border-bottom border-secondary-subtle px-4 py-3">
                <div class="d-flex align-items-center gap-3 flex-grow-1 overflow-hidden">
                    <div id="inboxModalAvatar"
                         class="d-flex align-items-center justify-content-center rounded-circle text-white fw-bold flex-shrink-0"
                         style="width: 46px; height: 46px; font-size: .84rem;"></div>
                    <div class="overflow-hidden">
                        <h6 class="fw-bold mb-0 text-truncate" id="inboxModalSubject"
                            style="letter-spacing: -.01em;"></h6>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <small class="text-secondary" id="inboxModalSender"></small>
                            <span id="inboxModalBadge" class="badge rounded-pill" style="font-size: .7rem;"></span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="px-4 py-2 border-bottom border-secondary-subtle bg-body-secondary d-flex align-items-center gap-4 flex-wrap">
                <small class="text-secondary">
                    <i class="bi bi-envelope me-1 opacity-50"></i>
                    <span id="inboxModalEmail" class="text-primary"></span>
                </small>
                <small class="text-secondary">
                    <i class="bi bi-clock me-1 opacity-50"></i>
                    <span id="inboxModalTime"></span>
                </small>
            </div>

            <div class="modal-body px-4 py-4">
                <pre id="inboxModalBody"
                     style="white-space: pre-wrap; word-break: break-word; font-family: inherit; font-size: .95rem; line-height: 1.75; margin: 0;"></pre>
            </div>

            <div class="modal-footer border-top border-secondary-subtle px-4 py-3 gap-2 justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3"
                        onclick="inboxArchive()">
                    <i class="bi bi-archive me-2"></i>Archive
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                            data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>Close
                    </button>
                    <a id="inboxModalReply" href="#" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-reply me-2"></i>Reply via email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mt-5 pt-4 border-top border-secondary-subtle">
    <small class="text-secondary" id="inboxCount">
        Showing <?php echo count($messages); ?> message<?php echo count($messages) !== 1 ? 's' : ''; ?>
    </small>
    <nav aria-label="Inbox pagination">
        <ul class="pagination pagination-sm mb-0 gap-1">
            <li class="page-item disabled">
                <span class="page-link rounded-3 border-secondary-subtle bg-body-secondary text-secondary">
                    <i class="bi bi-chevron-left"></i>
                </span>
            </li>
            <li class="page-item active">
                <span class="page-link rounded-3 bg-primary border-primary">1</span>
            </li>
            <li class="page-item">
                <a class="page-link rounded-3 border-secondary-subtle bg-body-secondary text-secondary" href="#">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
</div>

<script src="<?= Assets::url('assets/js/inbox.js') ?>"></script>