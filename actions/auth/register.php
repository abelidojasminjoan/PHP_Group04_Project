<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

/* =========================================================
   ONLY ALLOW POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


/* =========================================================
   GET FORM VALUES
========================================================= */

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));

$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

$termsAccepted = isset($_POST['terms']);


/* =========================================================
   SAVE VALUES TEMPORARILY
========================================================= */

$_SESSION['register_data'] = [
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email
];


/* =========================================================
   VALIDATION
========================================================= */

if (
    $firstName === '' ||
    $lastName === '' ||
    $email === '' ||
    $password === '' ||
    $confirmPassword === ''
) {
    $_SESSION['register_error'] = 'Please complete all required fields.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


/* FIRST NAME */

if (
    strlen($firstName) < 2 ||
    strlen($firstName) > 100
) {
    $_SESSION['register_error'] =
        'Please enter a valid first name.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


/* LAST NAME */

if (
    strlen($lastName) < 2 ||
    strlen($lastName) > 100
) {
    $_SESSION['register_error'] =
        'Please enter a valid last name.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


/* EMAIL */

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] =
        'Please enter a valid email address.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


/* PASSWORD MATCH */

if ($password !== $confirmPassword) {
    $_SESSION['register_error'] =
        'Passwords do not match.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


/* =========================================================
   PASSWORD REQUIREMENTS

   Based on your security_settings defaults:
   - Minimum 12 characters
   - Uppercase
   - Lowercase
   - Number
   - Special character
========================================================= */

if (strlen($password) < 12) {
    $_SESSION['register_error'] =
        'Password must contain at least 12 characters.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


if (!preg_match('/[A-Z]/', $password)) {
    $_SESSION['register_error'] =
        'Password must contain at least one uppercase letter.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


if (!preg_match('/[a-z]/', $password)) {
    $_SESSION['register_error'] =
        'Password must contain at least one lowercase letter.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


if (!preg_match('/[0-9]/', $password)) {
    $_SESSION['register_error'] =
        'Password must contain at least one number.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


if (!preg_match('/[^A-Za-z0-9]/', $password)) {
    $_SESSION['register_error'] =
        'Password must contain at least one special character.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


/* TERMS */

if (!$termsAccepted) {
    $_SESSION['register_error'] =
        'You must agree to the Terms of Service and Privacy Policy.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


/* =========================================================
   CHECK IF EMAIL ALREADY EXISTS
========================================================= */

$checkStatement = $conn->prepare(
    'SELECT id FROM users WHERE email = ? LIMIT 1'
);

$checkStatement->bind_param(
    's',
    $email
);

$checkStatement->execute();

$checkResult = $checkStatement->get_result();


if ($checkResult->num_rows > 0) {

    $checkStatement->close();

    $_SESSION['register_error'] =
        'An account with this email already exists.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


$checkStatement->close();


/* =========================================================
   HASH PASSWORD
========================================================= */

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);


/* =========================================================
   CREATE CUSTOMER ACCOUNT

   role_id = 3 → Customer

   DEVELOPMENT ONLY:
   Accounts are temporarily activated immediately so
   registration/login can be tested without OTP/email
   activation.

   PRODUCTION:
   Change this back to:
   $status = 'pending';
========================================================= */

$roleId = 3;


/* DEVELOPMENT */

$status = 'active';


/* PRODUCTION - ENABLE LATER */

// $status = 'pending';


$insertStatement = $conn->prepare(
    'INSERT INTO users
    (
        role_id,
        first_name,
        last_name,
        email,
        password,
        status
    )
    VALUES (?, ?, ?, ?, ?, ?)'
);


$insertStatement->bind_param(
    'isssss',
    $roleId,
    $firstName,
    $lastName,
    $email,
    $passwordHash,
    $status
);


/* =========================================================
   INSERT ACCOUNT
========================================================= */

if (!$insertStatement->execute()) {

    error_log(
        'Registration failed: ' .
        $insertStatement->error
    );

    $insertStatement->close();

    $_SESSION['register_error'] =
        'Unable to create your account. Please try again.';

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}


/* =========================================================
   SUCCESS
========================================================= */

$insertStatement->close();

unset($_SESSION['register_data']);

$_SESSION['login_success'] =
    'Account created successfully. You may now log in.';

header('Location: ' . BASE_URL . '/index.php');
exit;