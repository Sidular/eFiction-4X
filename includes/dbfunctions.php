<?php
// ----------------------------------------------------------------------
// eFiction v3 - Modernized Database Loader (PHP 7.4/8.x compatible)
// ----------------------------------------------------------------------
// Replaces the legacy mysql_* / mysqli_* selection logic with a single
// PDO-ready adapter. MySQLi is still supported, but the deprecated
// ext/mysql driver is removed entirely.
// ----------------------------------------------------------------------

if (!defined("_BASEDIR")) {
    die("Fatal error: _BASEDIR is not defined.");
}

if (extension_loaded("pdo_mysql")) {
    include_once(_BASEDIR . "includes/pdo_functions.php");
} else if (function_exists("mysqli_connect") || extension_loaded("mysqli")) {
    include_once(_BASEDIR . "includes/mysqli_functions.php");
} else {
    include(_BASEDIR . "languages/en.php"); // Because we haven't selected a language setting yet
    die(_FATALERROR . _NODBFUNCTIONALITY);
}
