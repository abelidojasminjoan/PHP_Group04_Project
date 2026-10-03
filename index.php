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
        c.category_name

    FROM products p

    INNER JOIN categories c
        ON p.category_id = c.id

    WHERE p.status = 'active'
      AND c.status = 'active'

    ORDER BY p.created_at DESC

    LIMIT 3
";


$productResult = $conn->query($sql);


/* =========================================
   CHECK QUERY
========================================= */

if (!$productResult) {

    error_log(
        'Homepage product query failed: '
        . $conn->error
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

                <!-- SECTION HEADER -->

                <div class="bestsellers-header">

                    <div class="bestsellers-heading">

                        <p class="section-eyebrow">
                            PUREVIA BESTSELLERS
                        </p>

                        <h2>
                            Your Clear Path to Better Skin.
                        </h2>

                    </div>


                    <a
                        href="./shop.php"
                        class="shop-all-link"
                    >
                        <span>SHOP ALL</span>

                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>

                </div>


                <!-- =============================
                     PRODUCT GRID
                ============================== -->

                <div class="product-grid">

                    <?php if (
                        $productResult &&
                        $productResult->num_rows > 0
                    ): ?>

                        <?php while (
                            $product = $productResult->fetch_assoc()
                        ): ?>

                            <article class="product-card">

                                <!-- PRODUCT IMAGE -->

                                <a
                                    href="./product.php?id=<?= (int) $product['id'] ?>"
                                    class="product-image-wrapper"
                                >

                                    <?php if (!empty($product['image'])): ?>

                                        <img
                                            src="./uploads/products/<?= htmlspecialchars($product['image']) ?>"
                                            alt="<?= htmlspecialchars($product['product_name']) ?>"
                                            class="product-image"
                                        >

                                    <?php else: ?>

                                        <img
                                            src="./assets/images/products/default-product.jpg"
                                            alt="<?= htmlspecialchars($product['product_name']) ?>"
                                            class="product-image"
                                        >

                                    <?php endif; ?>


                                    <span class="product-badge">

                                        <?= htmlspecialchars(
                                            strtoupper($product['category_name'])
                                        ) ?>

                                    </span>

                                </a>


                                <!-- PRODUCT DETAILS -->

                                <div class="product-details">


                                    <!-- STOCK STATUS -->

                                    <div class="product-rating">

                                        <?php if (
                                            (int) $product['stock_quantity'] > 0
                                        ): ?>

                                            <span>
                                                IN STOCK
                                            </span>

                                        <?php else: ?>

                                            <span>
                                                OUT OF STOCK
                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <!-- NAME + PRICE -->

                                    <div class="product-title-row">

                                        <a
                                            href="./product.php?id=<?= (int) $product['id'] ?>"
                                            class="product-name"
                                        >
                                            <?= htmlspecialchars(
                                                $product['product_name']
                                            ) ?>
                                        </a>


                                        <span class="product-price">

                                            ₱<?= number_format(
                                                (float) $product['price'],
                                                2
                                            ) ?>

                                        </span>

                                    </div>


                                    <!-- DESCRIPTION -->

                                    <p class="product-description">

                                        <?= htmlspecialchars(
                                            $product['description'] ?? ''
                                        ) ?>

                                    </p>

                                </div>

                            </article>

                        <?php endwhile; ?>


                    <?php else: ?>

                        <p>No products are currently available.</p>

                    <?php endif; ?>

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