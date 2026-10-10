
<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Shop All Products | PureVia';

function shopEscape(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

function shopImageUrl(?string $path): string
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

    $file = __DIR__ . '/uploads/products/' . $filename;

    return is_file($file)
        ? BASE_URL . '/uploads/products/' . rawurlencode($filename)
        : $fallback;
}

/* CATEGORIES */
$categories = [];

$categoryResult = $conn->query("
    SELECT id, category_name
    FROM categories
    WHERE status = 'active'
    ORDER BY category_name
");

if ($categoryResult) {
    $categories = $categoryResult->fetch_all(MYSQLI_ASSOC);
}

/* SKIN TYPES */
$skinTypes = [];

$typeResult = $conn->query("
    SELECT id, skin_type_name
    FROM skin_types
    WHERE status = 'active'
    ORDER BY skin_type_name
");

if ($typeResult) {
    $skinTypes = $typeResult->fetch_all(MYSQLI_ASSOC);
}

/* SKIN CONCERNS */
$skinConcerns = [];

$concernResult = $conn->query("
    SELECT id, concern_name
    FROM skin_concerns
    WHERE status = 'active'
    ORDER BY concern_name
");

if ($concernResult) {
    $skinConcerns = $concernResult->fetch_all(MYSQLI_ASSOC);
}

/* CUSTOMER SKIN PROFILE */
$userId = (int) ($_SESSION['user_id'] ?? 0);
$customerSkinTypeId = 0;
$customerConcernIds = [];

if ($userId > 0) {
    $stmt = $conn->prepare("
        SELECT skin_type_id
        FROM skin_profiles
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($profile) {
        $customerSkinTypeId = (int) $profile['skin_type_id'];
    }

    $stmt = $conn->prepare("
        SELECT spc.concern_id
        FROM skin_profile_concerns spc
        INNER JOIN skin_profiles sp
            ON sp.id = spc.skin_profile_id
        WHERE sp.user_id = ?
    ");

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $customerConcernIds[] = (int) $row['concern_id'];
    }

    $stmt->close();
}

/* PRODUCTS */
$products = [];

$productResult = $conn->query("
    SELECT
        p.id,
        p.product_name,
        p.product_code,
        p.description,
        p.price,
        p.image,
        p.stock_quantity,
        p.category_id,
        p.created_at,
        c.category_name,

        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.sort_order, pi.id
            LIMIT 1
        ) AS primary_image

    FROM products p
    INNER JOIN categories c
        ON c.id = p.category_id

    WHERE p.status = 'active'
      AND c.status = 'active'

    ORDER BY p.created_at DESC, p.id DESC
");

if ($productResult) {
    $products = $productResult->fetch_all(MYSQLI_ASSOC);
}

/* FETCH PRODUCT VARIANTS */
$variantsByProduct = [];

$variantResult = $conn->query("
    SELECT
        product_id,
        id,
        size_capacity,
        variant_label,
        price,
        stock_quantity
    FROM product_variants
    ORDER BY id ASC
");

if ($variantResult) {
    while ($variant = $variantResult->fetch_assoc()) {
        $variantsByProduct[(int) $variant['product_id']][] = $variant;
    }
}

/* PRODUCT SUITABILITY */
$productSkinTypes = [];
$productConcerns = [];

$result = $conn->query("
    SELECT product_id, skin_type_id
    FROM product_skin_types
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productSkinTypes[(int) $row['product_id']][] =
            (int) $row['skin_type_id'];
    }
}

$result = $conn->query("
    SELECT product_id, concern_id
    FROM product_concerns
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productConcerns[(int) $row['product_id']][] =
            (int) $row['concern_id'];
    }
}

/* PREPARE DISPLAY DATA */
$displayProducts = [];
$maxPrice = 100;

foreach ($products as $product) {
    $id = (int) $product['id'];

    $variants = $variantsByProduct[$id] ?? [];

    $price = count($variants) > 0
        ? (float) $variants[0]['price']
        : (float) $product['price'];

    $stock = count($variants) > 0
        ? array_sum(array_map(
            fn($v) => (int) $v['stock_quantity'],
            $variants
        ))
        : (int) $product['stock_quantity'];

    $maxPrice = max($maxPrice, $price);

    $suitableTypes = $productSkinTypes[$id] ?? [];
    $suitableConcerns = $productConcerns[$id] ?? [];

    $skinMatch = $customerSkinTypeId > 0
        && in_array($customerSkinTypeId, $suitableTypes, true);

    $concernMatch = count(array_intersect(
        $customerConcernIds,
        $suitableConcerns
    )) > 0;

    $product['display_price'] = $price;
    $product['display_stock'] = $stock;
    $product['variants'] = $variants;
    $product['skin_types'] = $suitableTypes;
    $product['skin_concerns'] = $suitableConcerns;
    $product['skin_match'] = $skinMatch;
    $product['concern_match'] = $concernMatch;
    $product['image_url'] = shopImageUrl(
        $product['primary_image'] ?: $product['image']
    );

    $displayProducts[] = $product;
}

$maxPrice = (int) (ceil($maxPrice / 100) * 100);
$totalProducts = count($displayProducts);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= shopEscape($pageTitle) ?></title>

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/footer.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/shop.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="shop-page">

    <div class="shop-container">

        <!-- PAGE HEADER -->
        <div class="shop-heading">
            <h1>All Products</h1>

            <p>
                Discover skincare made for your skin.
                Explore our complete collection.
            </p>
        </div>

        <!-- SEARCH AND SORT -->
        <div class="shop-toolbar">

            <div class="shop-search">
                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="search"
                    id="shopSearch"
                    placeholder="Search products, ingredients..."
                    aria-label="Search products"
                >
            </div>

            <select id="shopSort" aria-label="Sort products">
                <option value="popular">Most Popular</option>
                <option value="newest">Newest</option>
                <option value="price-low">Price: Low to High</option>
                <option value="price-high">Price: High to Low</option>
                <option value="name">Name: A to Z</option>
            </select>

        </div>

        <div class="shop-layout">

            <!-- FILTER SIDEBAR -->
            <aside class="shop-sidebar" id="shopSidebar">

                <div class="shop-sidebar-heading">
                    <h2>Filters</h2>

                    <button type="button" id="clearFilters">
                        Clear all
                    </button>
                </div>

                <label class="shop-match-filter">
                    <input
                        type="checkbox"
                        id="shopMyMatches"
                        <?= $customerSkinTypeId === 0 ? 'disabled' : '' ?>
                    >

                    <span>
                        <strong>Show my matches</strong>
                        <small>
                            <?= $customerSkinTypeId > 0
                                ? 'Matches your skin profile'
                                : 'Set up your skin profile first' ?>
                        </small>
                    </span>
                </label>

                <!-- CATEGORY -->
                <div class="shop-filter-group">

                    <h3>Category</h3>

                    <label class="shop-checkbox">
                        <input
                            type="checkbox"
                            name="category"
                            value="all"
                            checked
                        >
                        All
                    </label>

                    <?php foreach ($categories as $category): ?>
                        <label class="shop-checkbox">
                            <input
                                type="checkbox"
                                name="category"
                                value="<?= (int) $category['id'] ?>"
                            >
                            <?= shopEscape($category['category_name']) ?>
                        </label>
                    <?php endforeach; ?>

                </div>

                <!-- SKIN TYPE -->
                <div class="shop-filter-group">

                    <h3>Skin Type</h3>

                    <?php foreach ($skinTypes as $type): ?>
                        <label class="shop-checkbox">
                            <input
                                type="checkbox"
                                name="skin_type"
                                value="<?= (int) $type['id'] ?>"
                            >
                            <?= shopEscape($type['skin_type_name']) ?>
                        </label>
                    <?php endforeach; ?>

                </div>

                <!-- CONCERNS -->
                <div class="shop-filter-group">

                    <h3>Concern</h3>

                    <?php foreach ($skinConcerns as $concern): ?>
                        <label class="shop-checkbox">
                            <input
                                type="checkbox"
                                name="concern"
                                value="<?= (int) $concern['id'] ?>"
                            >
                            <?= shopEscape($concern['concern_name']) ?>
                        </label>
                    <?php endforeach; ?>

                </div>

                <!-- PRICE -->
                <div class="shop-filter-group shop-price-filter">

                    <h3>Pricing</h3>

                    <input
                        type="range"
                        id="shopPrice"
                        min="0"
                        max="<?= $maxPrice ?>"
                        value="<?= $maxPrice ?>"
                        step="10"
                    >

                    <div class="shop-price-labels">
                        <span>
                            Min<br>
                            ₱0
                        </span>

                        <span>
                            Max<br>
                            <strong id="shopPriceValue">
                                ₱<?= number_format($maxPrice) ?>
                            </strong>
                        </span>
                    </div>

                </div>

            </aside>

            <!-- MAIN PRODUCTS AREA -->
            <section class="shop-products">

                <!-- CATEGORY TABS -->
                <div class="shop-category-tabs" id="shopCategoryTabs">

                    <button
                        type="button"
                        class="shop-tab active"
                        data-category="all"
                    >
                        All
                    </button>

                    <?php foreach ($categories as $category): ?>
                        <button
                            type="button"
                            class="shop-tab"
                            data-category="<?= (int) $category['id'] ?>"
                        >
                            <?= shopEscape($category['category_name']) ?>
                        </button>
                    <?php endforeach; ?>

                </div>

                <div class="shop-results-heading">
                    <span id="shopResultCount">
                        <?= $totalProducts ?> products
                    </span>
                </div>

                <!-- PRODUCT GRID -->
                <div class="shop-product-grid" id="shopProductGrid">

                    <?php foreach ($displayProducts as $product): ?>

                        <?php
                        $productId = (int) $product['id'];

                        $typeValues = implode(',', $product['skin_types']);
                        $concernValues = implode(',', $product['skin_concerns']);

                        $matched = $product['skin_match'];
                        $outOfStock = $product['display_stock'] <= 0;
                        ?>

                        <article
                            class="shop-product-card"
                            data-id="<?= $productId ?>"
                            data-name="<?= shopEscape(strtolower($product['product_name'])) ?>"
                            data-description="<?= shopEscape(strtolower($product['description'] ?? '')) ?>"
                            data-category="<?= (int) $product['category_id'] ?>"
                            data-price="<?= $product['display_price'] ?>"
                            data-types="<?= shopEscape($typeValues) ?>"
                            data-concerns="<?= shopEscape($concernValues) ?>"
                            data-match="<?= $matched ? '1' : '0' ?>"
                            data-date="<?= shopEscape($product['created_at']) ?>"
                        >

                            <a
                                class="shop-product-image"
                                href="<?= BASE_URL ?>/product.php?id=<?= $productId ?>"
                            >

                                <img
                                    src="<?= shopEscape($product['image_url']) ?>"
                                    alt="<?= shopEscape($product['product_name']) ?>"
                                    loading="lazy"
                                    onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/products/default-product.jpg';"
                                >

                                <?php if ($matched): ?>
                                    <span class="shop-match-badge">
                                        <i class="fa-solid fa-sparkles"></i>
                                        YOUR MATCH
                                    </span>
                                <?php elseif ($product['concern_match']): ?>
                                    <span class="shop-match-badge">
                                        <i class="fa-solid fa-sparkles"></i>
                                        CONCERN MATCH
                                    </span>
                                <?php endif; ?>

                                <?php if ($outOfStock): ?>
                                    <span class="shop-stock-badge">
                                        OUT OF STOCK
                                    </span>
                                <?php endif; ?>

                            </a>

                            <div class="shop-product-info">

                                <span class="shop-product-category">
                                    <?= shopEscape($product['category_name']) ?>
                                </span>

                                <h3 class="shop-product-name">
                                    <a
                                        href="<?= BASE_URL ?>/product.php?id=<?= $productId ?>"
                                        title="<?= shopEscape($product['product_name']) ?>"
                                    >
                                        <?= shopEscape($product['product_name']) ?>
                                    </a>
                                </h3>

                                <div class="shop-product-sizes">

                                    <?php if (count($product['variants']) > 0): ?>

                                        <?php foreach (array_slice($product['variants'], 0, 2) as $variant): ?>

                                            <span class="shop-size-tag">
                                                <?= shopEscape($variant['size_capacity']) ?>
                                            </span>

                                            <?php if (!empty($variant['variant_label'])): ?>
                                                <span class="shop-label-tag">
                                                    <?= shopEscape($variant['variant_label']) ?>
                                                </span>
                                            <?php endif; ?>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <span class="shop-size-tag">
                                            Standard
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div class="shop-product-bottom">

                                    <span class="shop-product-price">
                                        ₱<?= number_format(
                                            $product['display_price'],
                                            2
                                        ) ?>
                                    </span>

                                    <a
                                        href="<?= BASE_URL ?>/product.php?id=<?= $productId ?>"
                                        class="shop-add-button"
                                    >
                                        <?= $outOfStock
                                            ? 'View Product'
                                            : 'View Product' ?>
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <!-- EMPTY STATE -->
                <div
                    class="shop-empty"
                    id="shopEmpty"
                    <?= $totalProducts > 0 ? 'hidden' : '' ?>
                >
                    <i class="fa-solid fa-magnifying-glass"></i>

                    <h3>No products found</h3>

                    <p>
                        Try changing your search or filters.
                    </p>

                    <button type="button" id="shopResetEmpty">
                        Clear Filters
                    </button>
                </div>

            </section>

        </div>

    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/shop.js"></script>

</body>
</html>
