<?php

/* =========================================
   LOAD CONFIGURATION
========================================= */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';

/* =========================================
   LOGIN MODAL MESSAGE
========================================= */

$loginError = $_SESSION['login_error'] ?? '';
$loginEmail = $_SESSION['login_email'] ?? '';
$loginSuccess = $_SESSION['login_success'] ?? '';

$showLoginModal =
    !empty($loginError) ||
    !empty($loginSuccess);


/* =========================================
   REGISTER MODAL MESSAGE
========================================= */

$registerError =
    $_SESSION['register_error'] ?? '';

$registerData =
    $_SESSION['register_data'] ?? [];

$registerFirstName =
    $registerData['first_name'] ?? '';

$registerLastName =
    $registerData['last_name'] ?? '';

$registerEmail =
    $registerData['email'] ?? '';

$showRegisterModal =
    !empty($registerError);


/* =========================================
   CLEAR TEMPORARY SESSION MESSAGES
========================================= */

unset($_SESSION['login_error']);
unset($_SESSION['login_email']);
unset($_SESSION['login_success']);

unset($_SESSION['register_error']);
unset($_SESSION['register_data']);

/* =========================================
   PAGE INFORMATION
========================================= */

$pageTitle = "PureVia Skin Care";


/* =========================================
   GET PRODUCTS FROM DATABASE
========================================= */


$sql = "
    SELECT
        p.id,
        p.product_name,
        p.description,
        p.price,
        p.image,
        p.stock_quantity,
        c.category_name,

        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.sort_order ASC, pi.id ASC
            LIMIT 1
        ) AS primary_image

    FROM products p

    INNER JOIN categories c
        ON p.category_id = c.id

    WHERE p.status = 'active'
      AND c.status = 'active'

    ORDER BY p.created_at DESC

    LIMIT 3
";

$productResult = $conn->query($sql);

