/* =========================================================
   PUREVIA ADMIN - LOGIN ATTEMPTS
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    const searchInput =
        document.getElementById('loginAttemptSearch');

    const resultFilter =
        document.getElementById('loginResultFilter');

    const browserFilter =
        document.getElementById('loginBrowserFilter');

    const rows =
        Array.from(
            document.querySelectorAll('.login-attempt-row')
        );

    const noResultsRow =
        document.getElementById('noLoginAttemptsRow');

    const countElement =
        document.getElementById('loginAttemptCount');

    const labelElement =
        document.getElementById('loginAttemptLabel');


    /* =====================================================
       FILTER TABLE
    ====================================================== */

    function filterLoginAttempts() {

        const searchValue =
            searchInput
                ? searchInput.value
                    .trim()
                    .toLowerCase()
                : '';

        const resultValue =
            resultFilter
                ? resultFilter.value.toLowerCase()
                : 'all';

        const browserValue =
            browserFilter
                ? browserFilter.value.toLowerCase()
                : 'all';


        let visibleCount = 0;


        rows.forEach(function (row) {

            const rowSearch =
                (
                    row.dataset.search || ''
                ).toLowerCase();

            const rowResult =
                (
                    row.dataset.result || ''
                ).toLowerCase();

            const rowBrowser =
                (
                    row.dataset.browser || ''
                ).toLowerCase();


            /* SEARCH */

            const matchesSearch =
                searchValue === ''
                ||
                rowSearch.includes(searchValue);


            /* RESULT */

            const matchesResult =
                resultValue === 'all'
                ||
                rowResult === resultValue;


            /* BROWSER */

            const matchesBrowser =
                browserValue === 'all'
                ||
                rowBrowser === browserValue;


            const shouldShow =
                matchesSearch
                &&
                matchesResult
                &&
                matchesBrowser;


            row.hidden = !shouldShow;


            if (shouldShow) {
                visibleCount++;
            }

        });


        /* =================================================
           NO RESULTS
        ================================================== */

        if (noResultsRow) {

            noResultsRow.hidden =
                visibleCount !== 0;
        }


        /* =================================================
           UPDATE RECORD COUNT
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
       EVENTS
    ====================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterLoginAttempts
        );
    }


    if (resultFilter) {

        resultFilter.addEventListener(
            'change',
            filterLoginAttempts
        );
    }


    if (browserFilter) {

        browserFilter.addEventListener(
            'change',
            filterLoginAttempts
        );
    }


    /* =====================================================
       INITIAL FILTER
    ====================================================== */

    filterLoginAttempts();

});