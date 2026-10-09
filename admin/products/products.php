<?php

session_start();

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

$pageTitle = "Products";


/* =========================================================
   ADMIN ACCESS ONLY

   1 = Admin
   2 = Staff
   3 = Customer
========================================================= */

if (
    empty($_SESSION['user_id']) ||
    empty($_SESSION['role_id']) ||
    (int) $_SESSION['role_id'] !== 1
) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}


/* =========================================================
   GET CATEGORIES
========================================================= */

$categories = [];

$categoryResult = $conn->query("
    SELECT
        id,
        category_name

    FROM categories

    WHERE status = 'active'

    ORDER BY category_name ASC
");

if ($categoryResult) {

    while ($row = $categoryResult->fetch_assoc()) {

        $categories[] = $row;
    }
}


/* =========================================================
   GET PRODUCTS
========================================================= */

$products = [];

$productResult = $conn->query("
    SELECT
        p.id,
        p.product_name,
        p.product_code,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,

        c.category_name,

        GROUP_CONCAT(
            pv.size_capacity
            ORDER BY pv.id ASC
            SEPARATOR '||'
        ) AS sizes

    FROM products AS p

    INNER JOIN categories AS c
        ON p.category_id = c.id

    LEFT JOIN product_variants AS pv
        ON p.id = pv.product_id

    GROUP BY
        p.id,
        p.product_name,
        p.product_code,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        c.category_name

    ORDER BY p.created_at DESC, p.id DESC
");

if ($productResult) {

    while ($row = $productResult->fetch_assoc()) {

        $products[] = $row;
    }
}


$totalProducts = count($products);


/* =========================================================
   FLASH MESSAGE
========================================================= */

$successMessage =
    $_SESSION['product_success'] ?? '';

$errorMessage =
    $_SESSION['product_error'] ?? '';

unset($_SESSION['product_success']);
unset($_SESSION['product_error']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($pageTitle) ?> | PureVia Admin
    </title>


    <!-- GOOGLE FONTS -->

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- EXISTING CSS -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/sidemenu.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/product.css"
    >


    <!-- NEW REUSABLE FORM CSS -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/form2.css"
    >

</head>


<body>


<?php
include __DIR__ . '/../../includes/sidemenu.php';
?>


<div class="admin-layout">


    <!-- TOP BAR -->

    <header class="admin-topbar">

        <p class="admin-topbar-title">
            Products
        </p>

    </header>


    <main class="products-main">


        <!-- PAGE HEADER -->

        <section class="products-page-header">

            <div class="products-heading">

                <h1>Products</h1>

                <p>
                    <span id="productCount">
                        <?= number_format($totalProducts) ?>
                    </span>
                    records
                </p>

            </div>


            <button
                type="button"
                class="add-product-button"
                id="openAddProductModal"
            >

                <i class="fa-solid fa-plus"></i>

                <span>Add Product</span>

            </button>

        </section>


        <!-- SUCCESS MESSAGE -->

        <?php if ($successMessage !== ''): ?>

            <div class="form-alert form-alert-success">

                <i class="fa-solid fa-circle-check"></i>

                <?= htmlspecialchars($successMessage) ?>

            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->

        <?php if ($errorMessage !== ''): ?>

            <div class="form-alert form-alert-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= htmlspecialchars($errorMessage) ?>

            </div>

        <?php endif; ?>


        <!-- PRODUCTS CARD -->

        <section class="products-card">


            <!-- SEARCH / FILTER -->

            <div class="products-toolbar">


                <div class="products-search">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="search"
                        id="productSearch"
                        placeholder="Search products..."
                        autocomplete="off"
                    >

                </div>


                <!-- CATEGORY -->

                <div class="product-filter-dropdown">

                    <i class="fa-solid fa-filter product-filter-icon"></i>

                    <select
                        id="categoryFilter"
                        class="product-filter-select"
                    >

                        <option value="all">
                            All Categories
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    strtolower(
                                        $category['category_name']
                                    )
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    $category['category_name']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <i class="fa-solid fa-chevron-down product-filter-arrow"></i>

                </div>


                <!-- STATUS -->

                <div class="product-filter-dropdown">

                    <select
                        id="statusFilter"
                        class="
                            product-filter-select
                            status-filter-select
                        "
                    >

                        <option value="all">
                            All Status
                        </option>

                        <option value="active">
                            Active
                        </option>

                        <option value="inactive">
                            Inactive
                        </option>

                        <option value="out_of_stock">
                            Out of Stock
                        </option>

                    </select>

                    <i class="fa-solid fa-chevron-down product-filter-arrow"></i>

                </div>


            </div>


            <!-- PRODUCT TABLE -->

            <div class="products-table-wrapper">

                <table class="products-table">

                    <thead>

                        <tr>

                            <th>PRODUCT</th>
                            <th>SKU</th>
                            <th>CATEGORY</th>
                            <th>SIZES</th>
                            <th>STOCK</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($products as $product): ?>


                        <?php

                        $status = strtolower(
                            $product['status']
                        );

                        $category = strtolower(
                            $product['category_name']
                        );

                        $searchData = strtolower(
                            $product['product_name']
                            . ' '
                            . $product['product_code']
                            . ' '
                            . $product['category_name']
                        );

                        $sizes = [];

                        if (!empty($product['sizes'])) {

                            $sizes = explode(
                                '||',
                                $product['sizes']
                            );
                        }

                        ?>


                        <tr
                            class="product-row"

                            data-category="<?= htmlspecialchars(
                                $category
                            ) ?>"

                            data-status="<?= htmlspecialchars(
                                $status
                            ) ?>"

                            data-search="<?= htmlspecialchars(
                                $searchData
                            ) ?>"
                        >


                            <!-- PRODUCT -->

                            <td>

                                <div class="product-information">


                                    <div class="product-image">

                                        <?php if (!empty($product['image'])): ?>

                                            <img
                                                src="<?= htmlspecialchars(
                                                    BASE_URL . '/' . ltrim(
                                                        $product['image'],
                                                        '/'
                                                    )
                                                ) ?>"
                                                alt="<?= htmlspecialchars(
                                                    $product['product_name']
                                                ) ?>"
                                            >

                                        <?php else: ?>

                                            <div
                                                style="
                                                    width:100%;
                                                    height:100%;
                                                    display:flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    color:#9a9288;
                                                "
                                            >
                                                <i class="fa-solid fa-pump-soap"></i>
                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <p class="product-name">

                                        <?= htmlspecialchars(
                                            $product['product_name']
                                        ) ?>

                                    </p>

                                </div>

                            </td>


                            <!-- SKU -->

                            <td class="product-sku">

                                <?= htmlspecialchars(
                                    $product['product_code']
                                ) ?>

                            </td>


                            <!-- CATEGORY -->

                            <td class="product-category">

                                <?= htmlspecialchars(
                                    $product['category_name']
                                ) ?>

                            </td>


                            <!-- SIZES -->

                            <td>

                                <div class="product-sizes">

                                    <?php if (!empty($sizes)): ?>

                                        <?php foreach ($sizes as $size): ?>

                                            <span class="size-badge">

                                                <?= htmlspecialchars(
                                                    $size
                                                ) ?>

                                            </span>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <span class="size-badge">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <!-- STOCK -->

                            <td>

                                <span
                                    class="
                                        product-stock

                                        <?= (int) $product['stock_quantity'] <= 10
                                            ? 'low-stock'
                                            : '' ?>
                                    "
                                >

                                    <?= number_format(
                                        (int) $product['stock_quantity']
                                    ) ?>

                                </span>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="
                                        product-status
                                        status-<?= htmlspecialchars(
                                            $status
                                        ) ?>
                                    "
                                >

                                    <?= strtoupper(
                                        str_replace(
                                            '_',
                                            ' ',
                                            htmlspecialchars($status)
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <div class="product-actions">


                                    <a
                                        href="./edit_product.php?id=<?= (int) $product['id'] ?>"
                                        class="
                                            product-action-button
                                            edit-product-button
                                        "
                                        title="Edit product"
                                    >

                                        <i class="fa-regular fa-pen-to-square"></i>

                                    </a>


                                    <button
                                        type="button"
                                        class="
                                            product-action-button
                                            delete-product-button
                                        "
                                        data-product-id="<?= (int) $product['id'] ?>"
                                        data-product-name="<?= htmlspecialchars(
                                            $product['product_name']
                                        ) ?>"
                                        title="Delete product"
                                    >

                                        <i class="fa-regular fa-trash-can"></i>

                                    </button>


                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    <!-- NO PRODUCTS -->

                    <tr
                        id="noProductsFound"
                        class="no-products-row"
                        <?= !empty($products)
                            ? 'style="display:none;"'
                            : '' ?>
                    >

                        <td colspan="7">

                            <div class="no-products-message">

                                <i class="fa-solid fa-box-open"></i>

                                <p>
                                    No products found.
                                </p>

                            </div>

                        </td>

                    </tr>


                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>



<!-- =========================================================
     ADD PRODUCT MODAL
========================================================= -->

<div
    class="form-modal"
    id="addProductModal"
    aria-hidden="true"
>


    <div
        class="form-modal-backdrop"
        data-close-form-modal
    ></div>


    <div
        class="form-modal-dialog form-modal-large"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addProductTitle"
    >


        <form
            action="./add_product.php"
            method="POST"
            id="addProductForm"
            class="purevia-form"
        >


            <!-- HEADER -->

            <div class="form-modal-header">

                <div>

                    <h2 id="addProductTitle">
                        Add New Product
                    </h2>

                    <p>
                        Fill in the details and add all available sizes
                    </p>

                </div>


                <button
                    type="button"
                    class="form-modal-close"
                    data-close-form-modal
                    aria-label="Close"
                >

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>


            <!-- BODY -->

            <div class="form-modal-body">


                <!-- PRODUCT NAME + SKU -->

                <div class="form-grid form-grid-2">


                    <div class="form-group">

                        <label for="productName">

                            Product Name

                            <span class="form-required">
                                *
                            </span>

                        </label>

                        <input
                            type="text"
                            id="productName"
                            name="product_name"
                            class="form-control"
                            placeholder="e.g. Brightening Essence"
                            maxlength="200"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="productCode">

                            SKU

                            <span class="form-required">
                                *
                            </span>

                        </label>

                        <input
                            type="text"
                            id="productCode"
                            name="product_code"
                            class="form-control"
                            placeholder="e.g. SRM-005"
                            maxlength="100"
                            required
                        >

                    </div>


                </div>


                <!-- CATEGORY + STATUS -->

                <div class="form-grid form-grid-2">


                    <div class="form-group">

                        <label for="productCategory">

                            Category

                            <span class="form-required">
                                *
                            </span>

                        </label>


                        <div class="form-select-wrapper">

                            <select
                                id="productCategory"
                                name="category_id"
                                class="form-control form-select"
                                required
                            >

                                <option value="">
                                    Select category
                                </option>


                                <?php foreach ($categories as $category): ?>

                                    <option
                                        value="<?= (int) $category['id'] ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $category['category_name']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>


                            </select>

                            <i class="fa-solid fa-chevron-down"></i>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="productStatus">
                            Status
                        </label>


                        <div class="form-select-wrapper">

                            <select
                                id="productStatus"
                                name="status"
                                class="form-control form-select"
                            >

                                <option value="active">
                                    Active
                                </option>

                                <option value="inactive">
                                    Inactive
                                </option>

                            </select>

                            <i class="fa-solid fa-chevron-down"></i>

                        </div>

                    </div>


                </div>


                <!-- DESCRIPTION -->

                <div class="form-group">

                    <label for="productDescription">
                        Description
                    </label>

                    <textarea
                        id="productDescription"
                        name="description"
                        class="form-control form-textarea"
                        placeholder="Product description..."
                        rows="3"
                    ></textarea>

                </div>


                <!-- =================================================
                     SIZES
                ================================================== -->

                <div class="form-section">


                    <div class="form-section-header">

                        <div>

                            <h3>
                                Sizes &amp; Options
                            </h3>

                            <p>
                                Price and stock are set per size.
                                Base price is derived from the first size.
                            </p>

                        </div>


                        <button
                            type="button"
                            class="form-outline-button"
                            id="addSizeButton"
                        >

                            <i class="fa-solid fa-plus"></i>

                            Add Size

                        </button>

                    </div>


                    <!-- COLUMN LABELS -->

                    <div class="variant-header">

                        <span>SIZE / CAPACITY</span>

                        <span>LABEL (OPTIONAL)</span>

                        <span>PRICE (₱)</span>

                        <span>STOCK</span>

                        <span></span>

                    </div>


                    <!-- VARIANT ROWS -->

                    <div id="variantRows">


                        <div class="variant-row">


                            <input
                                type="text"
                                name="size_capacity[]"
                                class="form-control"
                                placeholder="e.g. 30ml, 1 sheet"
                                maxlength="100"
                                required
                            >


                            <input
                                type="text"
                                name="variant_label[]"
                                class="form-control"
                                placeholder="e.g. Regular, Trial"
                                maxlength="100"
                            >


                            <div class="price-input">

                                <span>₱</span>

                                <input
                                    type="number"
                                    name="variant_price[]"
                                    class="form-control"
                                    placeholder="0.00"
                                    min="0"
                                    step="0.01"
                                    required
                                >

                            </div>


                            <input
                                type="number"
                                name="variant_stock[]"
                                class="form-control"
                                value="0"
                                min="0"
                                step="1"
                                required
                            >


                            <button
                                type="button"
                                class="variant-remove"
                                aria-label="Remove size"
                                disabled
                            >

                                <i class="fa-solid fa-xmark"></i>

                            </button>


                        </div>


                    </div>


                    <p class="form-helper">

                        <i class="fa-solid fa-arrow-up"></i>

                        First size sets the base price shown in the catalog.

                    </p>


                </div>


            </div>


            <!-- FOOTER -->

            <div class="form-modal-footer">


                <button
                    type="button"
                    class="form-cancel-button"
                    data-close-form-modal
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="form-submit-button"
                    id="submitProductButton"
                >

                    Add Product

                </button>


            </div>


        </form>


    </div>


</div>



<!-- EXISTING JS -->

<script src="<?= BASE_URL ?>/assets/js/sidemenu.js"></script>

<script src="<?= BASE_URL ?>/assets/js/products.js"></script>


<!-- NEW REUSABLE FORM JS -->

<script src="<?= BASE_URL ?>/assets/js/forms.js"></script>


</body>

</html>