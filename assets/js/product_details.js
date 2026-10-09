
/* =========================================
   PUREVIA PRODUCT DETAILS JAVASCRIPT
========================================= */

document.addEventListener('DOMContentLoaded', () => {


    /* =====================================
       ELEMENTS
    ====================================== */

    const radios = [
        ...document.querySelectorAll(
            'input[name="variant_id"]'
        )
    ];

    const quantity = document.getElementById(
        'quantity'
    );

    const price = document.getElementById(
        'displayPrice'
    );

    const stock = document.getElementById(
        'stockStatus'
    );

    const button = document.getElementById(
        'addToBag'
    );

    const message = document.getElementById(
        'cartMessage'
    );


    /* =====================================
       FORMAT PHILIPPINE PESO
    ====================================== */

    const peso = value => {

        return '₱' + value.toLocaleString(
            'en-PH',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

    };


    /* =====================================
       GET SELECTED VARIANT
    ====================================== */

    const active = () => {

        return radios.find(
            radio => radio.checked
        );

    };


    /* =====================================
       UPDATE PRODUCT INFORMATION
    ====================================== */

    function refresh() {

        const selected = active();

        if (!selected) return;


        /* GET VARIANT DETAILS */

        const available = Number(
            selected.dataset.stock
        );

        const unitPrice = Number(
            selected.dataset.price
        );


        /* VALIDATE QUANTITY */

        let count = Number.parseInt(
            quantity.value,
            10
        );

        if (!Number.isFinite(count)) {
            count = 1;
        }

        count = Math.max(
            1,
            Math.min(
                count,
                Math.max(1, available)
            )
        );


        /* UPDATE QUANTITY */

        quantity.value = count;

        quantity.max = Math.max(
            1,
            available
        );


        /* UPDATE SELECTED VARIANT STYLE */

        document.querySelectorAll(
            '.variant-option'
        ).forEach(option => {

            option.classList.toggle(
                'selected',
                option.querySelector('input').checked
            );

        });


        /* UPDATE DISPLAYED PRICE */

        price.textContent = peso(
            unitPrice
        );


        /* UPDATE STOCK STATUS */

        stock.textContent = available
            ? `In Stock (${available})`
            : 'Out of Stock';

        stock.style.color = available
            ? '#00a05c'
            : '#b34c42';


        /* UPDATE ADD TO BAG BUTTON */

        button.disabled = available === 0;

        button.textContent = available
            ? `Add to Bag — ${peso(unitPrice * count)}`
            : 'Out of Stock';


        /* CLEAR PREVIOUS MESSAGE */

        message.textContent = '';

    }



    /* =====================================
       VARIANT SELECTION
    ====================================== */

    radios.forEach(radio => {

        radio.addEventListener(
            'change',
            refresh
        );

    });



    /* =====================================
       DECREASE QUANTITY
    ====================================== */

    document.getElementById(
        'decreaseQty'
    ).addEventListener('click', () => {

        quantity.value =
            Number(quantity.value || 1) - 1;

        refresh();

    });



    /* =====================================
       INCREASE QUANTITY
    ====================================== */

    document.getElementById(
        'increaseQty'
    ).addEventListener('click', () => {

        quantity.value =
            Number(quantity.value || 1) + 1;

        refresh();

    });



    /* =====================================
       MANUAL QUANTITY INPUT
    ====================================== */

    quantity.addEventListener(
        'input',
        refresh
    );



    /* =====================================
       ADD TO BAG

       FRONTEND PREVIEW ONLY
    ====================================== */

    document.getElementById(
        'productForm'
    ).addEventListener('submit', event => {

        event.preventDefault();

        refresh();

        message.textContent =
            'Frontend preview only. Connect actions/cart/add.php to save items in the cart.';

    });



    /* =====================================
       INITIALIZE PRODUCT
    ====================================== */

    refresh();

});
