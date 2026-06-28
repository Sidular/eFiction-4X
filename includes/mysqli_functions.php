<?php
// ----------------------------------------------------------------------
// eFiction v3 - MySQLi Database Adapter (PHP 7.4/8.x compatible)
// ----------------------------------------------------------------------
// Replaces the legacy mysqli_functions.php wrapper. The old code used an
// invalid property name ($dbconnect->mysqli_errno) and relied on raw SQL
// concatenation. This version:
//   * Fixes the invalid property reference to use $dbconnect->errno / error
//   * Adds a dbprepare() / dbexecute() stub for incremental migration
//   * Continues to use real_escape_string for legacy compatibility
// ----------------------------------------------------------------------

if (!function_exists("dbconnect")) { // just in case

    function dbconnect($dbhost, $dbuser, $dbpass, $dbname)
    {
        $mysql_access = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        if ($mysql_access->connect_error) {
            include(_BASEDIR . "languages/en.php"); // Because we haven't got a language set yet.
            die(_FATALERROR . " " . _NOTCONNECTED . " (" . $mysql_access->connect_errno . ") " . htmlspecialchars($mysql_access->connect_error));
        }
        $mysql_access->set_charset("utf8mb4");
        $mysql_access->query("SET SESSION sql_mode = 'MYSQL40'");
        return $mysql_access;
    }

    function dbquery($query)
    {
        global $debug, $headerSent, $dbconnect;
        if ($debug && $headerSent) {
            echo "<!-- " . htmlspecialchars($query) . " -->\n";
        }
        $result = $dbconnect->query($query);
        if ($result === false) {
            accessDenied(_FATALERROR . (isADMIN ? "Query: " . htmlspecialchars($query) . "<br />Error: (" . $dbconnect->errno . ") " . htmlspecialchars($dbconnect->error) : ""));
        }
        return $result;
    }

    /**
     * Prepare a statement. MySQLi does not support ? positional placeholders
     * in the same way PDO does, so this returns the statement object for
     * binding/execution. For best results, use the PDO adapter if available.
     *
     * @param string $query SQL statement with ? placeholders.
     * @return mysqli_stmt|false
     */
    function dbprepare($query)
    {
        global $debug, $headerSent, $dbconnect;

        if ($debug && $headerSent) {
            echo "<!-- prepare: " . htmlspecialchars($query) . " -->\n";
        }

        $stmt = $dbconnect->prepare($query);
        if ($stmt === false) {
            accessDenied(_FATALERROR . (isADMIN ? "Prepare: " . htmlspecialchars($query) . "<br />Error: (" . $dbconnect->errno . ") " . htmlspecialchars($dbconnect->error) : ""));
        }
        return $stmt;
    }

    /**
     * Execute a MySQLi prepared statement. Bind parameters before calling.
     *
     * Example:
     *   $stmt = dbprepare("SELECT * FROM fanfiction_stories WHERE sid = ?");
     *   $stmt->bind_param("i", $sid);
     *   dbexecute($stmt);
     *   $story = dbassoc($stmt);
     *
     * @param mysqli_stmt|false $stmt
     * @param array $params Ignored for MySQLi; use bind_param() instead.
     * @return mysqli_stmt|false
     */
    function dbexecute($stmt, array $params = [])
    {
        global $debug, $headerSent;

        if (!$stmt) {
            return false;
        }

        if ($debug && $headerSent) {
            echo "<!-- execute -->\n";
        }

        if ($stmt->execute() === false) {
            accessDenied(_FATALERROR . (isADMIN ? "Execute error: (" . $stmt->errno . ") " . htmlspecialchars($stmt->error) : ""));
            return false;
        }

        return $stmt;
    }

    function dbnumrows($query)
    {
        global $debug, $dbconnect;
        if ($query === false && $debug) {
            echo "<!-- dbnumrows " . htmlspecialchars($dbconnect->error) . " -->\n";
        }
        return $query->num_rows;
    }

    function dbassoc($query)
    {
        global $debug, $dbconnect;
        if ($query === false && $debug) {
            echo "<!-- dbassoc " . htmlspecialchars($dbconnect->error) . " -->\n";
        }
        return $query->fetch_assoc();
    }

    function dbinsertid($tablename = 0)
    {
        global $dbconnect;
        return $dbconnect->insert_id;
    }

    function dbrow($query)
    {
        global $debug, $dbconnect;
        if ($query === false && $debug) {
            echo "<!-- dbrow " . htmlspecialchars($dbconnect->error) . " -->\n";
        }
        return $query->fetch_row();
    }

    function dbclose()
    {
        global $dbconnect;
        $dbconnect->close();
    }

    // Used to escape text being put into the database.
    function escapestring($str)
    {
        global $dbconnect;
        if (!is_array($str)) {
            return $dbconnect->real_escape_string($str);
        }
        return array_map('escapestring', $str);
    }

}
