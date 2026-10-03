/* =========================================
   PRODUCT SEARCH AND FILTER
========================================= */

const productSearch =
    document.getElementById("productSearch");

const categoryFilter =
    document.getElementById("categoryFilter");

const statusFilter =
    document.getElementById("statusFilter");

const productRows =
    document.querySelectorAll(".product-row");

const noProductsFound =
    document.getElementById("noProductsFound");


function filterProducts() {

    const searchValue =
        productSearch.value
            .trim()
            .toLowerCase();

    const categoryValue =
        categoryFilter.value.toLowerCase();

    const statusValue =
        statusFilter.value.toLowerCase();

    let visibleProducts = 0;


    productRows.forEach(function (row) {

        const searchData =
            row.dataset.search || "";

        const category =
            row.dataset.category || "";

        const status =
            row.dataset.status || "";


        const matchesSearch =
            searchData.includes(searchValue);

        const matchesCategory =
            categoryValue === "all" ||
            category === categoryValue;

        const matchesStatus =
            statusValue === "all" ||
            status === statusValue;


        if (
            matchesSearch &&
            matchesCategory &&
            matchesStatus
        ) {

            row.style.display = "";

            visibleProducts++;

        } else {

            row.style.display = "none";

        }

    });


    if (noProductsFound) {

        noProductsFound.style.display =
            visibleProducts === 0
                ? "table-row"
                : "none";

    }

}


productSearch.addEventListener(
    "input",
    filterProducts
);

categoryFilter.addEventListener(
    "change",
    filterProducts
);

statusFilter.addEventListener(
    "change",
    filterProducts
);