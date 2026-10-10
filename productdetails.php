<?php
/* =========================================================
   PUREVIA
   CUSTOMER PRODUCT DETAILS
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';


/* =========================================================
   SECURE OUTPUT
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
   CUSTOMER AUTHENTICATION
========================================================= */

if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'customer'
) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$customerId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT u.id
     FROM users u
     INNER JOIN roles r ON r.id = u.role_id
     WHERE u.id = ?
       AND u.status = 'active'
       AND LOWER(r.role_name) = 'customer'
     LIMIT 1"
);

$stmt->bind_param('i', $customerId);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    http_response_code(403);
    exit('Customer access required.');
}


/* =========================================================
   STEP 1: COLLECT PRODUCT ID
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
   CSRF TOKEN
========================================================= */

if (empty($_SESSION['product_cart_csrf'])) {
    $_SESSION['product_cart_csrf'] =
        bin2hex(random_bytes(32));
}

$errors = [];

$success = $_SESSION['product_cart_success'] ?? '';

unset($_SESSION['product_cart_success']);

$enteredQuantity = '1';


/* =========================================================
   GET SELECTED PRODUCT
========================================================= */

$stmt = $conn->prepare(
    "SELECT
        p.id,
        p.product_name,
        p.product_code,
        p.description,
        p.usage_instructions,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        c.category_name

     FROM products p

     INNER JOIN categories c
        ON c.id = p.category_id

     WHERE p.id = ?
       AND p.status IN ('active', 'out_of_stock')
       AND c.status = 'active'

     LIMIT 1"
);

$stmt->bind_param('i', $productId);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    http_response_code(404);
    exit('This product is unavailable or does not exist.');
}


