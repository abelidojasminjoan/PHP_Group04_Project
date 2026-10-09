
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

function e(mixed $value): string {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT first_name, last_name, email
    FROM users
    WHERE id = ?
      AND role_id = 3
      AND status = 'active'
    LIMIT 1
");

$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(403);
    exit('Access denied.');
}

$fullName = trim(
    $user['first_name'] . ' ' . $user['last_name']
);

$initials = strtoupper(
    mb_substr($user['first_name'], 0, 1) .
    mb_substr($user['last_name'], 0, 1)
);

$stmt = $conn->prepare("
    SELECT *
    FROM user_addresses
    WHERE user_id = ?
    ORDER BY is_default DESC, created_at ASC, id ASC
");

$stmt->bind_param('i', $userId);
$stmt->execute();

$addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = $_SESSION['address_success'] ?? '';
$error = $_SESSION['address_error'] ?? '';

unset(
    $_SESSION['address_success'],
    $_SESSION['address_error']
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Delivery Addresses | PureVia</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/account.css">
</head>

<body>

<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="account-page">
    <div class="account-layout">

        <aside class="account-sidebar">

            <div class="account-user">
                <div class="account-user-avatar">
                    <?= e($initials) ?>
                </div>

                <h3><?= e($fullName) ?></h3>
                <p><?= e($user['email']) ?></p>

                <span class="account-role">CUSTOMER</span>
            </div>

            <div class="account-sidebar-divider"></div>

            <nav class="account-sidebar-nav">

                <a href="<?= BASE_URL ?>/account/profile.php"
                   class="account-nav-item">
                    <span>👤</span> Profile
                </a>

                <a href="<?= BASE_URL ?>/account/addresses.php"
                   class="account-nav-item active"
                   aria-current="page">
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

        <section class="account-content">

            <div class="account-content-header">
                <h1>Delivery Addresses</h1>

                <a href="<?= BASE_URL ?>/account/address-form.php"
                   class="account-edit-btn address-add-btn">
                    + Add Address
                </a>
            </div>

            <?php if ($success): ?>
                <div class="account-alert success" role="status">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="account-alert error" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="address-list">

                <?php if (empty($addresses)): ?>

                    <div class="address-empty">
                        <div class="address-empty-icon">📍</div>
                        <h3>No delivery addresses yet</h3>
                        <p>
                            Add your first address to make
                            checkout easier.
                        </p>

                        <a href="<?= BASE_URL ?>/account/address-form.php"
                           class="account-save-btn">
                            + Add Address
                        </a>
                    </div>

                <?php else: ?>

                    <?php foreach ($addresses as $address): ?>

                        <?php
                        $addressId = (int) $address['id'];
                        $isDefault = (int) $address['is_default'] === 1;

                        $label = $address['address_label'] ?? 'Home';

                        if (!in_array(
                            $label,
                            ['Home', 'Office', 'Other'],
                            true
                        )) {
                            $label = 'Other';
                        }
                        ?>

                        <article class="address-card">

                            <div class="address-card-content">

                                <div class="address-card-heading">

                                    <h3><?= e($label) ?></h3>

                                    <?php if ($isDefault): ?>
                                        <span class="address-default-badge">
                                            DEFAULT
                                        </span>
                                    <?php endif; ?>

                                </div>

                                <p class="address-recipient">
                                    <?= e(
                                        trim(
                                            $address['recipient_first_name'] .
                                            ' ' .
                                            $address['recipient_last_name']
                                        )
                                    ) ?>
                                </p>

                                <p><?= e($address['address_line1']) ?></p>

                                <?php if (!empty($address['address_line2'])): ?>
                                    <p><?= e($address['address_line2']) ?></p>
                                <?php endif; ?>

                                <?php if (!empty($address['barangay'])): ?>
                                    <p><?= e($address['barangay']) ?></p>
                                <?php endif; ?>

                                <p>
                                    <?= e($address['city']) ?>,
                                    <?= e($address['province']) ?>
                                    <?= e($address['postal_code']) ?>
                                </p>

                                <?php if (!empty($address['phone'])): ?>
                                    <p class="address-phone">
                                        <?= e($address['phone']) ?>
                                    </p>
                                <?php endif; ?>

                            </div>

                            <div class="address-card-actions">

                                <a href="<?= BASE_URL ?>/account/address-form.php?id=<?= $addressId ?>"
                                   class="address-edit-link">
                                    Edit
                                </a>

                                <?php if (!$isDefault): ?>

                                    <form method="POST"
                                          action="<?= BASE_URL ?>/actions/account/manage-address.php">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="set_default">

                                        <input type="hidden"
                                               name="address_id"
                                               value="<?= $addressId ?>">

                                        <button type="submit"
                                                class="address-default-btn">
                                            Set Default
                                        </button>
                                    </form>

                                    <form method="POST"
                                          action="<?= BASE_URL ?>/actions/account/manage-address.php"
                                          data-address-delete-form>

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="delete">

                                        <input type="hidden"
                                               name="address_id"
                                               value="<?= $addressId ?>">

                                        <button type="submit"
                                                class="address-delete-btn">
                                            Delete
                                        </button>
                                    </form>

                                <?php endif; ?>

                            </div>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>
    </div>
</main>

<script src="<?= BASE_URL ?>/assets/js/account.js"></script>

</body>
</html>
