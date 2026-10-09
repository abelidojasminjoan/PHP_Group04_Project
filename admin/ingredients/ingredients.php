<?php

session_start();

$pageTitle = "Ingredients";


/* =========================================
   TEMPORARY ADMIN SESSION
   Remove when login is connected
========================================= */

$_SESSION['first_name'] =
    $_SESSION['first_name'] ?? 'Ava';

$_SESSION['last_name'] =
    $_SESSION['last_name'] ?? 'Santos';

$_SESSION['email'] =
    $_SESSION['email'] ?? 'admin@purevia.com';



/* =========================================================
   DATABASE CONNECTION
========================================================= */

$host = "localhost";
$username = "root";
$password = "";
$database = "purevia_db";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );
} catch (PDOException $e) {

    error_log("Database connection failed: " . $e->getMessage());

    exit("Unable to connect to the database.");
}


/* =========================================================
   INGREDIENT CRUD (ADD / EDIT / DELETE)
========================================================= */

if (empty($_SESSION['ingredient_csrf'])) {
    $_SESSION['ingredient_csrf'] = bin2hex(random_bytes(32));
}

$ingredientError = '';
$ingredientSuccess = $_SESSION['ingredient_success'] ?? '';

unset($_SESSION['ingredient_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ADMIN AUTHORIZATION FOR DATABASE CHANGES */

    if (
        !isset($_SESSION['user_id'], $_SESSION['role_id']) ||
        (int) $_SESSION['role_id'] !== 1
    ) {
        http_response_code(403);
        exit("Access denied. Administrator login required.");
    }

    /* VERIFY CSRF TOKEN */

    $postedToken = $_POST['csrf_token'] ?? null;

    if (
        !is_string($postedToken) ||
        !hash_equals(
            $_SESSION['ingredient_csrf'],
            $postedToken
        )
    ) {
        http_response_code(403);
        exit("Invalid security token.");
    }

    $action = $_POST['action'] ?? '';

    try {

        /* =============================================
           ADD / EDIT VALIDATION
        ============================================= */

        if ($action === 'add' || $action === 'edit') {

            $name = $_POST['ingredient_name'] ?? null;
            $description = $_POST['description'] ?? null;
            $status = $_POST['status'] ?? null;

            if (
                !is_string($name) ||
                !is_string($description) ||
                !is_string($status)
            ) {
                throw new Exception("Invalid ingredient details.");
            }

            $name = trim($name);
            $description = trim($description);

            if (
                $name === '' ||
                mb_strlen($name, 'UTF-8') > 150
            ) {
                throw new Exception(
                    "Ingredient name is required (maximum 150 characters)."
                );
            }

            if (!in_array(
                $status,
                ['active', 'inactive'],
                true
            )) {
                throw new Exception("Invalid ingredient status.");
            }

            $id = null;

            if ($action === 'edit') {

                $id = filter_var(
                    $_POST['ingredient_id'] ?? null,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );

                if (!$id) {
                    throw new Exception("Invalid ingredient ID.");
                }
            }

            /* CHECK DUPLICATE INGREDIENT NAME */

            $duplicate = $pdo->prepare(
                "SELECT id
                 FROM ingredients
                 WHERE ingredient_name = ?
                 AND id <> ?
                 LIMIT 1"
            );

            $duplicate->execute([
                $name,
                $id ?? 0
            ]);

            if ($duplicate->fetch()) {
                throw new Exception(
                    "Ingredient name already exists."
                );
            }

            /* =============================================
               ADD INGREDIENT
            ============================================= */

            if ($action === 'add') {

                $stmt = $pdo->prepare(
                    "INSERT INTO ingredients
                     (ingredient_name, description, status)
                     VALUES (?, ?, ?)"
                );

                $stmt->execute([
                    $name,
                    $description,
                    $status
                ]);

                $_SESSION['ingredient_success'] =
                    "Ingredient added successfully.";
            } else {

                /* =========================================
                   EDIT INGREDIENT
                ========================================= */

                $exists = $pdo->prepare(
                    "SELECT id
                     FROM ingredients
                     WHERE id = ?"
                );

                $exists->execute([$id]);

                if (!$exists->fetch()) {
                    throw new Exception("Ingredient not found.");
                }

                $stmt = $pdo->prepare(
                    "UPDATE ingredients
                     SET ingredient_name = ?,
                         description = ?,
                         status = ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $name,
                    $description,
                    $status,
                    $id
                ]);

                $_SESSION['ingredient_success'] =
                    "Ingredient updated successfully.";
            }
        } elseif ($action === 'delete') {

            /* =============================================
               DELETE INGREDIENT
            ============================================= */

            $id = filter_var(
                $_POST['ingredient_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if (!$id) {
                throw new Exception("Invalid ingredient ID.");
            }

            $pdo->beginTransaction();

            /* LOCK INGREDIENT RECORD */

            $lock = $pdo->prepare(
                "SELECT id
                 FROM ingredients
                 WHERE id = ?
                 FOR UPDATE"
            );

            $lock->execute([$id]);

            if (!$lock->fetch()) {
                throw new Exception("Ingredient not found.");
            }

            /* CHECK EXISTING RELATIONSHIPS */

            $links = $pdo->prepare(
                "SELECT
                    (
                        SELECT COUNT(*)
                        FROM product_ingredients
                        WHERE ingredient_id = ?
                    )
                    +
                    (
                        SELECT COUNT(*)
                        FROM user_avoided_ingredients
                        WHERE ingredient_id = ?
                    )"
            );

            $links->execute([$id, $id]);

            if ((int) $links->fetchColumn() > 0) {

                throw new Exception(
                    "This ingredient is used by products or customers. Set it to inactive instead."
                );
            }

            /* DELETE UNLINKED INGREDIENT */

            $stmt = $pdo->prepare(
                "DELETE FROM ingredients WHERE id = ?"
            );

            $stmt->execute([$id]);

            $pdo->commit();

            $_SESSION['ingredient_success'] =
                "Ingredient deleted successfully.";
        } else {

            throw new Exception("Invalid action.");
        }

        header("Location: ingredients.php", true, 303);
        exit;
    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Ingredient CRUD error: " . $e->getMessage());

        $ingredientError = $e->getCode() === '23000'
            ? "This ingredient name already exists or is linked to other records."
            : "A database error occurred. Please try again.";
    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $ingredientError = $e->getMessage();
    }
}


/* =========================================================
   FETCH INGREDIENTS FROM DATABASE
========================================================= */

try {

    $sql = "
        SELECT
            i.id,
            i.ingredient_name AS name,
            i.description,
            i.status,

            COALESCE(
                GROUP_CONCAT(
                    DISTINCT c.category_name
                    ORDER BY c.category_name
                    SEPARATOR ', '
                ),
                ''
            ) AS common_in

        FROM ingredients i

        LEFT JOIN product_ingredients pi
            ON pi.ingredient_id = i.id

        LEFT JOIN products p
            ON p.id = pi.product_id

        LEFT JOIN categories c
            ON c.id = p.category_id

        GROUP BY
            i.id,
            i.ingredient_name,
            i.description,
            i.status

        ORDER BY i.ingredient_name ASC
    ";

    $statement = $pdo->prepare($sql);

    $statement->execute();

    $ingredients = $statement->fetchAll();
} catch (PDOException $e) {

    error_log("Ingredients query failed: " . $e->getMessage());

    exit("Unable to load ingredients.");
}


/* =========================================================
   TOTAL INGREDIENTS
========================================================= */

$totalIngredients = count($ingredients);


?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($pageTitle) ?> | PureVia
    </title>


    <!-- GOOGLE FONTS -->

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@400;500;600&display=swap">


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- SIDEBAR CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/sidemenu.css">


    <!-- INGREDIENT CSS -->

    <link rel="stylesheet" href="../../assets/css/ingredient.css">

    <!-- REUSABLE FORM CSS -->

    <link rel="stylesheet" href="../../assets/css/form2.css">

</head>


<body>


    <!-- =====================================
         SIDEBAR
    ====================================== -->

    <?php include("../../includes/sidemenu.php"); ?>


    <!-- =====================================
         ADMIN LAYOUT
    ====================================== -->

    <div class="admin-layout">


        <!-- =================================
             TOP BAR
        ================================== -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Ingredients
            </p>

        </header>


        <!-- =================================
             MAIN
        ================================== -->

        <main class="ingredients-main">


            <!-- =================================
                 PAGE HEADER
            ================================== -->

            <section class="ingredients-page-header">


                <div class="ingredients-heading">

                    <h1>
                        Ingredients
                    </h1>

                    <p>
                        <?= number_format($totalIngredients) ?>
                        records
                    </p>

                </div>



                <!-- ADD INGREDIENT -->

                <button
                    type="button"
                    id="openAddIngredientModal"
                    class="add-ingredient-button">

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Add Ingredient
                    </span>

                </button>


            </section>



            <!-- SUCCESS MESSAGE -->

            <?php if ($ingredientSuccess !== ''): ?>

                <div class="form-alert form-alert-success">
                    <?= htmlspecialchars(
                        $ingredientSuccess,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if ($ingredientError !== ''): ?>

                <div class="form-alert form-alert-error">
                    <?= htmlspecialchars(
                        $ingredientError,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>




            <!-- =================================
                 INGREDIENT CARD
            ================================== -->

            <section class="ingredients-card">


                <!-- =================================
                     SEARCH + FILTER
                ================================== -->

                <div class="ingredients-toolbar">


                    <!-- SEARCH -->

                    <div class="ingredients-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="ingredientSearch"
                            placeholder="Search ingredients..."
                            autocomplete="off">

                    </div>


                    <!-- STATUS FILTER -->

                    <div class="ingredient-filter-dropdown">

                        <i
                            class="fa-solid fa-filter ingredient-filter-icon"></i>

                        <select
                            id="ingredientStatusFilter"
                            class="ingredient-filter-select"
                            aria-label="Filter ingredients by status">

                            <option value="all">
                                All Status
                            </option>

                            <option value="active">
                                Active
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>

                        </select>

                        <i
                            class="fa-solid fa-chevron-down ingredient-filter-arrow"></i>

                    </div>


                </div>


                <!-- =================================
                     TABLE
                ================================== -->

                <div class="ingredients-table-wrapper">


                    <table class="ingredients-table">


                        <!-- TABLE HEADER -->

                        <thead>

                            <tr>

                                <th>NAME</th>

                                <th>DESCRIPTION</th>

                                <th>COMMON IN</th>

                                <th>STATUS</th>

                                <th>ACTIONS</th>

                            </tr>

                        </thead>


                        <!-- TABLE BODY -->

                        <tbody>


                            <?php foreach ($ingredients as $ingredient): ?>


                                <?php

                                $status =
                                    strtolower(
                                        $ingredient['status']
                                    );

                                $searchData =
                                    strtolower(
                                        $ingredient['name']
                                            . ' '
                                            . ($ingredient['description'] ?? '')
                                            . ' '
                                            . ($ingredient['common_in'] ?? '')
                                    );

                                ?>


                                <tr
                                    class="ingredient-row"

                                    data-status="<?= htmlspecialchars(
                                                        $status
                                                    ) ?>"

                                    data-search="<?= htmlspecialchars(
                                                        $searchData
                                                    ) ?>">


                                    <!-- NAME -->

                                    <td>

                                        <span class="ingredient-name">

                                            <?= htmlspecialchars(
                                                $ingredient['name']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DESCRIPTION -->

                                    <td>

                                        <span class="ingredient-description">

                                            <?= htmlspecialchars(
                                                $ingredient['description'] ?? ''
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- COMMON IN -->

                                    <td>

                                        <span class="ingredient-common">

                                            <?= htmlspecialchars(
                                                $ingredient['common_in']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                ingredient-status
                                                status-<?= htmlspecialchars(
                                                            $status
                                                        ) ?>
                                            ">

                                            <?= strtoupper(
                                                htmlspecialchars(
                                                    $ingredient['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="ingredient-actions">



                                            <!-- EDIT -->

                                            <button
                                                type="button"
                                                class="ingredient-action-button edit-ingredient-button"
                                                title="Edit ingredient"
                                                aria-label="Edit <?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-ingredient-id="<?= (int) $ingredient['id'] ?>"
                                                data-ingredient-name="<?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-ingredient-description="<?= htmlspecialchars($ingredient['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-ingredient-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">

                                                <i class="fa-regular fa-pen-to-square"></i>

                                            </button>



                                            <!-- DELETE -->

                                            <button
                                                type="button"
                                                class="ingredient-action-button delete-ingredient-button"

                                                title="Delete ingredient"

                                                aria-label="Delete <?= htmlspecialchars(
                                                                        $ingredient['name']
                                                                    ) ?>"

                                                data-ingredient-id="<?= htmlspecialchars(
                                                                        $ingredient['id']
                                                                    ) ?>"

                                                data-ingredient-name="<?= htmlspecialchars(
                                                                            $ingredient['name']
                                                                        ) ?>">

                                                <i class="fa-regular fa-trash-can"></i>

                                            </button>


                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            <!-- NO RESULTS -->

                            <tr
                                id="noIngredientsFound"
                                class="no-ingredients-row"
                                style="display: none;">

                                <td colspan="5">

                                    <div class="no-ingredients-message">

                                        <i class="fa-solid fa-flask"></i>

                                        <p>
                                            No ingredients found.
                                        </p>

                                    </div>

                                </td>

                            </tr>


                        </tbody>


                    </table>


                </div>


            </section>


        </main>


    </div>




    <!-- =====================================================
     ADD / EDIT INGREDIENT MODAL
===================================================== -->

    <div
        class="form-modal"
        id="ingredientModal"
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="ingredientModalTitle">

        <div
            class="form-modal-backdrop"
            data-close-ingredient-modal></div>

        <div class="form-modal-dialog">

            <form
                method="POST"
                action="ingredients.php"
                class="purevia-form"
                id="ingredientForm">

                <!-- CSRF TOKEN -->

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['ingredient_csrf'], ENT_QUOTES, 'UTF-8') ?>">

                <input
                    type="hidden"
                    name="action"
                    id="ingredientAction"
                    value="add">

                <input
                    type="hidden"
                    name="ingredient_id"
                    id="ingredientId">

                <!-- MODAL HEADER -->

                <div class="form-modal-header">

                    <div>

                        <h2 id="ingredientModalTitle">
                            Add Ingredient
                        </h2>

                        <p>
                            Enter the ingredient details below.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="form-modal-close"
                        data-close-ingredient-modal
                        aria-label="Close form">

                        <i class="fa-solid fa-xmark"></i>

                    </button>

                </div>

                <!-- MODAL BODY -->

                <div class="form-modal-body">

                    <!-- INGREDIENT NAME -->

                    <div class="form-group">

                        <label for="ingredientName">

                            Ingredient Name

                            <span class="form-required">*</span>

                        </label>

                        <input
                            type="text"
                            name="ingredient_name"
                            id="ingredientName"
                            class="form-control"
                            placeholder="Enter ingredient name"
                            maxlength="150"
                            required>

                    </div>

                    <!-- DESCRIPTION -->

                    <div class="form-group">

                        <label for="ingredientDescription">
                            Description
                        </label>

                        <textarea
                            name="description"
                            id="ingredientDescription"
                            class="form-control form-textarea"
                            placeholder="Enter ingredient description"
                            rows="4"></textarea>

                    </div>

                    <!-- STATUS -->

                    <div class="form-group">

                        <label for="ingredientStatus">

                            Status

                            <span class="form-required">*</span>

                        </label>

                        <div class="form-select-wrapper">

                            <select
                                name="status"
                                id="ingredientStatus"
                                class="form-control form-select"
                                required>

                                <option value="active">
                                    Active
                                </option>

                                <option value="inactive">
                                    Inactive
                                </option>

                            </select>

                            <i class="fa-solid fa-chevron-down"></i>

                        </div>

                    </div>

                </div>

                <!-- MODAL FOOTER -->

                <div class="form-modal-footer">

                    <button
                        type="button"
                        class="form-cancel-button"
                        data-close-ingredient-modal>
                        Cancel
                    </button>

                    <button
                        type="submit"
                        id="submitIngredientButton"
                        class="form-submit-button">
                        Add Ingredient
                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- =====================================================
     DELETE INGREDIENT FORM
===================================================== -->

    <form
        method="POST"
        action="ingredients.php"
        id="deleteIngredientForm"
        hidden>

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['ingredient_csrf'], ENT_QUOTES, 'UTF-8') ?>">

        <input
            type="hidden"
            name="action"
            value="delete">

        <input
            type="hidden"
            name="ingredient_id"
            id="deleteIngredientId">

    </form>



    <!-- SIDEBAR JS -->

    <script src="../../assets/js/sidemenu.js"></script>


    <!-- INGREDIENT JS -->

    <script src="../../assets/js/ingredients.js"></script>


</body>

</html>