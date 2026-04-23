(function () {
    const statusBadge = {
        archived: {cls: "text-bg-secondary", label: "Archived"},
        new: {cls: "text-bg-primary", label: "New"},
        replied: {cls: "text-bg-success", label: "Replied"}
    };

    let currentArticle = null;
    let activeFilter = "all";

    window.inboxOpen = function (article) {
        currentArticle = article;
        const d = article.dataset;

        const avatar = document.getElementById("inboxModalAvatar");
        avatar.textContent = d.initials;
        avatar.style.cssText = "width:46px;height:46px;font-size:.84rem;";
        avatar.className = [
            "d-flex align-items-center justify-content-center",
            "rounded-circle text-white fw-bold flex-shrink-0",
            "bg-" + d.color
        ].join(" ");

        document.getElementById("inboxModalSubject").textContent = d.subject;
        document.getElementById("inboxModalSender").textContent = d.name;
        document.getElementById("inboxModalEmail").textContent = d.email;
        document.getElementById("inboxModalTime").textContent = d.time;
        document.getElementById("inboxModalBody").textContent = d.body;

        const meta = statusBadge[d.status] || statusBadge.new;
        const badge = document.getElementById("inboxModalBadge");
        badge.textContent = meta.label;
        badge.className = "badge rounded-pill " + meta.cls;

        const em = encodeURIComponent(d.email);
        const sb = encodeURIComponent("Re: " + d.subject);
        const replyHref = "mailto:" + em + "?subject=" + sb;
        document.getElementById("inboxModalReply").href = replyHref;

        if (d.unread === "1") {
            article.dataset.unread = "0";
            article.classList.remove(
                "border-primary",
                "border-opacity-25",
                "bg-primary",
                "bg-opacity-10"
            );
            article.classList.add(
                "border-secondary-subtle",
                "bg-body-secondary"
            );
            const dot = article.querySelector("[title='Unread']");
            if (dot) {
                dot.removeAttribute("title");
                dot.classList.remove("bg-primary");
            }
            const subj = article.querySelector(".fw-bold");
            if (subj) {
                subj.classList.replace("fw-bold", "fw-semibold");
            }
            _updateBadge(-1);
            _apiMarkRead([parseInt(d.id, 10)]);
        }

        bootstrap.Modal.getOrCreateInstance(
            document.getElementById("inboxModal")
        ).show();
    };

    window.inboxArchive = function () {
        if (!currentArticle) {
            return;
        }
        currentArticle.dataset.status = "archived";
        const badgeEl = currentArticle.querySelector(".badge");
        if (badgeEl) {
            badgeEl.textContent = "Archived";
            badgeEl.className = "badge text-bg-secondary rounded-pill";
            badgeEl.style.fontSize = ".72rem";
        }
        bootstrap.Modal.getInstance(
            document.getElementById("inboxModal")
        ).hide();
        window.inboxFilter();
    };
    window.inboxReplied = function () {
        if (!currentArticle) {
            return;
        }
        currentArticle.dataset.status = "replied";
        const badgeEl = currentArticle.querySelector(".badge");
        if (badgeEl) {
            badgeEl.textContent = "Replied";
            badgeEl.className = "badge text-bg-success rounded-pill";
            badgeEl.style.fontSize = ".72rem";
        }
        bootstrap.Modal.getInstance(
            document.getElementById("inboxModal")
        ).hide();
        window.inboxFilter();
    }

    window.inboxSetTab = function (btn, filter) {
        activeFilter = filter;
        document.querySelectorAll(".inbox-tab").forEach(function (b) {
            b.classList.remove("btn-primary", "active-tab");
            b.classList.add("text-secondary");
        });
        btn.classList.add("btn-primary", "active-tab");
        btn.classList.remove("text-secondary");
        window.inboxFilter();
    };

    window.inboxFilter = function () {
        const qRaw = document.getElementById("inboxSearch").value || "";
        const q = qRaw.toLowerCase();
        const rows = document.querySelectorAll("#inboxList .inbox-msg");
        let visible = 0;

        rows.forEach(function (row) {
            const statusOk = row.dataset.status === activeFilter;
            const matchStatus = activeFilter === "all" || statusOk;
            const inSubj = row.dataset.subject.toLowerCase().includes(q);
            const inName = row.dataset.name.toLowerCase().includes(q);
            const inEmail = row.dataset.email.toLowerCase().includes(q);
            const inBody = row.dataset.body.toLowerCase().includes(q);
            const matchSearch = !q || inSubj || inName || inEmail || inBody;

            const show = matchStatus && matchSearch;
            row.style.display = (
                show
                ? ""
                : "none"
            );
            if (show) {
                visible += 1;
            }
        });

        document.getElementById("inboxEmpty").classList.toggle(
            "d-none",
            visible > 0
        );
        const plural = (
            visible !== 1
            ? "s"
            : ""
        );
        const countMsg = "Showing " + visible + " message" + plural;
        document.getElementById("inboxCount").textContent = countMsg;
    };

    window.inboxMarkAllRead = function () {
        const unreadRows = Array.from(document.querySelectorAll(
            "#inboxList .inbox-msg[data-unread='1']"
        ));
        if (unreadRows.length === 0) {
            return;
        }

        const ids = unreadRows.map(function (row) {
            return parseInt(row.dataset.id, 10);
        });

        unreadRows.forEach(function (row) {
            row.dataset.unread = "0";
            row.classList.remove(
                "border-primary",
                "border-opacity-25",
                "bg-primary",
                "bg-opacity-10"
            );
            row.classList.add("border-secondary-subtle", "bg-body-secondary");
            const dot = row.querySelector("[title='Unread']");
            if (dot) {
                dot.removeAttribute("title");
                dot.classList.remove("bg-primary");
            }
            const subj = row.querySelector(".fw-bold");
            if (subj) {
                subj.classList.replace("fw-bold", "fw-semibold");
            }
        });
        const badge = document.querySelector(".badge.bg-primary.rounded-pill");
        if (badge) {
            badge.remove();
        }
        _apiMarkRead(ids);
    };

    function _apiMarkRead(ids) {
        fetch("/api/v1/markInboxRead.php", {
            body: JSON.stringify({ids}),
            headers: {"Content-Type": "application/json"},
            method: "POST"
        }).catch(function (err) {
            console.error("markInboxRead failed:", err);
        });
    }

    function _updateBadge(delta) {
        const badge = document.querySelector(".badge.bg-primary.rounded-pill");
        if (!badge) {
            return;
        }
        const n = parseInt(badge.textContent) + delta;
        if (n <= 0) {
            badge.remove();
        } else {
            badge.textContent = n + " unread";
        }
    }
}());
