document.addEventListener("DOMContentLoaded", function () {

    const searchInput =
        document.getElementById("userSearch");

    const roleFilter =
        document.getElementById("roleFilter");

    const userRows =
        document.querySelectorAll(".user-row");

    const noUsersFound =
        document.getElementById("noUsersFound");


    /* =====================================
       FILTER USERS
    ====================================== */

    function filterUsers() {

        const searchValue =
            searchInput.value
                .trim()
                .toLowerCase();

        const selectedRole =
            roleFilter.value;

        let visibleUsers = 0;


        userRows.forEach(function (row) {

            const role =
                row.dataset.role;

            const searchableText =
                row.dataset.search;


            const matchesRole =
                selectedRole === "all" ||
                role === selectedRole;


            const matchesSearch =
                searchableText.includes(
                    searchValue
                );


            if (matchesRole && matchesSearch) {

                row.style.display = "";

                visibleUsers++;

            } else {

                row.style.display = "none";

            }

        });


        noUsersFound.style.display =
            visibleUsers === 0
                ? "table-row"
                : "none";
    }


    /* =====================================
       SEARCH
    ====================================== */

    searchInput.addEventListener(
        "input",
        filterUsers
    );


    /* =====================================
       ROLE DROPDOWN
    ====================================== */

    roleFilter.addEventListener(
        "change",
        filterUsers
    );

});