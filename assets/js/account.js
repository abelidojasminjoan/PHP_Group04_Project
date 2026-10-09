
document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("profileEditForm");
    const saveBtn = document.getElementById("saveProfileBtn");

    if (!form || !saveBtn) return;

    form.addEventListener("submit", function (event) {

        const firstName = document.getElementById("firstName");
        const lastName = document.getElementById("lastName");
        const phone = document.getElementById("phone");

        firstName.value = firstName.value.trim();
        lastName.value = lastName.value.trim();
        phone.value = phone.value.trim();

        if (!firstName.value || !lastName.value) {
            event.preventDefault();
            alert("First name and last name are required.");
            return;
        }

        if (phone.value) {
            const phonePattern = /^[0-9+\s()\-]+$/;

            if (!phonePattern.test(phone.value)) {
                event.preventDefault();
                alert("Please enter a valid phone number.");
                phone.focus();
                return;
            }
        }

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
        saveBtn.textContent = "Saving Changes...";

    });

});
