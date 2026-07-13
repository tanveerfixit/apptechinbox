# Hostinger Secure Deployment Guide

This guide details the security practices and step-by-step instructions to deploy the Vape Order Builder securely on Hostinger (or any other cPanel/shared hosting provider).

---

## 1. Secure Database Credentials (.env)

Currently, the credentials inside `db.php` are hardcoded. When deploying to a production server, it is best practice to store sensitive variables outside the code files.

### Step A: Create a Configuration file (or `.env`)
Create a file named `.env.php` inside your domain's folder (preferably one level **above** the public web root `public_html`, or protected via `.htaccess`):

```php
<?php
// .env.php
return [
    'DB_HOST' => 'vape-postgres-vape-inventory.h.aivencloud.com',
    'DB_PORT' => '24279',
    'DB_NAME' => 'defaultdb',
    'DB_USER' => 'avnadmin',
    'DB_PASS' => 'YOUR_AIVEN_PASSWORD_HERE'
];
```

### Step B: Update `db.php` to load credentials
Modify `db.php` to load these configuration options dynamically:

```php
<?php
// db.php
$config = require __DIR__ . '/.env.php';

$host = $config['DB_HOST'];
$port = $config['DB_PORT'];
$dbname = $config['DB_NAME'];
$user = $config['DB_USER'];
$password = $config['DB_PASS'];

try {
    // Keep sslmode=require enabled to ensure database traffic is encrypted!
    $db = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require", $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed."); // Do not expose raw error messages to public users
}
```

---

## 2. Restricting Public Access via `.htaccess`

Create an `.htaccess` file inside your domain's root folder (`public_html`) on Hostinger to restrict access to sensitive configuration files:

```apache
# Block access to environment/configuration files
<FilesMatch "^\.env|composer\.json|db\.php">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent directory listings
Options -Indexes

# Force HTTPS redirect
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 3. Enable HTTPS (SSL Certificate)

To prevent attackers from intercepting your username, password, or session cookies over public networks, you **must** configure HTTPS on Hostinger:
1. Log in to your **Hostinger hPanel**.
2. Go to **Websites** -> **Manage** -> **SSL**.
3. Install a free **Let's Encrypt SSL Certificate** for your domain.
4. Ensure the redirect to HTTPS is turned on in the Hostinger settings (or handled by the `.htaccess` configuration above).

---

## 4. Secure PHP Sessions

At the top of your scripts (`index.php`, `past_orders.php`, `api.php`), configure session security flags before calling `session_start()` to protect the session cookies from XSS and session hijacking:

```php
ini_set('session.cookie_httponly', 1); // Prevents JS from reading session cookie
ini_set('session.cookie_secure', 1);   // Session cookie only sent over HTTPS
ini_set('session.use_only_cookies', 1); // Disables passing session ID in URL parameters
session_start();
```

---

## 5. Steps to Upload Files to Hostinger

1. Export/Zip all your files:
   - `index.php`
   - `api.php`
   - `db.php`
   - `login.php`
   - `past_orders.php`
   - `style.css`
   - `.htaccess` (new)
   - `.env.php` (new)
2. Open Hostinger **hPanel** -> **File Manager**.
3. Navigate into the `public_html` directory of your domain.
4. Upload the zip file and extract its contents.
5. Move `.env.php` to a directory one level *above* `public_html` for maximum security, then adjust the `require` path in `db.php` accordingly (e.g., `require __DIR__ . '/../.env.php';`).
