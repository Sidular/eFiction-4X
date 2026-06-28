<?php

/**
 * eFiction Installer Backend
 *
 * Self-contained installer logic that does not rely on the application
 * autoloader or configuration. Designed to work on shared hosting and
 * standard LAMP/LEMP stacks.
 */

declare(strict_types=1);

namespace eFiction\Install;

use PDO;
use PDOException;
use Throwable;

final class Installer
{
    public const VERSION = '4.0.0';
    public const MIN_PHP = '8.3.0';
    public const REQUIRED_EXTENSIONS = ['pdo', 'pdo_mysql', 'mbstring', 'session', 'json', 'fileinfo', 'openssl'];
    public const RECOMMENDED_EXTENSIONS = ['gd', 'intl', 'zip'];

    private string $root;
    private string $configPath;
    private string $lockPath;
    private string $schemaPath;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/\\');
        $this->configPath = $this->root . '/config.php';
        $this->lockPath = $this->root . '/install/install.lock';
        $this->schemaPath = $this->root . '/install/schema.sql';
    }

    /**
     * Determine whether the application is already installed.
     */
    public function isInstalled(): bool
    {
        return file_exists($this->configPath) || file_exists($this->lockPath);
    }

    /**
     * Run a full system requirements check.
     *
     * @return array<string, array{ok: bool, required: bool, value: string, message: string}>
     */
    public function checkSystem(): array
    {
        $checks = [];

        $phpVersion = PHP_VERSION;
        $checks['php_version'] = [
            'ok' => version_compare($phpVersion, self::MIN_PHP, '>='),
            'required' => true,
            'value' => $phpVersion,
            'message' => 'PHP ' . self::MIN_PHP . ' or newer is required.',
        ];

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);
            $checks['ext_' . $ext] = [
                'ok' => $loaded,
                'required' => true,
                'value' => $loaded ? 'Loaded' : 'Missing',
                'message' => "The PHP extension '{$ext}' is required.",
            ];
        }

        foreach (self::RECOMMENDED_EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);
            $checks['ext_' . $ext] = [
                'ok' => $loaded,
                'required' => false,
                'value' => $loaded ? 'Loaded' : 'Missing',
                'message' => "The PHP extension '{$ext}' is recommended for full functionality.",
            ];
        }

        $checks['config_writable'] = [
            'ok' => $this->canWrite($this->configPath),
            'required' => true,
            'value' => $this->canWrite($this->configPath) ? 'Writable' : 'Not writable',
            'message' => 'The web server must be able to write config.php in the site root.',
        ];

        $storageDirs = [
            'storage_writable' => $this->root . '/storage',
            'storage_cache_writable' => $this->root . '/storage/cache',
            'storage_logs_writable' => $this->root . '/storage/logs',
            'storage_stories_writable' => $this->root . '/storage/stories',
            'storage_images_writable' => $this->root . '/storage/images',
        ];

        foreach ($storageDirs as $key => $path) {
            $checks[$key] = [
                'ok' => $this->ensureWritable($path),
                'required' => true,
                'value' => is_writable($path) ? 'Writable' : 'Not writable',
                'message' => "The web server must be able to write to '{$path}'.",
            ];
        }

        $checks['install_lock_writable'] = [
            'ok' => $this->canWrite($this->lockPath),
            'required' => false,
            'value' => $this->canWrite($this->lockPath) ? 'Writable' : 'Not writable',
            'message' => 'The installer can write a lock file to prevent accidental reinstallation.',
        ];

        return $checks;
    }

    /**
     * Check whether a path can be written by the web server.
     */
    public function canWrite(string $path): bool
    {
        if (is_dir($path)) {
            return is_writable($path);
        }

        if (file_exists($path)) {
            return is_writable($path);
        }

        $dir = dirname($path);
        return is_dir($dir) && is_writable($dir);
    }

    /**
     * Ensure a directory exists and is writable, creating it if needed.
     */
    public function ensureWritable(string $path): bool
    {
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        if (!is_dir($path)) {
            return false;
        }

        if (!is_writable($path)) {
            @chmod($path, 0755);
        }

        return is_writable($path);
    }

    /**
     * Test a database connection. Optionally create the database if it does not exist.
     *
     * @return array{ok: bool, message: string, pdo: PDO|null}
     */
    public function testDatabase(array $db, bool $createIfMissing = false): array
    {
        $host = $db['host'] ?? 'localhost';
        $database = $db['database'] ?? '';
        $user = $db['user'] ?? '';
        $password = $db['password'] ?? '';
        $charset = $db['charset'] ?? 'utf8mb4';

        if ($database === '') {
            return ['ok' => false, 'message' => 'Database name is required.', 'pdo' => null];
        }

        if ($user === '') {
            return ['ok' => false, 'message' => 'Database user is required.', 'pdo' => null];
        }

        $dsn = sprintf('mysql:host=%s;charset=%s', $host, $charset);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $password, $options);
            $pdo->exec("SET NAMES {$charset} COLLATE {$charset}_unicode_ci");
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => 'Could not connect to the database server: ' . $e->getMessage(), 'pdo' => null];
        }

        try {
            $stmt = $pdo->prepare('SELECT 1 FROM information_schema.schemata WHERE schema_name = ?');
            $stmt->execute([$database]);
            $exists = (bool) $stmt->fetch();

            if (!$exists) {
                if ($createIfMissing) {
                    try {
                        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
                    } catch (PDOException $e) {
                        return [
                            'ok' => false,
                            'message' => 'Database does not exist and could not be created: ' . $e->getMessage(),
                            'pdo' => null,
                        ];
                    }
                } else {
                    return [
                        'ok' => false,
                        'message' => 'Database does not exist. Create it first or enable "Create database" below.',
                        'pdo' => null,
                    ];
                }
            }

            $pdo->exec("USE `{$database}`");
            return ['ok' => true, 'message' => 'Database connection successful.', 'pdo' => $pdo];
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => 'Database error: ' . $e->getMessage(), 'pdo' => null];
        }
    }

    /**
     * Install the database schema.
     */
    public function installSchema(PDO $pdo, string $prefix): void
    {
        if (!file_exists($this->schemaPath)) {
            throw new \RuntimeException('Schema file not found: ' . $this->schemaPath);
        }

        $schema = file_get_contents($this->schemaPath);
        if ($schema === false) {
            throw new \RuntimeException('Could not read schema file.');
        }

        $schema = str_replace('{prefix}', $prefix, $schema);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec($schema);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Create the administrator account.
     */
    public function createAdmin(PDO $pdo, string $prefix, array $admin): int
    {
        $penname = trim($admin['penname'] ?? '');
        $email = trim($admin['email'] ?? '');
        $password = $admin['password'] ?? '';
        $realName = trim($admin['realname'] ?? '');

        if ($penname === '' || $email === '' || $password === '') {
            throw new \InvalidArgumentException('Admin penname, email, and password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Admin email is not valid.');
        }

        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Admin password must be at least 8 characters.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new \RuntimeException('Password hashing failed.');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO {$prefix}authors (penname, realname, email, password, level, validated, date) VALUES (?, ?, ?, ?, ?, 1, NOW())"
        );
        $stmt->execute([$penname, $realName, $email, $hash, 3]);
        $uid = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO {$prefix}authorprefs (uid, level) VALUES (?, 3)");
        $stmt->execute([$uid]);

        return $uid;
    }

    /**
     * Insert default site settings into the database.
     */
    public function insertSettings(PDO $pdo, string $prefix, array $site, string $siteKey): void
    {
        $settings = [
            'sitekey' => $siteKey,
            'tableprefix' => $prefix,
            'siteemail' => $site['email'],
            'sitetitle' => $site['title'],
            'siteurl' => rtrim($site['url'], '/'),
            'language' => $site['language'] ?? 'en',
            'skin' => 'default',
            'storiespath' => $this->root . '/storage/stories',
            'imagespath' => $this->root . '/storage/images',
            'maintenance' => 0,
            'regvalidate' => 0,
            'store' => 1,
            'characters' => 1,
            'reviewsallowed' => 1,
            'anonreviews' => 1,
            'rateonly' => 0,
            'captcha' => 0,
            'allowed_tags' => '<p><br><a><strong><em><u><ul><ol><li><blockquote><h1><h2><h3><h4>',
            'disallowed_tags' => '',
            'newsstories' => 0,
            'timezone' => $site['timezone'] ?? 'UTC',
            'version' => self::VERSION,
        ];

        $columns = implode(', ', array_keys($settings));
        $placeholders = implode(', ', array_fill(0, count($settings), '?'));
        $stmt = $pdo->prepare("INSERT INTO {$prefix}settings ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($settings));
    }

    /**
     * Write the application configuration file.
     */
    public function writeConfig(array $db, array $site, string $siteKey): bool
    {
        $config = [
            'db' => [
                'host' => $db['host'],
                'database' => $db['database'],
                'user' => $db['user'],
                'password' => $db['password'],
                'charset' => $db['charset'],
                'prefix' => $db['prefix'],
            ],
            'site' => [
                'key' => $siteKey,
                'url' => rtrim($site['url'], '/'),
                'title' => $site['title'],
                'email' => $site['email'],
                'language' => $site['language'] ?? 'en',
                'timezone' => $site['timezone'] ?? 'UTC',
                'maintenance' => false,
                'stories_path' => $this->root . '/storage/stories',
                'images_path' => $this->root . '/storage/images',
            ],
            'mail' => [
                'method' => $site['mail_method'] ?? 'mail',
                'from' => $site['email'],
                'smtp' => [
                    'host' => $site['smtp_host'] ?? '',
                    'port' => (int) ($site['smtp_port'] ?? 587),
                    'secure' => $site['smtp_secure'] ?? 'tls',
                    'auth' => (bool) ($site['smtp_auth'] ?? false),
                    'username' => $site['smtp_username'] ?? '',
                    'password' => $site['smtp_password'] ?? '',
                ],
            ],
            'session' => [
                'name' => 'efiction_session',
                'lifetime' => 86400,
            ],
            'security' => [
                'csrf_token_name' => 'csrf_token',
                'password_cost' => 12,
            ],
        ];

        $export = var_export($config, true);
        $contents = "<?php\n\n/**\n * eFiction configuration file.\n * Generated by the installer on " . date('Y-m-d H:i:s') . ".\n */\n\ndeclare(strict_types=1);\n\nreturn {$export};\n";

        return file_put_contents($this->configPath, $contents) !== false;
    }

    /**
     * Write a lock file to prevent accidental reinstallation.
     */
    public function writeLock(): bool
    {
        return file_put_contents($this->lockPath, "Installed on " . date('Y-m-d H:i:s') . "\n") !== false;
    }

    /**
     * Perform a full installation with the provided data.
     *
     * @return array{ok: bool, message: string, details: array<string, mixed>}
     */
    public function install(array $data): array
    {
        if ($this->isInstalled()) {
            return ['ok' => false, 'message' => 'eFiction is already installed.', 'details' => []];
        }

        $checks = $this->checkSystem();
        foreach ($checks as $check) {
            if ($check['required'] && !$check['ok']) {
                return [
                    'ok' => false,
                    'message' => 'System requirement not met: ' . $check['message'],
                    'details' => ['requirement' => $check],
                ];
            }
        }

        $db = $data['db'] ?? [];
        $site = $data['site'] ?? [];
        $admin = $data['admin'] ?? [];

        $validation = $this->validateInput($db, $site, $admin);
        if (!$validation['ok']) {
            return $validation;
        }

        $test = $this->testDatabase($db, (bool) ($db['create'] ?? false));
        if (!$test['ok']) {
            return ['ok' => false, 'message' => $test['message'], 'details' => []];
        }

        $pdo = $test['pdo'];
        $prefix = preg_replace('/[^a-z0-9_]/i', '', $db['prefix'] ?? 'fanfiction_');
        if ($prefix === '') {
            $prefix = 'fanfiction_';
        }

        $siteKey = bin2hex(random_bytes(20));

        try {
            $this->installSchema($pdo, $prefix);
            $this->insertSettings($pdo, $prefix, $site, $siteKey);
            $uid = $this->createAdmin($pdo, $prefix, $admin);

            $this->ensureWritable($this->root . '/storage/stories');
            $this->ensureWritable($this->root . '/storage/images');
            $this->ensureWritable($this->root . '/storage/cache');
            $this->ensureWritable($this->root . '/storage/logs');

            if (!$this->writeConfig($db, $site, $siteKey)) {
                return ['ok' => false, 'message' => 'Could not write config.php. Please check file permissions.', 'details' => []];
            }

            $this->writeLock();

            return [
                'ok' => true,
                'message' => 'eFiction has been installed successfully.',
                'details' => [
                    'admin_uid' => $uid,
                    'site_key' => $siteKey,
                    'table_prefix' => $prefix,
                ],
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Installation failed: ' . $e->getMessage(), 'details' => []];
        }
    }

    /**
     * Validate installer form input.
     *
     * @return array{ok: bool, message: string, details: array<string, mixed>}
     */
    public function validateInput(array $db, array $site, array $admin): array
    {
        $errors = [];

        if (trim($db['database'] ?? '') === '') {
            $errors[] = 'Database name is required.';
        }
        if (trim($db['user'] ?? '') === '') {
            $errors[] = 'Database user is required.';
        }

        if (trim($site['title'] ?? '') === '') {
            $errors[] = 'Site title is required.';
        }
        $email = trim($site['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid site email is required.';
        }
        if (trim($site['url'] ?? '') === '') {
            $errors[] = 'Site URL is required.';
        }

        $adminPenname = trim($admin['penname'] ?? '');
        $adminEmail = trim($admin['email'] ?? '');
        $adminPassword = $admin['password'] ?? '';
        $adminConfirm = $admin['password_confirm'] ?? '';

        if ($adminPenname === '') {
            $errors[] = 'Admin penname is required.';
        }
        if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid admin email is required.';
        }
        if (strlen($adminPassword) < 8) {
            $errors[] = 'Admin password must be at least 8 characters.';
        }
        if ($adminPassword !== $adminConfirm) {
            $errors[] = 'Admin passwords do not match.';
        }

        if (!empty($errors)) {
            return ['ok' => false, 'message' => implode(' ', $errors), 'details' => ['errors' => $errors]];
        }

        return ['ok' => true, 'message' => 'Input validated.', 'details' => []];
    }

    /**
     * Generate a CSRF token for the installer session.
     */
    public function csrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['installer_csrf'])) {
            $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['installer_csrf'];
    }

    /**
     * Verify a CSRF token.
     */
    public function verifyCsrf(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['installer_csrf']) && hash_equals($_SESSION['installer_csrf'], $token);
    }

    /**
     * Sanitize a string for safe output.
     */
    public static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
