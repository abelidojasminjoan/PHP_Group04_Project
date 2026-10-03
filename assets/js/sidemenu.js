/* =========================================
   PUREVIA ADMIN SIDEBAR
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.getElementById("adminSidebar");

    const mobileToggle =
        document.getElementById("sidebarMobileToggle");

    const overlay =
        document.getElementById("sidebarOverlay");

    const dropdownButtons =
        document.querySelectorAll(
            ".sidebar-dropdown-button"
        );


    /* =====================================
       DROPDOWN MENUS
    ====================================== */

    dropdownButtons.forEach(function (button) {

        const menuId =
            button.getAttribute("data-dropdown");

        const menu =
            document.getElementById(menuId);


        if (!menu) {
            return;
        }


        /* Current section may already be open */

        if (menu.classList.contains("open")) {
            button.classList.add("dropdown-open");
        }


        button.addEventListener("click", function () {

            const isOpen =
                menu.classList.contains("open");


            /* Close other dropdowns */

            dropdownButtons.forEach(
                function (otherButton) {

                    const otherMenuId =
                        otherButton.getAttribute(
                            "data-dropdown"
                        );

                    const otherMenu =
                        document.getElementById(
                            otherMenuId
                        );


                    if (
                        otherMenu &&
                        otherMenu !== menu
                    ) {

                        otherMenu.classList.remove(
                            "open"
                        );

                        otherButton.classList.remove(
                            "dropdown-open"
                        );

                    }

                }
            );


            /* Toggle selected dropdown */

            if (isOpen) {

                menu.classList.remove("open");

                button.classList.remove(
                    "dropdown-open"
                );

            } else {

                menu.classList.add("open");

                button.classList.add(
                    "dropdown-open"
                );

            }

        });

    });


    /* =====================================
       OPEN MOBILE SIDEBAR
    ====================================== */

    function openSidebar() {

        if (!sidebar || !overlay) {
            return;
        }


        sidebar.classList.add(
            "sidebar-open"
        );

        overlay.classList.add(
            "active"
        );


        if (mobileToggle) {

            mobileToggle.setAttribute(
                "aria-expanded",
                "true"
            );

        }


        document.body.style.overflow =
            "hidden";
    }


    /* =====================================
       CLOSE MOBILE SIDEBAR
    ====================================== */

    function closeSidebar() {

        if (!sidebar || !overlay) {
            return;
        }


        sidebar.classList.remove(
            "sidebar-open"
        );

        overlay.classList.remove(
            "active"
        );


        if (mobileToggle) {

            mobileToggle.setAttribute(
                "aria-expanded",
                "false"
            );

        }


        document.body.style.overflow = "";
    }


    /* =====================================
       MOBILE BUTTON
    ====================================== */

    if (mobileToggle) {

        mobileToggle.addEventListener(
            "click",
            function () {

                if (
                    sidebar.classList.contains(
                        "sidebar-open"
                    )
                ) {

                    closeSidebar();

                } else {

                    openSidebar();

                }

            }
        );

    }


    /* =====================================
       OVERLAY CLICK
    ====================================== */

    if (overlay) {

        overlay.addEventListener(
            "click",
            closeSidebar
        );

    }


    /* =====================================
       ESC KEY
    ====================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Escape") {
                closeSidebar();
            }

        }
    );


    /* =====================================
       CLOSE MOBILE MENU AFTER LINK CLICK
    ====================================== */

    const sidebarLinks =
        document.querySelectorAll(
            ".admin-sidebar a"
        );


    sidebarLinks.forEach(function (link) {

        link.addEventListener(
            "click",
            function () {

                if (window.innerWidth <= 900) {
                    closeSidebar();
                }

            }
        );

    });


    /* =====================================
       RESET WHEN RETURNING TO DESKTOP
    ====================================== */

    window.addEventListener(
        "resize",
        function () {

            if (window.innerWidth > 900) {

                sidebar.classList.remove(
                    "sidebar-open"
                );

                overlay.classList.remove(
                    "active"
                );

                document.body.style.overflow =
                    "";

                if (mobileToggle) {

                    mobileToggle.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                }

            }

        }
    );

});