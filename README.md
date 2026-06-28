# eFiction 4.0.0

## Project Overview
- **Name**: eFiction v4.0.0 — Modernized PHP 8.x fork
- **Goal**: Preserve the existing eFiction fanfiction archive while making it runnable on modern PHP 8.1+ and Apache/Nginx + PHP-FPM stacks.
- **Features**: Fanfiction story/series management, reviews, categories, user profiles, admin panel, RSS, templates.

## Requirements
- **PHP**: 8.1 or newer (8.3 recommended)
- **Extensions**: `pdo`, `pdo_mysql`, `mbstring`, `session`, `gd` (optional, for captchas)
- **Database**: MySQL 5.7+ / MariaDB 10.3+ / Percona (PDO MySQL)
- **Web server**: Apache 2.4 with `mod_rewrite` and `mod_headers`, or Nginx with equivalent security headers.
- **Composer**: For dependency management (PHPMailer 6.x).

## URLs / Entry Points
- Public site: `/index.php`
- Admin area: `/admin.php`
- Browse: `/browse.php`
- Story view: `/viewstory.php?sid=<id>`
- User profile: `/viewuser.php?uid=<id>`
- Search: `/search.php`
- RSS: `/rss.php`
- Login: `/user.php?action=login`

## Data Architecture
- **Database**: MySQL-compatible relational database, accessed via the PDO adapter in `includes/pdo_functions.php`.
- **Table prefix**: Configurable via `config.php` (`TABLEPREFIX`).
- **Stories**: Stored as flat files under `STORIESPATH` (configured during install).
- **Templates**: `skins/<skin>/` and fallback `default_tpls/`.

## Modernization Changes
- `includes/dbfunctions.php` now routes to PDO by default, with MySQLi as fallback. The legacy `ext/mysql` driver is no longer supported.
- `includes/pdo_functions.php` provides PDO-compatible wrappers (`dbquery`, `dbassoc`, `dbrow`, `dbnumrows`, `dbinsertid`, `dbprepare`, `dbexecute`) and exception-based error handling.
- `header.php`:
  - Removed calls to `get_magic_quotes_gpc()` and `register_globals` (removed in PHP 7).
  - Replaced `list($usec,$sec)+microtime()` with `microtime(true)`.
  - Fixed inverted `strpos()` arguments in IE detection.
  - Added a guard when settings cannot be loaded.
  - Initialized `$blocks` array before the loop.
- `includes/corefunctions.php`:
  - Replaced curly-brace string offset (`$var{0}`) with bracket notation (`$var[0]`) for PHP 8 compatibility.
  - Replaced the custom `validEmail()` regex with `filter_var($str, FILTER_VALIDATE_EMAIL)`.
  - Refactored `categoryitems()` to avoid `unset($catquery)` inside a `while` loop.
  - Cleaned up `recurseCategories()` to use `$catlist` directly instead of a confusing variable variable.
- Added `.htaccess` with security headers, file protection, and PHP runtime directives.
- Added `composer.json` with PHPMailer 6.x and PHP 8.1+ requirement.

## Installation / Upgrade Notes
1. Ensure your server runs PHP 8.1+ with `pdo_mysql` enabled.
2. Run `composer install --no-dev` to install PHPMailer 6.x into `vendor/`.
3. Update `includes/emailer.php` to load `vendor/autoload.php` and use `PHPMailer\PHPMailer\PHPMailer` instead of the bundled PHPMailer 5.x classes (this is the next recommended step).
4. If you have an existing `config.php` from an older install, it should still work as long as the PDO adapter is used.
5. Visit `install/install.php` for a fresh install or follow the upgrade prompts from the original `README.txt`.

## Features Not Yet Modernized
- Many pages still use raw string concatenation in SQL queries (`"... WHERE id = '".$var."'"`). These should be migrated to `dbprepare()`/`dbexecute()` over time.
- The bundled TinyMCE 3.x and old PHPMailer 5.x files in `includes/` and `tinymce/` should be replaced with current versions.
- Template engine (`class.TemplatePower.inc.php`) is old but functional; consider migrating to a modern engine (Twig/Plates) in a future rewrite.
- No formal test suite exists yet; PHPStan and PHP_CodeSniffer are configured as dev dependencies.

## Recommended Next Steps
1. Replace the bundled PHPMailer files with Composer's PHPMailer 6.x and update `includes/emailer.php`.
2. Migrate the highest-risk SQL queries to parameterized statements using `dbprepare()`/`dbexecute()`.
3. Run `composer install` and `composer run lint` to validate syntax across the project.
4. Add a `phpstan.neon` and run `composer run analyze` to catch undefined variables and type issues.
5. Set up a CI pipeline (GitHub Actions / GitLab CI) that runs `php -l` and PHPStan on every push.

## Deployment Status
- **Platform**: Traditional PHP hosting (Apache/Nginx + PHP-FPM) — NOT Cloudflare Pages.
- **Status**: Partially modernized for PHP 8.x; runtime validation pending.
- **Tech Stack**: PHP 8.1+, PDO MySQL, PHPMailer 6.x, Composer.
- **Last Updated**: 2026-06-28

## License
GPL-2.0-or-later (see original `README.txt` and source headers).
