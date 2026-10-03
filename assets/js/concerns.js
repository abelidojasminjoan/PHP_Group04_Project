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
       DELETE BUTTONS
    ===================================================== */

    deleteButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {


                const concernId =
                    button.dataset.concernId;


                const concernName =
                    button.dataset.concernName;


                if (!concernId) {
                    return;
                }


                /* =========================================
                   CONFIRM DELETE
                ========================================= */

                const confirmed =
                    confirm(
                        'Are you sure you want to delete "' +
                        concernName +
                        '"?'
                    );


                if (!confirmed) {
                    return;
                }


                /* =========================================
                   DELETE PROCESSOR
                ========================================= */

                window.location.href =
                    './delete_concern.php?id=' +
                    encodeURIComponent(
                        concernId
                    );

            }
        );

    });


    /* =====================================================
       INITIAL FILTER
    ===================================================== */

    filterSkinConcerns();

});