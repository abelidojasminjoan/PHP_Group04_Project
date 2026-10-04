<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'purevia_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO(
        $dsn,
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'message' => 'Database connection failed.',
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* =========================================================
   FETCH PRODUCTS
========================================================= */

function getProducts(PDO $pdo): array
{
    $sql = "
        SELECT
            p.id,
            p.product_name,
            p.product_code,
            p.stock_quantity,
            p.image,
            p.status,
            p.price,
            p.description,
            p.usage_instructions,
            p.category_id,
            c.category_name
        FROM products AS p
        INNER JOIN categories AS c
            ON c.id = p.category_id
        ORDER BY p.id DESC
    ";

    $statement = $pdo->query($sql);
    $rows = $statement->fetchAll();

    $products = [];

    foreach ($rows as $row) {
        $products[] = [
            // Names below match what products.php currently expects.
            'id'       => (int) $row['id'],
            'name'     => $row['product_name'],
            'sku'      => $row['product_code'],
            'category' => $row['category_name'],

            // The current database has no size field/table.
            'sizes'    => [],

            'stock'    => (int) $row['stock_quantity'],
            'status'   => $row['status'],
            'image'    => $row['image'] ?? '',

            // Extra existing database fields are also made available.
            'price'              => (float) $row['price'],
            'description'        => $row['description'],
            'usage_instructions' => $row['usage_instructions'],
            'category_id'        => (int) $row['category_id'],
        ];
    }

    return $products;
}


/* =========================================================
   FETCH CATEGORIES
========================================================= */

function getProductCategories(PDO $pdo): array
{
    $sql = "
        SELECT
            id,
            category_name,
            description,
            status
        FROM categories
        ORDER BY category_name ASC
    ";

    $statement = $pdo->query($sql);

    return $statement->fetchAll();
}


/* =========================================================
   BUILD BACKEND RESPONSE
========================================================= */

try {
    $products   = getProducts($pdo);
    $categories = getProductCategories($pdo);

    echo json_encode(
        [
            'success'       => true,
            'totalProducts' => count($products),
            'products'      => $products,
            'categories'    => $categories,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'message' => 'Unable to load products.',
        ],
        JSON_UNESCAPED_SLASHES
    );
}
