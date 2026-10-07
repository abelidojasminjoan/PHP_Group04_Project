<?php

session_start();

$pageTitle = "Inventory";


/* =========================================================
   TEMPORARY ADMIN SESSION
   Remove when your login system is fully connected
========================================================= */

$_SESSION['first_name'] =
    $_SESSION['first_name'] ?? 'Ava';

$_SESSION['last_name'] =
    $_SESSION['last_name'] ?? 'Santos';

$_SESSION['email'] =
    $_SESSION['email'] ?? 'admin@purevia.com';


/* =========================================================
   SAMPLE INVENTORY DATA
   Replace with database query later
========================================================= */

$inventory = [

    [
        'id' => 1,
        'product' => 'Gentle Botanical Cleanser',
        'sku' => 'CLN-001',
        'category' => 'Cleansers',
        'stock' => 45,
        'threshold' => 15,
        'last_restocked' => '2026-09-10'
    ],

    [
        'id' => 2,
        'product' => 'Salicylic Pore Refining Cleanser',
        'sku' => 'CLN-002',
        'category' => 'Cleansers',
        'stock' => 62,
        'threshold' => 15,
        'last_restocked' => '2026-09-10'
    ],

    [
        'id' => 3,
        'product' => 'Hyaluronic Acid Hydration Serum',
        'sku' => 'SRM-001',
        'category' => 'Serums',
        'stock' => 89,
        'threshold' => 15,
        'last_restocked' => '2026-09-10'
    ],

    [
        'id' => 4,
        'product' => 'Vitamin C Brightening Serum',
        'sku' => 'SRM-002',
        'category' => 'Serums',
        'stock' => 34,
        'threshold' => 15,
        'last_restocked' => '2026-09-10'
    ],

    [
        'id' => 5,
        'product' => 'Retinol Renewal Night Serum',
        'sku' => 'SRM-003',
        'category' => 'Serums',
        'stock' => 27,
        'threshold' => 15,
        'last_restocked' => '2026-09-10'
    ],

    [
        'id' => 6,
        'product' => 'Niacinamide 10% + Zinc 1% Serum',
        'sku' => 'SRM-004',
        'category' => 'Serums',
        'stock' => 71,
        'threshold' => 15,
        'last_restocked' => '2026-09-10'
    ],

    [
        'id' => 7,
        'product' => 'Ceramide Barrier Repair Moisturizer',
        'sku' => 'MST-001',
        'category' => 'Moisturizers',
        'stock' => 53,
        'threshold' => 15,
        'last_restocked' => '2026-09-10'
    ],

    [
        'id' => 8,
        'product' => 'Daily Hydrating Moisturizer',
        'sku' => 'MST-002',
        'category' => 'Moisturizers',
        'stock' => 12,
        'threshold' => 15,
        'last_restocked' => '2026-09-08'
    ],

    [
        'id' => 9,
        'product' => 'Daily Defense Sunscreen SPF 50',
        'sku' => 'SUN-001',
        'category' => 'Sunscreen',
        'stock' => 38,
        'threshold' => 15,
        'last_restocked' => '2026-09-07'
    ],

    [
        'id' => 10,
        'product' => 'Mineral Sunscreen SPF 40',
        'sku' => 'SUN-002',
        'category' => 'Sunscreen',
        'stock' => 8,
        'threshold' => 15,
        'last_restocked' => '2026-09-05'
    ],

    [
        'id' => 11,
        'product' => 'Soothing Hydration Toner',
        'sku' => 'TNR-001',
        'category' => 'Toners',
        'stock' => 25,
        'threshold' => 15,
        'last_restocked' => '2026-09-03'
    ],

    [
        'id' => 12,
        'product' => 'Gentle Exfoliating Toner',
        'sku' => 'TNR-002',
        'category' => 'Toners',
        'stock' => 0,
        'threshold' => 15,
        'last_restocked' => '2026-08-28'
    ]

];


$totalInventory = count($inventory);


/* =========================================================
   CREATE UNIQUE CATEGORY LIST
========================================================= */

$categories = [];

foreach ($inventory as $item) {

    if (!in_array($item['category'], $categories, true)) {

        $categories[] = $item['category'];

    }

}

sort($categories);

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
        <?= htmlspecialchars($pageTitle) ?> | PureVia
    </title>


    <!-- GOOGLE FONTS -->

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@400;500;600&display=swap"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- SIDEBAR CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/sidemenu.css"
    >


    <!-- INVENTORY CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/inventory.css"
    >

</head>


