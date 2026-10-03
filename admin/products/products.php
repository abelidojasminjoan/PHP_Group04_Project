<?php

session_start();

$pageTitle = "Products";


/* =========================================
   TEMPORARY ADMIN SESSION
   Remove when login is connected
========================================= */

$_SESSION['first_name'] =
    $_SESSION['first_name'] ?? 'Ava';

$_SESSION['last_name'] =
    $_SESSION['last_name'] ?? 'Santos';

$_SESSION['email'] =
    $_SESSION['email'] ?? 'admin@purevia.com';


/* =========================================
   SAMPLE PRODUCT DATA
   Replace with MySQL query later
========================================= */

$products = [

    [
        'id' => 1,
        'name' => 'Gentle Botanical Cleanser',
        'sku' => 'CLN-001',
        'category' => 'Cleansers',
        'sizes' => ['100ML', '150ML'],
        'stock' => 45,
        'status' => 'active',
        'image' => '../../assets/images/products/gentle-cleanser.jpg'
    ],

    [
        'id' => 2,
        'name' => 'Salicylic Pore Refining Cleanser',
        'sku' => 'CLN-002',
        'category' => 'Cleansers',
        'sizes' => ['50ML', '100ML'],
        'stock' => 62,
        'status' => 'active',
        'image' => '../../assets/images/products/salicylic-cleanser.jpg'
    ],

    [
        'id' => 3,
        'name' => 'Hyaluronic Acid Hydration Serum',
        'sku' => 'SRM-001',
        'category' => 'Serums',
        'sizes' => ['15ML', '30ML', '60ML'],
        'stock' => 89,
        'status' => 'active',
        'image' => '../../assets/images/products/hyaluronic-serum.jpg'
    ],

    [
        'id' => 4,
        'name' => 'Vitamin C Brightening Serum',
        'sku' => 'SRM-002',
        'category' => 'Serums',
        'sizes' => ['15ML', '30ML'],
        'stock' => 34,
        'status' => 'active',
        'image' => '../../assets/images/products/vitamin-c-serum.jpg'
    ],

    [
        'id' => 5,
        'name' => 'Retinol Renewal Night Serum',
        'sku' => 'SRM-003',
        'category' => 'Serums',
        'sizes' => ['15ML', '30ML'],
        'stock' => 27,
        'status' => 'inactive',
        'image' => '../../assets/images/products/retinol-serum.jpg'
    ],

    [
        'id' => 6,
        'name' => 'Niacinamide 10% + Zinc',
        'sku' => 'SRM-004',
        'category' => 'Serums',
        'sizes' => ['30ML', '60ML'],
        'stock' => 71,
        'status' => 'active',
        'image' => '../../assets/images/products/niacinamide-serum.jpg'
    ]

];


$totalProducts = count($products);


/* =========================================
   GET UNIQUE CATEGORIES
========================================= */

$categories = [];

foreach ($products as $product) {

    if (!in_array(
        $product['category'],
        $categories,
        true
    )) {

        $categories[] =
            $product['category'];
    }
}

sort($categories);

?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle) ?> | PureVia</title>

    <!-- GOOGLE FONTS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@400;500;600&display=swap">

    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="../../assets/css/sidemenu.css">
    <link rel="stylesheet" href="../../assets/css/product.css">

</head>


