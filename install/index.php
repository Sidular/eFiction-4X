<?php

declare(strict_types=1);

/**
 * eFiction installer entry point.
 * Self-contained: does not rely on the application autoloader or config.
 */

const EF_VERSION = '4.0.0';

if (file_exists(__DIR__ . '/../config.php')) {
    header('Location: /');
    exit;
}

$step = $_POST['step'] ?? '1';
$errors = [];
$success = false;
$baseUrl = rtrim((string) ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');

function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function input(string $name, $default = ''): string
{
    return e((string) ($_POST[$name] ?? $default));
}

function ensureWritable(string $path): bool
{
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
    return is_dir($path) && is_writable($path);
}

function installSchema(PDO $pdo, string $prefix): void
{
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $schema = str_replace('{prefix}', $prefix, $schema);

    // Split into individual statements while preserving DELIMITER blocks
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec($schema);
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

function createAdmin(PDO $pdo, string $prefix, array $data): void
{
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO {$prefix}authors (penname, email, password, level, validated) VALUES (?, ?, ?, ?, 1)"
    );
    $stmt->execute([$data['penname'], $data['email'], $hash, 3]);
    $uid = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare(
        "INSERT INTO {$prefix}authorprefs (uid, level) VALUES (?, 3)"
    );
    $stmt->execute([$uid]);
}

function writeConfig(array $config): bool
{
    $path = __DIR__ . '/../config.php';
    $export = var_export($config, true);
    $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn {$export};\n";
    return file_put_contents($path, $contents) !== false;
}

function testConnection(array $db): ?PDO
{
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['database'], $db['charset']);
    try {
        $pdo = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET NAMES {$db['charset']} COLLATE {$db['charset']}_unicode_ci");
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '2') {
    $db = [
        'host'     => trim($_POST['db_host'] ?? 'localhost'),
        'database' => trim($_POST['db_database'] ?? ''),
        'user'     => trim($_POST['db_user'] ?? ''),
        'password' => $_POST['db_password'] ?? '',
        'charset'  => 'utf8mb4',
        'prefix'   => trim($_POST['db_prefix'] ?? 'fanfiction_'),
    ];
    $site = [
        'title' => trim($_POST['site_title'] ?? 'eFiction Archive'),
        'email' => trim($_POST['site_email'] ?? ''),
        'url'   => trim($_POST['site_url'] ?? $baseUrl),
    ];
    $admin = [
        'penname'  => trim($_POST['admin_penname'] ?? ''),
        'email'    => trim($_POST['admin_email'] ?? ''),
        'password' => $_POST['admin_password'] ?? '',
    ];
    $confirm = $_POST['admin_password_confirm'] ?? '';

    if ($db['database'] === '') {
        $errors[] = 'Database name is required.';
    }
    if ($site['title'] === '') {
        $errors[] = 'Site title is required.';
    }
    if ($site['email'] === '' || !filter_var($site['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid site email is required.';
    }
    if ($admin['penname'] === '') {
        $errors[] = 'Admin penname is required.';
    }
    if ($admin['email'] === '' || !filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid admin email is required.';
    }
    if (strlen($admin['password']) < 8) {
        $errors[] = 'Admin password must be at least 8 characters.';
    }
    if ($admin['password'] !== $confirm) {
        $errors[] = 'Admin passwords do not match.';
    }

    $pdo = null;
    if (empty($errors)) {
        $pdo = testConnection($db);
        if (!$pdo) {
            $errors[] = 'Could not connect to the database with the provided credentials.';
        }
    }

    if (empty($errors)) {
        try {
            installSchema($pdo, $db['prefix']);
            createAdmin($pdo, $db['prefix'], $admin);

            $settings = [
                'sitekey'    => bin2hex(random_bytes(20)),
                'tableprefix'=> $db['prefix'],
                'siteemail'  => $site['email'],
                'sitetitle'  => $site['title'],
                'siteurl'    => $site['url'],
                'language'   => 'en',
                'skin'       => 'default',
                'storiespath'=> __DIR__ . '/../storage/stories',
                'imagespath' => __DIR__ . '/../storage/images',
                'maintenance'=> 0,
                'regvalidate'=> 0,
                'store'      => 1,
                'characters' => 1,
                'reviewsallowed'=> 1,
                'anonreviews'=> 1,
                'rateonly'   => 0,
                'captcha'    => 0,
                'allowed_tags'=> '<p><br><a><strong><em><u><ul><ol><li><blockquote><h1><h2><h3><h4>',
                'disallowed_tags'=> '',
                'newsstories'=> 0,
                'timezone'   => 'UTC',
                'version'    => EF_VERSION,
            ];

            $stmt = $pdo->prepare(
                "INSERT INTO {$db['prefix']}settings (" . implode(', ', array_keys($settings)) . ') VALUES (' . implode(', ', array_fill(0, count($settings), '?')) . ')'
            );
            $stmt->execute(array_values($settings));

            $root = __DIR__ . '/..';
            ensureWritable("{$root}/storage/stories");
            ensureWritable("{$root}/storage/images");
            ensureWritable("{$root}/storage/cache");
            ensureWritable("{$root}/storage/logs");

            $config = [
                'db' => $db,
                'site' => [
                    'key'        => $settings['sitekey'],
                    'url'        => $settings['siteurl'],
                    'title'      => $settings['sitetitle'],
                    'email'      => $settings['siteemail'],
                    'language'   => $settings['language'],
                    'timezone'   => $settings['timezone'],
                    'maintenance'=> false,
                    'stories_path' => $settings['storiespath'],
                    'images_path'  => $settings['imagespath'],
                ],
                'mail' => [
                    'method'  => 'mail',
                    'from'    => $settings['siteemail'],
                    'smtp' => [
                        'host'     => '',
                        'port'     => 587,
                        'secure'   => 'tls',
                        'auth'     => false,
                        'username' => '',
                        'password' => '',
                    ],
                ],
                'session' => [
                    'name' => 'efiction_session',
                    'lifetime' => 86400,
                ],
                'security' => [
                    'csrf_token_name' => 'csrf_token',
                    'password_cost'   => 12,
                ],
            ];

            if (!writeConfig($config)) {
                $errors[] = 'Could not write config.php. Please check file permissions.';
            } else {
                $success = true;
            }
        } catch (Throwable $e) {
            $errors[] = 'Installation error: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eFiction Installer</title>
    <link rel="stylesheet" href="/install/assets/install.css">
</head>
<body>
    <div class="install-container">
        <header class="install-header">
            <h1>eFiction <?= e(EF_VERSION) ?> Installer</h1>
        </header>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <h2>Installation Complete</h2>
                <p>eFiction has been installed successfully.</p>
                <p><strong>For security, please delete or rename the <code>install</code> directory.</strong></p>
                <p>
                    <a href="/" class="btn btn-primary">Go to site</a>
                    <a href="/admin" class="btn">Admin panel</a>
                </p>
            </div>
        <?php else: ?>
            <form method="post" action="/install/" class="install-form">
                <input type="hidden" name="step" value="2">

                <section>
                    <h2>Database Settings</h2>
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="<?= input('db_host', 'localhost') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="db_database">Database Name</label>
                        <input type="text" id="db_database" name="db_database" value="<?= input('db_database', 'efiction') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="db_user">Database User</label>
                        <input type="text" id="db_user" name="db_user" value="<?= input('db_user', '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="db_password">Database Password</label>
                        <input type="password" id="db_password" name="db_password" value="<?= input('db_password', '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="db_prefix">Table Prefix</label>
                        <input type="text" id="db_prefix" name="db_prefix" value="<?= input('db_prefix', 'fanfiction_') ?>" required>
                    </div>
                </section>

                <section>
                    <h2>Site Settings</h2>
                    <div class="form-group">
                        <label for="site_title">Site Title</label>
                        <input type="text" id="site_title" name="site_title" value="<?= input('site_title', 'eFiction Archive') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="site_email">Site Email</label>
                        <input type="email" id="site_email" name="site_email" value="<?= input('site_email', '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="site_url">Site URL</label>
                        <input type="url" id="site_url" name="site_url" value="<?= input('site_url', $baseUrl) ?>" required>
                    </div>
                </section>

                <section>
                    <h2>Admin Account</h2>
                    <div class="form-group">
                        <label for="admin_penname">Penname</label>
                        <input type="text" id="admin_penname" name="admin_penname" value="<?= input('admin_penname', '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="admin_email">Email</label>
                        <input type="email" id="admin_email" name="admin_email" value="<?= input('admin_email', '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="admin_password">Password</label>
                        <input type="password" id="admin_password" name="admin_password" minlength="8" required>
                    </div>
                    <div class="form-group">
                        <label for="admin_password_confirm">Confirm Password</label>
                        <input type="password" id="admin_password_confirm" name="admin_password_confirm" minlength="8" required>
                    </div>
                </section>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Install eFiction</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
