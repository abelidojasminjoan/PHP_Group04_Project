/* =========================================================
   PUREVIA ADMIN INVENTORY
   SEARCH + CATEGORY FILTER + STOCK FILTER
========================================================= */

document.addEventListener('DOMContentLoaded', function () {


    /* =====================================================
       ELEMENTS
    ===================================================== */

    const searchInput =
        document.getElementById('inventorySearch');

    const categoryFilter =
        document.getElementById('inventoryCategoryFilter');

    const stockFilter =
        document.getElementById('inventoryStockFilter');

    const inventoryRows =
        document.querySelectorAll('.inventory-row');

    const noResultsRow =
        document.getElementById('noInventoryFound');


    /* =====================================================
       FILTER INVENTORY
    ===================================================== */

    function filterInventory() {


        /* SEARCH */

        const searchValue =
            searchInput
                ? searchInput.value.trim().toLowerCase()
                : '';


        /* CATEGORY */

        const categoryValue =
            categoryFilter
                ? categoryFilter.value.toLowerCase()
                : 'all';


        /* STOCK */

        const stockValue =
            stockFilter
                ? stockFilter.value.toLowerCase()
                : 'all';


        let visibleRows = 0;


        /* =================================================
           CHECK EVERY INVENTORY ROW
        ================================================= */

        inventoryRows.forEach(function (row) {


            const searchableText =
                (row.dataset.search || '')
                    .toLowerCase();


            const rowCategory =
                (row.dataset.category || '')
                    .toLowerCase();


            const rowStockLevel =
                (row.dataset.stockLevel || '')
                    .toLowerCase();


            /* =============================================
               SEARCH MATCH
            ============================================= */

            const matchesSearch =
                searchValue === '' ||
                searchableText.includes(searchValue);


            /* =============================================
               CATEGORY MATCH
            ============================================= */

            const matchesCategory =
                categoryValue === 'all' ||
                rowCategory === categoryValue;


            /* =============================================
               STOCK MATCH
            ============================================= */

            const matchesStock =
                stockValue === 'all' ||
                rowStockLevel === stockValue;


            /* =============================================
               SHOW / HIDE
            ============================================= */

            if (
                matchesSearch &&
                matchesCategory &&
                matchesStock
            ) {

                row.style.display = '';

                visibleRows++;

            } else {

                row.style.display = 'none';

            }

        });


        /* =================================================
           NO RESULTS
        ================================================= */

        if (noResultsRow) {

            noResultsRow.style.display =
                visibleRows === 0
                    ? ''
                    : 'none';

        }

    }


    /* =====================================================
       SEARCH EVENT
    ===================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterInventory
        );

    }


    /* =====================================================
       CATEGORY FILTER EVENT
    ===================================================== */

    if (categoryFilter) {

        categoryFilter.addEventListener(
            'change',
            filterInventory
        );

    }


    /* =====================================================
       STOCK FILTER EVENT
    ===================================================== */

    if (stockFilter) {

        stockFilter.addEventListener(
            'change',
            filterInventory
        );

    }


    /* =====================================================
       INITIAL FILTER
    ===================================================== */

    filterInventory();

});