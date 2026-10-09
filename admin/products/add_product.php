<?php

session_start();

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';


/* =========================================================
   ADMIN ONLY
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
   POST ONLY
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: ./products.php");
    exit;
}


/* =========================================================
   GET FORM DATA
========================================================= */

$productName = trim(
    $_POST['product_name'] ?? ''
);

$productCode = strtoupper(
    trim(
        $_POST['product_code'] ?? ''
    )
);

$categoryId = filter_input(
    INPUT_POST,
    'category_id',
    FILTER_VALIDATE_INT
);

$status = strtolower(
    trim(
        $_POST['status'] ?? 'active'
    )
);

$description = trim(
    $_POST['description'] ?? ''
);


$sizeCapacities =
    $_POST['size_capacity'] ?? [];

$variantLabels =
    $_POST['variant_label'] ?? [];

$variantPrices =
    $_POST['variant_price'] ?? [];

$variantStocks =
    $_POST['variant_stock'] ?? [];


/* =========================================================
   BASIC VALIDATION
========================================================= */

$errors = [];


if ($productName === '') {

    $errors[] =
        'Product name is required.';
}


if ($productCode === '') {

    $errors[] =
        'SKU is required.';
}


if (!$categoryId) {

    $errors[] =
        'Please select a category.';
}


$allowedStatuses = [
    'active',
    'inactive'
];


if (
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    $errors[] =
        'Invalid product status.';
}


/* =========================================================
   VALIDATE CATEGORY
========================================================= */

if ($categoryId) {

    $categoryStmt = $conn->prepare("
        SELECT id
        FROM categories
        WHERE
            id = ?
            AND status = 'active'
        LIMIT 1
    ");

    $categoryStmt->bind_param(
        "i",
        $categoryId
    );

    $categoryStmt->execute();

    $categoryResult =
        $categoryStmt->get_result();

    if (!$categoryResult->fetch_assoc()) {

        $errors[] =
            'The selected category is invalid.';
    }

    $categoryStmt->close();
}


/* =========================================================
   CHECK DUPLICATE SKU
========================================================= */

if ($productCode !== '') {

    $skuStmt = $conn->prepare("
        SELECT id
        FROM products
        WHERE product_code = ?
        LIMIT 1
    ");

    $skuStmt->bind_param(
        "s",
        $productCode
    );

    $skuStmt->execute();

    $skuResult =
        $skuStmt->get_result();

    if ($skuResult->fetch_assoc()) {

        $errors[] =
            'That SKU already exists.';
    }

    $skuStmt->close();
}


/* =========================================================
   VALIDATE VARIANTS
========================================================= */

$variants = [];


if (
    !is_array($sizeCapacities) ||
    count($sizeCapacities) === 0
) {

    $errors[] =
        'At least one product size is required.';

} else {


    foreach (
        $sizeCapacities as $index => $size
    ) {


        $size = trim(
            (string) $size
        );


        $label = trim(
            (string) (
                $variantLabels[$index]
                ?? ''
            )
        );


        $price =
            $variantPrices[$index]
            ?? null;


        $stock =
            $variantStocks[$index]
            ?? null;


        /* SIZE */

        if ($size === '') {

            $errors[] =
                'Every size must have a size or capacity.';

            continue;
        }


        /* PRICE */

        if (
            $price === null ||
            $price === '' ||
            !is_numeric($price) ||
            (float) $price < 0
        ) {

            $errors[] =
                'Every size must have a valid price.';

            continue;
        }


        /* STOCK */

        if (
            $stock === null ||
            $stock === '' ||
            filter_var(
                $stock,
                FILTER_VALIDATE_INT
            ) === false ||
            (int) $stock < 0
        ) {

            $errors[] =
                'Every size must have a valid stock quantity.';

            continue;
        }


        $variants[] = [

            'size' => $size,

            'label' =>
                $label !== ''
                    ? $label
                    : null,

            'price' =>
                round(
                    (float) $price,
                    2
                ),

            'stock' =>
                (int) $stock
        ];
    }
}


/* =========================================================
   STOP IF VALIDATION FAILED
========================================================= */

if (!empty($errors)) {

    $_SESSION['product_error'] =
        implode(' ', $errors);

    header("Location: ./products.php");
    exit;
}


/* =========================================================
   BASE PRICE

   First size determines catalog price.
========================================================= */

$basePrice =
    $variants[0]['price'];


/* =========================================================
   TOTAL STOCK

   Sum all size stocks.
========================================================= */

$totalStock = 0;


foreach ($variants as $variant) {

    $totalStock +=
        $variant['stock'];
}


/* =========================================================
   IF NO STOCK, PRODUCT SHOULD BE OUT OF STOCK
========================================================= */

if ($totalStock === 0) {

    $finalStatus = 'out_of_stock';

} else {

    $finalStatus = $status;
}


/* =========================================================
   DATABASE TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       INSERT PRODUCT
    ====================================================== */

    $productStmt = $conn->prepare("
        INSERT INTO products
        (
            category_id,
            product_name,
            product_code,
            description,
            price,
            stock_quantity,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");


    $productStmt->bind_param(
        "isssdis",
        $categoryId,
        $productName,
        $productCode,
        $description,
        $basePrice,
        $totalStock,
        $finalStatus
    );


    $productStmt->execute();


    $productId =
        $conn->insert_id;


    $productStmt->close();


    /* =====================================================
       INSERT PRODUCT VARIANTS
    ====================================================== */

    $variantStmt = $conn->prepare("
        INSERT INTO product_variants
        (
            product_id,
            size_capacity,
            variant_label,
            price,
            stock_quantity
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");


    foreach ($variants as $variant) {


        $size =
            $variant['size'];

        $label =
            $variant['label'];

        $price =
            $variant['price'];

        $stock =
            $variant['stock'];


        $variantStmt->bind_param(
            "issdi",
            $productId,
            $size,
            $label,
            $price,
            $stock
        );


        $variantStmt->execute();

    }


    $variantStmt->close();


    /* =====================================================
       AUDIT LOG
    ====================================================== */

    $adminId =
        (int) $_SESSION['user_id'];


    $action = 'CREATE';

    $module = 'Products';

    $recordId =
        (string) $productId;

    $descriptionLog =
        'Created product "' .
        $productName .
        '" (' .
        $productCode .
        ').';

    $ipAddress =
        $_SERVER['REMOTE_ADDR']
        ?? null;


    $auditStmt = $conn->prepare("
        INSERT INTO audit_logs
        (
            user_id,
            action,
            module,
            record_id,
            description,
            ip_address
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");


    $auditStmt->bind_param(
        "isssss",
        $adminId,
        $action,
        $module,
        $recordId,
        $descriptionLog,
        $ipAddress
    );


    $auditStmt->execute();

    $auditStmt->close();


    /* =====================================================
       COMMIT
    ====================================================== */

    $conn->commit();


    $_SESSION['product_success'] =
        'Product added successfully.';


} catch (Throwable $e) {


    /* =====================================================
       ROLLBACK EVERYTHING
    ====================================================== */

    $conn->rollback();


    $_SESSION['product_error'] =
        'Unable to add the product. Please try again.';


    /*
       DEVELOPMENT ONLY:

       If you need to see the actual database error,
       temporarily replace the message above with:

       $_SESSION['product_error'] = $e->getMessage();

       Do not keep database errors visible in production.
    */
}


/* =========================================================
   RETURN TO PRODUCTS
========================================================= */

header("Location: ./products.php");
exit;