
<?php
/* =========================================================
   PUREVIA PRODUCT CATALOG
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';


/* =========================================================
   GET SEARCH QUERY
========================================================= */

$rawQuery = $_GET['q'] ?? '';

if (!is_string($rawQuery)) {
    http_response_code(400);
    exit('Invalid search query.');
}

$searchQuery = trim($rawQuery);

if (mb_strlen($searchQuery, 'UTF-8') > 100) {
    http_response_code(400);
    exit('Search must not exceed 100 characters.');
}


/* =========================================================
   GET PRODUCTS
========================================================= */

$sql = "
    SELECT
        p.id,
        p.product_name,
        p.product_code,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        c.category_name

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    WHERE p.status IN ('active', 'out_of_stock')
      AND c.status = 'active'
";

if ($searchQuery !== '') {

    $sql .= "
        AND (
            p.product_name LIKE ? ESCAPE '!'
            OR c.category_name LIKE ? ESCAPE '!'
            OR p.description LIKE ? ESCAPE '!'
        )
    ";
}

$sql .= " ORDER BY p.id DESC";


/* =========================================================
   EXECUTE QUERY
========================================================= */

$stmt = $conn->prepare($sql);

if ($searchQuery !== '') {

    $escapedQuery = str_replace(
        ['!', '%', '_'],
        ['!!', '!%', '!_'],
        $searchQuery
    );

    $searchTerm = '%' . $escapedQuery . '%';

    $stmt->bind_param(
        'sss',
        $searchTerm,
        $searchTerm,
        $searchTerm
    );
}

$stmt->execute();

$products = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

?>
