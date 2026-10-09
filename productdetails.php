<?php

/* =========================================
   PUREVIA PRODUCT DETAILS
========================================= */

/* SESSION */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* CONFIG */

require_once __DIR__ . '/config/app.php';


/* =========================================
   SAMPLE PRODUCT DATA

   FRONTEND ONLY:
   Replace with database records later.
========================================= */

$product = [

    'id' => 1,

    'name' => 'Hyaluronic Acid Hydration Serum',

    'sku' => 'SRM-001',

    'category' => 'Serums',

    'description' =>
    'Multi-molecular Hyaluronic Acid serum with 3 different molecular weights to hydrate every layer of skin. Plumps fine lines and delivers 72 hours of moisture. Fragrance-free and non-comedogenic.',

    'image' =>
    BASE_URL . '/assets/images/hyaluronic-serum.jpg',

    'rating' => '4.9',

    'reviews' => 217,

    'skin_types' => [
        'Dry',
        'Normal',
        'Combination',
        'Sensitive'
    ],

    'concerns' => [
        'Dryness',
        'Wrinkles'
    ],

    'variants' => [

        [
            'id' => 1,
            'size' => '15ml',
            'label' => 'Trial',
            'price' => 22.00,
            'stock' => 40
        ],

        [
            'id' => 2,
            'size' => '30ml',
            'label' => 'Regular',
            'price' => 52.00,
            'stock' => 25
        ],

        [
            'id' => 3,
            'size' => '60ml',
            'label' => 'Value',
            'price' => 88.00,
            'stock' => 12
        ]

    ]

];


