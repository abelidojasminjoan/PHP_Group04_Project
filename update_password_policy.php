<?php

/* =========================================================
   LOAD CONFIGURATION
========================================================= */

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/db.php';


/* =========================================================
   ONLY ALLOW POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        'Location: '
            . BASE_URL
            . '/admin/security/settings.php'
    );

    exit;
}


/* =========================================================
   GET PASSWORD SETTINGS
========================================================= */

$passwordMinLength =
    (int) ($_POST['password_min_length'] ?? 12);

$requireUppercase =
    (int) ($_POST['password_require_uppercase'] ?? 1);

$requireNumber =
    (int) ($_POST['password_require_number'] ?? 1);

$requireSpecial =
    (int) ($_POST['password_require_special'] ?? 1);


/* =========================================================
   VALIDATE PASSWORD LENGTH
========================================================= */

if (
    $passwordMinLength < 8 ||
    $passwordMinLength > 128
) {

    $_SESSION['security_error'] =
        'Password length must be between 8 and 128.';

    header(
        'Location: '
            . BASE_URL
            . '/admin/security/settings.php'
    );

    exit;
}


/* =========================================================
   UPDATE PASSWORD POLICY
========================================================= */

$stmt = $conn->prepare("
    UPDATE security_settings
    SET
        password_min_length = ?,
        require_uppercase = ?,
        require_number = ?,
        require_special_character = ?
    WHERE id = 1
");


/* =========================================================
   BIND VALUES
========================================================= */

$stmt->bind_param(
    'iiii',
    $passwordMinLength,
    $requireUppercase,
    $requireNumber,
    $requireSpecial
);


/* =========================================================
   SAVE CHANGES
========================================================= */

if ($stmt->execute()) {

    $_SESSION['security_success'] =
        'Password policy updated successfully.';
} else {

    $_SESSION['security_error'] =
        'Unable to update password policy.';
}


/* =========================================================
   CLOSE STATEMENT
========================================================= */

$stmt->close();


/* =========================================================
   RETURN TO SECURITY SETTINGS
========================================================= */

header(
    'Location: '
        . BASE_URL
        . '/admin/security/settings.php'
);

exit;