/* =========================================================
   HANDLE ADD TO BAG FORM
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =====================================================
       STEP 1: COLLECTION
    ====================================================== */

    $enteredQuantity = trim(
        (string) ($_POST['quantity'] ?? '')
    );

    $submittedProductId = filter_var(
        $_POST['product_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $csrf = $_POST['csrf_token'] ?? '';


    /* =====================================================
       STEP 2: VALIDATION
    ====================================================== */

    if (
        !is_string($csrf) ||
        !hash_equals(
            $_SESSION['product_cart_csrf'],
            $csrf
        )
    ) {
        $errors[] = 'Invalid request. Please refresh the page.';
    }

    if ($submittedProductId !== $productId) {
        $errors[] = 'The selected product is invalid.';
    }

    if (
        $enteredQuantity === '' ||
        !ctype_digit($enteredQuantity) ||
        strlen($enteredQuantity) > 9
    ) {
        $errors[] = 'Please enter a valid whole-number quantity.';
    } elseif ((int) $enteredQuantity < 1) {
        $errors[] = 'Quantity must be at least 1.';
    } elseif (
        $product['status'] !== 'active' ||
        (int) $enteredQuantity >
        (int) $product['stock_quantity']
    ) {
        $errors[] = 'The requested quantity is not available.';
    }


    /* =====================================================
       STEP 3: SANITIZATION

       User values are validated above and escaped
       with htmlspecialchars() when displayed below.
    ====================================================== */


    /* =====================================================
       STEP 4: DATABASE PROCESSING
    ====================================================== */

    if (empty($errors)) {

        try {

            $conn->begin_transaction();

            $requested = (int) $enteredQuantity;


            /* LOCK PRODUCT AND RECHECK STOCK */

            $stmt = $conn->prepare(
                "SELECT stock_quantity, status
                 FROM products
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param('i', $productId);
            $stmt->execute();

            $freshProduct = $stmt
                ->get_result()
                ->fetch_assoc();

            $stmt->close();

            if (
                !$freshProduct ||
                $freshProduct['status'] !== 'active' ||
                $requested >
                (int) $freshProduct['stock_quantity']
            ) {
                throw new DomainException(
                    'The requested quantity is no longer available.'
                );
            }


            /* CREATE OR RETRIEVE CUSTOMER CART */

            $stmt = $conn->prepare(
                "INSERT INTO carts (user_id)
                 VALUES (?)
                 ON DUPLICATE KEY UPDATE
                 id = LAST_INSERT_ID(id)"
            );

            $stmt->bind_param('i', $customerId);
            $stmt->execute();

            $cartId = (int) $conn->insert_id;

            $stmt->close();


            /* CHECK EXISTING CART ITEM */

            $stmt = $conn->prepare(
                "SELECT quantity
                 FROM cart_items
                 WHERE cart_id = ?
                   AND product_id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param(
                'ii',
                $cartId,
                $productId
            );

            $stmt->execute();

            $existingItem = $stmt
                ->get_result()
                ->fetch_assoc();

            $stmt->close();


            /* VALIDATE COMBINED CART QUANTITY */

            $currentQuantity =
                (int) ($existingItem['quantity'] ?? 0);

            $newQuantity = $currentQuantity + $requested;

            if (
                $newQuantity >
                (int) $freshProduct['stock_quantity']
            ) {
                throw new DomainException(
                    'Your cart quantity exceeds available stock.'
                );
            }


            /* UPDATE OR INSERT CART ITEM */

            if ($existingItem) {

                $stmt = $conn->prepare(
                    "UPDATE cart_items
                     SET quantity = ?
                     WHERE cart_id = ?
                       AND product_id = ?"
                );

                $stmt->bind_param(
                    'iii',
                    $newQuantity,
                    $cartId,
                    $productId
                );
            } else {

                $stmt = $conn->prepare(
                    "INSERT INTO cart_items
                     (cart_id, product_id, quantity)
                     VALUES (?, ?, ?)"
                );

                $stmt->bind_param(
                    'iii',
                    $cartId,
                    $productId,
                    $newQuantity
                );
            }

            $stmt->execute();
            $stmt->close();

            $conn->commit();


            /* =================================================
               STEP 5: RESPONSE
            ================================================== */

            $_SESSION['product_cart_success'] =
                'Product successfully added to your bag.';

            $_SESSION['product_cart_csrf'] =
                bin2hex(random_bytes(32));

            header(
                'Location: ' . BASE_URL .
                    '/productdetails.php?id=' . $productId,
                true,
                303
            );

            exit;
        } catch (DomainException $exception) {

            $conn->rollback();

            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {

            $conn->rollback();

            error_log(
                'Add to bag error: ' .
                    $exception->getMessage()
            );

            $errors[] =
                'Unable to add the product. Please try again.';
        }
    }
}


/* =========================================================
   GET PRODUCT SKIN TYPES
========================================================= */

$skinTypes = [];

$stmt = $conn->prepare(
    "SELECT st.skin_type_name
     FROM product_skin_types pst
     INNER JOIN skin_types st
        ON st.id = pst.skin_type_id
     WHERE pst.product_id = ?
       AND st.status = 'active'
     ORDER BY st.skin_type_name"
);

$stmt->bind_param('i', $productId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $skinTypes[] = $row['skin_type_name'];
}

$stmt->close();


/* =========================================================
   GET PRODUCT SKIN CONCERNS
========================================================= */

$skinConcerns = [];

$stmt = $conn->prepare(
    "SELECT sc.concern_name
     FROM product_concerns pc
     INNER JOIN skin_concerns sc
        ON sc.id = pc.concern_id
     WHERE pc.product_id = ?
       AND sc.status = 'active'
     ORDER BY sc.concern_name"
);

$stmt->bind_param('i', $productId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $skinConcerns[] = $row['concern_name'];
}

$stmt->close();


/* =========================================================
   GET INGREDIENTS AND COMPATIBILITY
========================================================= */

$ingredients = [];
$avoidedMatches = [];

$stmt = $conn->prepare(
    "SELECT
        i.ingredient_name,
        CASE
            WHEN uai.user_id IS NOT NULL THEN 1
            ELSE 0
        END AS avoided

     FROM product_ingredients pi

     INNER JOIN ingredients i
        ON i.id = pi.ingredient_id

     LEFT JOIN user_avoided_ingredients uai
        ON uai.ingredient_id = i.id
       AND uai.user_id = ?

     WHERE pi.product_id = ?
       AND i.status = 'active'

     ORDER BY i.ingredient_name"
);

$stmt->bind_param(
    'ii',
    $customerId,
    $productId
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $ingredients[] = $row['ingredient_name'];

    if ((int) $row['avoided'] === 1) {
        $avoidedMatches[] = $row['ingredient_name'];
    }
}

$stmt->close();


/* =========================================================
   PRODUCT IMAGE
========================================================= */

$imageName = trim(
    (string) ($product['image'] ?? '')
);

$productImage = '';

if ($imageName !== '') {

    if (
        filter_var($imageName, FILTER_VALIDATE_URL) &&
        strtolower((string) parse_url(
            $imageName,
            PHP_URL_SCHEME
        )) === 'https'
    ) {
        $productImage = $imageName;
    } else {
        $productImage = BASE_URL .
            '/assets/images/' .
            rawurlencode(basename($imageName));
    }
}


/* =========================================================
   AVAILABILITY
========================================================= */

$available =
    $product['status'] === 'active' &&
    (int) $product['stock_quantity'] > 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= productEscape($product['product_name']) ?> | PureVia
    </title>

    <link
        rel="stylesheet"
        href="<?= productEscape(BASE_URL) ?>/css/header.css">

    <link
        rel="stylesheet"
        href="<?= productEscape(BASE_URL) ?>/css/footer.css">

    <link
        rel="stylesheet"
        href="<?= productEscape(BASE_URL) ?>/css/product_details.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

    <?php include __DIR__ . '/includes/header.php'; ?>


    <main class="product-page">

        <!-- BREADCRUMB -->

        <nav class="product-breadcrumb" aria-label="Breadcrumb">

            <a href="<?= productEscape(BASE_URL) ?>/index.php">
                Home
            </a>

            <span>/</span>

            <a href="<?= productEscape(BASE_URL) ?>/shop.php">
                Products
            </a>

            <span>/</span>

            <span aria-current="page">
                <?= productEscape($product['product_name']) ?>
            </span>

        </nav>


        <!-- PRODUCT LAYOUT -->

        <section class="product-layout">


            <!-- PRODUCT IMAGE -->

            <div class="product-image-panel">

                <?php if ($productImage !== ''): ?>

                    <img
                        src="<?= productEscape($productImage) ?>"
                        alt="<?= productEscape($product['product_name']) ?>"
                        class="product-image"
                        onerror="this.hidden=true;this.nextElementSibling.hidden=false;">

                <?php endif; ?>

                <div
                    class="image-placeholder"
                    <?= $productImage !== '' ? 'hidden' : '' ?>>

                    <i class="fa-solid fa-pump-soap"></i>

                    <span>Product Image</span>

                    <small>No product image available</small>

                </div>

            </div>


            <!-- PRODUCT INFORMATION -->

            <div class="product-information">

                <p class="product-meta">

                    <?= productEscape(
                        strtoupper($product['category_name'])
                    ) ?>

                    <span>·</span>

                    <?= productEscape($product['product_code']) ?>

                </p>


                <h1>
                    <?= productEscape($product['product_name']) ?>
                </h1>


                <!-- STOCK -->

                <div class="product-rating">

                    <span
                        id="stockStatus"
                        class="stock-indicator <?= !$available ? 'out-of-stock' : '' ?>">
                        <?= $available
                            ? 'In Stock (' .
                            (int) $product['stock_quantity'] . ')'
                            : 'Out of Stock' ?>
                    </span>

                </div>


                <!-- PRICE -->

                <p class="product-price" id="displayPrice">
                    ₱<?= number_format(
                            (float) $product['price'],
                            2
                        ) ?>
                </p>


                <!-- DESCRIPTION -->

                <p class="product-description">
                    <?= nl2br(
                        productEscape(
                            $product['description'] ?? ''
                        )
                    ) ?>
                </p>


                <!-- SUCCESS MESSAGE -->

                <?php if ($success !== ''): ?>

                    <p class="cart-message cart-success" role="status">
                        <?= productEscape($success) ?>
                    </p>

                <?php endif; ?>


                <!-- VALIDATION ERRORS -->

                <?php if (!empty($errors)): ?>

                    <div class="cart-message cart-error" role="alert">

                        <strong>Unable to add to bag:</strong>

                        <ul>
                            <?php foreach ($errors as $error): ?>

                                <li>
                                    <?= productEscape($error) ?>
                                </li>

                            <?php endforeach; ?>
                        </ul>

                    </div>

                <?php endif; ?>


                <!-- SUITABLE SKIN TYPES -->

                <?php if (!empty($skinTypes)): ?>

                    <div class="product-tags-section">

                        <p class="field-label">SUITABLE FOR</p>

                        <div class="product-tags">

                            <?php foreach ($skinTypes as $type): ?>

                                <span class="tag">
                                    <?= productEscape(strtoupper($type)) ?>
                                </span>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- TARGETED CONCERNS -->

                <?php if (!empty($skinConcerns)): ?>

                    <div class="product-tags-section">

                        <p class="field-label">TARGETS</p>

                        <div class="product-tags">

                            <?php foreach ($skinConcerns as $concern): ?>

                                <span class="tag concern-tag">
                                    <?= productEscape(strtoupper($concern)) ?>
                                </span>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- INGREDIENTS -->

                <?php if (!empty($ingredients)): ?>

                    <div class="product-tags-section">

                        <p class="field-label">INGREDIENTS</p>

                        <p class="product-ingredients">
                            <?= productEscape(
                                implode(', ', $ingredients)
                            ) ?>
                        </p>

                    </div>

                <?php endif; ?>


                <!-- INGREDIENT COMPATIBILITY -->

                <?php if (!empty($avoidedMatches)): ?>

                    <div
                        class="compatibility-notice"
                        role="status">

                        <strong>
                            Ingredient Compatibility Notice
                        </strong>

                        <p>
                            This product contains
                            <?= productEscape(
                                implode(', ', $avoidedMatches)
                            ) ?>,
                            which
                            <?= count($avoidedMatches) === 1
                                ? 'is'
                                : 'are' ?>
                            on your ingredients-to-avoid list.
                        </p>

                        <small>
                            This notice is informational and
                            does not constitute medical advice.
                        </small>

                    </div>

                <?php endif; ?>


                <!-- USAGE INSTRUCTIONS -->

                <?php if (
                    trim((string) (
                        $product['usage_instructions'] ?? ''
                    )) !== ''
                ): ?>

                    <div class="product-tags-section">

                        <p class="field-label">HOW TO USE</p>

                        <p class="product-description">
                            <?= nl2br(
                                productEscape(
                                    $product['usage_instructions']
                                )
                            ) ?>
                        </p>

                    </div>

                <?php endif; ?>


                <!-- ADD TO BAG FORM -->

                <form
                    id="productForm"
                    method="POST"
                    action="<?= productEscape(BASE_URL) ?>/productdetails.php?id=<?= (int) $productId ?>">

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int) $productId ?>">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= productEscape(
                                    $_SESSION['product_cart_csrf']
                                ) ?>">


                    <div class="purchase-row">

                        <div
                            class="quantity-control"
                            data-price="<?= productEscape($product['price']) ?>"
                            data-stock="<?= (int) $product['stock_quantity'] ?>">

                            <button
                                type="button"
                                id="decreaseQty"
                                aria-label="Decrease quantity"
                                <?= !$available ? 'disabled' : '' ?>>
                                −
                            </button>

                            <input
                                type="number"
                                name="quantity"
                                id="quantity"
                                value="<?= productEscape($enteredQuantity) ?>"
                                min="1"
                                max="<?= (int) $product['stock_quantity'] ?>"
                                step="1"
                                required
                                aria-label="Quantity"
                                <?= !$available ? 'disabled' : '' ?>>

                            <button
                                type="button"
                                id="increaseQty"
                                aria-label="Increase quantity"
                                <?= !$available ? 'disabled' : '' ?>>
                                +
                            </button>

                        </div>


                        <button
                            type="submit"
                            class="add-to-bag"
                            id="addToBag"
                            <?= !$available ? 'disabled' : '' ?>>
                            <?= $available
                                ? 'Add to Bag — ₱' .
                                number_format(
                                    (float) $product['price'],
                                    2
                                )
                                : 'Out of Stock' ?>
                        </button>

                    </div>

                </form>


                <!-- SHIPPING INFORMATION -->

                <div class="shipping-note">

                    <i class="fa-solid fa-truck-fast"></i>

                    Free shipping on orders over ₱800
                    · 30-day return policy

                </div>

            </div>

        </section>

    </main>


    <?php include __DIR__ . '/includes/footer.php'; ?>


    <script
        src="<?= productEscape(BASE_URL) ?>/js/product_details.js"
        defer></script>

</body>

</html>