/* =========================================
   ESCAPE OUTPUT
========================================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
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
        <?= e($product['name']) ?> | PureVia
    </title>


    <!-- HEADER CSS -->

    <link
        rel="stylesheet"
        href="<?= e(BASE_URL) ?>/css/header.css">


    <!-- FOOTER CSS -->

    <link
        rel="stylesheet"
        href="<?= e(BASE_URL) ?>/css/footer.css">


    <!-- PRODUCT DETAILS CSS -->

    <link
        rel="stylesheet"
        href="<?= e(BASE_URL) ?>/css/productdetails.css">


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>


<body>


    <!-- =========================================
     HEADER
========================================= -->

    <?php
    include __DIR__ . '/includes/header.php';
    ?>


    <!-- =========================================
     PRODUCT DETAILS PAGE
========================================= -->

    <main class="product-page">


        <!-- =====================================
         BREADCRUMB
    ====================================== -->

        <nav
            class="product-breadcrumb"
            aria-label="Breadcrumb">

            <a href="<?= e(BASE_URL) ?>/index.php">
                Home
            </a>

            <span>/</span>

            <a href="<?= e(BASE_URL) ?>/shop.php">
                Products
            </a>

            <span>/</span>

            <span aria-current="page">
                <?= e($product['name']) ?>
            </span>

        </nav>



        <!-- =====================================
         PRODUCT LAYOUT
    ====================================== -->

        <section
            class="product-layout"
            aria-label="Product details">


            <!-- =================================
             LEFT: PRODUCT IMAGE
        ================================== -->

            <div class="product-image-panel">

                <img
                    src="<?= e($product['image']) ?>"
                    alt="<?= e($product['name']) ?>"
                    class="product-image"
                    onerror="this.hidden=true;this.nextElementSibling.hidden=false;">


                <!-- IMAGE FALLBACK -->

                <div
                    class="image-placeholder"
                    hidden>

                    <i class="fa-solid fa-pump-soap"></i>

                    <span>
                        Product image
                    </span>

                    <small>
                        Add your image to
                        assets/images/hyaluronic-serum.jpg
                    </small>

                </div>

            </div>



            <!-- =================================
             RIGHT: PRODUCT INFORMATION
        ================================== -->

            <div class="product-information">


                <!-- CATEGORY AND PRODUCT CODE -->

                <p class="product-meta">

                    <?= e(strtoupper($product['category'])) ?>

                    <span>·</span>

                    <?= e($product['sku']) ?>

                </p>



                <!-- PRODUCT NAME -->

                <h1>
                    <?= e($product['name']) ?>
                </h1>



                <!-- =================================
                 RATING AND STOCK
            ================================== -->

                <div class="product-rating">

                    <span
                        class="stars"
                        aria-label="5 stars">
                        ★★★★★
                    </span>

                    <strong>
                        <?= e($product['rating']) ?>
                    </strong>

                    <span>
                        <?= (int) $product['reviews'] ?>
                        reviews
                    </span>

                    <span
                        class="stock-indicator"
                        id="stockStatus">
                        In Stock (40)
                    </span>

                </div>



                <!-- =================================
                 PRODUCT PRICE
            ================================== -->

                <p
                    class="product-price"
                    id="displayPrice">
                    ₱22.00
                </p>



                <!-- =================================
                 DESCRIPTION
            ================================== -->

                <p class="product-description">

                    <?= e($product['description']) ?>

                </p>



                <!-- =================================
                 PRODUCT FORM
            ================================== -->

                <form
                    id="productForm"
                    action="<?= e(BASE_URL) ?>/actions/cart/add.php"
                    method="POST">


                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int) $product['id'] ?>">



                    <!-- =============================
                     SIZE / OPTION
                ============================== -->

                    <fieldset class="variant-fieldset">

                        <legend class="field-label">
                            SIZE / OPTION
                        </legend>


                        <div class="variant-options">


                            <?php foreach (
                                $product['variants'] as $index => $variant
                            ): ?>


                                <label
                                    class="variant-option
                                <?= $index === 0 ? 'selected' : '' ?>">


                                    <input
                                        type="radio"
                                        name="variant_id"
                                        value="<?= (int) $variant['id'] ?>"
                                        data-price="<?= e($variant['price']) ?>"
                                        data-stock="<?= (int) $variant['stock'] ?>"
                                        <?= $index === 0 ? 'checked' : '' ?>>


                                    <!-- SIZE NAME -->

                                    <span class="variant-copy">

                                        <strong>
                                            <?= e($variant['size']) ?>
                                        </strong>

                                        <small>
                                            <?= e($variant['label']) ?>
                                        </small>

                                    </span>



                                    <!-- SIZE PRICE -->

                                    <strong class="variant-price">

                                        ₱<?= number_format(
                                                $variant['price'],
                                                2
                                            ) ?>

                                    </strong>



                                    <!-- SELECTED CHECK -->

                                    <span
                                        class="variant-check"
                                        aria-hidden="true">
                                        ✓
                                    </span>


                                </label>


                            <?php endforeach; ?>


                        </div>

                    </fieldset>



                    <!-- =============================
                     SUITABLE SKIN TYPES
                ============================== -->

                    <div class="product-tags-section">

                        <p class="field-label">
                            SUITABLE FOR
                        </p>


                        <div class="product-tags skin-tags">


                            <?php foreach (
                                $product['skin_types'] as $type
                            ): ?>


                                <span
                                    class="tag tag-<?= e(strtolower($type)) ?>">

                                    <?= e(strtoupper($type)) ?>

                                </span>


                            <?php endforeach; ?>


                        </div>

                    </div>



                    <!-- =============================
                     TARGET SKIN CONCERNS
                ============================== -->

                    <div class="product-tags-section">

                        <p class="field-label">
                            TARGETS
                        </p>


                        <div class="product-tags">


                            <?php foreach (
                                $product['concerns'] as $concern
                            ): ?>


                                <span class="tag concern-tag">

                                    <?= e(strtoupper($concern)) ?>

                                </span>


                            <?php endforeach; ?>


                        </div>

                    </div>



                    <!-- =============================
                     QUANTITY AND ADD TO BAG
                ============================== -->

                    <div class="purchase-row">


                        <!-- QUANTITY SELECTOR -->

                        <div
                            class="quantity-control"
                            aria-label="Quantity selector">


                            <button
                                type="button"
                                id="decreaseQty"
                                aria-label="Decrease quantity">
                                −
                            </button>


                            <input
                                type="number"
                                name="quantity"
                                id="quantity"
                                value="1"
                                min="1"
                                max="40"
                                aria-label="Quantity">


                            <button
                                type="button"
                                id="increaseQty"
                                aria-label="Increase quantity">
                                +
                            </button>


                        </div>



                        <!-- ADD TO BAG BUTTON -->

                        <button
                            class="add-to-bag"
                            id="addToBag"
                            type="submit">

                            Add to Bag — ₱22.00

                        </button>


                    </div>



                    <!-- CART MESSAGE -->

                    <p
                        class="cart-message"
                        id="cartMessage"
                        role="status"
                        aria-live="polite"></p>


                </form>



                <!-- =================================
                 SHIPPING INFORMATION
            ================================== -->

                <div class="shipping-note">

                    <i class="fa-solid fa-truck-fast"></i>

                    Free shipping on orders over ₱800
                    · 30-day return policy

                </div>


            </div>


        </section>


    </main>



    <!-- =========================================
     FOOTER
========================================= -->

    <?php
    include __DIR__ . '/includes/footer.php';
    ?>



    <!-- =========================================
     PRODUCT DETAILS JAVASCRIPT
========================================= -->

    <script
        src="<?= e(BASE_URL) ?>/js/productdetails.js"
        defer></script>



    <!-- =========================================
     ACCOUNT DROPDOWN
========================================= -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const trigger = document.getElementById(
                'accountDropdownTrigger'
            );

            if (!trigger) return;

            trigger.addEventListener('click', function() {

                const wrapper = trigger.closest(
                    '.account-dropdown'
                );

                const expanded = wrapper.classList.toggle(
                    'active'
                );

                trigger.setAttribute(
                    'aria-expanded',
                    String(expanded)
                );

                document.getElementById(
                    'accountDropdownMenu'
                )?.setAttribute(
                    'aria-hidden',
                    String(!expanded)
                );

            });

        });
    </script>


</body>

</html>