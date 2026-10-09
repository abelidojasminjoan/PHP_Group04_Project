
<?php
declare(strict_types=1);

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
    SELECT first_name, last_name, email, phone
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

/* ADD OR EDIT */

$addressId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
) ?: 0;

$isEdit = $addressId > 0;

$address = [
    'address_label' => 'Home',
    'recipient_first_name' => $user['first_name'],
    'recipient_last_name' => $user['last_name'],
    'phone' => $user['phone'] ?? '',
    'address_line1' => '',
    'address_line2' => '',
    'barangay' => '',
    'city' => '',
    'province' => '',
    'postal_code' => '',
    'country' => 'Philippines',
    'is_default' => 0
];

if ($isEdit) {

    $stmt = $conn->prepare("
        SELECT *
        FROM user_addresses
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param('ii', $addressId, $userId);
    $stmt->execute();

    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        http_response_code(404);
        exit('Address not found.');
    }

    $address = $existing;
}

/* FIRST ADDRESS CHECK */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM user_addresses
    WHERE user_id = ?
");

$stmt->bind_param('i', $userId);
$stmt->execute();

$addressCount = (int) $stmt
    ->get_result()
    ->fetch_assoc()['total'];

$stmt->close();

$isFirstAddress = !$isEdit && $addressCount === 0;

if ($isFirstAddress) {
    $address['is_default'] = 1;
}

$isCurrentDefault = $isEdit &&
    (int) $address['is_default'] === 1;

/* CSRF */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = $_SESSION['address_error'] ?? '';
unset($_SESSION['address_error']);

/* FORM FIELD DEFINITIONS */

$fields = [
    [
        'name' => 'recipient_first_name',
        'label' => 'First Name',
        'required' => true,
        'maxlength' => 100,
        'placeholder' => ''
    ],
    [
        'name' => 'recipient_last_name',
        'label' => 'Last Name',
        'required' => true,
        'maxlength' => 100,
        'placeholder' => ''
    ],
    [
        'name' => 'phone',
        'label' => 'Phone Number',
        'required' => false,
        'maxlength' => 30,
        'placeholder' => '+63 9XX XXX XXXX'
    ],
    [
        'name' => 'address_line1',
        'label' => 'Street Address',
        'required' => true,
        'maxlength' => 255,
        'placeholder' => 'House number, street name'
    ],
    [
        'name' => 'address_line2',
        'label' => 'Apartment, Unit, Building',
        'required' => false,
        'maxlength' => 255,
        'placeholder' => 'Optional'
    ],
    [
        'name' => 'barangay',
        'label' => 'Barangay',
        'required' => false,
        'maxlength' => 100,
        'placeholder' => 'Enter barangay'
    ],
    [
        'name' => 'city',
        'label' => 'City / Municipality',
        'required' => true,
        'maxlength' => 100,
        'placeholder' => 'e.g. Makati'
    ],
    [
        'name' => 'province',
        'label' => 'Province / Region',
        'required' => true,
        'maxlength' => 100,
        'placeholder' => 'e.g. Metro Manila'
    ],
    [
        'name' => 'postal_code',
        'label' => 'Postal Code',
        'required' => true,
        'maxlength' => 20,
        'placeholder' => 'e.g. 1200'
    ],
    [
        'name' => 'country',
        'label' => 'Country',
        'required' => true,
        'maxlength' => 100,
        'placeholder' => ''
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= $isEdit ? 'Edit Address' : 'Add Address' ?> | PureVia
    </title>

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
                   class="account-nav-item active">
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
                <h1>
                    <?= $isEdit
                        ? 'Edit Address'
                        : 'Add New Address' ?>
                </h1>
            </div>

            <?php if ($error): ?>
                <div class="account-alert error" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="account-edit-card">

                <form
                    id="addressForm"
                    method="POST"
                    action="<?= BASE_URL ?>/actions/account/manage-address.php"
                >

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= e($_SESSION['csrf_token']) ?>">

                    <input type="hidden"
                           name="action"
                           value="<?= $isEdit ? 'update' : 'create' ?>">

                    <input type="hidden"
                           name="address_id"
                           value="<?= $addressId ?>">

                    <div class="account-edit-form">

                        <div class="account-form-group">

                            <label for="address_label">
                                Address Label *
                            </label>

                            <select
                                id="address_label"
                                name="address_label"
                                class="address-label-select"
                                required
                            >
                                <?php foreach (['Home', 'Office', 'Other'] as $label): ?>

                                    <option
                                        value="<?= e($label) ?>"
                                        <?= ($address['address_label'] ?? 'Home') === $label
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= e($label) ?>
                                    </option>

                                <?php endforeach; ?>
                            </select>

                            <small>
                                Choose how you want to identify this address.
                            </small>
                        </div>

                        <?php foreach ($fields as $field): ?>

                            <?php
                            $name = $field['name'];
                            $required = $field['required'];
                            ?>

                            <div class="account-form-group">

                                <label for="<?= e($name) ?>">
                                    <?= e($field['label']) ?>
                                    <?= $required ? '*' : '' ?>
                                </label>

                                <input
                                    type="<?= $name === 'phone' ? 'tel' : 'text' ?>"
                                    id="<?= e($name) ?>"
                                    name="<?= e($name) ?>"
                                    maxlength="<?= (int) $field['maxlength'] ?>"
                                    placeholder="<?= e($field['placeholder']) ?>"
                                    value="<?= e($address[$name] ?? '') ?>"
                                    <?= $required ? 'required' : '' ?>
                                >

                            </div>

                        <?php endforeach; ?>

                        <div>

                            <label class="address-default-option">

                                <input
                                    type="checkbox"
                                    id="is_default"
                                    name="is_default"
                                    value="1"
                                    <?= (int) $address['is_default'] === 1
                                        ? 'checked'
                                        : '' ?>
                                    <?= ($isFirstAddress || $isCurrentDefault)
                                        ? 'disabled'
                                        : '' ?>
                                >

                                Set as default delivery address
                            </label>

                            <?php if ($isFirstAddress || $isCurrentDefault): ?>

                                <input
                                    type="hidden"
                                    name="is_default"
                                    value="1"
                                >

                                <small class="address-default-note">
                                    <?= $isFirstAddress
                                        ? 'Your first address automatically becomes the default.'
                                        : 'This is your current default address.' ?>
                                </small>

                            <?php endif; ?>

                        </div>

                        <div class="account-form-actions">

                            <button
                                type="submit"
                                id="saveAddressBtn"
                                class="account-save-btn"
                            >
                                <?= $isEdit
                                    ? 'Save Changes'
                                    : 'Add Address' ?>
                            </button>

                            <a
                                href="<?= BASE_URL ?>/account/addresses.php"
                                class="account-cancel-btn"
                            >
                                Cancel
                            </a>

                        </div>

                    </div>
                </form>
            </div>
        </section>
    </div>
</main>

<script src="<?= BASE_URL ?>/assets/js/account.js"></script>

</body>
</html>
