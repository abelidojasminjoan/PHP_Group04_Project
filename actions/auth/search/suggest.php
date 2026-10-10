
<?php
/* =========================================================
   PUREVIA
   LIVE PRODUCT SEARCH
========================================================= */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');


/* =========================================================
   DEFAULT RESPONSE
========================================================= */

$response = [
    'suggestions' => [],
    'products' => []
];


/* =========================================================
   ONLY ALLOW GET REQUESTS
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    header('Allow: GET');

    echo json_encode([
        'error' => 'Method not allowed.'
    ]);

    exit;
}


/* =========================================================
   STEP 1: COLLECTION
========================================================= */

$query = trim(
    (string) ($_GET['q'] ?? '')
);


/* =========================================================
   STEP 2: VALIDATION
========================================================= */

if ($query === '') {

    echo json_encode($response);
    exit;
}

if (mb_strlen($query, 'UTF-8') > 100) {

    http_response_code(400);

    echo json_encode([
        'error' => 'Search must not exceed 100 characters.'
    ]);

    exit;
}


/* =========================================================
   PREPARE SEARCH PATTERN
========================================================= */

$escapedQuery = str_replace(
    ['!', '%', '_'],
    ['!!', '!%', '!_'],
    $query
);

$searchTerm = '%' . $escapedQuery . '%';


/* =========================================================
   STEP 3: SECURE OUTPUT

   Response is JSON, not HTML.
   Frontend inserts text using textContent.
========================================================= */


/* =========================================================
   STEP 4: GET PRODUCT MATCHES
========================================================= */

try {

    $sql = "
        SELECT
            p.id,
            p.product_name,
            p.image,
            p.price,
            c.category_name

        FROM products p

        INNER JOIN categories c
            ON c.id = p.category_id

        WHERE p.status IN ('active', 'out_of_stock')
          AND c.status = 'active'

          AND (
              p.product_name LIKE ? ESCAPE '!'
              OR c.category_name LIKE ? ESCAPE '!'
              OR p.description LIKE ? ESCAPE '!'
          )

        ORDER BY
            CASE
                WHEN p.product_name LIKE ? ESCAPE '!'
                THEN 0
                ELSE 1
            END,
            p.product_name ASC

        LIMIT 5
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        'ssss',
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    );

    $stmt->execute();

    $result = $stmt->get_result();


    /* PROCESS PRODUCTS */

    while ($product = $result->fetch_assoc()) {

        $imageName = trim(
            (string) ($product['image'] ?? '')
        );

        $imageUrl = '';

        if ($imageName !== '') {

            if (
                filter_var(
                    $imageName,
                    FILTER_VALIDATE_URL
                ) &&
                strtolower(
                    (string) parse_url(
                        $imageName,
                        PHP_URL_SCHEME
                    )
                ) === 'https'
            ) {

                $imageUrl = $imageName;
            } else {

                $imageUrl = BASE_URL .
                    '/uploads/products/' .
                    rawurlencode(basename($imageName));
            }
        }


        $response['products'][] = [
            'id' => (int) $product['id'],
            'name' => $product['product_name'],
            'price' => (float) $product['price'],
            'image' => $imageUrl
        ];
    }

    $stmt->close();


    /* =====================================================
       GET SEARCH SUGGESTIONS
    ====================================================== */

    $sql = "
        SELECT
            p.product_name

        FROM products p

        INNER JOIN categories c
            ON c.id = p.category_id

        WHERE p.status IN ('active', 'out_of_stock')
          AND c.status = 'active'
          AND p.product_name LIKE ? ESCAPE '!'

        ORDER BY p.product_name

        LIMIT 6
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        's',
        $searchTerm
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $response['suggestions'][] =
            $row['product_name'];
    }

    $stmt->close();


    /* =====================================================
       ADD MATCHING CATEGORIES
    ====================================================== */

    $sql = "
        SELECT category_name
        FROM categories
        WHERE status = 'active'
          AND category_name LIKE ? ESCAPE '!'
        ORDER BY category_name
        LIMIT 6
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param('s', $searchTerm);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $response['suggestions'][] =
            $row['category_name'];
    }

    $stmt->close();


    /* REMOVE DUPLICATES AND LIMIT SUGGESTIONS */

    $response['suggestions'] = array_slice(
        array_values(
            array_unique($response['suggestions'])
        ),
        0,
        6
    );


    /* =====================================================
       STEP 5: RESPONSE
    ====================================================== */

    echo json_encode(
        $response,
        JSON_UNESCAPED_UNICODE |
            JSON_INVALID_UTF8_SUBSTITUTE
    );
} catch (Throwable $exception) {

    error_log(
        'Search suggestion error: ' .
            $exception->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'error' => 'Search is temporarily unavailable.'
    ]);
}
