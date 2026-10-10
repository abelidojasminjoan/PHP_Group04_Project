document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("addProductModal");
    const openButton = document.getElementById("openAddProductModal");
    const form = document.getElementById("addProductForm");

    if (!modal || !form || !openButton) {
        return;
    }

    const submitButton = document.getElementById("submitProductButton");
    const closeButtons = modal.querySelectorAll("[data-close-form-modal]");

    let lastFocusedElement = null;

    /* OPEN MODAL */

    function openModal() {
        lastFocusedElement = document.activeElement;

        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");

        document.body.classList.add("form-modal-open");

        document.getElementById("productName")?.focus();
    }

    /* CLOSE MODAL */

    function closeModal() {
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");

        document.body.classList.remove("form-modal-open");

        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
    }

    openButton.addEventListener("click", openModal);

    closeButtons.forEach(button => {
        button.addEventListener("click", closeModal);
    });

    document.addEventListener("keydown", function (event) {

        if (!modal.classList.contains("is-open")) {
            return;
        }

        if (event.key === "Escape") {
            closeModal();
        }

        if (event.key === "Tab") {

            const focusable = Array.from(
                modal.querySelectorAll(
                    'button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled])'
                )
            ).filter(element => element.getClientRects().length > 0);

            if (!focusable.length) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    /* SEARCH CHECKBOXES */

    function setupCheckboxSearch(inputId, listId) {

        const searchInput = document.getElementById(inputId);
        const list = document.getElementById(listId);

        if (!searchInput || !list) {
            return;
        }

        const options = list.querySelectorAll(".product-checkbox");

        searchInput.addEventListener("input", function () {

            const query = searchInput.value
                .trim()
                .toLowerCase();

            options.forEach(option => {

                const text = option.textContent.toLowerCase();

                option.hidden = !text.includes(query);
            });
        });
    }

    setupCheckboxSearch("concernSearch", "concernOptions");
    setupCheckboxSearch("ingredientSearch", "ingredientOptions");

    /* DYNAMIC PRODUCT SIZES */

    const variantRows = document.getElementById("variantRows");
    const addSizeButton = document.getElementById("addSizeButton");

    function updateRemoveButtons() {

        const rows = variantRows.querySelectorAll(".variant-row");

        rows.forEach(row => {
            const button = row.querySelector(".variant-remove");

            button.disabled = rows.length === 1;
        });

        addSizeButton.disabled = rows.length >= 50;
    }

    addSizeButton.addEventListener("click", function () {

        if (variantRows.querySelectorAll(".variant-row").length >= 50) {
            return;
        }

        const firstRow = variantRows.querySelector(".variant-row");
        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll("input").forEach(input => {

            if (input.name === "variant_stock[]") {
                input.value = "0";
            } else if (input.name === "variant_threshold[]") {
                input.value = "15";
            } else {
                input.value = "";
            }
        });

        variantRows.appendChild(newRow);

        updateRemoveButtons();

        newRow.querySelector('input[name="size_capacity[]"]').focus();
    });

    variantRows.addEventListener("click", function (event) {

        const removeButton = event.target.closest(".variant-remove");

        if (!removeButton) {
            return;
        }

        const rows = variantRows.querySelectorAll(".variant-row");

        if (rows.length <= 1) {
            return;
        }

        removeButton.closest(".variant-row").remove();

        updateRemoveButtons();
    });

    updateRemoveButtons();

    /* ==========================================
    PRODUCT IMAGES
    ========================================== */

    const imageUrlInput = document.getElementById("productImageUrl");
    const addImageUrlButton = document.getElementById("addImageUrl");

    const imageFileInput = document.getElementById("productImageFiles");
    const uploadImagesButton = document.getElementById("uploadImagesButton");

    const imageList = document.getElementById("productImageList");
    const imageUrlFields = document.getElementById("productImageUrlFields");

    let imageEntries = [];

    /* COUNT IMAGES */

    function imageCount() {
        return imageEntries.length;
    }

    /* SYNCHRONIZE IMAGES WITH FORM */

    function syncImageFields() {

        imageUrlFields.replaceChildren();

        imageEntries.forEach(entry => {

            if (entry.type !== "url") {
                return;
            }

            const input = document.createElement("input");

            input.type = "hidden";
            input.name = "image_urls[]";
            input.value = entry.value;

            imageUrlFields.appendChild(input);
        });

        const transfer = new DataTransfer();

        imageEntries.forEach(entry => {

            if (entry.type === "file") {
                transfer.items.add(entry.file);
            }
        });

        imageFileInput.files = transfer.files;
    }

    /* DISPLAY IMAGE THUMBNAILS */

    function renderImageList() {

        imageList.replaceChildren();

        imageEntries.forEach((entry, index) => {

            const item = document.createElement("div");
            item.className = "product-image-item";

            /* IMAGE */

            const img = document.createElement("img");

            img.src = entry.type === "url"
                ? entry.value
                : entry.preview;

            img.alt = "Product image " + (index + 1);

            img.onerror = function () {

                img.style.display = "none";

                if (!item.querySelector(".product-image-error")) {

                    const errorIcon = document.createElement("div");
                    errorIcon.className = "product-image-error";

                    const icon = document.createElement("i");
                    icon.className = "fa-regular fa-image";

                    errorIcon.appendChild(icon);
                    item.insertBefore(errorIcon, item.firstChild);
                }
            };

            item.appendChild(img);

            /* REMOVE BUTTON */

            const removeButton = document.createElement("button");

            removeButton.type = "button";
            removeButton.className = "product-image-remove";
            removeButton.innerHTML = '<i class="fa-solid fa-xmark"></i>';

            removeButton.setAttribute(
                "aria-label",
                "Remove image " + (index + 1)
            );

            removeButton.addEventListener("click", function () {

                const removed = imageEntries.splice(index, 1)[0];

                if (removed.type === "file") {
                    URL.revokeObjectURL(removed.preview);
                }

                refreshImages();
            });

            item.appendChild(removeButton);

            /* PRIMARY IMAGE BADGE */

            if (index === 0) {

                const primaryBadge = document.createElement("span");

                primaryBadge.className = "product-image-primary";
                primaryBadge.textContent = "Primary";

                item.appendChild(primaryBadge);
            }

            imageList.appendChild(item);
        });
    }

    /* REFRESH IMAGES */

    function refreshImages() {
        syncImageFields();
        renderImageList();
    }

    /* ADD IMAGE URL */

    addImageUrlButton.addEventListener("click", function () {

        const url = imageUrlInput.value.trim();

        if (!url) {
            imageUrlInput.focus();
            return;
        }

        try {

            const parsed = new URL(url);

            if (!["http:", "https:"].includes(parsed.protocol)) {
                throw new Error("Invalid image URL");
            }

        } catch {

            alert("Please enter a valid HTTP or HTTPS image URL.");
            return;
        }

        if (url.length > 2048) {
            alert("Image URL is too long.");
            return;
        }

        if (imageCount() >= 10) {
            alert("Maximum of 10 product images.");
            return;
        }

        imageEntries.push({
            type: "url",
            value: url
        });

        imageUrlInput.value = "";

        refreshImages();
    });

    /* ENTER KEY ADDS URL */

    imageUrlInput.addEventListener("keydown", function (event) {

        if (event.key === "Enter") {
            event.preventDefault();
            addImageUrlButton.click();
        }
    });

    /* OPEN FILE PICKER */

    uploadImagesButton.addEventListener("click", function () {
        imageFileInput.click();
    });

    /* UPLOAD MULTIPLE IMAGES */

    imageFileInput.addEventListener("change", function () {

        const files = Array.from(imageFileInput.files);

        // Reset before synchronizing selected files.
        imageFileInput.value = "";

        for (const file of files) {

            if (imageCount() >= 10) {
                alert("You can upload a maximum of 10 images.");
                break;
            }

            const allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            if (!allowedTypes.includes(file.type)) {

                alert("Only JPG, PNG, and WebP images are allowed.");
                continue;
            }

            if (file.size > 5 * 1024 * 1024) {

                alert("Each image must be 5 MB or smaller.");
                continue;
            }

            imageEntries.push({
                type: "file",
                file: file,
                preview: URL.createObjectURL(file)
            });
        }

        refreshImages();
    });

    /* SUBMIT VALIDATION */

    form.addEventListener("submit", function (event) {

        const rows = variantRows.querySelectorAll(".variant-row");

        if (!rows.length) {
            event.preventDefault();
            alert("Please add at least one product size.");
            return;
        }

        const seenVariants = new Set();

        for (const row of rows) {

            const size = row.querySelector(
                'input[name="size_capacity[]"]'
            ).value.trim();

            const label = row.querySelector(
                'input[name="variant_label[]"]'
            ).value.trim();

            const key = (size + "|" + label).toLowerCase();

            if (seenVariants.has(key)) {
                event.preventDefault();

                alert("Duplicate size and label combination.");

                return;
            }

            seenVariants.add(key);
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            return;
        }

        syncImageFields();

        submitButton.disabled = true;
        submitButton.textContent = "Adding Product...";
    });

});