/* =====================================================
   CUSTOMER ACCOUNT DROPDOWN
===================================================== */

const accountDropdown =
    document.querySelector('.account-dropdown');

const accountDropdownTrigger =
    document.getElementById('accountDropdownTrigger');

const accountDropdownMenu =
    document.getElementById('accountDropdownMenu');


function closeAccountDropdown() {

    if (!accountDropdown) {
        return;
    }

    accountDropdown.classList.remove('active');

    if (accountDropdownTrigger) {
        accountDropdownTrigger.setAttribute(
            'aria-expanded',
            'false'
        );
    }

    if (accountDropdownMenu) {
        accountDropdownMenu.setAttribute(
            'aria-hidden',
            'true'
        );
    }
}


if (
    accountDropdown &&
    accountDropdownTrigger &&
    accountDropdownMenu
) {

    /* CLICK AVATAR / NAME */

    accountDropdownTrigger.addEventListener(
        'click',
        function (event) {

            event.preventDefault();
            event.stopPropagation();

            const isOpen =
                accountDropdown.classList.contains('active');

            if (isOpen) {

                closeAccountDropdown();

            } else {

                accountDropdown.classList.add('active');

                accountDropdownTrigger.setAttribute(
                    'aria-expanded',
                    'true'
                );

                accountDropdownMenu.setAttribute(
                    'aria-hidden',
                    'false'
                );

            }

        }
    );


    /* PREVENT MENU CLICK FROM CLOSING BEFORE LINK WORKS */

    accountDropdownMenu.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();

        }
    );


    /* CLICK OUTSIDE */

    document.addEventListener(
        'click',
        function () {

            closeAccountDropdown();

        }
    );

}