if (!$productResult) {
    error_log(
        'Homepage product query failed: ' . $conn->error
    );
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Main Website CSS -->
    <link rel="stylesheet" href="./assets/css/style.css">

    <!-- Header CSS -->
    <link rel="stylesheet" href="./assets/css/header.css">
    <link rel="stylesheet" href="./assets/css/footer.css">

    <link rel="stylesheet" href="./assets/css/form1.css">


    <!-- Font Awesome Icons -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body>

    <?php include("./includes/header.php"); ?>


    <!-- =====================================
         MAIN CONTENT
    ====================================== -->

    <main>


        <!-- =================================
             HERO SECTION
        ================================== -->

        <section class="hero">

            <!-- DARK OVERLAY -->

            <div class="hero-overlay"></div>


            <!-- HERO CONTENT -->

            <div class="hero-container">


                <!-- =========================
                     TOP LABEL
                ========================== -->

                <p class="hero-eyebrow">
                    CLEAN SKINCARE • CLEAR GUIDANCE
                </p>


                <!-- =========================
                     MAIN HEADING
                ========================== -->

                <h1 class="hero-title">
                    Pure Care. Clear
                    <br>
                    Choices.
                </h1>


                <!-- =========================
                     BOTTOM CONTENT
                ========================== -->

                <div class="hero-bottom">


                    <!-- LEFT SIDE -->

                    <div class="hero-info">

                        <p class="hero-description">
                            Gentle, effective skincare made simple.
                            Review ingredients, skin-type guidance,
                            usage, price, and availability before you buy.
                        </p>


                        <!-- HERO BUTTONS -->

                        <div class="hero-actions">

                            <a
                                href="./skin-match.php"
                                class="hero-primary-btn"
                            >
                                SHOP BY SKIN TYPE
                            </a>


                            <a
                                href="./skin-type-finder.php"
                                class="hero-secondary-btn"
                            >
                                See how it works
                            </a>

                        </div>

                    </div>


                    <!-- RIGHT SIDE / BENEFITS -->

                    <div class="hero-benefits">

                        <div class="hero-benefit">

                            <i class="fa-solid fa-check"></i>

                            <span>
                                CLEAN FORMULAS
                            </span>

                        </div>


                        <div class="hero-benefit">

                            <i class="fa-solid fa-check"></i>

                            <span>
                                SKIN-TYPE GUIDANCE
                            </span>

                        </div>


                        <div class="hero-benefit">

                            <i class="fa-solid fa-check"></i>

                            <span>
                                CLEAR PRICING
                            </span>

                        </div>

                    </div>


                </div>

            </div>

        </section>

        <section class="bestsellers">

            <div class="bestsellers-container">

                <div class="bestsellers-layout">

                    <div class="bestsellers-intro">

                        <p class="section-eyebrow">
                            SKINCARE LOVED BY PUREVIA<br>
                            CUSTOMERS
                        </p>

                        <h2>
                            BEST SELLERS
                        </h2>

                        <p class="bestsellers-copy">
                            Discover our most-loved formulas,
                            chosen for visible results and trusted by
                            every kind of skin.
                        </p>

                        <p class="bestsellers-copy bestsellers-copy-secondary">
                            From barrier care to brightening, explore
                            the essentials customers return to again and
                            again.
                        </p>

                        <a
                            href="./shop.php"
                            class="shop-all-link"
                        >
                            <span>SEE ALL PRODUCTS</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>

                    </div>


                    <div class="product-grid">

                        <?php if (
                            $productResult &&
                            $productResult->num_rows > 0
                        ): ?>

                            <?php while (
                                $product = $productResult->fetch_assoc()
                            ): ?>

                                <article class="product-card">               
                                <?php
                                $imagePath = trim(
                                    (string) (
                                        $product['primary_image']
                                        ?: $product['image']
                                        ?: ''
                                    )
                                );

                                $defaultImage = './assets/images/products/default-product.jpg';

                                if ($imagePath === '') {

                                    $productImage = $defaultImage;

                                } elseif (
                                    filter_var($imagePath, FILTER_VALIDATE_URL) &&
                                    in_array(
                                        strtolower((string) parse_url($imagePath, PHP_URL_SCHEME)),
                                        ['http', 'https'],
                                        true
                                    )
                                ) {

                                    // External image URL
                                    $productImage = $imagePath;

                                } else {

                                    // Local image path
                                    $imagePath = str_replace('\\', '/', $imagePath);
                                    $imagePath = ltrim($imagePath, '/');

                                    // Only allow files in the product uploads directory
                                    $filename = basename($imagePath);

                                    $fullPath = __DIR__ . '/uploads/products/' . $filename;

                                    if (
                                        $filename !== '.' &&
                                        $filename !== '..' &&
                                        is_file($fullPath)
                                    ) {
                                        $productImage =
                                            './uploads/products/' . rawurlencode($filename);
                                    } else {
                                        $productImage = $defaultImage;
                                    }
                                }
                                ?>

                                <a
                                    href="./product.php?id=<?= (int) $product['id'] ?>"
                                    class="product-image-wrapper"
                                >
                                    <img
                                        src="<?= htmlspecialchars(
                                            $productImage,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $product['product_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        class="product-image"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='./assets/images/products/default-product.jpg';"
                                    >
                                </a>


                                    <div class="product-details">

                                        <h3 class="product-name">
                                            <a
                                                href="./product.php?id=<?= (int) $product['id'] ?>"
                                            >
                                                <?= htmlspecialchars(
                                                    $product['product_name']
                                                ) ?>
                                            </a>
                                        </h3>

                                        <p class="product-description">
                                            <?= htmlspecialchars(
                                                $product['description'] ?? ''
                                            ) ?>
                                        </p>

                                        <div class="product-size-row" aria-label="Available sizes">
                                            <button type="button" class="size-option selected" aria-pressed="true">15ml</button>
                                            <button type="button" class="size-option" aria-pressed="false">30ml</button>
                                            <button type="button" class="size-option" aria-pressed="false">60ml</button>
                                        </div>

                                        <div class="product-meta">

                                            <div class="product-rating" aria-label="4.8 out of 5 stars">
                                                <span class="stars">★★★★★</span>
                                                <span class="review-count">
                                                    217 Reviews
                                                </span>
                                            </div>

                                            <button
                                                type="button"
                                                class="add-to-bag-btn"
                                            >
                                                <span>Add to Bag</span>

                                                <span class="product-price">
                                                    ₱<?= number_format(
                                                        (float) $product['price'],
                                                        2
                                                    ) ?>
                                                </span>
                                            </button>

                                        </div>

                                    </div>

                                </article>

                            <?php endwhile; ?>


                        <?php else: ?>

                            <p>No products are currently available.</p>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </section>


        <!-- =================================
             OTHER HOMEPAGE SECTIONS
             Add your next sections here.
        ================================== -->


    </main>

    <!-- =========================================
        LOGIN MODAL
    ========================================== -->

    <div
        class="login-modal <?= $showLoginModal ? 'active' : '' ?>"
        id="loginModal"
        aria-hidden="<?= $showLoginModal ? 'false' : 'true' ?>"
    >

        <!-- BACKGROUND OVERLAY -->

        <div
            class="login-modal-overlay"
            id="loginModalOverlay"
        ></div>


        <!-- LOGIN CARD -->

        <section
            class="login-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="loginModalTitle"
        >


            <!-- CLOSE -->

            <button
                type="button"
                class="close-button"
                id="closeLoginModal"
                aria-label="Close login"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>


            <!-- LEAF -->

            <div class="login-brand">

                <i class="fa-brands fa-pagelines"></i>

            </div>


            <!-- TITLE -->

            <h2 id="loginModalTitle">
                WELCOME BACK!
            </h2>


            <!-- ERROR -->

            <?php if ($loginError !== ''): ?>

                <div class="login-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= htmlspecialchars($loginError) ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- LOGIN FORM -->

            <form
                action="<?= BASE_URL ?>/actions/auth/login.php"
                method="POST"
                class="login-form"
            >


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="loginEmail">
                        Email
                    </label>

                    <input
                        type="email"
                        id="loginEmail"
                        name="email"
                        value="<?= htmlspecialchars($loginEmail) ?>"
                        autocomplete="email"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label for="loginPassword">
                        Password
                    </label>


                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="loginPassword"
                            name="password"
                            autocomplete="current-password"
                            required
                        >


                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                        >

                            <i
                                class="fa-regular fa-eye-slash"
                                id="passwordIcon"
                            ></i>

                        </button>

                    </div>


                    <a
                        href="<?= BASE_URL ?>/forgot-password.php"
                        class="forgot-password"
                    >
                        Forgot Password?
                    </a>

                </div>


                <!-- LOGIN -->

                <button
                    type="submit"
                    class="login-button"
                >
                    LOGIN
                </button>

            </form>


            <!-- REGISTER -->

            <p class="register-text">

                Don't have an account?

                <a href="#" id="openRegisterModal">
                    Register here
                </a>

            </p>

        </section>

    </div>

    <!-- =========================================
        REGISTER MODAL
    ========================================== -->

    <div
        class="register-modal <?= $showRegisterModal ? 'active' : '' ?>"
        id="registerModal"
        aria-hidden="<?= $showRegisterModal ? 'false' : 'true' ?>"
    >

        <!-- OVERLAY -->

        <div
            class="register-modal-overlay"
            id="registerModalOverlay"
        ></div>


        <!-- REGISTER CARD -->

        <section
            class="register-modal-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="registerModalTitle"
        >

            <!-- CLOSE BUTTON -->

            <button
                type="button"
                class="register-close-button"
                id="closeRegisterModal"
                aria-label="Close registration"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>


            <!-- TOP TEXT -->

            <p class="register-eyebrow">
                WELCOME TO PUREVIA
            </p>


            <h2 id="registerModalTitle">
                CREATE ACCOUNT
            </h2>


            <p class="register-subtitle">
                A few details and your personal skincare space is ready.
            </p>


            <!-- ERROR -->

            <?php if ($registerError !== ''): ?>

                <div class="register-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= htmlspecialchars($registerError) ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- REGISTER FORM -->

            <form
                action="<?= BASE_URL ?>/actions/auth/register.php"
                method="POST"
                class="register-form"
            >

                <!-- NAME ROW -->

                <div class="register-name-row">

                    <div class="register-form-group">

                        <label for="registerFirstName">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="registerFirstName"
                            name="first_name"
                            value="<?= htmlspecialchars($registerFirstName) ?>"
                            maxlength="100"
                            autocomplete="given-name"
                            required
                        >

                    </div>


                    <div class="register-form-group">

                        <label for="registerLastName">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="registerLastName"
                            name="last_name"
                            value="<?= htmlspecialchars($registerLastName) ?>"
                            maxlength="100"
                            autocomplete="family-name"
                            required
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="register-form-group">

                    <label for="registerEmail">
                        Email
                    </label>

                    <input
                        type="email"
                        id="registerEmail"
                        name="email"
                        value="<?= htmlspecialchars($registerEmail) ?>"
                        autocomplete="email"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="register-form-group">

                    <label for="registerPassword">
                        Password
                    </label>

                    <div class="register-password-wrapper">

                        <input
                            type="password"
                            id="registerPassword"
                            name="password"
                            minlength="12"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="register-password-toggle"
                            data-password-target="registerPassword"
                            aria-label="Show password"
                        >
                            <i class="fa-regular fa-eye-slash"></i>
                        </button>

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="register-form-group">

                    <label for="registerConfirmPassword">
                        Confirm Password
                    </label>

                    <div class="register-password-wrapper">

                        <input
                            type="password"
                            id="registerConfirmPassword"
                            name="confirm_password"
                            minlength="12"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="register-password-toggle"
                            data-password-target="registerConfirmPassword"
                            aria-label="Show password"
                        >
                            <i class="fa-regular fa-eye-slash"></i>
                        </button>

                    </div>

                </div>


                <!-- TERMS -->

                <label class="register-terms">

                    <input
                        type="checkbox"
                        name="terms"
                        value="1"
                        required
                    >

                    <span>
                        I agree to the
                        <a href="<?= BASE_URL ?>/terms.php">
                            Terms of Service
                        </a>
                        and
                        <a href="<?= BASE_URL ?>/privacy-policy.php">
                            Privacy Policy
                        </a>
                    </span>

                </label>


                <!-- REGISTER BUTTON -->

                <button
                    type="submit"
                    class="register-submit-button"
                >
                    Register
                </button>

            </form>


            <!-- LOGIN -->

            <p class="register-login-text">

                Already have an account?

                <a href="#" id="backToLoginModal">
                    Login here
                </a>

            </p>

        </section>

    </div>

    <!-- =====================================
         FOOTER
    ====================================== -->

    <?php include("./includes/footer.php"); ?>


    <!-- =====================================
         JAVASCRIPT
    ====================================== -->

    <script src="./assets/js/login-modal.js"></script>
    <script src="./assets/js/main.js"></script>

</body>

</html>