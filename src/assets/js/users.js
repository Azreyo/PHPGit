(function () {
    const pageSize = 10;
    let currentPage = 1;
    let lastPage = 1;

    function updatePagination(totalUsers) {
        const indicator = document.getElementById("usersPageIndicator");
        const prevItem = document.getElementById("usersPagePrevItem");
        const nextItem = document.getElementById("usersPageNextItem");
        const prevButton = document.getElementById("usersPagePrev");
        const nextButton = document.getElementById("usersPageNext");

        const hasResults = totalUsers > 0;
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

    function applyUsersPagination() {
        const rows = Array.from(document.querySelectorAll("#usersList .users-row"));
        const totalUsers = rows.length;
        let maxPage = 1;
        if (totalUsers > 0) {
            maxPage = Math.ceil(totalUsers / pageSize);
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
        let visibleUsers = 0;

        rows.forEach(function (row, index) {
            const isVisible = index >= pageStart && index < pageEnd;
            row.classList.toggle("d-none", !isVisible);
            if (isVisible) {
                visibleUsers += 1;
            }
        });

        const usersCount = document.getElementById("usersCount");
        if (usersCount) {
            usersCount.textContent = "Showing " + visibleUsers + " of " + totalUsers + " users";
        }

        updatePagination(totalUsers);
    }

    const prevButton = document.getElementById("usersPagePrev");
    const nextButton = document.getElementById("usersPageNext");
    if (prevButton) {
        prevButton.addEventListener("click", function () {
            if (currentPage <= 1) {
                return;
            }
            currentPage -= 1;
            applyUsersPagination();
        });
    }
    if (nextButton) {
        nextButton.addEventListener("click", function () {
            if (currentPage >= lastPage) {
                return;
            }
            currentPage += 1;
            applyUsersPagination();
        });
    }

    applyUsersPagination();

    const createModal = document.getElementById("createUserModal");
    if (createModal) {
        createModal.addEventListener("show.bs.modal", function () {
            document.body.classList.add("create-user-modal-open");
        });

        createModal.addEventListener("hide.bs.modal", function () {
            document.body.classList.remove("create-user-modal-open");
        });
    }

    const editModal = document.getElementById("editUserModal");
    if (editModal) {
        editModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            if (!button) {
                return;
            }

            const id = button.getAttribute("data-bs-id");
            const username = button.getAttribute("data-bs-username");
            const email = button.getAttribute("data-bs-email");
            const displayName = button.getAttribute("data-bs-display-name");
            const role = button.getAttribute("data-bs-role");
            const status = button.getAttribute("data-bs-status");
            const bio = button.getAttribute("data-bs-bio");

            editModal.querySelector("input[name=\"user_id\"]").value = (
                id || ""
            );
            editModal.querySelector("input[name=\"username\"]").value = (
                username || ""
            );
            editModal.querySelector("input[name=\"email\"]").value = (
                email || ""
            );
            editModal.querySelector("input[name=\"display_name\"]").value = (
                displayName || ""
            );
            editModal.querySelector("select[name=\"role\"]").value = (
                role || "USER"
            );
            editModal.querySelector("select[name=\"status\"]").value = (
                status || "ACTIVE"
            );
            editModal.querySelector("textarea[name=\"bio\"]").value = (
                bio || ""
            );

            document.body.classList.add("create-user-modal-open");
        });

        editModal.addEventListener("hide.bs.modal", function () {
            document.body.classList.remove("create-user-modal-open");
        });
    }
}());
