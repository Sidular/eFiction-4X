<?php

/**
 * eFiction Graphical Installer
 *
 * A multi-step, AJAX-enhanced installer that works on most hosting environments.
 * Falls back to traditional form posts when JavaScript is unavailable.
 */

declare(strict_types=1);

require_once __DIR__ . '/Installer.php';

use eFiction\Install\Installer;

$installer = new Installer(__DIR__ . '/..');

if ($installer->isInstalled()) {
    header('Location: /');
    exit;
}

$csrf = $installer->csrfToken();
// Always use the root domain for the site URL, never the /install path.
$baseUrl = rtrim((string) ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');

$action = $_POST['action'] ?? '';
$ajax = $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

function sendJson(array $data): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function input(string $name, $default = ''): string
{
    return \eFiction\Install\Installer::e((string) ($_POST[$name] ?? $default));
}

function inputRaw(string $name, $default = '')
{
    return $_POST[$name] ?? $default;
}

if ($action === 'check') {
    $checks = $installer->checkSystem();
    $requiredPass = true;
    foreach ($checks as $check) {
        if ($check['required'] && !$check['ok']) {
            $requiredPass = false;
            break;
        }
    }
    sendJson(['ok' => $requiredPass, 'checks' => $checks]);
}

if ($action === 'test_db') {
    $db = [
        'host' => trim(inputRaw('db_host', 'localhost')),
        'database' => trim(inputRaw('db_database', '')),
        'user' => trim(inputRaw('db_user', '')),
        'password' => inputRaw('db_password', ''),
        'charset' => 'utf8mb4',
        'create' => (bool) inputRaw('db_create', false),
        'auto_mode' => (bool) inputRaw('db_auto_mode', false),
        'admin_user' => trim(inputRaw('db_admin_user', '')),
        'admin_password' => inputRaw('db_admin_password', ''),
    ];
    $result = $installer->testDatabase($db, $db['create']);
    sendJson(['ok' => $result['ok'], 'message' => $result['message']]);
}

$installResult = null;

if ($action === 'install') {
    if (!$installer->verifyCsrf(inputRaw('csrf_token', ''))) {
        if ($ajax) {
            sendJson(['ok' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
        }
        $installResult = ['ok' => false, 'message' => 'Invalid security token. Please refresh the page and try again.'];
    } else {
        $db = [
            'host' => trim(inputRaw('db_host', 'localhost')),
            'database' => trim(inputRaw('db_database', '')),
            'user' => trim(inputRaw('db_user', '')),
            'password' => inputRaw('db_password', ''),
            'charset' => 'utf8mb4',
            'prefix' => trim(inputRaw('db_prefix', 'fanfiction_')),
            'create' => (bool) inputRaw('db_create', false),
            'auto_mode' => (bool) inputRaw('db_auto_mode', false),
            'admin_user' => trim(inputRaw('db_admin_user', '')),
            'admin_password' => inputRaw('db_admin_password', ''),
        ];

        $site = [
            'title' => trim(inputRaw('site_title', 'eFiction Archive')),
            'email' => trim(inputRaw('site_email', '')),
            'url' => rtrim(trim(inputRaw('site_url', $baseUrl)), '/'),
            'language' => trim(inputRaw('site_language', 'en')),
            'timezone' => trim(inputRaw('site_timezone', 'UTC')),
            'mail_method' => trim(inputRaw('mail_method', 'mail')),
            'smtp_host' => trim(inputRaw('smtp_host', '')),
            'smtp_port' => (int) inputRaw('smtp_port', 587),
            'smtp_secure' => trim(inputRaw('smtp_secure', 'tls')),
            'smtp_auth' => (bool) inputRaw('smtp_auth', false),
            'smtp_username' => trim(inputRaw('smtp_username', '')),
            'smtp_password' => inputRaw('smtp_password', ''),
        ];

        $admin = [
            'penname' => trim(inputRaw('admin_penname', '')),
            'realname' => trim(inputRaw('admin_realname', '')),
            'email' => trim(inputRaw('admin_email', '')),
            'password' => inputRaw('admin_password', ''),
            'password_confirm' => inputRaw('admin_password_confirm', ''),
        ];

        $result = $installer->install(['db' => $db, 'site' => $site, 'admin' => $admin]);
        if ($ajax) {
            sendJson($result);
        }
        $installResult = $result;
    }
}

$checks = $installer->checkSystem();
$requirementsPass = true;
foreach ($checks as $check) {
    if ($check['required'] && !$check['ok']) {
        $requirementsPass = false;
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eFiction <?= \eFiction\Install\Installer::e(\eFiction\Install\Installer::VERSION) ?> Installer</title>
    <link rel="stylesheet" href="/install/assets/install.css">
</head>
<body>
    <div class="install-wrapper">
        <div class="install-card">
            <header class="install-header">
                <div class="logo">eFiction</div>
                <h1>Installer</h1>
                <p class="version">Version <?= \eFiction\Install\Installer::e(\eFiction\Install\Installer::VERSION) ?></p>
            </header>

            <div id="global-alerts"></div>

            <div class="no-js-note">
                <strong>JavaScript disabled:</strong> You can still install eFiction using this form. All fields are shown below; scroll down to review and submit.
            </div>

            <?php if ($installResult): ?>
                <?php if ($installResult['ok']): ?>
                    <div class="alert alert-success">
                        <p><?= \eFiction\Install\Installer::e($installResult['message']) ?></p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <p><?= \eFiction\Install\Installer::e($installResult['message']) ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="wizard <?= ($installResult && $installResult['ok']) ? 'install-complete' : '' ?>" id="wizard">
                <div class="steps">
                    <div class="step-indicator active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-label">Requirements</span>
                    </div>
                    <div class="step-indicator" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-label">Database</span>
                    </div>
                    <div class="step-indicator" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-label">Site</span>
                    </div>
                    <div class="step-indicator" data-step="4">
                        <span class="step-number">4</span>
                        <span class="step-label">Admin</span>
                    </div>
                    <div class="step-indicator" data-step="5">
                        <span class="step-number">5</span>
                        <span class="step-label">Install</span>
                    </div>
                </div>

                <form method="post" action="/install/" id="install-form" class="install-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= \eFiction\Install\Installer::e($csrf) ?>">
                    <input type="hidden" name="action" value="install">

                    <section class="step-panel active" data-step="1">
                        <h2>System Requirements</h2>
                        <p class="step-description">Before installing, your server must meet the following requirements.</p>

                        <div class="requirements-grid" id="requirements-grid">
                            <?php foreach ($checks as $key => $check): ?>
                                <div class="requirement <?= $check['ok'] ? 'ok' : ($check['required'] ? 'fail' : 'warn') ?>">
                                    <div class="requirement-icon">
                                        <?php if ($check['ok']): ?>
                                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        <?php elseif ($check['required']): ?>
                                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                        <?php else: ?>
                                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="requirement-body">
                                        <div class="requirement-title"><?= \eFiction\Install\Installer::e(str_replace(['ext_', '_'], ['', ' '], $key)) ?></div>
                                        <div class="requirement-value"><?= \eFiction\Install\Installer::e($check['value']) ?></div>
                                        <?php if (!$check['ok']): ?>
                                            <div class="requirement-message"><?= \eFiction\Install\Installer::e($check['message']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="btn-step-1-next" <?= $requirementsPass ? '' : 'disabled' ?>>
                                Continue to Database
                            </button>
                        </div>
                    </section>

                    <section class="step-panel" data-step="2">
                        <h2>Database Configuration</h2>
                        <p class="step-description">Choose how the installer should connect to MySQL or MariaDB.</p>

                        <div class="form-group">
                            <label for="db_auto_mode">Database Setup Mode</label>
                            <select id="db_auto_mode" name="db_auto_mode" disabled>
                                <option value="0" selected>Manual — provide an existing database and user</option>
                            </select>
                            <input type="hidden" name="db_auto_mode" value="0">
                            <small>Automatic database/user creation is disabled because it requires a privileged MySQL account. Enter your database details below.</small>
                        </div>

                        <div class="form-group">
                            <label for="db_host">Database Host</label>
                            <input type="text" id="db_host" name="db_host" value="<?= input('db_host', 'localhost') ?>" required>
                            <small>Usually <code>localhost</code> or a host-provided database server.</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="db_database">Database Name</label>
                                <input type="text" id="db_database" name="db_database" value="<?= input('db_database', 'efiction') ?>" required>
                            <small>The database must already exist unless your database user has CREATE privileges.</small>
                        </div>
                            <div class="form-group">
                                <label for="db_prefix">Table Prefix</label>
                                <input type="text" id="db_prefix" name="db_prefix" value="<?= input('db_prefix', 'fanfiction_') ?>" required pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only">
                            <small>Table prefix used for all eFiction tables.</small>
                        </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="db_user">Database User</label>
                                <input type="text" id="db_user" name="db_user" value="<?= input('db_user', 'efiction') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="db_password">Database Password</label>
                                <input type="password" id="db_password" name="db_password" value="<?= input('db_password', '') ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <span id="db-connection-status" class="db-status unsaved">Not yet saved</span>
                            <small>Click <strong>Save & Test Connection</strong> to verify and store these credentials.</small>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" data-prev>Back</button>
                            <button type="button" class="btn btn-primary" id="btn-test-db">Save & Test Connection</button>
                            <button type="button" class="btn btn-primary" id="btn-step-2-next" disabled>Continue</button>
                        </div>
                    </section>

                    <section class="step-panel" data-step="3">
                        <h2>Site Configuration</h2>
                        <p class="step-description">Configure your archive's basic settings.</p>

                        <div class="form-group">
                            <label for="site_title">Site Title</label>
                            <input type="text" id="site_title" name="site_title" value="<?= input('site_title', 'eFiction Archive') ?>" required>
                            <small>Displayed in the site header and page titles.</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="site_email">Site Email</label>
                                <input type="email" id="site_email" name="site_email" value="<?= input('site_email', 'admin@example.com') ?>" required>
                                <small>Used as the sender address for site emails.</small>
                            </div>
                            <div class="form-group">
                                <label for="site_url">Site URL</label>
                                <input type="url" id="site_url" name="site_url" value="<?= input('site_url', $baseUrl) ?>" required>
                            <small>The URL where eFiction will be accessed. Update if the detected value is incorrect.</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="site_language">Default Language</label>
                                <select id="site_language" name="site_language">
                                    <option value="en" <?= (inputRaw('site_language', 'en') === 'en') ? 'selected' : '' ?>>English</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="site_timezone">Timezone</label>
                                <select id="site_timezone" name="site_timezone">
                                    <?php foreach (timezone_identifiers_list() as $tz): ?>
                                        <option value="<?= \eFiction\Install\Installer::e($tz) ?>" <?= (inputRaw('site_timezone', 'UTC') === $tz) ? 'selected' : '' ?>><?= \eFiction\Install\Installer::e($tz) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <h3 class="subsection">Mail Settings</h3>
                        <div class="form-group">
                            <label for="mail_method">Mail Method</label>
                            <select id="mail_method" name="mail_method">
                                <option value="mail" <?= (inputRaw('mail_method', 'mail') === 'mail') ? 'selected' : '' ?>>PHP mail()</option>
                                <option value="smtp" <?= (inputRaw('mail_method', 'mail') === 'smtp') ? 'selected' : '' ?>>SMTP</option>
                                <option value="sendmail" <?= (inputRaw('mail_method', 'mail') === 'sendmail') ? 'selected' : '' ?>>Sendmail</option>
                            </select>
                        </div>

                        <div id="smtp-settings" class="conditional-panel <?= (inputRaw('mail_method', 'mail') === 'smtp') ? 'open' : '' ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="smtp_host">SMTP Host</label>
                                    <input type="text" id="smtp_host" name="smtp_host" value="<?= input('smtp_host', '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="smtp_port">SMTP Port</label>
                                    <input type="number" id="smtp_port" name="smtp_port" value="<?= input('smtp_port', '587') ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="smtp_secure">Encryption</label>
                                    <select id="smtp_secure" name="smtp_secure">
                                        <option value="tls" <?= (inputRaw('smtp_secure', 'tls') === 'tls') ? 'selected' : '' ?>>TLS</option>
                                        <option value="ssl" <?= (inputRaw('smtp_secure', 'tls') === 'ssl') ? 'selected' : '' ?>>SSL</option>
                                        <option value="" <?= (inputRaw('smtp_secure', 'tls') === '') ? 'selected' : '' ?>>None</option>
                                    </select>
                                </div>
                                <div class="form-group checkbox">
                                    <label>
                                        <input type="checkbox" id="smtp_auth" name="smtp_auth" value="1" <?= inputRaw('smtp_auth', false) ? 'checked' : '' ?>>
                                        SMTP Authentication
                                    </label>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="smtp_username">SMTP Username</label>
                                    <input type="text" id="smtp_username" name="smtp_username" value="<?= input('smtp_username', '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="smtp_password">SMTP Password</label>
                                    <input type="password" id="smtp_password" name="smtp_password" value="<?= input('smtp_password', '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" data-prev>Back</button>
                            <button type="button" class="btn btn-primary" data-next>Continue to Admin</button>
                        </div>
                    </section>

                    <section class="step-panel" data-step="4">
                        <h2>Administrator Account</h2>
                        <p class="step-description">Create the first admin account for your archive.</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="admin_penname">Penname</label>
                                <input type="text" id="admin_penname" name="admin_penname" value="<?= input('admin_penname', 'admin') ?>" required>
                            <small>Your public author name on the archive.</small>
                            </div>
                            <div class="form-group">
                                <label for="admin_realname">Real Name <span class="optional">(optional)</span></label>
                                <input type="text" id="admin_realname" name="admin_realname" value="<?= input('admin_realname', 'Administrator') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="admin_email">Email Address</label>
                            <input type="email" id="admin_email" name="admin_email" value="<?= input('admin_email', 'admin@example.com') ?>" required>
                            <small>Used for password recovery and site notifications.</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="admin_password">Password</label>
                                <input type="password" id="admin_password" name="admin_password" minlength="8" required value="<?= input('admin_password', '') ?>">
                                <small>At least 8 characters. Choose a strong password.</small>
                            </div>
                            <div class="form-group">
                                <label for="admin_password_confirm">Confirm Password</label>
                                <input type="password" id="admin_password_confirm" name="admin_password_confirm" minlength="8" required value="<?= input('admin_password_confirm', '') ?>">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" data-prev>Back</button>
                            <button type="button" class="btn btn-primary" data-next>Review & Install</button>
                        </div>
                    </section>

                    <section class="step-panel" data-step="5">
                        <h2>Ready to Install</h2>
                        <p class="step-description">Review your settings below, then click Install to finish.</p>

                        <div class="review-panel">
                            <div class="review-section">
                                <h3>Database</h3>
                                <dl id="review-database"></dl>
                            </div>
                            <div class="review-section">
                                <h3>Site</h3>
                                <dl id="review-site"></dl>
                            </div>
                            <div class="review-section">
                                <h3>Admin</h3>
                                <dl id="review-admin"></dl>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" data-prev>Back</button>
                            <button type="submit" class="btn btn-success" id="btn-install">Install eFiction</button>
                        </div>
                    </section>
                </form>

                <section class="step-panel success-panel <?= ($installResult && $installResult['ok']) ? 'active' : '' ?>" data-step="success">
                    <div class="success-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </div>
                    <h2>Installation Complete</h2>
                    <p>eFiction has been installed successfully. For security, please delete or rename the <code>install</code> directory.</p>

                    <?php if ($installResult && $installResult['ok'] && !empty($installResult['details']['db_user']) && !empty($installResult['details']['db_password'])): ?>
                        <div class="alert alert-info">
                            <p><strong>Database credentials created automatically:</strong></p>
                            <p>User: <code><?= \eFiction\Install\Installer::e($installResult['details']['db_user']) ?></code></p>
                            <p>Password: <code><?= \eFiction\Install\Installer::e($installResult['details']['db_password']) ?></code></p>
                            <p>Save these somewhere safe. They are also stored in <code>config.php</code>.</p>
                        </div>
                    <?php endif; ?>

                    <div class="success-actions">
                        <a href="/" class="btn btn-primary">Go to Site</a>
                        <a href="/admin" class="btn btn-secondary">Admin Panel</a>
                    </div>
                </section>
            </div>

            <footer class="install-footer">
                <p>eFiction <?= \eFiction\Install\Installer::e(\eFiction\Install\Installer::VERSION) ?> — Open source fanfiction archive software.</p>
            </footer>
        </div>
    </div>

    <script src="/install/assets/install.js"></script>
</body>
</html>
