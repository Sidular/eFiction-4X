<?php
// ----------------------------------------------------------------------
// eFiction v3 - Password hashing helpers (PHP 7.4/8.x compatible)
// ----------------------------------------------------------------------
// Replaces legacy MD5-based password hashing with PHP's native
// password_hash() / password_verify() API (bcrypt). Includes a backward-
// compatibility shim so existing MD5 hashes can still be verified during
// a transition period, while new/changed passwords are upgraded.
//
// IMPORTANT: After a successful login with an MD5 hash, call
//   upgradePasswordHash($user, $uid, $password)
// to re-hash the password using bcrypt. This removes the weak MD5 hash
// over time.
// ----------------------------------------------------------------------

if (!defined("_CHARSET")) exit();

if (!function_exists("hashPassword")) {

    /**
     * Hash a plaintext password for storage.
     *
     * @param string $password
     * @return string
     */
    function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a plaintext password against a stored hash.
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    function verifyPassword($password, $hash)
    {
        if (empty($hash)) {
            return false;
        }

        // Modern bcrypt / argon2 hash.
        if (password_verify($password, $hash)) {
            return true;
        }

        // Legacy MD5 fallback (hex only, 32 chars) for migration.
        if (preg_match('/^[a-f0-9]{32}$/i', $hash) && md5($password) === $hash) {
            return true;
        }

        return false;
    }

    /**
     * Check whether a hash needs rehashing (e.g., MD5 or weak bcrypt cost).
     *
     * @param string $hash
     * @return bool
     */
    function passwordNeedsRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * Upgrade an MD5 password hash to bcrypt after a successful login.
     * This must be called while the plaintext password is still available.
     *
     * @param string $tablePrefix  e.g., TABLEPREFIX
     * @param string $uidField      e.g., uid
     * @param string $uid
     * @param string $password
     * @return void
     */
    function upgradePasswordHash($tablePrefix, $uidField, $uid, $password)
    {
        $stmt = dbprepare("UPDATE " . $tablePrefix . "fanfiction_authors SET password = ? WHERE " . $uidField . " = ?");
        dbexecute($stmt, [hashPassword($password), $uid]);
    }

}
