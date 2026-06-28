<?php
// ----------------------------------------------------------------------
// eFiction v3 - PDO Database Adapter (PHP 7.4/8.x compatible)
// ----------------------------------------------------------------------
// Provides a drop-in replacement for the legacy dbquery()/dbassoc()/etc.
// wrappers. The new layer supports prepared statements via dbprepare()
// and dbexecute(), while still preserving the old API for incremental
// migration.
//
// IMPORTANT: New code MUST use parameterized queries:
//     $stmt = dbprepare("SELECT * FROM fanfiction_stories WHERE sid = ?");
//     dbexecute($stmt, [$sid]);
//     $story = dbassoc($stmt);
// ----------------------------------------------------------------------

if (!function_exists("dbconnect")) {

    function dbconnect($dbhost, $dbuser, $dbpass, $dbname)
    {
        global $dbconnect;

        // Build DSN. Default to MySQL over TCP; allow a leading slash to mean unix_socket.
        if (substr($dbhost, 0, 1) === "/") {
            $dsn = "mysql:unix_socket=" . $dbhost . ";dbname=" . $dbname . ";charset=utf8mb4";
        } else {
            $dsn = "mysql:host=" . $dbhost . ";dbname=" . $dbname . ";charset=utf8mb4";
        }

        try {
            $dbconnect = new PDO($dsn, $dbuser, $dbpass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            include(_BASEDIR . "languages/en.php"); // Because we haven't got a language set yet.
            die(_FATALERROR . " " . _NOTCONNECTED . " (PDO: " . htmlspecialchars($e->getMessage()) . ")");
        }

        // Keep the legacy strict mode used by the MySQLi adapter for compatibility.
        dbquery("SET SESSION sql_mode = 'MYSQL40'");

        return $dbconnect;
    }

    function dbquery($query)
    {
        global $debug, $headerSent, $dbconnect;

        if ($debug && $headerSent) {
            echo "<!-- " . htmlspecialchars($query) . " -->\n";
        }

        try {
            $result = $dbconnect->query($query);
        } catch (PDOException $e) {
            accessDenied(_FATALERROR . (isADMIN ? "Query: " . htmlspecialchars($query) . "<br />Error: (" . $e->getCode() . ") " . htmlspecialchars($e->getMessage()) : ""));
            return false;
        }
        return $result;
    }

    /**
     * Prepare a statement for safe execution. Use this for all new code.
     *
     * @param string $query SQL with ? placeholders or named parameters.
     * @return PDOStatement|false
     */
    function dbprepare($query)
    {
        global $debug, $headerSent, $dbconnect;

        if ($debug && $headerSent) {
            echo "<!-- prepare: " . htmlspecialchars($query) . " -->\n";
        }

        try {
            return $dbconnect->prepare($query);
        } catch (PDOException $e) {
            accessDenied(_FATALERROR . (isADMIN ? "Prepare: " . htmlspecialchars($query) . "<br />Error: (" . $e->getCode() . ") " . htmlspecialchars($e->getMessage()) : ""));
            return false;
        }
    }

    /**
     * Execute a prepared statement and return it for fetching.
     *
     * @param PDOStatement|false $stmt
     * @param array $params
     * @return PDOStatement|false
     */
    function dbexecute($stmt, array $params = [])
    {
        global $debug, $headerSent;

        if (!$stmt) {
            return false;
        }

        if ($debug && $headerSent) {
            echo "<!-- execute: " . htmlspecialchars(json_encode($params)) . " -->\n";
        }

        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            accessDenied(_FATALERROR . (isADMIN ? "Execute error: (" . $e->getCode() . ") " . htmlspecialchars($e->getMessage()) : ""));
            return false;
        }
        return $stmt;
    }

    function dbnumrows($query)
    {
        global $debug, $dbconnect;

        if ($query === false && $debug) {
            echo "<!-- dbnumrows error -->\n";
            return 0;
        }
        return $query->rowCount();
    }

    function dbassoc($query)
    {
        global $debug, $dbconnect;

        if ($query === false && $debug) {
            echo "<!-- dbassoc error -->\n";
            return false;
        }
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    function dbrow($query)
    {
        global $debug, $dbconnect;

        if ($query === false && $debug) {
            echo "<!-- dbrow error -->\n";
            return false;
        }
        return $query->fetch(PDO::FETCH_NUM);
    }

    function dbinsertid($tablename = 0)
    {
        global $dbconnect;
        return $dbconnect->lastInsertId($tablename ? $tablename : null);
    }

    function dbclose()
    {
        global $dbconnect;
        $dbconnect = null;
    }

    /**
     * Escape a string for safe use in legacy queries.
     * PREFER dbprepare/dbexecute for all new code.
     */
    function escapestring($str)
    {
        global $dbconnect;
        if (!is_array($str)) {
            return substr($dbconnect->quote($str), 1, -1);
        }
        return array_map('escapestring', $str);
    }

}
