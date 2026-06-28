# eFiction 4X

A modern PHP fanfiction archive built on a clean, lightweight framework. It is designed for self-hosting on any web server with PHP 8.3+, MySQL/MariaDB, and Apache (or any server with URL rewriting).

> **Status:** In development. Core routing, controllers, and installation are functional; many features are stubs or partially implemented.

## Requirements

- PHP >= 8.3
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled, or another web server configured to rewrite all requests to `index.php`
- PHP extensions: `pdo`, `pdo_mysql`, `mbstring`, `session`, `json`, `fileinfo`, `openssl`
- Composer is **optional** — eFiction now works with a built-in autoloader and no external dependencies.

## Installation (Upload and Go)

1. **Upload the files** to your web host's public directory (e.g., `public_html`, `htdocs`, `www`, or a subdirectory).

   No shell commands, no Composer, and no dependency installation are required. Simply upload the entire contents of the repository.

2. **Choose a database setup mode** in the installer:

   - **Automatic (recommended for shared hosting):** Enter a temporary privileged MySQL/MariaDB account (such as `root` or a hosting control panel admin user). The installer will create the database, generate a secure dedicated database user, and grant that user full privileges on the new database. The generated credentials are saved in `config.php` and shown on the success screen.

   - **Manual:** Create your own database and database user through your hosting control panel or phpMyAdmin, then enter those credentials in the installer. The installer can still create the database if it does not exist, provided the supplied user has the `CREATE` privilege.

3. **Make the storage and install directories writable** by the web server:

   - `storage/` — must be writable for stories, images, cache, and logs
   - `install/` — must be writable during the installation process
   - `config.php` will be created by the installer, so the web root must be writable

   On most shared hosts, you can set permissions to `755` via your FTP client or file manager:

   ```
   storage/
   install/
   ```

4. **Open the installer in your browser**:

   Navigate to `https://yourdomain.com/install/` (or `https://yourdomain.com/subdir/install/` if you uploaded to a subdirectory).

   The installer provides a guided, multi-step wizard that checks system requirements, tests your database connection, and fills in site and admin details. It works with JavaScript enabled (AJAX-enhanced) and falls back to a traditional form when JavaScript is unavailable for maximum hosting compatibility.

   The installer will:
   - Check PHP version and required extensions
   - Verify that `config.php`, `storage/`, and `install/` are writable
   - Test (and optionally create) the MySQL/MariaDB database
   - Create the database schema from `install/schema.sql`
   - Insert default site settings
   - Write `config.php`
   - Create the admin user with a securely hashed password
   - Write `install/install.lock` to prevent accidental reinstallation

5. **Remove or rename the `install/` directory** after installation for security.

   The application also checks for `config.php` or `install/install.lock` on each request and redirects back to the site if the installer is no longer needed.

6. **Optional: manual configuration**

   If you prefer to configure the site manually, copy `config.php.example` to `config.php` and adjust the values. The site will not run until `config.php` exists.

## Composer-based installation (optional)

If you prefer to use Composer or want to run development tools, you can still install dependencies:

```bash
composer install --no-dev --optimize-autoloader
```

When a `vendor/autoload.php` file is present, eFiction will use it. Otherwise, it falls back to the built-in PSR-4 autoloader for the `eFiction\` and `eFiction\Install\` namespaces.

PHPMailer is now optional. It is listed in `composer.json` under `suggest` and is not required for upload-and-go deployments. The built-in mailer uses PHP's `mail()` function and can optionally send mail through raw SMTP sockets.

## Installer

The graphical installer is located in `install/` and is designed to work on the widest range of shared hosting environments:

- `install/index.php` — wizard entry point and AJAX endpoints
- `install/Installer.php` — self-contained backend installer logic (no Composer autoloader required)
- `install/assets/install.css` — installer styles
- `install/assets/install.js` — vanilla JavaScript wizard controller (progressive enhancement)
- `install/schema.sql` — database schema; `{prefix}` is replaced at install time

Features:

- Server-side system requirements check (PHP 8.3+, required/recommended extensions, writable paths)
- PDO-based MySQL/MariaDB connection test with optional database creation and automatic database/user creation
- CSRF protection via PHP sessions
- Password hashing with `password_hash()`
- Install lock file (`install/install.lock`) to prevent accidental reinstallation
- No-JavaScript fallback for browsers or hosting environments where JavaScript is unavailable

## Configuration

The main configuration file is `config.php`. It is created automatically by the installer, but you can also edit it manually. Key sections:

- `db` — database host, name, user, password, and table prefix
- `site` — site URL, title, email, timezone, language, and storage paths
- `mail` — email delivery method (`mail`, `smtp`, or `sendmail`). PHPMailer is no longer required; SMTP is handled by the built-in mailer.
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

To run syntax checks on all PHP files, PHP must be installed locally:

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
