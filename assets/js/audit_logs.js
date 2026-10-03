/* =========================================================
   PUREVIA ADMIN - AUDIT LOGS
========================================================= */

document.addEventListener('DOMContentLoaded', function () {


    /* =====================================================
       ELEMENTS
    ====================================================== */

    const searchInput =
        document.getElementById('auditSearch');

    const actionFilter =
        document.getElementById('auditActionFilter');

    const moduleFilter =
        document.getElementById('auditModuleFilter');

    const rows =
        Array.from(
            document.querySelectorAll('.audit-row')
        );

    const noResultsRow =
        document.getElementById('noAuditResults');

    const countElement =
        document.getElementById('auditRecordCount');

    const labelElement =
        document.getElementById('auditRecordLabel');


    /* =====================================================
       FILTER AUDIT LOGS
    ====================================================== */

    function filterAuditLogs() {


        /* SEARCH */

        const searchValue =
            searchInput
                ? searchInput.value
                    .trim()
                    .toLowerCase()
                : '';


        /* ACTION */

        const actionValue =
            actionFilter
                ? actionFilter.value
                    .trim()
                    .toLowerCase()
                : 'all';


        /* MODULE */

        const moduleValue =
            moduleFilter
                ? moduleFilter.value
                    .trim()
                    .toLowerCase()
                : 'all';


        let visibleCount = 0;


        /* =================================================
           CHECK EACH ROW
        ================================================== */

        rows.forEach(function (row) {


            const rowSearch =
                (
                    row.dataset.search || ''
                ).toLowerCase();


            const rowAction =
                (
                    row.dataset.action || ''
                ).toLowerCase();


            const rowModule =
                (
                    row.dataset.module || ''
                ).toLowerCase();


            /* =============================================
               SEARCH MATCH
            ============================================== */

            const matchesSearch =
                searchValue === ''
                ||
                rowSearch.includes(
                    searchValue
                );


            /* =============================================
               ACTION MATCH
            ============================================== */

            const matchesAction =
                actionValue === 'all'
                ||
                rowAction === actionValue;


            /* =============================================
               MODULE MATCH
            ============================================== */

            const matchesModule =
                moduleValue === 'all'
                ||
                rowModule === moduleValue;


            /* =============================================
               FINAL RESULT
            ============================================== */

            const shouldShow =
                matchesSearch
                &&
                matchesAction
                &&
                matchesModule;


            row.hidden = !shouldShow;


            if (shouldShow) {

                visibleCount++;
            }

        });


        /* =================================================
           EMPTY RESULT
        ================================================== */

        if (noResultsRow) {

            noResultsRow.hidden =
                visibleCount !== 0;
        }


        /* =================================================
           RECORD COUNT
        ================================================== */

        if (countElement) {

            countElement.textContent =
                visibleCount;
        }


        if (labelElement) {

            labelElement.textContent =
                visibleCount === 1
                    ? 'record'
                    : 'records';
        }

    }


    /* =====================================================
       SEARCH EVENT
    ====================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterAuditLogs
        );
    }


    /* =====================================================
       ACTION FILTER EVENT
    ====================================================== */

    if (actionFilter) {

        actionFilter.addEventListener(
            'change',
            filterAuditLogs
        );
    }


    /* =====================================================
       MODULE FILTER EVENT
    ====================================================== */

    if (moduleFilter) {

        moduleFilter.addEventListener(
            'change',
            filterAuditLogs
        );
    }


    /* =====================================================
       INITIAL STATE
    ====================================================== */

    filterAuditLogs();

});