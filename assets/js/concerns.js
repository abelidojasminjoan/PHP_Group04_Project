/* =========================================================
   PUREVIA ADMIN SKIN CONCERNS
   SEARCH + FILTER + DELETE
========================================================= */

document.addEventListener('DOMContentLoaded', function () {


    /* =====================================================
       ELEMENTS
    ===================================================== */

    const searchInput =
        document.getElementById('skinConcernSearch');

    const statusFilter =
        document.getElementById('skinConcernStatusFilter');

    const concernRows =
        document.querySelectorAll('.skin-concern-row');

    const noResultsRow =
        document.getElementById('noSkinConcernsFound');

    const deleteButtons =
        document.querySelectorAll('.delete-concern-button');


    /* =====================================================
       SEARCH + FILTER
    ===================================================== */

    function filterSkinConcerns() {


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


        let visibleRows = 0;


        /* =================================================
           CHECK EVERY ROW
        ================================================= */

        concernRows.forEach(function (row) {


            const searchableText =
                (row.dataset.search || '')
                    .toLowerCase();


            const rowStatus =
                (row.dataset.status || '')
                    .toLowerCase();


            /* SEARCH MATCH */

            const matchesSearch =
                searchValue === '' ||
                searchableText.includes(searchValue);


            /* STATUS MATCH */

            const matchesStatus =
                statusValue === 'all' ||
                rowStatus === statusValue;


            /* SHOW / HIDE */

            if (matchesSearch && matchesStatus) {

                row.style.display = '';

                visibleRows++;

            } else {

                row.style.display = 'none';

            }

        });


        /* =================================================
           NO RESULTS MESSAGE
        ================================================= */

        if (noResultsRow) {

            noResultsRow.style.display =
                visibleRows === 0
                    ? ''
                    : 'none';

        }

    }


    /* =====================================================
       LIVE SEARCH
    ===================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterSkinConcerns
        );

    }


    /* =====================================================
       STATUS FILTER
    ===================================================== */

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            filterSkinConcerns
        );

    }


    /* =====================================================
       ADD / EDIT / DELETE SKIN CONCERNS
    ===================================================== */

    const modal =
        document.getElementById('concernModal');

    const concernForm =
        document.getElementById('concernForm');

    const actionInput =
        document.getElementById('concernAction');

    const idInput =
        document.getElementById('concernId');

    const nameInput =
        document.getElementById('concernName');

    const descriptionInput =
        document.getElementById('concernDescription');

    const concernStatusInput =
        document.getElementById('concernStatus');

    const modalTitle =
        document.getElementById('concernModalTitle');

    const submitButton =
        document.getElementById('submitConcernButton');


    /* =====================================================
       OPEN / CLOSE MODAL
    ===================================================== */

    function openConcernModal() {

        modal.classList.add('is-open');

        modal.setAttribute('aria-hidden', 'false');

        document.body.classList.add('form-modal-open');

        nameInput.focus();
    }

    function closeConcernModal() {

        modal.classList.remove('is-open');

        modal.setAttribute('aria-hidden', 'true');

        document.body.classList.remove('form-modal-open');
    }


    /* =====================================================
       ADD CONCERN
    ===================================================== */

    document.getElementById('openAddConcernModal')
        .addEventListener('click', function () {

            concernForm.reset();

            actionInput.value = 'add';

            idInput.value = '';

            concernStatusInput.value = 'active';

            modalTitle.textContent = 'Add Skin Concern';

            submitButton.textContent = 'Add Concern';

            submitButton.disabled = false;

            openConcernModal();
        });


    /* =====================================================
       EDIT CONCERN
    ===================================================== */

    document.querySelectorAll('.edit-concern-button')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                concernForm.reset();

                actionInput.value = 'edit';

                idInput.value =
                    button.dataset.concernId;

                nameInput.value =
                    button.dataset.concernName;

                descriptionInput.value =
                    button.dataset.concernDescription;

                concernStatusInput.value =
                    button.dataset.concernStatus;

                modalTitle.textContent = 'Edit Skin Concern';

                submitButton.textContent = 'Save Changes';

                submitButton.disabled = false;

                openConcernModal();
            });
        });


    /* =====================================================
       CLOSE MODAL
    ===================================================== */

    document.querySelectorAll('[data-close-concern-modal]')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                closeConcernModal
            );
        });

    document.addEventListener('keydown', function (event) {

        if (
            event.key === 'Escape' &&
            modal.classList.contains('is-open')
        ) {
            closeConcernModal();
        }
    });


    /* =====================================================
       DELETE CONCERN
    ===================================================== */

    deleteButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const concernId =
                button.dataset.concernId;

            const concernName =
                button.dataset.concernName;

            if (!concernId) {
                return;
            }

            const confirmed = confirm(
                'Are you sure you want to delete "' +
                concernName +
                '"?'
            );

            if (!confirmed) {
                return;
            }

            document.getElementById('deleteConcernId').value =
                concernId;

            document.getElementById('deleteConcernForm')
                .requestSubmit();
        });
    });


    /* =====================================================
       PREVENT DOUBLE SUBMISSION
    ===================================================== */

    concernForm.addEventListener('submit', function () {

        if (!concernForm.checkValidity()) {
            return;
        }

        submitButton.disabled = true;

        submitButton.textContent = 'Saving...';
    });


    /* =====================================================
       INITIAL FILTER
    ===================================================== */

    filterSkinConcerns();

});