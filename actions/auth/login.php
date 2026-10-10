<?php

/* =========================================================
   PUREVIA LOGIN PROCESS
========================================================= */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';


/* =========================================================
   ONLY ALLOW POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        'Location: ' . BASE_URL . '/index.php'
    );

    exit;
}






/* =========================================================
   INPUT
========================================================= */

$email = trim($_POST['email'] ?? '');

$password = $_POST['password'] ?? '';


/* =========================================================
   VALIDATION
========================================================= */

if ($email === '' || $password === '') {

    $_SESSION['login_error'] =
        'Please enter your email and password.';

    $_SESSION['login_email'] =
        $email;

    header(
        'Location: ' . BASE_URL . '/index.php'
    );

    exit;
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $_SESSION['login_error'] =
        'Please enter a valid email address.';

    $_SESSION['login_email'] =
        $email;

    header(
        'Location: ' . BASE_URL . '/index.php'
    );

    exit;
}


/* =========================================================
   GET USER
========================================================= */

$sql = "
    SELECT
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        u.password,
        u.status,
        r.role_name

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE u.email = ?

    LIMIT 1
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    error_log(
        'Login prepare failed: '
        . $conn->error
    );

    $_SESSION['login_error'] =
        'Unable to login right now. Please try again.';

    header(
        'Location: ' . BASE_URL . '/index.php'
    );

    exit;
}


$stmt->bind_param(
    's',
    $email
);


$stmt->execute();


$result =
    $stmt->get_result();


$user =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   VERIFY USER
========================================================= */

if (
    !$user ||
    !password_verify(
        $password,
        $user['password']
    )
) {

    $_SESSION['login_error'] =
        'Invalid email or password.';

    $_SESSION['login_email'] =
        $email;

    header(
        'Location: ' . BASE_URL . '/index.php'
    );

    exit;
}


/* =========================================================
   ACCOUNT STATUS
========================================================= */

if ($user['status'] !== 'active') {

    $_SESSION['login_error'] =
        'Your account is not currently active.';

    $_SESSION['login_email'] =
        $email;

    header(
        'Location: ' . BASE_URL . '/index.php'
    );

    exit;
}


/* =========================================================
   CREATE SESSION
========================================================= */

session_regenerate_id(true);


$_SESSION['user_id'] =
    (int) $user['id'];


$_SESSION['role'] =
    strtolower($user['role_name']);


$_SESSION['first_name'] =
    $user['first_name'];


$_SESSION['last_name'] =
    $user['last_name'];


$_SESSION['name'] =
    $user['first_name'];


$_SESSION['email'] =
    $user['email'];


$_SESSION['last_activity'] =
    time();


/* =========================================================
   UPDATE LAST LOGIN
========================================================= */

$updateLogin = $conn->prepare(
    "
    UPDATE users

    SET last_login_at = NOW()

    WHERE id = ?
    "
);


if ($updateLogin) {

    $updateLogin->bind_param(
        'i',
        $_SESSION['user_id']
    );


    $updateLogin->execute();


    $updateLogin->close();

}


/* =========================================================
   REDIRECT BY ROLE
========================================================= */

if ($_SESSION['role'] === 'admin') {

    header(
        'Location: '
        . BASE_URL
        . '/admin/dashboard.php'
    );

    exit;
}


if ($_SESSION['role'] === 'staff') {

    header(
        'Location: '
        . BASE_URL
        . '/staff/dashboard.php'
    );

    exit;
}


/* CUSTOMER */

header(
    'Location: '
    . BASE_URL
    . '/index.php'
);

exit;

