<?php

/* =========================================================
   GET CATALOG PRODUCTS
========================================================= */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';

$sql = "
    SELECT
        p.id,
        p.product_name,
        p.product_code,
        p.image,
        p.price,
        p.stock_quantity,
        p.status,
        c.category_name

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    WHERE p.status IN ('active', 'out_of_stock')
      AND c.status = 'active'

    ORDER BY p.id DESC
";

$result = $conn->query($sql);

$products = $result->fetch_all(MYSQLI_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Shop | PureVia</title>

    <link rel="stylesheet"
        href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/css/header.css">

    <link rel="stylesheet"
        href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/css/footer.css">
</head>

<body>

    <?php include __DIR__ . '/includes/header.php'; ?>

    <main>

        <h1>Shop All Products</h1>

        <div class="product-grid">

            <?php foreach ($products as $product): ?>

                <a
                    href="<?= htmlspecialchars(
                                BASE_URL . '/productdetails.php?id=' .
                                    (int) $product['id'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                    class="product-card">

                    <div class="product-card-image">

                        <?php if (!empty($product['image'])): ?>

                            <img
                                src="<?= htmlspecialchars(
                                            BASE_URL . '/assets/images/' .
                                                rawurlencode(basename($product['image'])),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                alt="<?= htmlspecialchars(
                                            $product['product_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">

                        <?php endif; ?>

                    </div>

                    <div class="product-card-info">

                        <p>
                            <?= htmlspecialchars(
                                $product['category_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <h3>
                            <?= htmlspecialchars(
                                $product['product_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>

                        <p>
                            ₱<?= number_format(
                                    (float) $product['price'],
                                    2
                                ) ?>
                        </p>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

</body>

</html>