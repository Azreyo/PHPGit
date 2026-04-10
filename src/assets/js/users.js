(function () {
    const modal = document.getElementById('createUserModal');
    if (!modal) {
        return;
    }

    modal.addEventListener('show.bs.modal', function () {
        document.body.classList.add('create-user-modal-open');
    });

    modal.addEventListener('hide.bs.modal', function () {
        document.body.classList.remove('create-user-modal-open');
    });
})();