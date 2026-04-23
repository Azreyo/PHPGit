(function () {
    const statusBadge = {
        archived: {cls: "text-bg-secondary", label: "Archived", color: "secondary"},
        new: {cls: "text-bg-primary", label: "New", color: "primary"},
        replied: {cls: "text-bg-success", label: "Replied", color: "success"}
    };

    let currentArticle = null;
    let activeFilter = "all";
    const pageSize = 10;
    let currentPage = 1;
    let lastPage = 1;
    const markAllReadButton = document.getElementById("inboxMarkAllReadBtn");
    let markReadEndpoint = "/api/v1/markInboxRead.php";
    if (markAllReadButton && markAllReadButton.dataset.markReadEndpoint) {
        markReadEndpoint = markAllReadButton.dataset.markReadEndpoint;
    }
    const updateStatusEndpoint = "/api/v1/updateInboxStatus.php";

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
        const id = parseInt(currentArticle.dataset.id, 10);
        _apiUpdateStatus(id, "archived");
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
        window.inboxFilter(false);
    };
    window.inboxReplied = function () {
        if (!currentArticle) {
            return;
        }
        const id = parseInt(currentArticle.dataset.id, 10);
        _apiUpdateStatus(id, "replied");
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
        window.inboxFilter(false);
    };

    window.inboxSetTab = function (btn, filter) {
        activeFilter = filter;
        document.querySelectorAll(".inbox-tab").forEach(function (b) {
            b.classList.remove("btn-primary", "active-tab");
            b.classList.add("text-secondary");
        });
        btn.classList.add("btn-primary", "active-tab");
        btn.classList.remove("text-secondary");
        window.inboxFilter(true);
    };

    window.inboxFilter = function (resetPage = true) {
        const qRaw = document.getElementById("inboxSearch").value || "";
        const q = qRaw.toLowerCase().trim();
        const rows = Array.from(
            document.querySelectorAll("#inboxList .inbox-msg")
        );
        const matchedRows = [];

        rows.forEach(function (row) {
            const statusOk = row.dataset.status === activeFilter;
            const matchStatus = activeFilter === "all" || statusOk;
            const subjectRaw = row.dataset.subject || "";
            const subject = String(subjectRaw).toLowerCase();
            const matchSearch = !q || subject.indexOf(q) >= 0;

            if (matchStatus && matchSearch) {
                matchedRows.push(row);
            }
        });

        const totalMatches = matchedRows.length;
        let maxPage = 1;
        if (totalMatches > 0) {
            maxPage = Math.ceil(totalMatches / pageSize);
        }

        if (resetPage) {
            currentPage = 1;
        }

        if (currentPage > maxPage) {
            currentPage = maxPage;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }
        lastPage = maxPage;

        const pageStart = (currentPage - 1) * pageSize;
        const pageEnd = pageStart + pageSize;
        let visible = 0;

        rows.forEach(function (row) {
            row.classList.add("d-none");
        });

        matchedRows.forEach(function (row, index) {
            if (index >= pageStart && index < pageEnd) {
                row.classList.remove("d-none");
                visible += 1;
            }
        });

        document.getElementById("inboxEmpty").classList.toggle(
            "d-none",
            totalMatches > 0
        );

        let countMsg = "Showing 0 messages";
        if (totalMatches > 0) {
            if (visible === totalMatches) {
                let plural = "";
                if (totalMatches !== 1) {
                    plural = "s";
                }
                countMsg = "Showing " + totalMatches + " message" + plural;
            } else {
                countMsg = "Showing " + visible;
                countMsg += " of " + totalMatches + " messages";
            }
        }

        document.getElementById("inboxCount").textContent = countMsg;
        _updatePagination(totalMatches);
    };

    function _updatePagination(totalMatches) {
        const indicator = document.getElementById("inboxPageIndicator");
        const prevItem = document.getElementById("inboxPagePrevItem");
        const nextItem = document.getElementById("inboxPageNextItem");
        const prevButton = document.getElementById("inboxPagePrev");
        const nextButton = document.getElementById("inboxPageNext");

        const hasResults = totalMatches > 0;
        let maxPage = 0;
        if (hasResults) {
            maxPage = lastPage;
        }
        const canGoPrev = hasResults && currentPage > 1;
        const canGoNext = hasResults && currentPage < maxPage;

        if (indicator) {
            if (hasResults) {
                indicator.textContent = "Page " + currentPage + " / " + maxPage;
            } else {
                indicator.textContent = "Page 0 / 0";
            }
        }

        if (prevItem) {
            prevItem.classList.toggle("disabled", !canGoPrev);
        }
        if (nextItem) {
            nextItem.classList.toggle("disabled", !canGoNext);
        }
        if (prevButton) {
            prevButton.disabled = !canGoPrev;
        }
        if (nextButton) {
            nextButton.disabled = !canGoNext;
        }
    }

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
        return fetch(markReadEndpoint, {
            body: JSON.stringify({ids}),
            credentials: "same-origin",
            headers: {"Content-Type": "application/json"},
            method: "POST"
        }).then(function (response) {
            if (!response.ok) {
                throw new Error(
                    "markInboxRead failed with status " + response.status
                );
            }
            return response.json();
        }).then(function (data) {
            if (data && typeof data.error === "string") {
                throw new Error(data.error);
            }
            return data;
        }).catch(function (err) {
            console.error("markInboxRead failed:", err);
            return null;
        });
    }

    function _apiUpdateStatus(id, status) {
        return fetch(updateStatusEndpoint, {
            body: JSON.stringify({id, status}),
            credentials: "same-origin",
            headers: {"Content-Type": "application/json"},
            method: "POST"
        }).then(function (response) {
            if (!response.ok) {
                throw new Error(
                    "updateInboxStatus failed with status " + response.status
                );
            }
            return response.json();
        }).then(function (data) {
            if (data && typeof data.error === "string") {
                throw new Error(data.error);
            }
            return data;
        }).catch(function (err) {
            console.error("updateInboxStatus failed:", err);
            return null;
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

    const prevPageButton = document.getElementById("inboxPagePrev");
    const nextPageButton = document.getElementById("inboxPageNext");

    if (prevPageButton) {
        prevPageButton.addEventListener("click", function () {
            if (currentPage <= 1) {
                return;
            }
            currentPage -= 1;
            window.inboxFilter(false);
        });
    }

    if (nextPageButton) {
        nextPageButton.addEventListener("click", function () {
            if (currentPage >= lastPage) {
                return;
            }
            currentPage += 1;
            window.inboxFilter(false);
        });
    }

    if (markAllReadButton) {
        markAllReadButton.addEventListener("click", function () {
            window.inboxMarkAllRead();
        });
    }

    const archiveReadButton = document.getElementById("inboxArchiveReadBtn");
    if (archiveReadButton) {
        archiveReadButton.addEventListener("click", function () {
            window.inboxArchiveRead();
        });
    }

    window.inboxArchiveRead = function () {
        const readRows = Array.from(document.querySelectorAll(
            "#inboxList .inbox-msg[data-unread='0']"
        ));
        if (readRows.length === 0) {
            return;
        }

        readRows.forEach(function (row) {
            const id = parseInt(row.dataset.id, 10);
            _apiUpdateStatus(id, "archived");
            row.dataset.status = "archived";
            const badgeEl = row.querySelector(".badge");
            if (badgeEl) {
                badgeEl.textContent = "Archived";
                badgeEl.className = "badge text-bg-secondary rounded-pill";
                badgeEl.style.fontSize = ".72rem";
            }
        });
        window.inboxFilter(false);
    };

    window.inboxFilter(false);
}());
