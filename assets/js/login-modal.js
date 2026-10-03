/* =========================================================
   PUREVIA LOGIN + REGISTER MODAL
========================================================= */

document.addEventListener('DOMContentLoaded', function () {


    /* =====================================================
       LOGIN MODAL ELEMENTS
    ====================================================== */

    const loginModal =
        document.getElementById('loginModal');

    const openLoginButton =
        document.getElementById('openLoginModal');

    const closeLoginButton =
        document.getElementById('closeLoginModal');

    const loginOverlay =
        document.getElementById('loginModalOverlay');

    const loginEmailInput =
        document.getElementById('loginEmail');

    const loginPasswordInput =
        document.getElementById('loginPassword');

    const loginPasswordToggle =
        document.getElementById('passwordToggle');

    const loginPasswordIcon =
        document.getElementById('passwordIcon');


    /* =====================================================
       REGISTER MODAL ELEMENTS
    ====================================================== */

    const registerModal =
        document.getElementById('registerModal');

    const openRegisterButton =
        document.getElementById('openRegisterModal');

    const closeRegisterButton =
        document.getElementById('closeRegisterModal');

    const registerOverlay =
        document.getElementById('registerModalOverlay');

    const backToLoginButton =
        document.getElementById('backToLoginModal');

    const registerFirstName =
        document.getElementById('registerFirstName');


    /* =====================================================
       BODY SCROLL CONTROL
    ====================================================== */

    function updateBodyScroll() {

        const loginIsOpen =
            loginModal &&
            loginModal.classList.contains('active');

        const registerIsOpen =
            registerModal &&
            registerModal.classList.contains('active');


        if (loginIsOpen || registerIsOpen) {

            document.body.classList.add(
                'login-modal-open'
            );

        } else {

            document.body.classList.remove(
                'login-modal-open'
            );

        }

    }


    /* =====================================================
       OPEN LOGIN MODAL
    ====================================================== */

    function openLoginModal(event) {

        if (event) {
            event.preventDefault();
        }


        /* Close register modal first */

        if (registerModal) {

            registerModal.classList.remove(
                'active'
            );

            registerModal.setAttribute(
                'aria-hidden',
                'true'
            );

        }


        /* Make sure login modal exists */

        if (!loginModal) {
            return;
        }


        /* Open login */

        loginModal.classList.add(
            'active'
        );

        loginModal.setAttribute(
            'aria-hidden',
            'false'
        );


        updateBodyScroll();


        /* Focus email field */

        if (loginEmailInput) {

            setTimeout(function () {

                loginEmailInput.focus();

            }, 100);

        }

    }


    /* =====================================================
       CLOSE LOGIN MODAL
    ====================================================== */

    function closeLoginModal(event) {

        if (event) {
            event.preventDefault();
        }


        if (!loginModal) {
            return;
        }


        loginModal.classList.remove(
            'active'
        );

        loginModal.setAttribute(
            'aria-hidden',
            'true'
        );


        updateBodyScroll();

    }


    /* =====================================================
       OPEN REGISTER MODAL
    ====================================================== */

    function openRegisterModal(event) {

        if (event) {
            event.preventDefault();
        }


        /* Close login modal */

        if (loginModal) {

            loginModal.classList.remove(
                'active'
            );

            loginModal.setAttribute(
                'aria-hidden',
                'true'
            );

        }


        /* Make sure register modal exists */

        if (!registerModal) {
            return;
        }


        /* Open register */

        registerModal.classList.add(
            'active'
        );

        registerModal.setAttribute(
            'aria-hidden',
            'false'
        );


        updateBodyScroll();


        /* Focus first name */

        if (registerFirstName) {

            setTimeout(function () {

                registerFirstName.focus();

            }, 100);

        }

    }


    /* =====================================================
       CLOSE REGISTER MODAL
    ====================================================== */

    function closeRegisterModal(event) {

        if (event) {
            event.preventDefault();
        }


        if (!registerModal) {
            return;
        }


        registerModal.classList.remove(
            'active'
        );

        registerModal.setAttribute(
            'aria-hidden',
            'true'
        );


        updateBodyScroll();

    }


    /* =====================================================
       PERSON ICON
       OPEN LOGIN
    ====================================================== */

    if (openLoginButton) {

        openLoginButton.addEventListener(
            'click',
            openLoginModal
        );

    }


    /* =====================================================
       LOGIN CLOSE BUTTON
    ====================================================== */

    if (closeLoginButton) {

        closeLoginButton.addEventListener(
            'click',
            closeLoginModal
        );

    }


    /* =====================================================
       LOGIN OVERLAY
       CLICK OUTSIDE LOGIN CARD
    ====================================================== */

    if (loginOverlay) {

        loginOverlay.addEventListener(
            'click',
            closeLoginModal
        );

    }


    /* =====================================================
       REGISTER HERE
       LOGIN -> REGISTER
    ====================================================== */

    if (openRegisterButton) {

        openRegisterButton.addEventListener(
            'click',
            openRegisterModal
        );

    }


    /* =====================================================
       REGISTER CLOSE BUTTON
    ====================================================== */

    if (closeRegisterButton) {

        closeRegisterButton.addEventListener(
            'click',
            closeRegisterModal
        );

    }


    /* =====================================================
       REGISTER OVERLAY
       CLICK OUTSIDE REGISTER CARD
    ====================================================== */

    if (registerOverlay) {

        registerOverlay.addEventListener(
            'click',
            closeRegisterModal
        );

    }


    /* =====================================================
       LOGIN HERE
       REGISTER -> LOGIN
    ====================================================== */

    if (backToLoginButton) {

        backToLoginButton.addEventListener(
            'click',
            openLoginModal
        );

    }


    /* =====================================================
       LOGIN PASSWORD
       SHOW / HIDE
    ====================================================== */

    if (
        loginPasswordToggle &&
        loginPasswordInput &&
        loginPasswordIcon
    ) {

        loginPasswordToggle.addEventListener(
            'click',
            function () {

                const isHidden =
                    loginPasswordInput.type ===
                    'password';


                /* Change input type */

                loginPasswordInput.type =
                    isHidden
                        ? 'text'
                        : 'password';


                /* Change eye icon */

                loginPasswordIcon.classList.toggle(
                    'fa-eye',
                    isHidden
                );

                loginPasswordIcon.classList.toggle(
                    'fa-eye-slash',
                    !isHidden
                );


                /* Accessibility */

                loginPasswordToggle.setAttribute(
                    'aria-label',
                    isHidden
                        ? 'Hide password'
                        : 'Show password'
                );

            }
        );

    }


    /* =====================================================
       REGISTER PASSWORD TOGGLES
    ====================================================== */

    const registerPasswordToggles =
        document.querySelectorAll(
            '.register-password-toggle'
        );


    registerPasswordToggles.forEach(
        function (toggleButton) {

            toggleButton.addEventListener(
                'click',
                function () {

                    const targetId =
                        toggleButton.getAttribute(
                            'data-password-target'
                        );


                    if (!targetId) {
                        return;
                    }


                    const passwordInput =
                        document.getElementById(
                            targetId
                        );


                    const passwordIcon =
                        toggleButton.querySelector(
                            'i'
                        );


                    if (
                        !passwordInput ||
                        !passwordIcon
                    ) {
                        return;
                    }


                    const isHidden =
                        passwordInput.type ===
                        'password';


                    /* Change input type */

                    passwordInput.type =
                        isHidden
                            ? 'text'
                            : 'password';


                    /* Change icon */

                    passwordIcon.classList.toggle(
                        'fa-eye',
                        isHidden
                    );

                    passwordIcon.classList.toggle(
                        'fa-eye-slash',
                        !isHidden
                    );


                    /* Accessibility */

                    toggleButton.setAttribute(
                        'aria-label',
                        isHidden
                            ? 'Hide password'
                            : 'Show password'
                    );

                }
            );

        }
    );


    /* =====================================================
       ESCAPE KEY
    ====================================================== */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key !== 'Escape') {
                return;
            }


            /* Close register first */

            if (
                registerModal &&
                registerModal.classList.contains(
                    'active'
                )
            ) {

                closeRegisterModal();

                return;
            }


            /* Close login */

            if (
                loginModal &&
                loginModal.classList.contains(
                    'active'
                )
            ) {

                closeLoginModal();

            }

        }
    );


    /* =====================================================
       INITIAL PAGE STATE

       PHP may already add class="active"
       after a validation error.
    ====================================================== */

    updateBodyScroll();


    /* =====================================================
       FOCUS LOGIN AFTER PHP ERROR
    ====================================================== */

    if (
        loginModal &&
        loginModal.classList.contains('active') &&
        loginEmailInput
    ) {

        setTimeout(function () {

            loginEmailInput.focus();

        }, 100);

    }


    /* =====================================================
       FOCUS REGISTER AFTER PHP ERROR
    ====================================================== */

    if (
        registerModal &&
        registerModal.classList.contains('active') &&
        registerFirstName
    ) {

        setTimeout(function () {

            registerFirstName.focus();

        }, 100);

    }

});