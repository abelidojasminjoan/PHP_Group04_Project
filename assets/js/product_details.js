
document.addEventListener("DOMContentLoaded", () => {

    /* ================================
       PRODUCT IMAGE GALLERY
    ================================ */

    const mainImage = document.getElementById("pdMainImage");
    const thumbnails = document.querySelectorAll(".pd-thumbnail");

    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener("click", () => {

            const imageUrl = thumbnail.dataset.image;

            if (mainImage && imageUrl) {
                mainImage.src = imageUrl;
            }

            thumbnails.forEach(item => {
                item.classList.remove("active");
            });

            thumbnail.classList.add("active");
        });
    });

    /* ================================
       PRODUCT VARIANTS
    ================================ */

    const variants = document.querySelectorAll(".pd-variant");

    const priceDisplay = document.getElementById("pdPrice");
    const stockDisplay = document.getElementById("pdStock");

    const variantInput = document.getElementById("pdVariantInput");

    const quantityInput = document.getElementById("pdQuantity");
    const decreaseButton = document.getElementById("pdDecrease");
    const increaseButton = document.getElementById("pdIncrease");

    const addButton = document.getElementById("pdAddButton");

    let selectedPrice = 0;
    let selectedStock = 0;

    const money = amount => {
        return "₱" + Number(amount).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    function updatePurchaseDisplay() {

        if (!quantityInput || !addButton) {
            return;
        }

        const quantity = Number(quantityInput.value) || 1;

        if (selectedStock <= 0) {
            addButton.disabled = true;
            addButton.textContent = "Out of Stock";
            return;
        }

        addButton.disabled = false;

        addButton.textContent =
            "Add to Bag — " + money(selectedPrice * quantity);
    }

    function selectVariant(variant) {

        if (!variant || variant.disabled) {
            return;
        }

        variants.forEach(item => {
            item.classList.remove("active");
            item.setAttribute("aria-pressed", "false");
        });

        variant.classList.add("active");
        variant.setAttribute("aria-pressed", "true");

        selectedPrice = Number(variant.dataset.price) || 0;
        selectedStock = Number(variant.dataset.stock) || 0;

        if (variantInput) {
            variantInput.value = variant.dataset.id;
        }

        if (priceDisplay) {
            priceDisplay.textContent = money(selectedPrice);
        }

        if (stockDisplay) {

            if (selectedStock > 0) {
                stockDisplay.textContent =
                    "In Stock (" + selectedStock + ")";

                stockDisplay.classList.remove("out");

            } else {
                stockDisplay.textContent = "Out of Stock";
                stockDisplay.classList.add("out");
            }
        }

        if (quantityInput) {
            quantityInput.value = 1;
            quantityInput.max = Math.max(1, selectedStock);
        }

        updatePurchaseDisplay();
    }

    variants.forEach(variant => {
        variant.addEventListener("click", () => {
            selectVariant(variant);
        });
    });

    const initialVariant =
        document.querySelector(".pd-variant.active:not(:disabled)");

    if (initialVariant) {
        selectVariant(initialVariant);
    } else {
        if (addButton) {
            addButton.disabled = true;
            addButton.textContent = "Out of Stock";
        }
    }

    /* ================================
       QUANTITY SELECTOR
    ================================ */

    if (decreaseButton && quantityInput) {

        decreaseButton.addEventListener("click", () => {

            let quantity = Number(quantityInput.value) || 1;

            quantity = Math.max(1, quantity - 1);

            quantityInput.value = quantity;

            updatePurchaseDisplay();
        });
    }

    if (increaseButton && quantityInput) {

        increaseButton.addEventListener("click", () => {

            let quantity = Number(quantityInput.value) || 1;

            if (quantity < selectedStock) {
                quantity++;
            }

            quantityInput.value = quantity;

            updatePurchaseDisplay();
        });
    }

    /* ================================
       INFORMATION TABS
    ================================ */

    const tabs = document.querySelectorAll(".pd-tab");
    const panels = document.querySelectorAll(".pd-tab-panel");

    tabs.forEach(tab => {

        tab.addEventListener("click", () => {

            const target = tab.dataset.tab;

            tabs.forEach(item => {
                item.classList.remove("active");
                item.setAttribute("aria-selected", "false");
            });

            panels.forEach(panel => {
                panel.classList.remove("active");
                panel.hidden = true;
            });

            tab.classList.add("active");
            tab.setAttribute("aria-selected", "true");

            const targetPanel =
                document.getElementById("pd-panel-" + target);

            if (targetPanel) {
                targetPanel.hidden = false;
                targetPanel.classList.add("active");
            }
        });
    });

});
