
<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';

/* ================================
   HELPERS
================================ */

function productEscape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function productImageUrl(?string $path): string
{
    $fallback = BASE_URL . '/assets/images/products/default-product.jpg';
    $path = trim((string) $path);

    if ($path === '') {
        return $fallback;
    }

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($path, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true)
            ? $path
            : $fallback;
    }

    $filename = basename(str_replace('\\', '/', $path));

    if ($filename === '.' || $filename === '..') {
        return $fallback;
    }

    return is_file(__DIR__ . '/uploads/products/' . $filename)
        ? BASE_URL . '/uploads/products/' . rawurlencode($filename)
        : $fallback;
}

/* ================================
   PRODUCT ID
================================ */

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    http_response_code(404);
    exit('Product not found.');
}

/* ================================
   PRODUCT INFORMATION
================================ */

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.product_name,
        p.product_code,
        p.description,
        p.usage_instructions,
        p.price,
        p.image,
        p.stock_quantity,
        c.category_name
    FROM products p
    INNER JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
      AND p.status = 'active'
      AND c.status = 'active'
    LIMIT 1
");

$stmt->bind_param('i', $productId);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    http_response_code(404);
    exit('This product is unavailable.');
}

$pageTitle = $product['product_name'] . ' | PureVia';

/* ================================
   PRODUCT IMAGES
================================ */

$images = [];

