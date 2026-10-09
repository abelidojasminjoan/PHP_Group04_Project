
document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("profileEditModal");
    const openBtn = document.getElementById("openProfileEdit");
    const closeBtn = document.getElementById("closeProfileEdit");
    const cancelBtn = document.getElementById("cancelProfileEdit");
    const form = document.getElementById("profileEditForm");
    const saveBtn = document.getElementById("saveProfileBtn");

    if (!modal || !openBtn || !form) return;

    function openModal() {
        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
        document.getElementById("firstName").focus();
    }

    function closeModal() {
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
        openBtn.focus();
    }

    openBtn.addEventListener("click", openModal);

    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);

    modal.addEventListener("click", function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" &&
            modal.classList.contains("show")) {
            closeModal();
        }
    });

    form.addEventListener("submit", function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            return;
        }

        if (saveBtn.disabled) {
            event.preventDefault();
            return;
        }

        saveBtn.disabled = true;
        saveBtn.textContent = "Saving...";
    });

});
