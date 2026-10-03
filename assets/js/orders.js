/* =========================================================
   PUREVIA ADMIN ORDERS
========================================================= */

document.addEventListener('DOMContentLoaded', function () {


    /* =====================================================
       ELEMENTS
    ====================================================== */

    const searchInput =
        document.getElementById('orderSearch');

    const statusFilter =
        document.getElementById('orderStatusFilter');

    const paymentFilter =
        document.getElementById('paymentStatusFilter');

    const orderCount =
        document.getElementById('orderCount');

    const noOrdersRow =
        document.getElementById('noOrdersRow');

    const orderRows =
        Array.from(
            document.querySelectorAll('.order-row')
        );

    const statusSelects =
        document.querySelectorAll(
            '.order-status-select'
        );


    /* =====================================================
       FILTER ORDERS
    ====================================================== */

    function filterOrders() {

        const searchValue =
            searchInput
                ? searchInput.value
                    .trim()
                    .toLowerCase()
                : '';

        const statusValue =
            statusFilter
                ? statusFilter.value.toLowerCase()
                : 'all';

        const paymentValue =
            paymentFilter
                ? paymentFilter.value.toLowerCase()
                : 'all';


        let visibleCount = 0;


        orderRows.forEach(function (row) {

            const orderId =
                (row.dataset.orderId || '')
                    .toLowerCase();

            const customer =
                (row.dataset.customer || '')
                    .toLowerCase();

            const currentStatusSelect =
                row.querySelector(
                    '.order-status-select'
                );

            const currentStatus =
                currentStatusSelect
                    ? currentStatusSelect.value
                        .toLowerCase()
                    : (row.dataset.status || '')
                        .toLowerCase();

            const paymentStatus =
                (row.dataset.payment || '')
                    .toLowerCase();


            /* SEARCH */

            const matchesSearch =
                searchValue === '' ||
                orderId.includes(searchValue) ||
                customer.includes(searchValue);


            /* ORDER STATUS */

            const matchesStatus =
                statusValue === 'all' ||
                currentStatus === statusValue;


            /* PAYMENT */

            const matchesPayment =
                paymentValue === 'all' ||
                paymentStatus === paymentValue;


            /* FINAL */

            const shouldShow =
                matchesSearch &&
                matchesStatus &&
                matchesPayment;


            if (shouldShow) {

                row.hidden = false;

                visibleCount++;

            } else {

                row.hidden = true;

            }

        });


        /* ===============================================
           UPDATE COUNT
        ================================================ */

        if (orderCount) {
            orderCount.textContent =
                visibleCount;
        }


        /* ===============================================
           NO RESULTS
        ================================================ */

        if (noOrdersRow) {

            noOrdersRow.hidden =
                visibleCount !== 0;

        }

    }


    /* =====================================================
       SEARCH EVENT
    ====================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterOrders
        );

    }


    /* =====================================================
       STATUS FILTER
    ====================================================== */

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            filterOrders
        );

    }


    /* =====================================================
       PAYMENT FILTER
    ====================================================== */

    if (paymentFilter) {

        paymentFilter.addEventListener(
            'change',
            filterOrders
        );

    }


    /* =====================================================
       ORDER STATUS CHANGE
    ====================================================== */

    statusSelects.forEach(function (select) {

        select.addEventListener(
            'change',
            function () {

                const row =
                    select.closest('.order-row');

                if (!row) {
                    return;
                }


                const saveButton =
                    row.querySelector(
                        '.order-save-button'
                    );

                const emptySave =
                    row.querySelector(
                        '.order-save-empty'
                    );


                const originalValue =
                    select.dataset.originalValue;

                const currentValue =
                    select.value;


                /* =======================================
                   CHANGED
                ======================================== */

                if (
                    currentValue !==
                    originalValue
                ) {

                    select.classList.add(
                        'changed'
                    );

                    if (saveButton) {
                        saveButton.hidden = false;
                    }

                    if (emptySave) {
                        emptySave.hidden = true;
                    }

                }


                /* =======================================
                   BACK TO ORIGINAL
                ======================================== */

                else {

                    select.classList.remove(
                        'changed'
                    );

                    if (saveButton) {
                        saveButton.hidden = true;
                    }

                    if (emptySave) {
                        emptySave.hidden = false;
                    }

                }


                /*
                 * Re-run filters because the user may
                 * currently be filtering by order status.
                 */

                filterOrders();

            }
        );

    });


    /* =====================================================
       SAVE BUTTON
    ====================================================== */

    const saveButtons =
        document.querySelectorAll(
            '.order-save-button'
        );


    saveButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                const row =
                    button.closest('.order-row');

                if (!row) {
                    return;
                }


                const select =
                    row.querySelector(
                        '.order-status-select'
                    );

                if (!select) {
                    return;
                }


                const orderId =
                    button.dataset.orderId;

                const newStatus =
                    select.value;


                /*
                 * The actual database update will be
                 * connected to:
                 *
                 * actions/admin/orders/update-status.php
                 *
                 * For now this preserves the UI behavior.
                 */

                console.log(
                    'Order ID:',
                    orderId
                );

                console.log(
                    'New status:',
                    newStatus
                );


                select.dataset.originalValue =
                    newStatus;

                row.dataset.status =
                    newStatus;

                select.classList.remove(
                    'changed'
                );

                button.hidden = true;


                const emptySave =
                    row.querySelector(
                        '.order-save-empty'
                    );

                if (emptySave) {
                    emptySave.hidden = false;
                }


                filterOrders();

            }
        );

    });


    /* =====================================================
       INITIAL FILTER
    ====================================================== */

    filterOrders();

});