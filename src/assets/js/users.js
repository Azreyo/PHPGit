(function () {
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
