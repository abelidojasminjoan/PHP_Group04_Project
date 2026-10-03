/* =========================================================
   PUREVIA ADMIN CATEGORIES
   SEARCH + FILTER + DELETE
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    /* =====================================================
       ELEMENTS
    ===================================================== */

    const searchInput =
        document.getElementById('categorySearch');

    const statusFilter =
        document.getElementById('categoryStatusFilter');

    const categoryRows =
        document.querySelectorAll('.category-row');

    const noCategoriesFound =
        document.getElementById('noCategoriesFound');

    const deleteButtons =
        document.querySelectorAll('.delete-category-button');


    /* =====================================================
       FILTER CATEGORIES
    ===================================================== */

    function filterCategories() {

        const searchValue =
            searchInput
                ? searchInput.value.trim().toLowerCase()
                : '';

        const statusValue =
            statusFilter
                ? statusFilter.value.toLowerCase()
                : 'all';

        let visibleCategories = 0;


        categoryRows.forEach(function (row) {

            const rowSearch =
                (row.dataset.search || '').toLowerCase();

            const rowStatus =
                (row.dataset.status || '').toLowerCase();


            /* SEARCH MATCH */

            const matchesSearch =
                searchValue === '' ||
                rowSearch.includes(searchValue);


            /* STATUS MATCH */

            const matchesStatus =
                statusValue === 'all' ||
                rowStatus === statusValue;


            /* SHOW / HIDE */

            if (matchesSearch && matchesStatus) {

                row.style.display = '';

                visibleCategories++;

            } else {

                row.style.display = 'none';

            }

        });


        /* =================================================
           NO RESULTS MESSAGE
        ================================================= */

        if (noCategoriesFound) {

            noCategoriesFound.style.display =
                visibleCategories === 0
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
            filterCategories
        );

    }


    /* =====================================================
       STATUS FILTER EVENT
    ===================================================== */

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            filterCategories
        );

    }


    /* =====================================================
       DELETE CATEGORY
    ===================================================== */

    deleteButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                const categoryId =
                    button.dataset.categoryId;

                const categoryName =
                    button.dataset.categoryName;


                if (!categoryId) {
                    return;
                }


                const confirmed =
                    confirm(
                        'Are you sure you want to delete "' +
                        categoryName +
                        '"?'
                    );


                if (!confirmed) {
                    return;
                }


                /* =========================================
                   SEND TO DELETE PROCESSOR
                ========================================= */

                window.location.href =
                    './delete_category.php?id=' +
                    encodeURIComponent(categoryId);

            }
        );

    });


    /* =====================================================
       INITIAL FILTER
    ===================================================== */

    filterCategories();

});