$stmt = $conn->prepare("
    SELECT image_path
    FROM product_images
    WHERE product_id = ?
    ORDER BY sort_order ASC, id ASC
");

$stmt->bind_param('i', $productId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (trim((string) $row['image_path']) !== '') {
        $images[] = productImageUrl($row['image_path']);
    }
}

$stmt->close();

if (!$images) {
    $images[] = productImageUrl($product['image']);
}

$images = array_values(array_unique($images));

/* ================================
   PRODUCT VARIANTS
================================ */

$variants = [];

$stmt = $conn->prepare("
    SELECT
        id,
        size_capacity,
        variant_label,
        price,
        stock_quantity
    FROM product_variants
    WHERE product_id = ?
    ORDER BY id ASC
");

$stmt->bind_param('i', $productId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $variants[] = $row;
}

$stmt->close();

if (!$variants) {
    $variants[] = [
        'id' => 0,
        'size_capacity' => 'Standard',
        'variant_label' => '',
        'price' => $product['price'],
        'stock_quantity' => $product['stock_quantity']
    ];
}

$selectedVariant = $variants[0];

foreach ($variants as $variant) {
    if ((int) $variant['stock_quantity'] > 0) {
        $selectedVariant = $variant;
        break;
    }
}

$totalStock = array_sum(
    array_map(
        fn($variant) => (int) $variant['stock_quantity'],
        $variants
    )
);

/* ================================
   SKIN TYPES
================================ */

$skinTypes = [];

$stmt = $conn->prepare("
    SELECT st.skin_type_name
    FROM product_skin_types pst
    INNER JOIN skin_types st
        ON st.id = pst.skin_type_id
    WHERE pst.product_id = ?
    ORDER BY st.skin_type_name
");

$stmt->bind_param('i', $productId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $skinTypes[] = $row['skin_type_name'];
}

$stmt->close();

/* ================================
   SKIN CONCERNS
================================ */

$skinConcerns = [];

$stmt = $conn->prepare("
    SELECT sc.concern_name
    FROM product_concerns pc
    INNER JOIN skin_concerns sc
        ON sc.id = pc.concern_id
    WHERE pc.product_id = ?
    ORDER BY sc.concern_name
");

$stmt->bind_param('i', $productId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $skinConcerns[] = $row['concern_name'];
}

$stmt->close();

/* ================================
   INGREDIENTS
================================ */

$ingredients = [];

$stmt = $conn->prepare("
    SELECT i.ingredient_name
    FROM product_ingredients pi
    INNER JOIN ingredients i
        ON i.id = pi.ingredient_id
    WHERE pi.product_id = ?
    ORDER BY i.ingredient_name
");

$stmt->bind_param('i', $productId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $ingredients[] = $row['ingredient_name'];
}

$stmt->close();

/* ================================
   HOW TO USE
================================ */

$usageInstructions = trim(
    (string) ($product['usage_instructions'] ?? '')
);

$usageSteps = preg_split(
    '/\r\n|\r|\n/',
    $usageInstructions,
    -1,
    PREG_SPLIT_NO_EMPTY
) ?: [];

$usageSteps = array_values(array_filter(array_map(
    'trim',
    $usageSteps
)));

/* ================================
   CSRF TOKEN
================================ */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= productEscape($pageTitle) ?></title>

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/footer.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/product_details.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="pd-page">

    <div class="pd-container">

        <!-- BREADCRUMB -->

        <nav class="pd-breadcrumb">

            <a href="<?= BASE_URL ?>/index.php">Home</a>

            <span>/</span>

            <a href="<?= BASE_URL ?>/shop.php">Products</a>

            <span>/</span>

            <strong>
                <?= productEscape($product['product_name']) ?>
            </strong>

        </nav>

        <!-- PRODUCT OVERVIEW -->

        <section class="pd-overview">

            <!-- PRODUCT IMAGE -->

            <div class="pd-gallery">

                <div class="pd-main-image">

                    <img
                        id="pdMainImage"
                        src="<?= productEscape($images[0]) ?>"
                        alt="<?= productEscape($product['product_name']) ?>"
                        onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/products/default-product.jpg';"
                    >

                </div>

                <?php if (count($images) > 1): ?>

                    <div class="pd-thumbnails">

                        <?php foreach ($images as $index => $image): ?>

                            <button
                                type="button"
                                class="pd-thumbnail <?= $index === 0 ? 'active' : '' ?>"
                                data-image="<?= productEscape($image) ?>"
                                aria-label="View product image <?= $index + 1 ?>"
                            >

                                <img
                                    src="<?= productEscape($image) ?>"
                                    alt="Product image <?= $index + 1 ?>"
                                >

                            </button>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

            <!-- PRODUCT INFORMATION -->

            <div class="pd-info">

                <p class="pd-category">

                    <?= productEscape($product['category_name']) ?>

                    <?php if (!empty($product['product_code'])): ?>

                        <span>·</span>

                        <?= productEscape($product['product_code']) ?>

                    <?php endif; ?>

                </p>

                <h1 class="pd-title">
                    <?= productEscape($product['product_name']) ?>
                </h1>

                <div class="pd-meta">

                    <span
                        class="pd-stock <?= (int) $selectedVariant['stock_quantity'] <= 0 ? 'out' : '' ?>"
                        id="pdStock"
                    >
                        <?= (int) $selectedVariant['stock_quantity'] > 0
                            ? 'In Stock (' . (int) $selectedVariant['stock_quantity'] . ')'
                            : 'Out of Stock' ?>
                    </span>

                </div>

                <div class="pd-price" id="pdPrice">

                    ₱<?= number_format(
                        (float) $selectedVariant['price'],
                        2
                    ) ?>

                </div>

                <p class="pd-description">
                    <?= nl2br(productEscape($product['description'])) ?>
                </p>

                <!-- SIZE / OPTION -->

                <div class="pd-field">

                    <p class="pd-field-label">SIZE / OPTION</p>

                    <div class="pd-variants" id="pdVariants">

                        <?php foreach ($variants as $variant): ?>

                            <?php
                                $isSelected =
                                    (int) $selectedVariant['id'] ===
                                    (int) $variant['id'];

                                $variantStock =
                                    (int) $variant['stock_quantity'];
                            ?>

                            <button
                                type="button"
                                class="pd-variant <?= $isSelected ? 'active' : '' ?>"
                                data-id="<?= (int) $variant['id'] ?>"
                                data-price="<?= (float) $variant['price'] ?>"
                                data-stock="<?= $variantStock ?>"
                                aria-pressed="<?= $isSelected ? 'true' : 'false' ?>"
                                <?= $variantStock <= 0 ? 'disabled' : '' ?>
                            >

                                <span class="pd-variant-text">

                                    <strong>
                                        <?= productEscape($variant['size_capacity']) ?>
                                    </strong>

                                    <small>
                                        <?= productEscape($variant['variant_label']) ?>
                                    </small>

                                </span>

                                <span class="pd-variant-price">

                                    ₱<?= number_format(
                                        (float) $variant['price'],
                                        2
                                    ) ?>

                                </span>

                            </button>

                        <?php endforeach; ?>

                    </div>

                </div>

                <!-- SUITABLE FOR -->

                <div class="pd-field">

                    <p class="pd-field-label">SUITABLE FOR</p>

                    <div class="pd-tags">

                        <?php if ($skinTypes): ?>

                            <?php foreach ($skinTypes as $type): ?>

                                <span class="pd-tag pd-tag-blue">
                                    <?= productEscape($type) ?>
                                </span>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <span class="pd-muted">
                                No skin types specified.
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- TARGETS -->

                <div class="pd-field">

                    <p class="pd-field-label">TARGETS</p>

                    <div class="pd-tags">

                        <?php if ($skinConcerns): ?>

                            <?php foreach ($skinConcerns as $concern): ?>

                                <span class="pd-tag pd-tag-peach">
                                    <?= productEscape($concern) ?>
                                </span>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <span class="pd-muted">
                                No targeted concerns specified.
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- ADD TO BAG -->

                <form
                    class="pd-purchase"
                    id="pdCartForm"
                    action="<?= BASE_URL ?>/actions/cart/add.php"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= productEscape($_SESSION['csrf_token']) ?>"
                    >

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int) $productId ?>"
                    >

                    <input
                        type="hidden"
                        name="product_variant_id"
                        id="pdVariantInput"
                        value="<?= (int) $selectedVariant['id'] ?>"
                    >

                    <div class="pd-quantity">

                        <button
                            type="button"
                            id="pdDecrease"
                            aria-label="Decrease quantity"
                        >−</button>

                        <input
                            type="number"
                            name="quantity"
                            id="pdQuantity"
                            value="1"
                            min="1"
                            max="<?= max(1, (int) $selectedVariant['stock_quantity']) ?>"
                            readonly
                        >

                        <button
                            type="button"
                            id="pdIncrease"
                            aria-label="Increase quantity"
                        >+</button>

                    </div>

                    <button
                        type="submit"
                        class="pd-add-button"
                        id="pdAddButton"
                        <?= $totalStock <= 0 ? 'disabled' : '' ?>
                    >

                        <?= $totalStock > 0
                            ? 'Add to Bag — ₱' . number_format(
                                (float) $selectedVariant['price'],
                                2
                            )
                            : 'Out of Stock' ?>

                    </button>

                </form>

                <div class="pd-shipping">

                    <i class="fa-solid fa-truck-fast"></i>

                    <span>
                        Shipping fees and delivery details are
                        calculated at checkout.
                    </span>

                </div>

            </div>

        </section>

        <!-- PRODUCT INFORMATION TABS -->

        <section class="pd-details">

            <div
                class="pd-tab-navigation"
                role="tablist"
                aria-label="Product information"
            >

                <button
                    type="button"
                    class="pd-tab active"
                    data-tab="ingredients"
                    role="tab"
                    aria-selected="true"
                    aria-controls="pd-panel-ingredients"
                >
                    Key Ingredients
                </button>

                <button
                    type="button"
                    class="pd-tab"
                    data-tab="usage"
                    role="tab"
                    aria-selected="false"
                    aria-controls="pd-panel-usage"
                >
                    How to Use
                </button>

                <button
                    type="button"
                    class="pd-tab"
                    data-tab="concerns"
                    role="tab"
                    aria-selected="false"
                    aria-controls="pd-panel-concerns"
                >
                    Skin Concerns
                </button>

            </div>

            <!-- KEY INGREDIENTS -->

            <div
                class="pd-tab-panel active"
                id="pd-panel-ingredients"
                role="tabpanel"
            >

                <?php if ($ingredients): ?>

                    <div class="pd-ingredient-grid">

                        <?php foreach ($ingredients as $ingredient): ?>

                            <div class="pd-ingredient-card">
                                <?= productEscape($ingredient) ?>
                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p class="pd-empty">
                        Ingredient information is not yet available.
                    </p>

                <?php endif; ?>

            </div>

            <!-- HOW TO USE -->

            <div
                class="pd-tab-panel"
                id="pd-panel-usage"
                role="tabpanel"
                hidden
            >

                <?php if ($usageSteps): ?>

                    <div class="pd-usage-list">

                        <?php foreach ($usageSteps as $index => $step): ?>

                            <div class="pd-usage-step">

                                <span class="pd-step-number">
                                    <?= str_pad(
                                        (string) ($index + 1),
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    ) ?>
                                </span>

                                <p>
                                    <?= productEscape($step) ?>
                                </p>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p class="pd-empty">
                        Usage instructions have not been provided.
                    </p>

                <?php endif; ?>

            </div>

            <!-- SKIN CONCERNS -->

            <div
                class="pd-tab-panel"
                id="pd-panel-concerns"
                role="tabpanel"
                hidden
            >

                <?php if ($skinConcerns): ?>

                    <div class="pd-concern-grid">

                        <?php foreach ($skinConcerns as $concern): ?>

                            <div class="pd-concern-card">

                                <h3>
                                    <?= productEscape($concern) ?>
                                </h3>

                                <span>TARGETED</span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p class="pd-empty">
                        No targeted skin concerns have been assigned.
                    </p>

                <?php endif; ?>

            </div>

        </section>

    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<script src="<?= BASE_URL ?>/assets/js/product_details.js"></script>

</body>
</html>
