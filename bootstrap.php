<?php

declare(strict_types=1);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'eFiction\\';
        $baseDir = __DIR__ . '/src/';
        $installBaseDir = __DIR__ . '/install/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative = substr($class, $len);
        $relative = str_replace('\\', '/', $relative);

        $file = $baseDir . $relative . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }

        $installFile = $installBaseDir . $relative . '.php';
        if (is_file($installFile)) {
            require_once $installFile;
        }
    });
}

use eFiction\App;
use eFiction\Config;
use eFiction\Database;
use eFiction\Session;
use eFiction\Auth;
use eFiction\I18n;
use eFiction\Template;
use eFiction\Security;
use eFiction\Mailer;
use eFiction\Router;

if (!file_exists(__DIR__ . '/config.php')) {
    if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/install')) {
        return null;
    }
    header('Location: /install/');
    exit;
}

$config = require __DIR__ . '/config.php';

$app = new App($config);

$app->singleton(Config::class, fn() => new Config($config));
$app->singleton(Database::class, fn(Config $cfg) => new Database($cfg));
$app->singleton(Session::class, fn(Config $cfg) => new Session($cfg->get('session')));
$app->singleton(Security::class, fn() => new Security());
$app->singleton(I18n::class, fn(Config $cfg) => new I18n($cfg->get('site.language', 'en')));
$app->singleton(Mailer::class, fn(Config $cfg) => new Mailer($cfg));
$app->singleton(Auth::class, fn(Database $db, Session $s, Config $cfg) => new Auth($db, $s, $cfg));
$app->singleton(Template::class, fn(Config $cfg, I18n $i18n) => new Template($cfg, $i18n));
$app->singleton(Router::class, fn() => new Router());

$app->boot();

require_once __DIR__ . '/routes.php';

return $app;
