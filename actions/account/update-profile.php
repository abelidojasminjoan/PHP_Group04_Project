
<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$redirect = BASE_URL . '/account/edit-profile.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

function profileError($message, $redirect) {
    $_SESSION['profile_error'] = $message;
    header('Location: ' . $redirect);
    exit;
}

/* CSRF VALIDATION */

$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $submittedToken
    )
) {
    http_response_code(403);
    exit('Invalid security token.');
}

$userId = (int) $_SESSION['user_id'];

/* GET FORM VALUES */

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

/* VALIDATION */

if ($firstName === '' || $lastName === '') {
    profileError(
        'First and last name are required.',
        $redirect
    );
}

if (
    mb_strlen($firstName) > 100 ||
    mb_strlen($lastName) > 100
) {
    profileError(
        'Name is too long.',
        $redirect
    );
}

if (
    $phone !== '' &&
    (
        strlen($phone) > 30 ||
        !preg_match('/^[0-9+\s()\-]+$/', $phone)
    )
) {
    profileError(
        'Enter a valid phone number.',
        $redirect
    );
}

/* VERIFY CUSTOMER ACCOUNT */

$check = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
      AND role_id = 3
      AND status = 'active'
    LIMIT 1
");

$check->bind_param("i", $userId);
$check->execute();

$authorized = $check->get_result()->fetch_assoc();
$check->close();

if (!$authorized) {
    http_response_code(403);
    exit('Access denied.');
}

/* UPDATE DATABASE */

$stmt = $conn->prepare("
    UPDATE users
    SET first_name = ?,
        last_name = ?,
        phone = ?
    WHERE id = ?
      AND role_id = 3
      AND status = 'active'
");

$stmt->bind_param(
    "sssi",
    $firstName,
    $lastName,
    $phone,
    $userId
);

if (!$stmt->execute()) {
    $stmt->close();

    profileError(
        'Unable to update profile. Please try again.',
        $redirect
    );
}

$stmt->close();

/* UPDATE SESSION */

$_SESSION['name'] = $firstName . ' ' . $lastName;

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$_SESSION['profile_success'] =
    'Your profile has been updated successfully.';

/* REDIRECT TO PROFILE */

header('Location: ' . BASE_URL . '/account/profile.php');
exit;
