<?php
// ----------------------------------------------------------------------
// eFiction v3 - Deprecated MySQL Adapter (ext/mysql removed in PHP 7.0)
// ----------------------------------------------------------------------
// The original ext/mysql extension was removed from PHP 7.0 and is no
// longer available. This file now acts as a safety guard: it refuses to
// load and tells the administrator to migrate to PDO or MySQLi.
// ----------------------------------------------------------------------

if (!defined("_BASEDIR")) {
    die("Fatal error: _BASEDIR is not defined.");
}

include(_BASEDIR . "languages/en.php"); // Because we haven't selected a language setting yet

$fatal = _FATALERROR . " " . _NODBFUNCTIONALITY
    . " The legacy MySQL extension (ext/mysql) is unavailable in PHP 7.0+. "
    . "Please make sure the PDO MySQL or MySQLi extension is installed and reload.";

// If this file is included, the site cannot run. Fail loudly so the admin
// notices immediately rather than getting undefined function errors later.
die($fatal);
