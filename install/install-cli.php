<?php

/**
 * eFiction Headless CLI Installer
 *
 * Run this from the command line to install eFiction without using the web
 * wizard. Useful for automated local setups, CI environments, and Docker.
 *
 * Example:
 *   php install/install-cli.php \
 *       --db-host=localhost \
 *       --db-database=efiction \
 *       --db-user=efiction \
 *       --db-password=secret \
 *       --db-prefix=fanfiction_ \
 *       --site-title="My Archive" \
 *       --site-email=admin@example.com \
 *       --site-url=https://example.com \
 *       --admin-penname=admin \
 *       --admin-email=admin@example.com \
 *       --admin-password="StrongPass123!"
 *
 * All values can also be supplied via environment variables using the same
 * names in UPPER_SNAKE_CASE (e.g., DB_HOST, SITE_TITLE, ADMIN_PASSWORD).
 */

declare(strict_types=1);

require_once __DIR__ . '/Installer.php';

use eFiction\Install\Installer;

function envOrArg(string $key, array $argv, ?string $default = null): ?string
{
    $env = getenv($key);
    if ($env !== false && $env !== '') {
        return $env;
    }

    $cliKey = '--' . strtolower(str_replace('_', '-', $key)) . '=';
    foreach ($argv as $arg) {
        if (str_starts_with($arg, $cliKey)) {
            return substr($arg, strlen($cliKey));
        }
    }

    return $default;
}

function prompt(string $message, bool $secret = false): string
{
    if ($secret) {
        echo $message;
        system('stty -echo');
        $value = fgets(STDIN);
        system('stty echo');
        echo "\n";
    } else {
        echo $message;
        $value = fgets(STDIN);
    }

    return $value === false ? '' : trim($value);
}

$root = __DIR__ . '/..';
$installer = new Installer($root);

if ($installer->isInstalled()) {
    fwrite(STDERR, "eFiction is already installed. Aborting.\n");
    exit(1);
}

$checks = $installer->checkSystem();
$failed = [];
foreach ($checks as $check) {
    if ($check['required'] && !$check['ok']) {
        $failed[] = $check['message'];
    }
}

if ($failed !== []) {
    fwrite(STDERR, "System requirements not met:\n - " . implode("\n - ", $failed) . "\n");
    exit(1);
}

$dbHost = envOrArg('DB_HOST', $argv, 'localhost');
$dbDatabase = envOrArg('DB_DATABASE', $argv, 'efiction');
$dbUser = envOrArg('DB_USER', $argv, 'efiction');
$dbPassword = envOrArg('DB_PASSWORD', $argv, null);
$dbPrefix = envOrArg('DB_PREFIX', $argv, 'fanfiction_');

if ($dbPassword === null || $dbPassword === '') {
    $dbPassword = prompt('Database password: ', true);
}

$siteTitle = envOrArg('SITE_TITLE', $argv, 'eFiction Archive');
$siteEmail = envOrArg('SITE_EMAIL', $argv, 'admin@example.com');
$siteUrl = envOrArg('SITE_URL', $argv, null);
$siteLanguage = envOrArg('SITE_LANGUAGE', $argv, 'en');
$siteTimezone = envOrArg('SITE_TIMEZONE', $argv, 'UTC');
$mailMethod = envOrArg('MAIL_METHOD', $argv, 'mail');

if ($siteUrl === null) {
    $siteUrl = envOrArg('SITE_URL', $argv, 'http://localhost');
}

$adminPenname = envOrArg('ADMIN_PENNAME', $argv, 'admin');
$adminRealname = envOrArg('ADMIN_REALNAME', $argv, 'Administrator');
$adminEmail = envOrArg('ADMIN_EMAIL', $argv, 'admin@example.com');
$adminPassword = envOrArg('ADMIN_PASSWORD', $argv, null);

if ($adminPassword === null || $adminPassword === '') {
    $adminPassword = prompt('Admin password (min 8 chars): ', true);
}

$db = [
    'host' => $dbHost,
    'database' => $dbDatabase,
    'user' => $dbUser,
    'password' => $dbPassword,
    'charset' => 'utf8mb4',
    'prefix' => $dbPrefix,
    'create' => false,
    'auto_mode' => false,
    'admin_user' => '',
    'admin_password' => '',
];

$site = [
    'title' => $siteTitle,
    'email' => $siteEmail,
    'url' => rtrim($siteUrl, '/'),
    'language' => $siteLanguage,
    'timezone' => $siteTimezone,
    'mail_method' => $mailMethod,
    'smtp_host' => envOrArg('SMTP_HOST', $argv, ''),
    'smtp_port' => (int) envOrArg('SMTP_PORT', $argv, '587'),
    'smtp_secure' => envOrArg('SMTP_SECURE', $argv, 'tls'),
    'smtp_auth' => (bool) envOrArg('SMTP_AUTH', $argv, '0'),
    'smtp_username' => envOrArg('SMTP_USERNAME', $argv, ''),
    'smtp_password' => envOrArg('SMTP_PASSWORD', $argv, ''),
];

$admin = [
    'penname' => $adminPenname,
    'realname' => $adminRealname,
    'email' => $adminEmail,
    'password' => $adminPassword,
    'password_confirm' => $adminPassword,
];

echo "Testing database connection...\n";
$result = $installer->install(['db' => $db, 'site' => $site, 'admin' => $admin]);

if (!$result['ok']) {
    fwrite(STDERR, "Installation failed: " . $result['message'] . "\n");
    exit(1);
}

echo "Installation complete!\n";
echo " - Admin UID: " . ($result['details']['admin_uid'] ?? 'N/A') . "\n";
echo " - Site key: " . ($result['details']['site_key'] ?? 'N/A') . "\n";
echo " - Table prefix: " . ($result['details']['table_prefix'] ?? 'N/A') . "\n";
echo "\nYou can now visit the site or delete the install directory.\n";