<body>

    <!-- =====================================
         SIDEBAR
    ====================================== -->

    <?php include("../../includes/sidemenu.php"); ?>


    <!-- =====================================
         ADMIN LAYOUT
    ====================================== -->

    <div class="admin-layout">


        <!-- =================================
             TOP BAR
        ================================== -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Products
            </p>

        </header>


        <!-- =================================
             MAIN
        ================================== -->

        <main class="products-main">


            <!-- =================================
                 PAGE HEADER
            ================================== -->

            <section class="products-page-header">

                <div class="products-heading">

                    <h1>
                        Products
                    </h1>

                    <p>
                        <?= number_format($totalProducts) ?>
                        records
                    </p>

                </div>


                <a
                    href="./add_product.php"
                    class="add-product-button"
                >

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Add Product
                    </span>

                </a>

            </section>


            <!-- =================================
                 PRODUCT CARD
            ================================== -->

            <section class="products-card">


                <!-- =================================
                     SEARCH AND FILTER
                ================================== -->

                <div class="products-toolbar">


                    <!-- SEARCH -->

                    <div class="products-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="productSearch"
                            placeholder="Search products..."
                            autocomplete="off"
                        >

                    </div>


                    <!-- CATEGORY FILTER -->

                    <div class="product-filter-dropdown">

                        <i class="fa-solid fa-filter product-filter-icon"></i>

                        <select
                            id="categoryFilter"
                            class="product-filter-select category-filter-select"
                            aria-label="Filter products by category"
                        >

                            <option value="all">
                                All Categories
                            </option>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        strtolower($category)
                                    ) ?>"
                                >
                                    <?= htmlspecialchars($category) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <i class="fa-solid fa-chevron-down product-filter-arrow"></i>

                    </div>


                    <!-- STATUS FILTER -->

                    <div class="product-filter-dropdown status-filter-dropdown">

                        <select
                            id="statusFilter"
                            class="product-filter-select status-filter-select"
                            aria-label="Filter products by status"
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

                        </select>

                        <i class="fa-solid fa-chevron-down product-filter-arrow"></i>

                    </div>

                </div>


                <!-- =================================
                     TABLE
                ================================== -->

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

                                    $status =
                                        strtolower(
                                            $product['status']
                                        );

                                    $category =
                                        strtolower(
                                            $product['category']
                                        );

                                    $searchData =
                                        strtolower(
                                            $product['name']
                                            . ' '
                                            . $product['sku']
                                            . ' '
                                            . $product['category']
                                        );

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

                                                <img
                                                    src="<?= htmlspecialchars(
                                                        $product['image']
                                                    ) ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $product['name']
                                                    ) ?>"
                                                >

                                            </div>


                                            <p class="product-name">

                                                <?= htmlspecialchars(
                                                    $product['name']
                                                ) ?>

                                            </p>

                                        </div>

                                    </td>


                                    <!-- SKU -->

                                    <td class="product-sku">

                                        <?= htmlspecialchars(
                                            $product['sku']
                                        ) ?>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td class="product-category">

                                        <?= htmlspecialchars(
                                            $product['category']
                                        ) ?>

                                    </td>


                                    <!-- SIZES -->

                                    <td>

                                        <div class="product-sizes">

                                            <?php foreach ($product['sizes'] as $size): ?>

                                                <span class="size-badge">

                                                    <?= htmlspecialchars(
                                                        $size
                                                    ) ?>

                                                </span>

                                            <?php endforeach; ?>

                                        </div>

                                    </td>


                                    <!-- STOCK -->

                                    <td>

                                        <span
                                            class="
                                                product-stock
                                                <?= $product['stock'] <= 10
                                                    ? 'low-stock'
                                                    : '' ?>
                                            "
                                        >

                                            <?= number_format(
                                                $product['stock']
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
                                                htmlspecialchars(
                                                    $product['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="product-actions">


                                            <!-- EDIT -->

                                            <a
                                                href="./edit_product.php?id=<?= urlencode(
                                                    $product['id']
                                                ) ?>"
                                                class="product-action-button edit-product-button"
                                                title="Edit product"
                                                aria-label="Edit <?= htmlspecialchars(
                                                    $product['name']
                                                ) ?>"
                                            >

                                                <i class="fa-regular fa-pen-to-square"></i>

                                            </a>


                                            <!-- DELETE -->

                                            <button
                                                type="button"
                                                class="product-action-button delete-product-button"
                                                title="Delete product"
                                                aria-label="Delete <?= htmlspecialchars(
                                                    $product['name']
                                                ) ?>"
                                                data-product-id="<?= htmlspecialchars(
                                                    $product['id']
                                                ) ?>"
                                                data-product-name="<?= htmlspecialchars(
                                                    $product['name']
                                                ) ?>"
                                            >

                                                <i class="fa-regular fa-trash-can"></i>

                                            </button>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                            <!-- NO RESULTS -->

                            <tr
                                id="noProductsFound"
                                class="no-products-row"
                                style="display: none;"
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


    <!-- SIDEBAR JS -->

    <script src="../../assets/js/sidemenu.js"></script>
    <script src="../../assets/js/products.js"></script>

</body>

</html>