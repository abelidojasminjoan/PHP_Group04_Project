
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
    WHERE id = ? AND role_id = 3
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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Account | PureVia</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/account.css">
</head>

<body>

<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="account-page">

    <div class="account-layout">

        <!-- SIDEBAR -->
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

                <a href="<?= BASE_URL ?>/account/security.php"
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

                <button type="button"
                        class="account-edit-btn"
                        id="openProfileEdit">
                    Edit
                </button>

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

<!-- EDIT PROFILE MODAL -->
<div class="account-modal-overlay"
     id="profileEditModal"
     aria-hidden="true">

    <div class="account-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="profileModalTitle">

        <div class="account-modal-header">

            <div>
                <h2 id="profileModalTitle">Edit Profile</h2>
                <p>Update your personal information.</p>
            </div>

            <button type="button"
                    class="account-modal-close"
                    id="closeProfileEdit"
                    aria-label="Close">
                &times;
            </button>

        </div>

        <form method="POST"
              action="<?= BASE_URL ?>/actions/account/update_profile.php"
              id="profileEditForm">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="account-modal-body">

                <div class="account-form-grid">

                    <div class="account-form-group">
                        <label for="firstName">
                            First Name *
                        </label>

                        <input type="text"
                               id="firstName"
                               name="first_name"
                               maxlength="100"
                               value="<?= e($user['first_name']) ?>"
                               required>
                    </div>

                    <div class="account-form-group">
                        <label for="lastName">
                            Last Name *
                        </label>

                        <input type="text"
                               id="lastName"
                               name="last_name"
                               maxlength="100"
                               value="<?= e($user['last_name']) ?>"
                               required>
                    </div>

                    <div class="account-form-group full">
                        <label for="email">
                            Email Address
                        </label>

                        <input type="email"
                               id="email"
                               value="<?= e($user['email']) ?>"
                               readonly>

                        <small>
                            Email changes are managed separately
                            for account security.
                        </small>
                    </div>

                    <div class="account-form-group full">
                        <label for="phone">
                            Phone Number
                        </label>

                        <input type="tel"
                               id="phone"
                               name="phone"
                               maxlength="30"
                               placeholder="+63 9XX XXX XXXX"
                               value="<?= e($user['phone']) ?>">
                    </div>

                </div>

            </div>

            <div class="account-modal-footer">

                <button type="button"
                        class="account-cancel-btn"
                        id="cancelProfileEdit">
                    Cancel
                </button>

                <button type="submit"
                        class="account-save-btn"
                        id="saveProfileBtn">
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

<script src="<?= BASE_URL ?>/assets/js/account.js"></script>

<!-- Keep your existing header dropdown JS here too. -->

</body>
</html>
