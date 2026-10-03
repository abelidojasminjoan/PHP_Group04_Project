<?php

/* =========================================================
   PUREVIA DATABASE CONFIGURATION
========================================================= */


/* =========================================================
   DATABASE SETTINGS
========================================================= */

$dbHost = 'localhost';

$dbName = 'purevia_db';

$dbUsername = 'root';

$dbPassword = '';

$dbCharset = 'utf8mb4';


/* =========================================================
   CREATE CONNECTION
========================================================= */

$conn = new mysqli(
    $dbHost,
    $dbUsername,
    $dbPassword,
    $dbName
);


/* =========================================================
   CHECK CONNECTION
========================================================= */

if ($conn->connect_error) {

    error_log(
        'PureVia database connection failed: '
        . $conn->connect_error
    );

    die(
        'Unable to connect to the database.'
    );
}


/* =========================================================
   CHARACTER SET
========================================================= */

if (!$conn->set_charset($dbCharset)) {

    error_log(
        'Unable to set database charset: '
        . $conn->error
    );

}