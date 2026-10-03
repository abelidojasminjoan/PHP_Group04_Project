<footer class="footer">

    <!-- =====================================
         TOP
    ====================================== -->

    <div class="footer-top">

        <!-- LOGO / BRAND -->

        <div class="footer-logo">

            <div class="footer-brand-name">

                <i class="fa-solid fa-leaf"></i>

                <h2>PUREVIA</h2>

            </div>

            <p>
                Gentle, effective skincare made simple.
                Clear ingredients, recommended skin types,
                and easy guidance for every routine.
            </p>

        </div>


        <!-- =====================================
             GUEST MENU
        ====================================== -->

        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] == 'guest'): ?>

            <div class="footer-menu">

                <a href="./login.php">
                    Login
                </a>

                <a href="./register.php">
                    Register
                </a>

            </div>

        <?php endif; ?>

    </div>


    <!-- =====================================
         FOOTER GRID
    ====================================== -->

    <div class="footer-grid">


        <!-- =================================
             COLUMN 1 - SHOP
        ================================== -->

        <div class="footer-column">

            <h3>SHOP</h3>

            <a href="./shop.php">
                All Products
            </a>

            <a href="./shop.php?category=cleansers">
                Cleansers
            </a>

            <a href="./shop.php?category=toners">
                Toners
            </a>

            <a href="./shop.php?category=serums">
                Serums
            </a>

            <a href="./shop.php?category=moisturizers">
                Moisturizers
            </a>

            <a href="./shop.php?category=sunscreens">
                Sunscreens
            </a>

        </div>


        <!-- =================================
             COLUMN 2 - DISCOVER
        ================================== -->

        <div class="footer-column">

            <h3>DISCOVER</h3>

            <a href="./skin-type-finder.php">
                Skin Type Finder
            </a>

            <a href="./skin-match.php">
                Skin Matching
            </a>

            <?php if (
                isset($_SESSION['role']) &&
                $_SESSION['role'] == 'customer'
            ): ?>

                <a href="./account/skin-profile.php">
                    My Skin Profile
                </a>

            <?php endif; ?>

            <a href="./shop.php">
                Product Ingredients
            </a>

            <a href="./about.php">
                About PureVia
            </a>

        </div>


        <!-- =================================
             COLUMN 3 - QUICK LINKS
        ================================== -->

        <div class="footer-column">

            <h3>QUICK LINKS</h3>


            <!-- ADMIN -->

            <?php if (
                isset($_SESSION['role']) &&
                $_SESSION['role'] == 'admin'
            ): ?>

                <a href="./admin/dashboard.php">
                    Dashboard
                </a>

                <a href="./admin/products/index.php">
                    Products
                </a>

                <a href="./admin/categories/index.php">
                    Categories
                </a>

                <a href="./admin/orders/index.php">
                    Orders
                </a>

                <a href="./admin/payments/index.php">
                    Payments
                </a>


            <!-- STAFF -->

            <?php elseif (
                isset($_SESSION['role']) &&
                $_SESSION['role'] == 'staff'
            ): ?>

                <a href="./staff/dashboard.php">
                    Dashboard
                </a>

                <a href="./staff/products/index.php">
                    Products
                </a>

                <a href="./staff/inventory/index.php">
                    Inventory
                </a>

                <a href="./staff/orders/index.php">
                    Orders
                </a>


            <!-- CUSTOMER -->

            <?php elseif (
                isset($_SESSION['role']) &&
                $_SESSION['role'] == 'customer'
            ): ?>

                <a href="./index.php">
                    Home
                </a>

                <a href="./shop.php">
                    Shop
                </a>

                <a href="./account/profile.php">
                    My Account
                </a>

                <a href="./my-orders.php">
                    My Orders
                </a>

                <a href="./cart.php">
                    My Bag
                </a>


            <!-- GUEST -->

            <?php else: ?>

                <a href="./index.php">
                    Home
                </a>

                <a href="./shop.php">
                    Shop
                </a>

                <a href="./login.php">
                    Login
                </a>

                <a href="./register.php">
                    Register
                </a>

            <?php endif; ?>

        </div>


        <!-- =================================
             COLUMN 4 - SUPPORT
        ================================== -->

        <div class="footer-column">

            <h3>SUPPORT</h3>

            <a href="./about.php">
                About Us
            </a>

            <a href="./contact.php">
                Contact
            </a>

            <a href="./faq.php">
                FAQs
            </a>

            <a href="./shipping.php">
                Shipping & Returns
            </a>

            <a href="./privacy-policy.php">
                Privacy Policy
            </a>

            <a href="./terms.php">
                Terms & Conditions
            </a>

        </div>

    </div>


    <!-- =====================================
         SOCIALS
    ====================================== -->

    <div class="footer-socials">

        <a href="#">
            Instagram
        </a>

        <a href="#">
            Pinterest
        </a>

        <a href="#">
            TikTok
        </a>

    </div>


    <!-- =====================================
         COPYRIGHT / LEGAL
    ====================================== -->

    <div class="footer-bottom">

        <p>
            &copy; <?= date('Y') ?> PureVia.
            All rights reserved.
        </p>


        <div class="footer-legal">

            <a href="./privacy-policy.php">
                Privacy
            </a>

            <span>·</span>

            <a href="./terms.php">
                Terms
            </a>

            <span>·</span>

            <button
                type="button"
                class="cookie-button"
            >
                Cookies
            </button>

            <span>·</span>

            <span>Philippines</span>

        </div>

    </div>

</footer>