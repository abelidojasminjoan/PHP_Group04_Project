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
   GET SESSION SETTINGS
========================================================= */

$sessionTimeout =
    (int) ($_POST['session_timeout_minutes'] ?? 30);

$maxLoginAttempts =
    (int) ($_POST['max_login_attempts'] ?? 3);

$lockoutMinutes =
    (int) ($_POST['lockout_duration_minutes'] ?? 15);


/* =========================================================
   VALIDATE SETTINGS
========================================================= */

if (
    $sessionTimeout < 1 ||
    $maxLoginAttempts < 1 ||
    $lockoutMinutes < 1
) {

    $_SESSION['security_error'] =
        'Please enter valid security settings.';

    header(
        'Location: '
            . BASE_URL
            . '/admin/security/settings.php'
    );

    exit;
}


/* =========================================================
   UPDATE SECURITY SETTINGS
========================================================= */

$stmt = $conn->prepare("
    UPDATE security_settings
    SET
        session_timeout_minutes = ?,
        max_login_attempts = ?,
        lockout_minutes = ?
    WHERE id = 1
");


/* =========================================================
   BIND VALUES
========================================================= */

$stmt->bind_param(
    'iii',
    $sessionTimeout,
    $maxLoginAttempts,
    $lockoutMinutes
);


/* =========================================================
   SAVE CHANGES
========================================================= */

if ($stmt->execute()) {

    $_SESSION['security_success'] =
        'Session and lockout settings updated successfully.';
} else {

    $_SESSION['security_error'] =
        'Unable to update session settings.';
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