<body>


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php include("../../includes/sidemenu.php"); ?>


    <!-- =====================================================
         ADMIN LAYOUT
    ====================================================== -->

    <div class="admin-layout">


        <!-- =================================================
             TOP BAR
        ================================================== -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Inventory
            </p>

        </header>


        <!-- =================================================
             MAIN CONTENT
        ================================================== -->

        <main class="inventory-main">


            <!-- =============================================
                 PAGE HEADER
            ============================================== -->

            <section class="inventory-page-header">


                <div class="inventory-heading">

                    <h1>
                        Inventory
                    </h1>

                    <p>
                        <?= number_format($totalInventory) ?>
                        records
                    </p>

                </div>


            </section>


            <!-- =============================================
                 INVENTORY CARD
            ============================================== -->

            <section class="inventory-card">


                <!-- =========================================
                     SEARCH + FILTERS
                ========================================== -->

                <div class="inventory-toolbar">


                    <!-- SEARCH -->

                    <div class="inventory-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="inventorySearch"
                            placeholder="Search inventory..."
                            autocomplete="off"
                        >

                    </div>


                    <!-- CATEGORY FILTER -->

                    <div class="inventory-filter-dropdown">

                        <i
                            class="fa-solid fa-filter inventory-filter-icon"
                        ></i>

                        <select
                            id="inventoryCategoryFilter"
                            class="inventory-filter-select"
                            aria-label="Filter inventory by category"
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


                        <i
                            class="fa-solid fa-chevron-down inventory-filter-arrow"
                        ></i>

                    </div>


                    <!-- STOCK FILTER -->

                    <div class="inventory-filter-dropdown">

                        <select
                            id="inventoryStockFilter"
                            class="inventory-filter-select inventory-filter-no-icon"
                            aria-label="Filter inventory by stock level"
                        >

                            <option value="all">
                                All Stock
                            </option>

                            <option value="in-stock">
                                In Stock
                            </option>

                            <option value="low-stock">
                                Low Stock
                            </option>

                            <option value="out-of-stock">
                                Out of Stock
                            </option>

                        </select>


                        <i
                            class="fa-solid fa-chevron-down inventory-filter-arrow"
                        ></i>

                    </div>


                </div>


                <!-- =========================================
                     TABLE
                ========================================== -->

                <div class="inventory-table-wrapper">


                    <table class="inventory-table">


                        <!-- TABLE HEADER -->

                        <thead>

                            <tr>

                                <th>PRODUCT</th>

                                <th>SKU</th>

                                <th>CATEGORY</th>

                                <th>STOCK</th>

                                <th>THRESHOLD</th>

                                <th>STATUS</th>

                                <th>LAST RESTOCKED</th>

                                <th>ACTIONS</th>

                            </tr>

                        </thead>


                        <!-- TABLE BODY -->

                        <tbody>


                            <?php foreach ($inventory as $item): ?>


                                <?php

                                $stock = (int) $item['stock'];

                                $threshold =
                                    (int) $item['threshold'];


                                /* =========================
                                   DETERMINE STOCK LEVEL
                                ========================== */

                                if ($stock <= 0) {

                                    $stockLevel =
                                        'out-of-stock';

                                } elseif ($stock <= $threshold) {

                                    $stockLevel =
                                        'low-stock';

                                } else {

                                    $stockLevel =
                                        'in-stock';

                                }


                                /* =========================
                                   SEARCH DATA
                                ========================== */

                                $searchData =
                                    strtolower(
                                        $item['product']
                                        . ' '
                                        . $item['sku']
                                        . ' '
                                        . $item['category']
                                    );

                                ?>


                                <tr
                                    class="inventory-row"

                                    data-search="<?= htmlspecialchars(
                                        $searchData
                                    ) ?>"

                                    data-category="<?= htmlspecialchars(
                                        strtolower(
                                            $item['category']
                                        )
                                    ) ?>"

                                    data-stock-level="<?= htmlspecialchars(
                                        $stockLevel
                                    ) ?>"
                                >


                                    <!-- PRODUCT -->

                                    <td>

                                        <span class="inventory-product-name">

                                            <?= htmlspecialchars(
                                                $item['product']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- SKU -->

                                    <td>

                                        <span class="inventory-sku">

                                            <?= htmlspecialchars(
                                                $item['sku']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <span class="inventory-category">

                                            <?= htmlspecialchars(
                                                $item['category']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STOCK -->

                                    <td>

                                        <span
                                            class="
                                                inventory-stock
                                                <?= htmlspecialchars(
                                                    $stockLevel
                                                ) ?>
                                            "
                                        >

                                            <?= number_format(
                                                $stock
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- THRESHOLD -->

                                    <td>

                                        <span class="inventory-threshold">

                                            <?= number_format(
                                                $threshold
                                            ) ?>

                                        </span>

                                    </td>

                                    <!-- STOCK STATUS -->
                                    <td>

                                        <?php

                                        if ($stockLevel === 'out-of-stock') {

                                            $stockStatusText = 'Out of Stock';

                                        } elseif ($stockLevel === 'low-stock') {

                                            $stockStatusText = 'Low Stock';

                                        } else {

                                            $stockStatusText = 'In Stock';

                                        }

                                        ?>

                                        <span
                                            class="inventory-stock-status <?= htmlspecialchars($stockLevel) ?>"
                                        >
                                            <?= htmlspecialchars($stockStatusText) ?>
                                        </span>

                                    </td>


                                    <!-- LAST RESTOCKED -->

                                    <td>

                                        <span class="inventory-date">

                                            <?= htmlspecialchars(
                                                $item['last_restocked']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <div class="inventory-actions">


                                            <a
                                                href="./edit_inventory.php?id=<?= urlencode(
                                                    $item['id']
                                                ) ?>"

                                                class="inventory-edit-button"

                                                title="Edit inventory"

                                                aria-label="Edit inventory for <?= htmlspecialchars(
                                                    $item['product']
                                                ) ?>"
                                            >

                                                <i class="fa-regular fa-pen-to-square"></i>

                                            </a>


                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            <!-- =================================
                                 NO RESULTS
                            ================================== -->

                            <tr
                                id="noInventoryFound"
                                class="no-inventory-row"
                                style="display: none;"
                            >

                                <td colspan="8">

                                    <div class="no-inventory-message">

                                        <i class="fa-solid fa-box-open"></i>

                                        <p>
                                            No inventory records found.
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


<!-- INVENTORY JS -->

<script src="../../assets/js/inventory.js"></script>


</body>

</html>