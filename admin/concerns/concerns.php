<?php

session_start();

$pageTitle = "Skin Concerns";


/* =========================================================
   ADMIN SESSION AUTHORIZATION
========================================================= */

if (
    !isset($_SESSION['user_id'], $_SESSION['role_id']) ||
    (int) $_SESSION['role_id'] !== 1
) {
    http_response_code(403);
    exit("Access denied. Administrator login required.");
}

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
   SKIN CONCERNS CRUD
========================================================= */

if (empty($_SESSION['concern_csrf'])) {
    $_SESSION['concern_csrf'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$successMessage = $_SESSION['concern_success'] ?? '';
unset($_SESSION['concern_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !is_string($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['concern_csrf'],
            $_POST['csrf_token']
        )
    ) {
        http_response_code(403);
        exit("Invalid security token.");
    }

    $action = $_POST['action'] ?? '';

    try {

        if ($action === 'add' || $action === 'edit') {

            $name = trim((string) ($_POST['concern_name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $status = $_POST['status'] ?? '';

            if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
                throw new Exception("Enter a valid concern name (maximum 100 characters).");
            }

            if (!in_array($status, ['active', 'inactive'], true)) {
                throw new Exception("Invalid concern status.");
            }

            $id = null;

            if ($action === 'edit') {
                $id = filter_var(
                    $_POST['concern_id'] ?? null,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );

                if (!$id) {
                    throw new Exception("Invalid concern ID.");
                }
            }

            $check = $pdo->prepare(
                "SELECT id FROM skin_concerns
                 WHERE concern_name = ?
                 AND id <> ?
                 LIMIT 1"
            );

            $check->execute([$name, $id ?? 0]);

            if ($check->fetch()) {
                throw new Exception("Skin concern already exists.");
            }

            if ($action === 'add') {

                $query = $pdo->prepare(
                    "INSERT INTO skin_concerns
                     (concern_name, description, status)
                     VALUES (?, ?, ?)"
                );

                $query->execute([$name, $description, $status]);

                $_SESSION['concern_success'] =
                    "Skin concern added successfully.";
            } else {

                $query = $pdo->prepare(
                    "UPDATE skin_concerns
                     SET concern_name = ?,
                         description = ?,
                         status = ?
                     WHERE id = ?"
                );

                $query->execute([$name, $description, $status, $id]);

                $_SESSION['concern_success'] =
                    "Skin concern updated successfully.";
            }
        } elseif ($action === 'delete') {

            $id = filter_var(
                $_POST['concern_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if (!$id) {
                throw new Exception("Invalid concern ID.");
            }

            // Protect product and customer skin-profile links.
            $check = $pdo->prepare(
                "SELECT
                    (SELECT COUNT(*) FROM product_concerns
                     WHERE concern_id = ?)
                    +
                    (SELECT COUNT(*) FROM skin_profile_concerns
                     WHERE concern_id = ?)"
            );

            $check->execute([$id, $id]);

            if ((int) $check->fetchColumn() > 0) {
                throw new Exception(
                    "This concern is in use. Set it to inactive instead."
                );
            }

            $query = $pdo->prepare(
                "DELETE FROM skin_concerns WHERE id = ?"
            );

            $query->execute([$id]);

            if ($query->rowCount() === 0) {
                throw new Exception("Skin concern not found.");
            }

            $_SESSION['concern_success'] =
                "Skin concern deleted successfully.";
        } else {
            throw new Exception("Invalid action.");
        }

        header("Location: concerns.php", true, 303);
        exit;
    } catch (PDOException $e) {

        error_log($e->getMessage());

        $errorMessage = $e->getCode() === '23000'
            ? "The concern already exists or is linked to other records."
            : "A database error occurred.";
    } catch (Exception $e) {

        $errorMessage = $e->getMessage();
    }
}


/* =========================================================
   FETCH SKIN CONCERNS FROM DATABASE
========================================================= */

try {

    $sql = "SELECT
                id,
                concern_name AS name,
                description,
                status
            FROM skin_concerns
            ORDER BY concern_name ASC";

    $statement = $pdo->prepare($sql);

    $statement->execute();

    $skinConcerns = $statement->fetchAll();
} catch (PDOException $e) {

    error_log("Skin concerns query failed: " . $e->getMessage());

    exit("Unable to load skin concerns.");
}


/* =========================================================
   TOTAL SKIN CONCERNS
========================================================= */

$totalConcerns = count($skinConcerns);

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


    <!-- SIDEBAR -->

    <link rel="stylesheet" href="../../assets/css/sidemenu.css">


    <!-- SKIN CONCERN CSS -->

    <link rel="stylesheet" href="../../assets/css/concerns.css">

    <!-- REUSABLE FORM CSS -->
    <link rel="stylesheet" href="../../assets/css/form2.css">

</head>


<body>


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php include("../../includes/sidemenu.php"); ?>


    <!-- =====================================================
         ADMIN LAYOUT
    ====================================================== -->

    <div class="admin-layout">


        <!-- =================================================
             TOP BAR
        ================================================== -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Skin Concerns
            </p>

        </header>


        <!-- =================================================
             MAIN
        ================================================== -->

        <main class="skin-concerns-main">


            <!-- =============================================
                 PAGE HEADER
            ============================================== -->

            <section class="skin-concerns-page-header">


                <div class="skin-concerns-heading">

                    <h1>
                        Skin Concerns
                    </h1>

                    <p>
                        <?= number_format($totalConcerns) ?>
                        records
                    </p>

                </div>


                <!-- ADD CONCERN -->

                <button
                    type="button"
                    id="openAddConcernModal"
                    class="add-concern-button">

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Add Concern
                    </span>

                </button>


            </section>

            <?php if ($successMessage !== ''): ?>

                <div class="form-alert form-alert-success">
                    <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>

                <div class="form-alert form-alert-error">
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>


            <!-- =============================================
                 CARD
            ============================================== -->

            <section class="skin-concerns-card">


                <!-- =========================================
                     SEARCH + FILTER
                ========================================== -->

                <div class="skin-concerns-toolbar">


                    <!-- SEARCH -->

                    <div class="skin-concerns-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="skinConcernSearch"
                            placeholder="Search skin concerns..."
                            autocomplete="off">

                    </div>


                    <!-- STATUS FILTER -->

                    <div class="skin-concern-filter-dropdown">

                        <i
                            class="fa-solid fa-filter skin-concern-filter-icon"></i>

                        <select
                            id="skinConcernStatusFilter"
                            class="skin-concern-filter-select"
                            aria-label="Filter skin concerns by status">

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
                            class="fa-solid fa-chevron-down skin-concern-filter-arrow"></i>

                    </div>


                </div>


                <!-- =========================================
                     TABLE
                ========================================== -->

                <div class="skin-concerns-table-wrapper">


                    <table class="skin-concerns-table">


                        <!-- TABLE HEADER -->

                        <thead>

                            <tr>

                                <th>NAME</th>

                                <th>DESCRIPTION</th>

                                <th>STATUS</th>

                                <th>ACTIONS</th>

                            </tr>

                        </thead>


                        <!-- TABLE BODY -->

                        <tbody>


                            <?php foreach ($skinConcerns as $concern): ?>


                                <?php

                                $status =
                                    strtolower(
                                        $concern['status']
                                    );


                                $searchData =
                                    strtolower(
                                        $concern['name']
                                            . ' '
                                            . ($concern['description'] ?? '')
                                    );


                                ?>


                                <tr
                                    class="skin-concern-row"

                                    data-status="<?= htmlspecialchars(
                                                        $status
                                                    ) ?>"

                                    data-search="<?= htmlspecialchars(
                                                        $searchData
                                                    ) ?>">


                                    <!-- NAME -->

                                    <td>

                                        <span class="skin-concern-name">

                                            <?= htmlspecialchars(
                                                $concern['name']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DESCRIPTION -->

                                    <td>

                                        <span class="skin-concern-description">


                                            <?= htmlspecialchars(
                                                $concern['description'] ?? ''
                                            ) ?>


                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                skin-concern-status
                                                status-<?= htmlspecialchars(
                                                            $status
                                                        ) ?>
                                            ">

                                            <?= strtoupper(
                                                htmlspecialchars(
                                                    $concern['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="skin-concern-actions">


                                            <!-- EDIT -->

                                            <button
                                                type="button"
                                                class="skin-concern-action-button edit-concern-button"
                                                title="Edit concern"
                                                aria-label="Edit <?= htmlspecialchars($concern['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-concern-id="<?= (int) $concern['id'] ?>"
                                                data-concern-name="<?= htmlspecialchars($concern['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-concern-description="<?= htmlspecialchars($concern['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-concern-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">

                                                <i class="fa-regular fa-pen-to-square"></i>

                                            </button>


                                            <!-- DELETE -->

                                            <button
                                                type="button"
                                                class="skin-concern-action-button delete-concern-button"

                                                title="Delete concern"

                                                aria-label="Delete <?= htmlspecialchars(
                                                                        $concern['name']
                                                                    ) ?>"

                                                data-concern-id="<?= htmlspecialchars(
                                                                        $concern['id']
                                                                    ) ?>"

                                                data-concern-name="<?= htmlspecialchars(
                                                                        $concern['name']
                                                                    ) ?>">

                                                <i class="fa-regular fa-trash-can"></i>

                                            </button>


                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            <!-- =================================
                                 NO RESULTS
                            ================================== -->

                            <tr
                                id="noSkinConcernsFound"
                                class="no-skin-concerns-row"
                                style="display: none;">

                                <td colspan="4">

                                    <div class="no-skin-concerns-message">

                                        <i class="fa-solid fa-magnifying-glass"></i>

                                        <p>
                                            No skin concerns found.
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
         ADD / EDIT SKIN CONCERN MODAL
    ====================================================== -->

    <div
        class="form-modal"
        id="concernModal"
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="concernModalTitle">

        <div
            class="form-modal-backdrop"
            data-close-concern-modal></div>

        <div class="form-modal-dialog">

            <form
                method="POST"
                action="concerns.php"
                class="purevia-form"
                id="concernForm">

                <!-- SECURITY TOKEN -->

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['concern_csrf'], ENT_QUOTES, 'UTF-8') ?>">

                <input
                    type="hidden"
                    name="action"
                    id="concernAction"
                    value="add">

                <input
                    type="hidden"
                    name="concern_id"
                    id="concernId">

                <!-- MODAL HEADER -->

                <div class="form-modal-header">

                    <div>

                        <h2 id="concernModalTitle">
                            Add Skin Concern
                        </h2>

                        <p>
                            Enter the skin concern details below.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="form-modal-close"
                        data-close-concern-modal
                        aria-label="Close form">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>

                <!-- MODAL BODY -->

                <div class="form-modal-body">

                    <!-- CONCERN NAME -->

                    <div class="form-group">

                        <label for="concernName">
                            Concern Name
                            <span class="form-required">*</span>
                        </label>

                        <input
                            type="text"
                            name="concern_name"
                            id="concernName"
                            class="form-control"
                            placeholder="Enter skin concern name"
                            maxlength="100"
                            required>

                    </div>

                    <!-- DESCRIPTION -->

                    <div class="form-group">

                        <label for="concernDescription">
                            Description
                        </label>

                        <textarea
                            name="description"
                            id="concernDescription"
                            class="form-control form-textarea"
                            placeholder="Enter skin concern description"
                            rows="4"></textarea>

                    </div>

                    <!-- STATUS -->

                    <div class="form-group">

                        <label for="concernStatus">
                            Status
                            <span class="form-required">*</span>
                        </label>

                        <div class="form-select-wrapper">

                            <select
                                name="status"
                                id="concernStatus"
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
                        data-close-concern-modal>
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="form-submit-button"
                        id="submitConcernButton">
                        Add Concern
                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- =====================================================
         DELETE SKIN CONCERN FORM
    ====================================================== -->

    <form
        method="POST"
        action="concerns.php"
        id="deleteConcernForm"
        hidden>

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['concern_csrf'], ENT_QUOTES, 'UTF-8') ?>">

        <input
            type="hidden"
            name="action"
            value="delete">

        <input
            type="hidden"
            name="concern_id"
            id="deleteConcernId">

    </form>



    <!-- SIDEBAR JS -->

    <script src="../../assets/js/sidemenu.js"></script>


    <!-- SKIN CONCERN JS -->

    <script src="../../assets/js/concerns.js"></script>


</body>

</html>