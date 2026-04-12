(function () {
    const statusBadge = {
        new: {label: 'New', cls: 'text-bg-primary'},
        replied: {label: 'Replied', cls: 'text-bg-success'},
        archived: {label: 'Archived', cls: 'text-bg-secondary'},
    };

    let currentArticle = null;
    let activeFilter = 'all';

    window.inboxOpen = function (article) {
        currentArticle = article;
        const d = article.dataset;

        const avatar = document.getElementById('inboxModalAvatar');
        avatar.textContent = d.initials;
        avatar.style.cssText = 'width:46px;height:46px;font-size:.84rem;';
        avatar.className = 'd-flex align-items-center justify-content-center rounded-circle text-white fw-bold flex-shrink-0 bg-' + d.color;

        document.getElementById('inboxModalSubject').textContent = d.subject;
        document.getElementById('inboxModalSender').textContent = d.name;
        document.getElementById('inboxModalEmail').textContent = d.email;
        document.getElementById('inboxModalTime').textContent = d.time;
        document.getElementById('inboxModalBody').textContent = d.body;

        const meta = statusBadge[d.status] || statusBadge.new;
        const badge = document.getElementById('inboxModalBadge');
        badge.textContent = meta.label;
        badge.className = 'badge rounded-pill ' + meta.cls;

        document.getElementById('inboxModalReply').href =
            'mailto:' + encodeURIComponent(d.email) +
            '?subject=' + encodeURIComponent('Re: ' + d.subject);

        if (d.unread === '1') {
            article.dataset.unread = '0';
            article.classList.remove('border-primary', 'border-opacity-25', 'bg-primary', 'bg-opacity-10');
            article.classList.add('border-secondary-subtle', 'bg-body-secondary');
            const dot = article.querySelector('[title="Unread"]');
            if (dot) {
                dot.removeAttribute('title');
                dot.classList.remove('bg-primary');
            }
            const subj = article.querySelector('.fw-bold');
            if (subj) subj.classList.replace('fw-bold', 'fw-semibold');
            _updateBadge(-1);
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('inboxModal')).show();
    };

    window.inboxArchive = function () {
        if (!currentArticle) return;
        currentArticle.dataset.status = 'archived';
        const badgeEl = currentArticle.querySelector('.badge');
        if (badgeEl) {
            badgeEl.textContent = 'Archived';
            badgeEl.className = 'badge text-bg-secondary rounded-pill';
            badgeEl.style.fontSize = '.72rem';
        }
        bootstrap.Modal.getInstance(document.getElementById('inboxModal')).hide();
        inboxFilter();
    };

    window.inboxSetTab = function (btn, filter) {
        activeFilter = filter;
        document.querySelectorAll('.inbox-tab').forEach(b => {
            b.classList.remove('btn-primary', 'active-tab');
            b.classList.add('text-secondary');
        });
        btn.classList.add('btn-primary');
        btn.classList.remove('text-secondary');
        inboxFilter();
    };

    window.inboxFilter = function () {
        const q = (document.getElementById('inboxSearch').value || '').toLowerCase();
        const rows = document.querySelectorAll('#inboxList .inbox-msg');
        let visible = 0;

        rows.forEach(row => {
            const matchStatus = activeFilter === 'all' || row.dataset.status === activeFilter;
            const matchSearch = !q ||
                row.dataset.subject.toLowerCase().includes(q) ||
                row.dataset.name.toLowerCase().includes(q) ||
                row.dataset.email.toLowerCase().includes(q) ||
                row.dataset.body.toLowerCase().includes(q);

            const show = matchStatus && matchSearch;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        document.getElementById('inboxEmpty').classList.toggle('d-none', visible > 0);
        document.getElementById('inboxCount').textContent =
            'Showing ' + visible + ' message' + (visible !== 1 ? 's' : '');
    };

    window.inboxMarkAllRead = function () {
        document.querySelectorAll('#inboxList .inbox-msg[data-unread="1"]').forEach(row => {
            row.dataset.unread = '0';
            row.classList.remove('border-primary', 'border-opacity-25', 'bg-primary', 'bg-opacity-10');
            row.classList.add('border-secondary-subtle', 'bg-body-secondary');
            const dot = row.querySelector('[title="Unread"]');
            if (dot) {
                dot.removeAttribute('title');
                dot.classList.remove('bg-primary');
            }
            const subj = row.querySelector('.fw-bold');
            if (subj) subj.classList.replace('fw-bold', 'fw-semibold');
        });
        const badge = document.querySelector('.badge.bg-primary.rounded-pill');
        if (badge) badge.remove();
    };

    function _updateBadge(delta) {
        const badge = document.querySelector('.badge.bg-primary.rounded-pill');
        if (!badge) return;
        const n = parseInt(badge.textContent) + delta;
        if (n <= 0) badge.remove();
        else badge.textContent = n + ' unread';
    }
})();