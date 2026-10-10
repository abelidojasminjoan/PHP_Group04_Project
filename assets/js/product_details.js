
/* =========================================================
   PUREVIA PRODUCT DETAILS
========================================================= */

document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
       ELEMENTS
    ====================================================== */

    const form = document.getElementById('productForm');

    if (!form) return;

    const container = form.querySelector('.quantity-control');

    const quantity = document.getElementById('quantity');

    const decrease = document.getElementById('decreaseQty');

    const increase = document.getElementById('increaseQty');

    const addToBag = document.getElementById('addToBag');

    if (
        !container ||
        !quantity ||
        !decrease ||
        !increase ||
        !addToBag
    ) {
        return;
    }


    /* =====================================================
       PRODUCT INFORMATION
    ====================================================== */

    const unitPrice = Number(container.dataset.price);

    const stock = Number(container.dataset.stock);


    /* =====================================================
       FORMAT PHILIPPINE PESO
    ====================================================== */

    function formatPeso(value) {

        return '₱' + value.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    }


    /* =====================================================
       UPDATE QUANTITY AND PRICE
    ====================================================== */

    function updateQuantity() {

        const entered = quantity.value.trim();

        const amount = Number(entered);

        const valid =
            entered !== '' &&
            Number.isInteger(amount) &&
            amount >= 1 &&
            amount <= stock;


        /* ADD TO BAG BUTTON */

        addToBag.disabled = !valid;

        addToBag.textContent = valid
            ? `Add to Bag — ${formatPeso(unitPrice * amount)}`
            : stock <= 0
                ? 'Out of Stock'
                : 'Enter Valid Quantity';


        /* QUANTITY BUTTONS */

        decrease.disabled =
            stock <= 0 ||
            (valid && amount <= 1);

        increase.disabled =
            stock <= 0 ||
            (valid && amount >= stock);

    }


    /* =====================================================
       DECREASE QUANTITY
    ====================================================== */

    decrease.addEventListener('click', () => {

        const current = Number(quantity.value);

        quantity.value = Math.max(
            1,
            Number.isInteger(current)
                ? current - 1
                : 1
        );

        updateQuantity();

    });


    /* =====================================================
       INCREASE QUANTITY
    ====================================================== */

    increase.addEventListener('click', () => {

        const current = Number(quantity.value);

        quantity.value = Math.min(
            stock,
            Number.isInteger(current)
                ? current + 1
                : 1
        );

        updateQuantity();

    });


    /* =====================================================
       MANUAL QUANTITY INPUT
    ====================================================== */

    quantity.addEventListener('input', updateQuantity);


    /* =====================================================
       CORRECT INVALID QUANTITY
    ====================================================== */

    quantity.addEventListener('change', () => {

        const current = Number(quantity.value);

        if (
            !Number.isInteger(current) ||
            current < 1
        ) {

            quantity.value = 1;

        } else if (current > stock) {

            quantity.value = stock;

        }

        updateQuantity();

    });


    /* =====================================================
       INITIALIZE
    ====================================================== */

    updateQuantity();

});
