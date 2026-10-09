<?php

/* =========================================================
   PUREVIA
   CUSTOMER PRODUCT DETAILS
========================================================= */

/* =========================================================
   SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   CONFIGURATION
========================================================= */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';


/* =========================================================
   CUSTOMER ACCESS
========================================================= */

if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'customer'
) {

    header(
        'Location: ' . BASE_URL . '/index.php'
    );

    exit;
}


/* =========================================================
   GET PRODUCT ID
========================================================= */

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$productId || $productId < 1) {

    http_response_code(404);

    exit('Product not found.');
}


/* =========================================================
   ESCAPE OUTPUT
========================================================= */

if (!function_exists('productEscape')) {

    function productEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


/* =========================================================
   DATABASE ASSUMPTIONS

   products:
   id, category_id, product_name, description,
   image, price, stock, status

   categories:
   id, category_name

   product_variants:
   id, product_id, variant_name, price, stock

   Adjust these columns to your actual MySQL schema.
========================================================= */


/* =========================================================
   VERIFY ACTIVE CUSTOMER
========================================================= */

$customerId = (int) $_SESSION['user_id'];

$userStatement = $conn->prepare(
    "SELECT u.id
     FROM users AS u
     INNER JOIN roles AS r ON r.id = u.role_id
     WHERE u.id = ?
       AND u.status = 'active'
       AND LOWER(r.role_name) = 'customer'
     LIMIT 1"
);

if (!$userStatement) {
    error_log($conn->error);
    http_response_code(500);
    exit('Unable to load your account.');
}

$userStatement->bind_param('i', $customerId);
$userStatement->execute();

$validCustomer = $userStatement
    ->get_result()
    ->fetch_assoc();

$userStatement->close();

if (!$validCustomer) {

    http_response_code(403);

    exit('Customer access required.');
}


/* =========================================================
   GET SELECTED PRODUCT
========================================================= */

$productSql = "
    SELECT
        p.id,
        p.product_name,
        p.description,
        p.image,
        p.price,
        p.stock,
        p.status,
        c.category_name

    FROM products AS p

    LEFT JOIN categories AS c
        ON c.id = p.category_id

    WHERE p.id = ?
      AND p.status = 'active'

    LIMIT 1
";

$productStatement = $conn->prepare($productSql);

if (!$productStatement) {
    error_log($conn->error);
    http_response_code(500);
    exit('Unable to load product.');
}

$productStatement->bind_param(
    'i',
    $productId
);

$productStatement->execute();

$product = $productStatement
    ->get_result()
    ->fetch_assoc();

$productStatement->close();


/* =========================================================
   PRODUCT NOT FOUND
========================================================= */

if (!$product) {

    http_response_code(404);

    exit('This product is unavailable or does not exist.');
}


/* =========================================================
   GET PRODUCT VARIANTS
========================================================= */

$variantSql = "
    SELECT
        id,
        variant_name,
        price,
        stock

    FROM product_variants

    WHERE product_id = ?

    ORDER BY id ASC
";

$variantStatement = $conn->prepare($variantSql);

if (!$variantStatement) {
    error_log($conn->error);
    http_response_code(500);
    exit('Unable to load product options.');
}

$variantStatement->bind_param(
    'i',
    $productId
);

$variantStatement->execute();

$variantResult = $variantStatement->get_result();

$variants = [];

while ($variant = $variantResult->fetch_assoc()) {

    $variants[] = [
        'id' => (int) $variant['id'],
        'name' => $variant['variant_name'],
        'price' => (float) $variant['price'],
        'stock' => max(0, (int) $variant['stock'])
    ];
}

$variantStatement->close();


/* =========================================================
   FALLBACK IF PRODUCT HAS NO VARIANTS

   When variant_id is 0, the cart backend must use
   the product's own price and stock.
========================================================= */

if (empty($variants)) {

    $variants[] = [
        'id' => 0,
        'name' => 'Standard',
        'price' => (float) $product['price'],
        'stock' => max(0, (int) $product['stock'])
    ];
}


/* =========================================================
   INITIAL SELECTED VARIANT
========================================================= */

$selectedIndex = 0;

foreach ($variants as $index => $variant) {

    if ($variant['stock'] > 0) {
        $selectedIndex = $index;
        break;
    }
}

$selectedVariant = $variants[$selectedIndex];

$initialPrice = $selectedVariant['price'];

$initialStock = $selectedVariant['stock'];


/* =========================================================
   SKIN SUITABILITY

   Replace these empty arrays with database queries
   once the skin-type, concern and ingredient
   relationship tables are connected.
========================================================= */

$skinTypes = [];

$skinConcerns = [];

$ingredients = [];


/* =========================================================
   PRODUCT IMAGE
========================================================= */

$imageName = trim((string) ($product['image'] ?? ''));

$productImage = '';

if ($imageName !== '') {

    if (
        filter_var($imageName, FILTER_VALIDATE_URL) &&
        in_array(
            strtolower((string) parse_url($imageName, PHP_URL_SCHEME)),
            ['http', 'https'],
            true
        )
    ) {

        $productImage = $imageName;
    } else {

        $productImage = BASE_URL
            . '/assets/images/'
            . rawurlencode(basename($imageName));
    }
}


/* =========================================================
   CATEGORY
========================================================= */

$categoryName = $product['category_name']
    ?? 'Skincare';


/* =========================================================
   SAFE PRODUCT DISPLAY VALUES
========================================================= */

$productName = $product['product_name'];

$productDescription = $product['description'];

$allOutOfStock = true;

foreach ($variants as $variant) {

    if ($variant['stock'] > 0) {
        $allOutOfStock = false;
        break;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= productEscape($productName) ?> | PureVia
    </title>


    <!-- HEADER CSS -->

    <link
        rel="stylesheet"
        href="<?= productEscape(BASE_URL) ?>/css/header.css">


    <!-- FOOTER CSS -->

    <link
        rel="stylesheet"
        href="<?= productEscape(BASE_URL) ?>/css/footer.css">


    <!-- PRODUCT DETAILS CSS -->

    <link
        rel="stylesheet"
        href="<?= productEscape(BASE_URL) ?>/css/product_details.css">


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>


<body>


    <!-- =========================================================
     SHARED HEADER
========================================================= -->

    <?php
    include __DIR__ . '/includes/header.php';
    ?>


    <!-- =========================================================
     PRODUCT DETAILS
========================================================= -->

    <main class="product-page">


        <!-- =====================================================
         BREADCRUMB
    ====================================================== -->

        <nav
            class="product-breadcrumb"
            aria-label="Breadcrumb">

            <a href="<?= productEscape(BASE_URL) ?>/index.php">
                Home
            </a>

            <span>/</span>

            <a href="<?= productEscape(BASE_URL) ?>/shop.php">
                Products
            </a>

            <span>/</span>

            <span aria-current="page">
                <?= productEscape($productName) ?>
            </span>

        </nav>



        <!-- =====================================================
         PRODUCT CONTENT
    ====================================================== -->

        <section class="product-layout">


            <!-- =================================================
             LEFT: PRODUCT IMAGE
        ================================================== -->

            <div class="product-image-panel">


                <?php if ($productImage !== ''): ?>

                    <img
                        src="<?= productEscape($productImage) ?>"
                        alt="<?= productEscape($productName) ?>"
                        class="product-image"
                        onerror="this.hidden=true;this.nextElementSibling.hidden=false;">

                <?php endif; ?>


                <!-- IMAGE PLACEHOLDER -->

                <div
                    class="image-placeholder"
                    <?= $productImage !== '' ? 'hidden' : '' ?>>

                    <i class="fa-solid fa-pump-soap"></i>

                    <span>
                        Product Image
                    </span>

                    <small>
                        No product image available
                    </small>

                </div>


            </div>



            <!-- =================================================
             RIGHT: PRODUCT INFORMATION
        ================================================== -->

            <div class="product-information">


                <!-- CATEGORY AND PRODUCT CODE -->

                <p class="product-meta">

                    <?= productEscape(strtoupper($categoryName)) ?>

                    <span>·</span>

                    PRODUCT #<?= (int) $product['id'] ?>

                </p>



                <!-- PRODUCT NAME -->

                <h1>
                    <?= productEscape($productName) ?>
                </h1>



                <!-- =================================================
                 AVAILABILITY
            ================================================== -->

                <div class="product-rating">

                    <span
                        class="stock-indicator <?= $allOutOfStock ? 'out-of-stock' : '' ?>"
                        id="stockStatus">

                        <?php if ($allOutOfStock): ?>

                            Out of Stock

                        <?php else: ?>

                            In Stock (<?= $initialStock ?>)

                        <?php endif; ?>

                    </span>

                </div>



                <!-- =================================================
                 PRODUCT PRICE
            ================================================== -->

                <p
                    class="product-price"
                    id="displayPrice">

                    ₱<?= number_format($initialPrice, 2) ?>

                </p>



                <!-- =================================================
                 DESCRIPTION
            ================================================== -->

                <p class="product-description">

                    <?= nl2br(productEscape($productDescription)) ?>

                </p>



                <!-- =================================================
                 PRODUCT FORM
            ================================================== -->

                <form
                    id="productForm"
                    action="<?= productEscape(BASE_URL) ?>/actions/cart/add.php"
                    method="POST">


                    <!-- PRODUCT ID -->

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int) $product['id'] ?>">



                    <!-- =================================================
                     PRODUCT VARIANTS
                ================================================== -->

                    <fieldset class="variant-fieldset">

                        <legend class="field-label">
                            SIZE / OPTION
                        </legend>


                        <div class="variant-options">


                            <?php foreach ($variants as $index => $variant): ?>


                                <label
                                    class="variant-option
                                <?= $index === $selectedIndex ? 'selected' : '' ?>
                                <?= $variant['stock'] <= 0 ? 'variant-unavailable' : '' ?>">


                                    <input
                                        type="radio"
                                        name="variant_id"
                                        value="<?= (int) $variant['id'] ?>"
                                        data-price="<?= productEscape($variant['price']) ?>"
                                        data-stock="<?= (int) $variant['stock'] ?>"
                                        <?= $index === $selectedIndex ? 'checked' : '' ?>
                                        <?= $variant['stock'] <= 0 ? 'disabled' : '' ?>>


                                    <span class="variant-copy">

                                        <strong>
                                            <?= productEscape($variant['name']) ?>
                                        </strong>


                                        <small>

                                            <?= $variant['stock'] > 0
                                                ? 'Available'
                                                : 'Out of Stock' ?>

                                        </small>

                                    </span>



                                    <strong class="variant-price">

                                        ₱<?= number_format(
                                                $variant['price'],
                                                2
                                            ) ?>

                                    </strong>



                                    <span
                                        class="variant-check"
                                        aria-hidden="true">
                                        ✓
                                    </span>


                                </label>


                            <?php endforeach; ?>


                        </div>

                    </fieldset>



                    <!-- =================================================
                     SUITABLE SKIN TYPES
                ================================================== -->

                    <?php if (!empty($skinTypes)): ?>

                        <div class="product-tags-section">

                            <p class="field-label">
                                SUITABLE FOR
                            </p>

                            <div class="product-tags skin-tags">

                                <?php foreach ($skinTypes as $type): ?>

                                    <span class="tag">

                                        <?= productEscape(strtoupper($type)) ?>

                                    </span>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endif; ?>



                    <!-- =================================================
                     TARGETED SKIN CONCERNS
                ================================================== -->

                    <?php if (!empty($skinConcerns)): ?>

                        <div class="product-tags-section">

                            <p class="field-label">
                                TARGETS
                            </p>

                            <div class="product-tags">

                                <?php foreach ($skinConcerns as $concern): ?>

                                    <span class="tag concern-tag">

                                        <?= productEscape(strtoupper($concern)) ?>

                                    </span>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endif; ?>



                    <!-- =================================================
                     INGREDIENTS
                ================================================== -->

                    <?php if (!empty($ingredients)): ?>

                        <div class="product-tags-section">

                            <p class="field-label">
                                INGREDIENTS
                            </p>

                            <p class="product-ingredients">

                                <?= productEscape(
                                    implode(', ', $ingredients)
                                ) ?>

                            </p>

                        </div>

                    <?php endif; ?>



                    <!-- =================================================
                     QUANTITY AND ADD TO BAG
                ================================================== -->

                    <div class="purchase-row">


                        <!-- QUANTITY CONTROL -->

                        <div class="quantity-control">


                            <button
                                type="button"
                                id="decreaseQty"
                                aria-label="Decrease quantity"
                                <?= $allOutOfStock ? 'disabled' : '' ?>>
                                −
                            </button>


                            <input
                                type="number"
                                name="quantity"
                                id="quantity"
                                value="1"
                                min="1"
                                max="<?= max(1, $initialStock) ?>"
                                aria-label="Quantity"
                                <?= $allOutOfStock ? 'disabled' : '' ?>>


                            <button
                                type="button"
                                id="increaseQty"
                                aria-label="Increase quantity"
                                <?= $allOutOfStock ? 'disabled' : '' ?>>
                                +
                            </button>


                        </div>



                        <!-- ADD TO BAG -->

                        <button
                            type="submit"
                            class="add-to-bag"
                            id="addToBag"
                            <?= $allOutOfStock ? 'disabled' : '' ?>>

                            <?= $allOutOfStock
                                ? 'Out of Stock'
                                : 'Add to Bag — ₱' .
                                number_format($initialPrice, 2) ?>

                        </button>


                    </div>



                    <!-- MESSAGE -->

                    <p
                        class="cart-message"
                        id="cartMessage"
                        role="status"
                        aria-live="polite"></p>


                </form>



                <!-- =================================================
                 SHIPPING INFORMATION
            ================================================== -->

                <div class="shipping-note">

                    <i class="fa-solid fa-truck-fast"></i>

                    Free shipping on orders over ₱800
                    · 30-day return policy

                </div>


            </div>


        </section>


    </main>



    <!-- =========================================================
     SHARED FOOTER
========================================================= -->

    <?php
    include __DIR__ . '/includes/footer.php';
    ?>


    <!-- =========================================================
     PRODUCT DETAILS JAVASCRIPT
========================================================= -->

    <script
        src="<?= productEscape(BASE_URL) ?>/js/product_details.js"
        defer></script>


</body>

</html>