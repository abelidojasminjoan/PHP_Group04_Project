<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function failProduct(string $message): never
{
    $_SESSION['product_error'] = $message;

    header('Location: ./products.php');
    exit;
}

/* POST ONLY */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./products.php');
    exit;
}

/* ADMIN ONLY */

if (
    empty($_SESSION['user_id']) ||
    (int) ($_SESSION['role_id'] ?? 0) !== 1
) {
    http_response_code(403);
    exit('Access denied.');
}

/* CSRF */

$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_product']) ||
    !hash_equals($_SESSION['csrf_product'], $token)
) {
    failProduct('Invalid security token. Refresh the page.');
}

/* VALIDATE SELECTED DATABASE IDS */

function validProductIds(
    mysqli $conn,
    string $table,
    array $ids
): array {

    $allowedTables = [
        'skin_types',
        'skin_concerns',
        'ingredients'
    ];

    if (!in_array($table, $allowedTables, true)) {
        throw new RuntimeException('Invalid selection table.');
    }

    if (count($ids) > 200) {
        throw new RuntimeException('Too many selections.');
    }

    foreach ($ids as $value) {
        if (
            !is_scalar($value) ||
            filter_var(
                $value,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            ) === false
        ) {
            throw new RuntimeException('Invalid selected item.');
        }
    }

    $ids = array_values(array_unique(array_map('intval', $ids)));

    if (!$ids) {
        return [];
    }

    $placeholders = implode(
        ',',
        array_fill(0, count($ids), '?')
    );

    $stmt = $conn->prepare("
        SELECT id
        FROM {$table}
        WHERE status = 'active'
        AND id IN ({$placeholders})
    ");

    $types = str_repeat('i', count($ids));

    $stmt->bind_param($types, ...$ids);
    $stmt->execute();

    $found = array_map(
        'intval',
        array_column(
            $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
            'id'
        )
    );

    $stmt->close();

    if (count($found) !== count($ids)) {
        throw new RuntimeException(
            'Some selected options are invalid.'
        );
    }

    return $found;
}

/* MAIN PROCESS */

$savedFiles = [];
$transactionStarted = false;

try {

    /* PRODUCT INFORMATION */

    $productName = trim(
        (string) ($_POST['product_name'] ?? '')
    );

    $productCode = strtoupper(trim(
        (string) ($_POST['product_code'] ?? '')
    ));

    $categoryId = filter_var(
        $_POST['category_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $status = (string) ($_POST['status'] ?? 'active');

    $description = trim(
        (string) ($_POST['description'] ?? '')
    );

    $benefitsText = trim(
        (string) ($_POST['key_benefits'] ?? '')
    );

    if (
        $productName === '' ||
        mb_strlen($productName) > 200 ||
        $productCode === '' ||
        strlen($productCode) > 100 ||
        !$categoryId ||
        !in_array($status, ['active', 'inactive'], true) ||
        mb_strlen($description) > 10000 ||
        mb_strlen($benefitsText) > 5000
    ) {
        throw new RuntimeException(
            'Please check the product details.'
        );
    }

    /* VALIDATE CATEGORY */

    $stmt = $conn->prepare("
        SELECT id
        FROM categories
        WHERE id = ?
        AND status = 'active'
        LIMIT 1
    ");

    $stmt->bind_param('i', $categoryId);
    $stmt->execute();

    $categoryExists = $stmt->get_result()->num_rows > 0;

    $stmt->close();

    if (!$categoryExists) {
        throw new RuntimeException('Invalid category.');
    }

    /* CHECK DUPLICATE SKU */

    $stmt = $conn->prepare("
        SELECT id
        FROM products
        WHERE product_code = ?
        LIMIT 1
    ");

    $stmt->bind_param('s', $productCode);
    $stmt->execute();

    $skuExists = $stmt->get_result()->num_rows > 0;

    $stmt->close();

    if ($skuExists) {
        throw new RuntimeException('Product SKU already exists.');
    }

    /* VARIANTS */

    $sizes = $_POST['size_capacity'] ?? [];
    $labels = $_POST['variant_label'] ?? [];
    $prices = $_POST['variant_price'] ?? [];
    $stocks = $_POST['variant_stock'] ?? [];
    $thresholds = $_POST['variant_threshold'] ?? [];

    if (
        !is_array($sizes) ||
        count($sizes) < 1 ||
        count($sizes) > 50 ||
        !is_array($labels) ||
        !is_array($prices) ||
        !is_array($stocks) ||
        !is_array($thresholds)
    ) {
        throw new RuntimeException(
            'At least one valid product size is required.'
        );
    }

    $variants = [];
    $variantKeys = [];
    $totalStock = 0;

    foreach ($sizes as $index => $rawSize) {

        if (!is_string($rawSize)) {
            throw new RuntimeException('Invalid size.');
        }

        $size = trim($rawSize);

        $rawLabel = $labels[$index] ?? '';

        if (!is_string($rawLabel)) {
            throw new RuntimeException('Invalid variant label.');
        }

        $label = trim($rawLabel);

        $price = $prices[$index] ?? null;
        $stock = $stocks[$index] ?? null;
        $threshold = $thresholds[$index] ?? null;

        if (
            $size === '' ||
            mb_strlen($size) > 100 ||
            mb_strlen($label) > 100 ||
            !is_scalar($price) ||
            !preg_match(
                '/^\d{1,8}(\.\d{1,2})?$/',
                (string) $price
            ) ||
            !is_scalar($stock) ||
            !ctype_digit((string) $stock) ||
            !is_scalar($threshold) ||
            !ctype_digit((string) $threshold) ||
            (float) $price > 99999999.99 ||
            (float) $price < 0 ||
            (int) $stock > 100000000 ||
            (int) $threshold > 100000000
        ) {
            throw new RuntimeException(
                'Invalid size, price, stock, or threshold.'
            );
        }

        $key = mb_strtolower($size . '|' . $label);

        if (isset($variantKeys[$key])) {
            throw new RuntimeException(
                'Duplicate size and label combination.'
            );
        }

        $variantKeys[$key] = true;

        $variants[] = [
            'size' => $size,
            'label' => $label === '' ? null : $label,
            'price' => (float) $price,
            'stock' => (int) $stock,
            'threshold' => (int) $threshold
        ];

        $totalStock += (int) $stock;
    }

    if ($totalStock > 4294967295) {
        throw new RuntimeException('Total stock exceeds the allowed limit.');
    }

    $basePrice = $variants[0]['price'];

    $finalStatus = $totalStock === 0
        ? 'out_of_stock'
        : $status;

    /* SKIN MATCHING */

    $skinTypeIds = validProductIds(
        $conn,
        'skin_types',
        is_array($_POST['skin_type_ids'] ?? null)
            ? $_POST['skin_type_ids']
            : []
    );

    $concernIds = validProductIds(
        $conn,
        'skin_concerns',
        is_array($_POST['concern_ids'] ?? null)
            ? $_POST['concern_ids']
            : []
    );

    $ingredientIds = validProductIds(
        $conn,
        'ingredients',
        is_array($_POST['ingredient_ids'] ?? null)
            ? $_POST['ingredient_ids']
            : []
    );

    /* IMAGE URLS */

    $imageUrls = $_POST['image_urls'] ?? [];

    if (!is_array($imageUrls) || count($imageUrls) > 10) {
        throw new RuntimeException('Invalid image list.');
    }

    $images = [];

    foreach ($imageUrls as $url) {

        if (!is_string($url)) {
            throw new RuntimeException('Invalid image URL.');
        }

        $url = trim($url);

        if (
            strlen($url) > 2048 ||
            !filter_var($url, FILTER_VALIDATE_URL) ||
            !in_array(
                strtolower((string) parse_url($url, PHP_URL_SCHEME)),
                ['http', 'https'],
                true
            )
        ) {
            throw new RuntimeException('Invalid image URL.');
        }

        $images[] = [
            'type' => 'url',
            'value' => $url
        ];
    }

    /* UPLOADED IMAGES */

    $uploads = $_FILES['product_images'] ?? null;

    if (
        $uploads &&
        isset($uploads['error']) &&
        is_array($uploads['error'])
    ) {

        foreach ($uploads['error'] as $index => $uploadError) {

            if ($uploadError === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($uploadError !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Image upload failed.');
            }

            $images[] = [
                'type' => 'file',
                'value' => $index
            ];
        }
    }

    if (count($images) > 10) {
        throw new RuntimeException(
            'A maximum of 10 product images is allowed.'
        );
    }

    $uploadFolder = __DIR__ . '/../../uploads/products';

    if (
        $uploads &&
        !is_dir($uploadFolder) &&
        !mkdir($uploadFolder, 0755, true) &&
        !is_dir($uploadFolder)
    ) {
        throw new RuntimeException(
            'Unable to create product image folder.'
        );
    }

    $resolvedImages = [];
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($images as $image) {

        if ($image['type'] === 'url') {
            $resolvedImages[] = $image['value'];
            continue;
        }

        $index = $image['value'];
        $temporaryFile = $uploads['tmp_name'][$index];
        $fileSize = (int) $uploads['size'][$index];

        if (
            !is_uploaded_file($temporaryFile) ||
            $fileSize <= 0 ||
            $fileSize > 5 * 1024 * 1024
        ) {
            throw new RuntimeException(
                'Each uploaded image must be under 5 MB.'
            );
        }

        $mime = $fileInfo->file($temporaryFile);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        $extension = $extensions[$mime] ?? null;

        if (!$extension) {
            throw new RuntimeException(
                'Only JPG, PNG, and WebP images are allowed.'
            );
        }

        $filename = bin2hex(random_bytes(16))
            . '.'
            . $extension;

        $destination = $uploadFolder . '/' . $filename;

        if (!move_uploaded_file($temporaryFile, $destination)) {
            throw new RuntimeException(
                'Unable to save uploaded image.'
            );
        }

        $savedFiles[] = $destination;

        $resolvedImages[] = 'uploads/products/' . $filename;
    }

    /* DATABASE TRANSACTION */

    $conn->begin_transaction();
    $transactionStarted = true;

    /* INSERT PRODUCT */

    $mainImage = $resolvedImages[0] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO products (
            category_id,
            product_name,
            product_code,
            description,
            price,
            stock_quantity,
            image,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        'isssdiss',
        $categoryId,
        $productName,
        $productCode,
        $description,
        $basePrice,
        $totalStock,
        $mainImage,
        $finalStatus
    );

    $stmt->execute();

    $productId = (int) $conn->insert_id;

    $stmt->close();

    /* INSERT VARIANTS */

    $stmt = $conn->prepare("
        INSERT INTO product_variants (
            product_id,
            size_capacity,
            variant_label,
            price,
            stock_quantity,
            low_stock_threshold
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($variants as $variant) {

        $size = $variant['size'];
        $label = $variant['label'];
        $price = $variant['price'];
        $stock = $variant['stock'];
        $threshold = $variant['threshold'];

        $stmt->bind_param(
            'issdii',
            $productId,
            $size,
            $label,
            $price,
            $stock,
            $threshold
        );

        $stmt->execute();
    }

    $stmt->close();

    /* SAVE SKIN TYPES */

    $stmt = $conn->prepare("
        INSERT INTO product_skin_types (
            product_id,
            skin_type_id
        )
        VALUES (?, ?)
    ");

    foreach ($skinTypeIds as $skinTypeId) {
        $stmt->bind_param(
            'ii',
            $productId,
            $skinTypeId
        );

        $stmt->execute();
    }

    $stmt->close();

    /* SAVE SKIN CONCERNS */

    $stmt = $conn->prepare("
        INSERT INTO product_concerns (
            product_id,
            concern_id
        )
        VALUES (?, ?)
    ");

    foreach ($concernIds as $concernId) {
        $stmt->bind_param(
            'ii',
            $productId,
            $concernId
        );

        $stmt->execute();
    }

    $stmt->close();

    /* SAVE INGREDIENTS */

    $stmt = $conn->prepare("
        INSERT INTO product_ingredients (
            product_id,
            ingredient_id
        )
        VALUES (?, ?)
    ");

    foreach ($ingredientIds as $ingredientId) {
        $stmt->bind_param(
            'ii',
            $productId,
            $ingredientId
        );

        $stmt->execute();
    }

    $stmt->close();

    /* SAVE IMAGES */

    $stmt = $conn->prepare("
        INSERT INTO product_images (
            product_id,
            image_path,
            sort_order
        )
        VALUES (?, ?, ?)
    ");

    foreach ($resolvedImages as $index => $imagePath) {

        $sortOrder = (int) $index;

        $stmt->bind_param(
            'isi',
            $productId,
            $imagePath,
            $sortOrder
        );

        $stmt->execute();
    }

    $stmt->close();

    /* SAVE BENEFITS */

    if ($benefitsText !== '') {

        $benefitLines = preg_split('/\R/u', $benefitsText);

        $stmt = $conn->prepare("
            INSERT INTO product_benefits (
                product_id,
                benefit_text,
                sort_order
            )
            VALUES (?, ?, ?)
        ");

        $sortOrder = 0;

        foreach ($benefitLines as $benefitLine) {

            $benefit = trim($benefitLine);

            if ($benefit === '') {
                continue;
            }

            if (mb_strlen($benefit) > 500) {
                throw new RuntimeException(
                    'Each benefit must be 500 characters or less.'
                );
            }

            $stmt->bind_param(
                'isi',
                $productId,
                $benefit,
                $sortOrder
            );

            $stmt->execute();

            $sortOrder++;
        }

        $stmt->close();
    }

    /* AUDIT LOG */

    $adminId = (int) $_SESSION['user_id'];

    $auditAction = 'CREATE';
    $auditModule = 'Products';
    $recordId = (string) $productId;

    $auditDescription = sprintf(
        'Created product "%s" (%s).',
        $productName,
        $productCode
    );

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO audit_logs (
            user_id,
            action,
            module,
            record_id,
            description,
            ip_address
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        'isssss',
        $adminId,
        $auditAction,
        $auditModule,
        $recordId,
        $auditDescription,
        $ipAddress
    );

    $stmt->execute();
    $stmt->close();

    /* COMMIT */

    $conn->commit();
    $transactionStarted = false;

    $_SESSION['product_success'] =
        'Product added successfully.';

    unset($_SESSION['csrf_product']);

} catch (Throwable $exception) {

    if ($transactionStarted) {
        $conn->rollback();
    }

    foreach ($savedFiles as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }

    error_log(
        'Add Product Error: ' . $exception->getMessage()
    );

    $_SESSION['product_error'] =
        $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Unable to add the product. Please try again.';
}

header('Location: ./products.php');
exit;