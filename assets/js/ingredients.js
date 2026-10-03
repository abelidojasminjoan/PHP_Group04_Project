/* =========================================================
   PUREVIA ADMIN INGREDIENTS
   SEARCH + FILTER + DELETE
========================================================= */

document.addEventListener('DOMContentLoaded', function () {


    /* =====================================================
       ELEMENTS
    ===================================================== */

    const searchInput =
        document.getElementById('ingredientSearch');

    const statusFilter =
        document.getElementById('ingredientStatusFilter');

    const ingredientRows =
        document.querySelectorAll('.ingredient-row');

    const noIngredientsFound =
        document.getElementById('noIngredientsFound');

    const deleteButtons =
        document.querySelectorAll('.delete-ingredient-button');


    /* =====================================================
       FILTER INGREDIENTS
    ===================================================== */

    function filterIngredients() {


        /* SEARCH VALUE */

        const searchValue =
            searchInput
                ? searchInput.value.trim().toLowerCase()
                : '';


        /* STATUS VALUE */

        const statusValue =
            statusFilter
                ? statusFilter.value.toLowerCase()
                : 'all';


        let visibleIngredients = 0;


        /* =================================================
           CHECK EACH ROW
        ================================================= */

        ingredientRows.forEach(function (row) {


            const rowSearch =
                (row.dataset.search || '').toLowerCase();


            const rowStatus =
                (row.dataset.status || '').toLowerCase();


            /* SEARCH */

            const matchesSearch =
                searchValue === '' ||
                rowSearch.includes(searchValue);


            /* STATUS */

            const matchesStatus =
                statusValue === 'all' ||
                rowStatus === statusValue;


            /* SHOW / HIDE */

            if (matchesSearch && matchesStatus) {

                row.style.display = '';

                visibleIngredients++;

            } else {

                row.style.display = 'none';

            }

        });


        /* =================================================
           NO RESULTS
        ================================================= */

        if (noIngredientsFound) {

            noIngredientsFound.style.display =
                visibleIngredients === 0
                    ? ''
                    : 'none';

        }

    }


    /* =====================================================
       SEARCH
    ===================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterIngredients
        );

    }


    /* =====================================================
       STATUS FILTER
    ===================================================== */

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            filterIngredients
        );

    }


    /* =====================================================
       DELETE INGREDIENT
    ===================================================== */

    deleteButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {


                const ingredientId =
                    button.dataset.ingredientId;


                const ingredientName =
                    button.dataset.ingredientName;


                if (!ingredientId) {
                    return;
                }


                /* CONFIRM DELETE */

                const confirmed =
                    confirm(
                        'Are you sure you want to delete "' +
                        ingredientName +
                        '"?'
                    );


                if (!confirmed) {
                    return;
                }


                /* =========================================
                   GO TO DELETE PROCESSOR
                ========================================= */

                window.location.href =
                    './delete_ingredient.php?id=' +
                    encodeURIComponent(
                        ingredientId
                    );

            }
        );

    });


    /* =====================================================
       INITIAL FILTER
    ===================================================== */

    filterIngredients();

});