
<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, role_id, first_name, last_name,
           email, phone, status, created_at
    FROM users
    WHERE id = ? AND role_id = 3 AND status = 'active'
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(403);
    exit('Access denied.');
}

function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$fullName = trim(
    $user['first_name'] . ' ' . $user['last_name']
);

$initials = strtoupper(
    substr($user['first_name'], 0, 1) .
    substr($user['last_name'], 0, 1)
);

$memberSince = date(
    'Y-m-d',
    strtotime($user['created_at'])
);

$_SESSION['role'] = 'customer';
$_SESSION['name'] = $fullName;
$_SESSION['email'] = $user['email'];

$success = $_SESSION['profile_success'] ?? '';
$error = $_SESSION['profile_error'] ?? '';

unset(
    $_SESSION['profile_success'],
    $_SESSION['profile_error']
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Account | PureVia</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/account.css">
</head>

<body>

<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="account-page">

    <div class="account-layout">

        <!-- ACCOUNT SIDEBAR -->
        <aside class="account-sidebar">

            <div class="account-user">

                <div class="account-user-avatar">
                    <?= e($initials) ?>
                </div>

                <h3><?= e($fullName) ?></h3>

                <p><?= e($user['email']) ?></p>

                <span class="account-role">
                    CUSTOMER
                </span>

            </div>

            <div class="account-sidebar-divider"></div>

            <nav class="account-sidebar-nav"
                 aria-label="Account navigation">

                <a href="<?= BASE_URL ?>/account/profile.php"
                   class="account-nav-item active">
                    <span>👤</span> Profile
                </a>

                <a href="<?= BASE_URL ?>/account/addresses.php"
                   class="account-nav-item">
                    <span>📍</span> Addresses
                </a>

                <a href="<?= BASE_URL ?>/account/skin-profile.php"
                   class="account-nav-item">
                    <span>✨</span> Skin Profile
                </a>

                <a href="<?= BASE_URL ?>/account/account-security.php"
                   class="account-nav-item">
                    <span>🔒</span> Security
                </a>

                <div class="account-sidebar-divider"></div>

                <a href="<?= BASE_URL ?>/my-orders.php"
                   class="account-nav-item">
                    <span>📦</span> My Orders
                </a>

                <a href="<?= BASE_URL ?>/shop.php"
                   class="account-nav-item">
                    <span>🛒</span> Shop
                </a>

            </nav>

        </aside>

        <!-- PROFILE CONTENT -->
        <section class="account-content">

            <div class="account-content-header">

                <h1>Personal Information</h1>

                <a href="<?= BASE_URL ?>/account/edit-profile.php"
                   class="account-edit-btn">
                    Edit
                </a>

            </div>

            <?php if ($success): ?>
                <div class="account-alert success">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="account-alert error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="account-info-card">

                <div class="account-info-row">
                    <span class="account-info-label">
                        FULL NAME
                    </span>

                    <span class="account-info-value">
                        <?= e($fullName) ?>
                    </span>
                </div>

                <div class="account-info-row">
                    <span class="account-info-label">
                        EMAIL ADDRESS
                    </span>

                    <span class="account-info-value">
                        <?= e($user['email']) ?>
                    </span>
                </div>

                <div class="account-info-row">
                    <span class="account-info-label">
                        PHONE
                    </span>

                    <span class="account-info-value">
                        <?= e($user['phone'] ?: 'Not provided') ?>
                    </span>
                </div>

                <div class="account-info-row">
                    <span class="account-info-label">
                        MEMBER SINCE
                    </span>

                    <span class="account-info-value">
                        <?= e($memberSince) ?>
                    </span>
                </div>

                <div class="account-info-row">
                    <span class="account-info-label">
                        ACCOUNT STATUS
                    </span>

                    <span class="account-info-value">
                        <?= e(ucfirst($user['status'])) ?>
                    </span>
                </div>

            </div>

        </section>

    </div>

</main>

<!-- Keep the existing header dropdown script here if required. -->
<script src="<?= BASE_URL ?>/assets/js/account.js"></script>

</body>
</html>
