<?php

/* =========================================================
   PUREVIA HEADER
========================================================= */


/* =========================================================
   CURRENT PAGE
========================================================= */

$currentPage = basename($_SERVER['PHP_SELF']);


/* =========================================================
   USER SESSION
========================================================= */

$role = $_SESSION['role'] ?? 'guest';

$userName = $_SESSION['name'] ?? 'Guest';


/* =========================================================
   USER INITIAL
========================================================= */

$userInitial = '';

if ($role !== 'guest' && !empty($userName)) {

    $userInitial = strtoupper(
        substr($userName, 0, 1)
    );

}


/* =========================================================
   CART COUNT
========================================================= */

/*
 * Temporary value.
 * Later this will come from carts/cart_items.
 */

$cartCount = 0;

?>


<!-- =========================================
     PUREVIA HEADER
========================================== -->

<header class="site-header">


    <!-- =====================================
         ANNOUNCEMENT BAR
    ====================================== -->

    <div class="announcement-bar">

        <p>
            COMPLIMENTARY SHIPPING ON ORDERS OVER ₱75+
            YOUR CLEAR PATH TO BETTER SKIN
        </p>

    </div>


    <!-- =====================================
         MAIN NAVIGATION
    ====================================== -->

    <div class="navbar">


        <!-- =================================
             LEFT SIDE
        ================================== -->

        <div class="navbar-left">


            <!-- BRAND -->

            <a
                href="<?= BASE_URL ?>/index.php"
                class="brand"
            >

                <span class="brand-icon">
                    <i class="fa-solid fa-leaf"></i>
                </span>

                <span class="brand-name">
                    PUREVIA
                </span>

            </a>


            <!-- MAIN MENU -->

            <nav class="main-nav">

                <a
                    href="<?= BASE_URL ?>/shop.php"
                    class="<?= $currentPage === 'shop.php' ? 'active' : '' ?>"
                >
                    Shop
                </a>


                <a
                    href="<?= BASE_URL ?>/skin-match.php"
                    class="<?= $currentPage === 'skin-match.php' ? 'active' : '' ?>"
                >
                    Skin Match
                </a>

            </nav>

        </div>


        <!-- =================================
             RIGHT SIDE
        ================================== -->

        <div class="navbar-right">


            <!-- =================================
                 SEARCH
            ================================== -->

            <a
                href="<?= BASE_URL ?>/search.php"
                class="nav-icon"
                aria-label="Search"
                title="Search"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

            </a>


            <!-- =================================
                HEART / SKIN PROFILE
            ================================== -->

            <?php if ($role === 'customer'): ?>

                <a
                    href="<?= BASE_URL ?>/account/skin-profile.php"
                    class="nav-icon"
                    aria-label="Skin Profile"
                    title="Skin Profile"
                >
                    <i class="fa-regular fa-heart"></i>
                </a>

            <?php else: ?>

                <a
                    href="#"
                    class="nav-icon"
                    aria-label="Favorites"
                    title="Favorites"
                >
                    <i class="fa-regular fa-heart"></i>
                </a>

            <?php endif; ?>


            <!-- =================================
                CUSTOMER ACCOUNT
            ================================== -->

            <?php if ($role === 'customer'): ?>

                <div class="account-dropdown">

                    <!-- ACCOUNT TRIGGER -->
                    <button
                        type="button"
                        class="account-link account-dropdown-trigger"
                        id="accountDropdownTrigger"
                        aria-label="Account menu"
                        aria-expanded="false"
                        aria-controls="accountDropdownMenu"
                    >

                        <span class="account-avatar">
                            <?= htmlspecialchars($userInitial) ?>
                        </span>

                        <span class="account-name">
                            <?= htmlspecialchars($userName) ?>
                        </span>

                    </button>


                    <!-- ACCOUNT DROPDOWN -->
                    <div
                        class="account-dropdown-menu"
                        id="accountDropdownMenu"
                        aria-hidden="true"
                    >

                        <a
                            href="<?= BASE_URL ?>/account/profile.php"
                            class="account-dropdown-item"
                        >
                            <i class="fa-regular fa-user"></i>

                            <span>My Profile</span>
                        </a>


                        <a
                            href="<?= BASE_URL ?>/my-orders.php"
                            class="account-dropdown-item"
                        >
                            <i class="fa-solid fa-box"></i>

                            <span>My Orders</span>
                        </a>


                        <div class="account-dropdown-divider"></div>


                        <a
                            href="<?= BASE_URL ?>/actions/auth/logout.php"
                            class="account-dropdown-item account-dropdown-logout"
                        >
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>

                            <span>Sign Out</span>
                        </a>

                    </div>

                </div>

            <!-- =================================
                 ADMIN ACCOUNT
            ================================== -->

            <?php elseif ($role === 'admin'): ?>

                <a
                    href="<?= BASE_URL ?>/admin/dashboard.php"
                    class="account-link"
                    aria-label="Admin Dashboard"
                    title="Admin Dashboard"
                >

                    <span class="account-avatar">
                        <?= htmlspecialchars($userInitial) ?>
                    </span>

                    <span class="account-name">
                        Admin
                    </span>

                </a>


            <!-- =================================
                 STAFF ACCOUNT
            ================================== -->

            <?php elseif ($role === 'staff'): ?>

                <a
                    href="<?= BASE_URL ?>/staff/dashboard.php"
                    class="account-link"
                    aria-label="Staff Dashboard"
                    title="Staff Dashboard"
                >

                    <span class="account-avatar">
                        <?= htmlspecialchars($userInitial) ?>
                    </span>

                    <span class="account-name">
                        Staff
                    </span>

                </a>


            <!-- =================================
                GUEST ACCOUNT
            ================================== -->

            <?php else: ?>

                <button
                    type="button"
                    class="nav-icon guest-account-icon login-modal-trigger"
                    id="openLoginModal"
                    aria-label="Login"
                    title="Login"
                >
                    <i class="fa-regular fa-user"></i>
                </button>

            <?php endif; ?>


            <!-- =================================
                 BAG
            ================================== -->

            <a
                href="<?= BASE_URL ?>/cart.php"
                class="bag-link"
                aria-label="Shopping Bag"
                title="Shopping Bag"
            >

                <i class="fa-solid fa-bag-shopping"></i>

                <span>
                    Bag

                    <span class="bag-count">
                        (<?= (int) $cartCount ?>)
                    </span>
                </span>

            </a>


        </div>

    </div>

</header>