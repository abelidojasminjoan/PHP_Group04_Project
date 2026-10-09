
/* =========================================================
   PUREVIA PRODUCT DETAILS JAVASCRIPT
========================================================= */

document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
       ELEMENTS
    ====================================================== */

    const form = document.getElementById('productForm');

    if (!form) return;

    const radios = [
        ...form.querySelectorAll(
            'input[name="variant_id"]'
        )
    ];

    const quantity = document.getElementById('quantity');

    const displayPrice = document.getElementById('displayPrice');

    const stockStatus = document.getElementById('stockStatus');

    const addToBag = document.getElementById('addToBag');

    const cartMessage = document.getElementById('cartMessage');

    const decreaseButton = document.getElementById('decreaseQty');

    const increaseButton = document.getElementById('increaseQty');


    /* =====================================================
       PESO FORMATTER
    ====================================================== */

    const formatPeso = value => {

        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(value);

    };


    /* =====================================================
       GET SELECTED VARIANT
    ====================================================== */

    function getSelectedVariant() {

        return radios.find(
            radio => radio.checked && !radio.disabled
        );

    }


    /* =====================================================
       GET VARIANT DETAILS
    ====================================================== */

    function getVariantDetails() {

        const selected = getSelectedVariant();

        if (!selected) return null;

        return {

            price: Number(selected.dataset.price),

            stock: Math.max(
                0,
                Number(selected.dataset.stock)
            )

        };

    }


    /* =====================================================
       VALIDATE QUANTITY
    ====================================================== */

    function getValidQuantity(stock) {

        const count = Number(quantity.value);

        if (
            quantity.value.trim() === '' ||
            !Number.isInteger(count) ||
            count < 1 ||
            count > stock
        ) {

            return null;

        }

        return count;

    }


    /* =====================================================
       UPDATE VARIANT SELECTION STYLE
    ====================================================== */

    function updateVariantStyles() {

        document.querySelectorAll(
            '.variant-option'
        ).forEach(option => {

            const radio = option.querySelector('input');

            option.classList.toggle(
                'selected',
                radio.checked && !radio.disabled
            );

        });

    }


    /* =====================================================
       REFRESH PRICE, STOCK AND BUTTON
    ====================================================== */

    function refreshProduct() {

        const variant = getVariantDetails();

        updateVariantStyles();

        if (!variant || variant.stock <= 0) {

            stockStatus.textContent = 'Out of Stock';

            stockStatus.classList.add('out-of-stock');

            addToBag.disabled = true;

            addToBag.textContent = 'Out of Stock';

            quantity.disabled = true;
            decreaseButton.disabled = true;
            increaseButton.disabled = true;

            return;
        }


        const unitPrice = variant.price;

        const availableStock = variant.stock;


        /* PRICE */

        displayPrice.textContent = formatPeso(unitPrice);


        /* STOCK */

        stockStatus.textContent =
            `In Stock (${availableStock})`;

        stockStatus.classList.remove('out-of-stock');


        /* QUANTITY LIMIT */

        quantity.disabled = false;

        quantity.max = availableStock;

        const count = getValidQuantity(availableStock);


        /* INVALID QUANTITY */

        if (count === null) {

            addToBag.disabled = true;

            addToBag.textContent = 'Enter Valid Quantity';

            decreaseButton.disabled = false;
            increaseButton.disabled = false;

            return;
        }


        /* QUANTITY BUTTON STATES */

        decreaseButton.disabled = count <= 1;

        increaseButton.disabled = count >= availableStock;


        /* ADD TO BAG PRICE */

        const totalPrice = unitPrice * count;

        addToBag.disabled = false;

        addToBag.textContent =
            `Add to Bag — ${formatPeso(totalPrice)}`;

    }


    /* =====================================================
       PRODUCT VARIANT CHANGE
    ====================================================== */

    radios.forEach(radio => {

        radio.addEventListener('change', () => {

            if (radio.disabled) return;

            quantity.value = 1;

            cartMessage.textContent = '';

            refreshProduct();

        });

    });


    /* =====================================================
       DECREASE QUANTITY
    ====================================================== */

    decreaseButton.addEventListener('click', () => {

        const variant = getVariantDetails();

        if (!variant) return;

        const count = getValidQuantity(variant.stock) ?? 1;

        quantity.value = Math.max(1, count - 1);

        refreshProduct();

    });


    /* =====================================================
       INCREASE QUANTITY
    ====================================================== */

    increaseButton.addEventListener('click', () => {

        const variant = getVariantDetails();

        if (!variant) return;

        const count = getValidQuantity(variant.stock) ?? 0;

        quantity.value = Math.min(
            variant.stock,
            count + 1
        );

        refreshProduct();

    });


    /* =====================================================
       MANUAL QUANTITY INPUT
    ====================================================== */

    quantity.addEventListener('input', () => {

        refreshProduct();

    });


    /* =====================================================
       CORRECT INVALID QUANTITY ON CHANGE
    ====================================================== */

    quantity.addEventListener('change', () => {

        const variant = getVariantDetails();

        if (!variant) return;

        const entered = Number(quantity.value);

        if (
            !Number.isInteger(entered) ||
            entered < 1
        ) {

            quantity.value = 1;

        } else if (entered > variant.stock) {

            quantity.value = variant.stock;

        }

        refreshProduct();

    });


    /* =====================================================
       ADD TO BAG
       FRONTEND PREVIEW ONLY
    ====================================================== */

    form.addEventListener('submit', event => {

        event.preventDefault();

        const variant = getVariantDetails();

        if (!variant) return;

        const count = getValidQuantity(variant.stock);

        if (count === null || variant.stock <= 0) {

            cartMessage.textContent =
                'Please select an available product and valid quantity.';

            return;
        }

        cartMessage.textContent =
            'Product selected successfully. Cart saving will be enabled when the cart backend is connected.';

    });


    /* =====================================================
       INITIALIZE
    ====================================================== */

    refreshProduct();

});
