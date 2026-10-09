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
       ADD / EDIT / DELETE INGREDIENTS
    ===================================================== */

    const modal =
        document.getElementById('ingredientModal');

    const ingredientForm =
        document.getElementById('ingredientForm');

    const actionInput =
        document.getElementById('ingredientAction');

    const idInput =
        document.getElementById('ingredientId');

    const nameInput =
        document.getElementById('ingredientName');

    const descriptionInput =
        document.getElementById('ingredientDescription');

    const ingredientStatusInput =
        document.getElementById('ingredientStatus');

    const modalTitle =
        document.getElementById('ingredientModalTitle');

    const submitButton =
        document.getElementById('submitIngredientButton');


    /* =====================================================
       OPEN / CLOSE MODAL
    ===================================================== */

    function openIngredientModal() {

        modal.classList.add('is-open');

        modal.setAttribute('aria-hidden', 'false');

        document.body.classList.add('form-modal-open');

        nameInput.focus();
    }

    function closeIngredientModal() {

        modal.classList.remove('is-open');

        modal.setAttribute('aria-hidden', 'true');

        document.body.classList.remove('form-modal-open');
    }


    /* =====================================================
       ADD INGREDIENT
    ===================================================== */

    document.getElementById('openAddIngredientModal')
        .addEventListener('click', function () {

            ingredientForm.reset();

            actionInput.value = 'add';

            idInput.value = '';

            ingredientStatusInput.value = 'active';

            modalTitle.textContent = 'Add Ingredient';

            submitButton.textContent = 'Add Ingredient';

            submitButton.disabled = false;

            openIngredientModal();
        });


    /* =====================================================
       EDIT INGREDIENT
    ===================================================== */

    document.querySelectorAll('.edit-ingredient-button')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                ingredientForm.reset();

                actionInput.value = 'edit';

                idInput.value =
                    button.dataset.ingredientId;

                nameInput.value =
                    button.dataset.ingredientName;

                descriptionInput.value =
                    button.dataset.ingredientDescription;

                ingredientStatusInput.value =
                    button.dataset.ingredientStatus;

                modalTitle.textContent = 'Edit Ingredient';

                submitButton.textContent = 'Save Changes';

                submitButton.disabled = false;

                openIngredientModal();
            });
        });


    /* =====================================================
       CLOSE MODAL
    ===================================================== */

    document.querySelectorAll('[data-close-ingredient-modal]')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                closeIngredientModal
            );
        });

    document.addEventListener('keydown', function (event) {

        if (
            event.key === 'Escape' &&
            modal.classList.contains('is-open')
        ) {
            closeIngredientModal();
        }
    });


    /* =====================================================
       DELETE INGREDIENT
    ===================================================== */

    deleteButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const ingredientId =
                button.dataset.ingredientId;

            const ingredientName =
                button.dataset.ingredientName;

            if (!ingredientId) {
                return;
            }

            const confirmed = confirm(
                'Are you sure you want to delete "' +
                ingredientName +
                '"?'
            );

            if (!confirmed) {
                return;
            }

            document.getElementById('deleteIngredientId').value =
                ingredientId;

            document.getElementById('deleteIngredientForm')
                .requestSubmit();
        });
    });


    /* =====================================================
       PREVENT DOUBLE SUBMISSION
    ===================================================== */

    ingredientForm.addEventListener('submit', function () {

        if (!ingredientForm.checkValidity()) {
            return;
        }

        submitButton.disabled = true;

        submitButton.textContent = 'Saving...';
    });



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