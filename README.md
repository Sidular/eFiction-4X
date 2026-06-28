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

   Open the site in a browser and visit `/install/`. Fill in the database credentials, site information, and admin account. The installer will:
   - Create the database schema
   - Insert default settings
   - Write `config.php`
   - Create the admin user

6. **Remove or rename the `install/` directory** after installation for security:

   ```bash
   rm -rf install
   ```

7. **Copy the example configuration** (optional):

   If you prefer to configure the site manually, copy `config.php.example` to `config.php` and adjust the values. The site will not run until `config.php` exists.

   ```bash
   cp config.php.example config.php
   ```

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
