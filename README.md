# eFiction 4X

A modern PHP fanfiction archive built on a clean, lightweight framework. It is designed for self-hosting on any web server with PHP 8.3+, MySQL/MariaDB, and Apache (or any server with URL rewriting).

> **Status:** In development. Core routing, controllers, and installation are functional; many features are stubs or partially implemented.

## Requirements

- PHP >= 8.3
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled, or another web server configured to rewrite all requests to `index.php`
- Composer 2.x
- PHP extensions: `pdo`, `pdo_mysql`, `mbstring`, `session`, `json`, `fileinfo`, `openssl`

## Installation

1. **Clone the repository** into your web root (or a subdirectory):

   ```bash
   git clone https://github.com/Sidular/eFiction-4X.git
   cd eFiction-4X
   ```

2. **Install dependencies** with Composer:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

   Or, if you do not have Composer installed on the server, you can run `composer install` locally and upload the `vendor/` directory.

3. **Create a MySQL/MariaDB database** and a user with full privileges on that database.

4. **Make the storage directories writable** by the web server:

   ```bash
   chmod -R 755 storage
   ```

   The following directories are used at runtime:
   - `storage/stories` — chapter text files
   - `storage/images` — uploaded images
   - `storage/cache` — cached data
   - `storage/logs` — error / application logs

5. **Run the web installer**:

   Open the site in a browser and visit `/install/`. The installer provides a guided, multi-step wizard that checks system requirements, tests your database connection, and fills in site and admin details. It works with JavaScript enabled (AJAX-enhanced) and falls back to a traditional form when JavaScript is disabled for maximum hosting compatibility.

   The installer will:
   - Check PHP version and required extensions
   - Verify that `config.php`, `storage/`, and `install/` are writable
   - Test (and optionally create) the MySQL/MariaDB database
   - Create the database schema from `install/schema.sql`
   - Insert default site settings
   - Write `config.php`
   - Create the admin user with a securely hashed password
   - Write `install/install.lock` to prevent accidental reinstallation

6. **Remove or rename the `install/` directory** after installation for security:

   ```bash
   rm -rf install
   ```

   The application also checks for `config.php` or `install/install.lock` on each request and redirects back to the site if the installer is no longer needed.

7. **Copy the example configuration** (optional):

   If you prefer to configure the site manually, copy `config.php.example` to `config.php` and adjust the values. The site will not run until `config.php` exists.

   ```bash
   cp config.php.example config.php
   ```

## Installer

The graphical installer is located in `install/` and is designed to work on the widest range of shared hosting environments:

- `install/index.php` — wizard entry point and AJAX endpoints
- `install/Installer.php` — self-contained backend installer logic (no Composer autoloader required)
- `install/assets/install.css` — installer styles
- `install/assets/install.js` — vanilla JavaScript wizard controller (progressive enhancement)
- `install/schema.sql` — database schema; `{prefix}` is replaced at install time

Features:

- Server-side system requirements check (PHP 8.3+, required/recommended extensions, writable paths)
- PDO-based MySQL/MariaDB connection test with optional database creation
- CSRF protection via PHP sessions
- Password hashing with `password_hash()`
- Install lock file (`install/install.lock`) to prevent accidental reinstallation
- No-JavaScript fallback for browsers or hosting environments where JavaScript is unavailable

## Configuration

The main configuration file is `config.php`. It is created automatically by the installer, but you can also edit it manually. Key sections:

- `db` — database host, name, user, password, and table prefix
- `site` — site URL, title, email, timezone, language, and storage paths
- `mail` — email delivery method (`mail`, `smtp`, or `sendmail`)
- `session` — session name and lifetime
- `security` — CSRF token name and password hashing cost

## URL Rewriting

An `.htaccess` file is included for Apache. It redirects all non-file/directory requests to `index.php` and blocks access to sensitive directories (`src`, `templates`, `storage`, `vendor`, `config.php`, `composer.json`).

For Nginx, use a configuration like:

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/efiction-4x;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ ^/(src|templates|storage|vendor|config\.php|composer\.json) {
        deny all;
    }
}
```

## Development

To run syntax checks on all PHP files:

```bash
composer run lint
```

To run PHPStan static analysis (development dependency):

```bash
composer install
composer run analyze
```

## Security Notes

- Keep `config.php` out of version control (it is already ignored by `.gitignore`).
- Remove or rename the `install/` directory after installation.
- Ensure `storage/` is writable but not directly accessible from the web.
- Use HTTPS in production and set `session.secure` to `true` in `config.php`.

## License

GPL-2.0-or-later
