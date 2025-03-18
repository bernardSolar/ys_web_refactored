
# GoDaddy Migration Guide

This guide provides step-by-step instructions for deploying the York Supplies POS system to GoDaddy's shared hosting environment.

## 1. Preparation

### 1.1 File Structure Organization

Before uploading to GoDaddy, organize your files to maintain proper security:

```
public_html/               # Web root directory (public)
├── api/                   # API endpoints (protected with limited access)
│   ├── authenticate.php
│   ├── place_order.php
│   ├── popular_products.php
│   ├── products.php  
│   └── user_profile.php
├── assets/                # Public assets
│   └── logo.jpg
├── css/                   # Public CSS files
│   └── styles.css
├── js/                    # Public JavaScript files
│   ├── app.js
│   ├── order-manager.js
│   ├── product-manager.js
│   ├── profile.js
│   └── ui-manager.js
├── .htaccess              # Main .htaccess file
├── .user.ini              # PHP configuration
├── index.php              # Main application entry point
├── login.php              # Login page
└── profile.php            # User profile page

private/                   # Private directory (outside web root)
├── inc/                   # PHP includes
│   ├── OrderManager.php
│   ├── ProductManager.php
│   ├── UserManager.php
│   ├── config.php
│   ├── db_connect.php
│   └── session_check.php  
├── data/                  # Data files
│   ├── products.csv
│   └── users.csv
├── logs/                  # Log files
│   └── app_errors.log
├── sessions/              # Session storage
├── .htaccess              # Deny all access to this directory
└── uploads/               # Any upload directory (if needed)
```

### 1.1.2 Adapting Current Files to New Structure

When moving files from the current flat structure to the GoDaddy hierarchical structure:

| Current Location | GoDaddy Location | Notes |
|------------------|------------------|-------|
| ys_web/index.php | public_html/index.php | Entry point |
| ys_web/api/*.php | public_html/api/*.php | API endpoints |
| ys_web/inc/*.php | private/inc/*.php | PHP includes |
| ys_web/assets/* | public_html/assets/* | Public images/resources |
| ys_web/css/* | public_html/css/* | Stylesheets |
| ys_web/js/* | public_html/js/* | JavaScript files |
| ys_web/products.db | N/A | Not used (replaced by MySQL) |
| ys_web/products.csv | private/data/products.csv | Import data |
| ys_web/users.csv | private/data/users.csv | User import data |

Create additional directories:
- `private/logs` - Error logs
- `private/sessions` - Session storage
- `private/data` - Data files

## 2. Required Code Modifications

Before deploying to GoDaddy, the following code modifications are necessary:

### 2.1 File Path References

Update all file path references to work with the new directory structure:

```php
// Original (current):
require_once 'inc/session_check.php';
require_once __DIR__ . '/inc/db_connect.php';

// Updated (for GoDaddy):
require_once $_SERVER['DOCUMENT_ROOT'] . '/../private/inc/session_check.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../private/inc/db_connect.php';
```

Files to modify:
- All PHP files in `public_html` directory (index.php, login.php, profile.php)
- All API endpoint files in `api` directory

Example changes for specific files:

```php
// index.php
// FROM: require_once 'inc/session_check.php';
// TO: require_once $_SERVER['DOCUMENT_ROOT'] . '/../private/inc/session_check.php';

// api/authenticate.php
// FROM: require_once '../inc/UserManager.php';
// TO: require_once $_SERVER['DOCUMENT_ROOT'] . '/../private/inc/UserManager.php';
```

### 2.2 Database Connection Modifications

The `db_connect.php` file needs updates to properly support MySQL:

```php
// In the initializeDatabase() method:

// Replace SQLite syntax:
"CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    is_custom BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);"

// With MySQL syntax:
"CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    is_custom BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);"
```

Also update all `AUTOINCREMENT` to `AUTO_INCREMENT` and change data types:
- `TEXT` to `VARCHAR(255)` or `TEXT`
- `INTEGER` to `INT`
- `REAL` to `DECIMAL(10,2)`

### 2.3 MySQL-Specific Query Changes

Replace SQLite-specific query patterns with MySQL equivalents:

```php
// Replace:
"INSERT OR IGNORE INTO categories (name) VALUES (?)"

// With:
"INSERT IGNORE INTO categories (name) VALUES (?)"
```

Make similar changes for all query patterns in:
- ProductManager.php
- OrderManager.php
- UserManager.php

### 2.4 CSV Import Path Updates

Update CSV import paths in `db_connect.php`:

```php
// Replace:
$csvFile = realpath(__DIR__ . '/../products.csv');
if (!$csvFile) {
    $csvFile = realpath(__DIR__ . '/../../products.csv');
}

// With:
$csvFile = realpath($_SERVER['DOCUMENT_ROOT'] . '/../private/data/products.csv');
```

### 2.5 Session Configuration

Update session configuration in `session_check.php`:

```php
// Add at the top of the file:
ini_set('session.save_path', $_SERVER['DOCUMENT_ROOT'] . '/../private/sessions');
session_save_path($_SERVER['DOCUMENT_ROOT'] . '/../private/sessions');

// Add these security configurations
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// Create the sessions directory in private/
// chmod 733 private/sessions
```

### 2.6 Error Logging

Update error logging to use GoDaddy's log paths:

```php
// Replace:
error_log("Database connection failed: " . $e->getMessage());

// With:
error_log("Database connection failed: " . $e->getMessage(), 3, $_SERVER['DOCUMENT_ROOT'] . '/../private/logs/app_errors.log');
```

Add a logs directory and ensure it's writable.

### 2.7 API Security Headers

Add proper domain restrictions to API endpoints:

```php
// Add this at the top of each API file:
// Get the referring domain
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$allowedDomains = ['https://yourdomain.com', 'https://www.yourdomain.com'];

// Skip check for authenticate.php to allow logins
if (basename($_SERVER['SCRIPT_NAME']) !== 'authenticate.php') {
    $allowed = false;
    foreach ($allowedDomains as $domain) {
        if (strpos($referer, $domain) === 0) {
            $allowed = true;
            break;
        }
    }
    
    if (!$allowed) {
        header('HTTP/1.0 403 Forbidden');
        exit('Access denied');
    }
}
```

### 2.8 Content Security Policy

Add a Content Security Policy header in the main `.htaccess` file:

```
# Add to the main .htaccess
<IfModule mod_headers.c>
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self'"
</IfModule>
```

### 2.9 MySQL Connection Pooling

Update `db_connect.php` to handle MySQL connection pooling:

```php
// In the constructor:
if ($db['type'] === 'mysql') {
    $this->connection = new PDO(
        "mysql:host={$db['host']};dbname={$db['name']}",
        $db['user'],
        $db['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true // Enable connection pooling
        ]
    );
}
```

### 2.10 File Upload Handling

If implementing file uploads, update the paths:

```php
// Replace:
$uploadDir = __DIR__ . '/../uploads/';

// With:
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/../private/uploads/';
```

### 2.11 Update Configuration Management

Update `config.php` path handling in case it's moved:

```php
// In db_connect.php, replace:
$this->config = require __DIR__ . '/config.php';

// With:
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    $configPath = $_SERVER['DOCUMENT_ROOT'] . '/../private/inc/config.php';
}
$this->config = require $configPath;
```

### 2.12. Add Database Connection Test Script

Create a test file to verify MySQL connection:

```php
// Create private/db_test.php
<?php
try {
    require_once 'inc/db_connect.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "Database connection successful!";
    
    // Test a simple query
    $stmt = $conn->query("SELECT 1");
    $result = $stmt->fetch();
    echo "<br>Query test successful: " . print_r($result, true);
    
    // Test each table
    $tables = ['categories', 'products', 'users', 'order_history', 'product_sales'];
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM {$table}");
            $count = $stmt->fetchColumn();
            echo "<br>Table {$table}: {$count} records";
        } catch (Exception $e) {
            echo "<br>Error with table {$table}: " . $e->getMessage();
        }
    }
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

### 2.13 Update Configuration

Update `config.php` to use MySQL instead of SQLite:

```php
<?php
/**
 * Configuration file for POS system
 * Contains database connection settings and other configuration options
 */
return [
    // Database configuration
    'database' => [
        'type' => 'mysql',  // Changed from 'sqlite' to 'mysql'
        
        // MySQL settings for GoDaddy
        'host' => 'localhost', 
        'name' => 'your_database_name',  // The database you'll create in GoDaddy
        'user' => 'your_username',       // Your GoDaddy database username
        'pass' => 'your_password'        // Your GoDaddy database password
    ],
    
    // Application settings
    'app' => [
        'name' => 'York Supplies POS',
        'version' => '1.0.0',
        'debug' => false  // Set to false in production
    ]
];
```

### 2.14 PHP Extension Verification

Ensure GoDaddy has these required PHP extensions enabled:
- PDO and PDO_MYSQL
- JSON
- MBString

You can check available extensions by creating a simple PHP info file:

```php
// Create public_html/phpinfo.php (delete after checking!)
<?php phpinfo(); ?>
```

### 2.15 Timezone Configuration

Add timezone settings to prevent date/time issues:

```php
// Add to db_connect.php:
date_default_timezone_set('Europe/London'); // Adjust for your timezone

// Add MySQL timezone setting when connecting to the database:
if ($db['type'] === 'mysql') {
    $this->connection = new PDO(...);
    // Set MySQL session timezone to match PHP
    $this->connection->exec("SET time_zone = '+00:00';"); // Adjust for your timezone
}
```

## 3. GoDaddy Setup

### 3.1 Create Database in GoDaddy

1. Log in to your GoDaddy hosting account
2. Navigate to cPanel > MySQL Databases
3. Create a new database:
   - Enter a name for your database (e.g., `york_pos`)
   - Click "Create Database"
4. Create a new database user:
   - Enter a username and password (use a strong password)
   - Click "Create User"
5. Add the user to the database:
   - Select the database and user you just created
   - Grant "ALL PRIVILEGES" to the user
   - Click "Add User To Database"

### 3.2 Upload Files to GoDaddy

#### Method 1: Using cPanel File Manager

1. Log in to your GoDaddy cPanel
2. Open File Manager and navigate to your root directory
3. Create two folders: `public_html` (if not exists) and `private`
4. Upload files to the appropriate directories as per the file structure above

#### Method 2: Using FTP Client

1. Use an FTP client like FileZilla
2. Connect to your GoDaddy hosting using your FTP credentials
3. Upload files to the appropriate directories as per the file structure above

### 3.3 Set File Permissions

Set appropriate permissions for files and directories:

```
Directories: 755 (rwxr-xr-x)
PHP Files:   644 (rw-r--r--)
Config Files: 600 (rw-------)
Sessions Dir: 733 (rwx-wx-wx)
Logs Dir:     755 (rwxr-xr-x)
```

In cPanel File Manager:
1. Select directories and click "Change Permissions"
2. Set to 755
3. Select PHP files and set to 644
4. Select config files and set to 600
5. Set sessions directory to 733
6. Ensure log files are writable

### 3.4 Create and Place .htaccess Files

#### Main .htaccess for public_html

Create a `.htaccess` file in your `public_html` directory with the following content:

```
# Prevent directory listing
Options -Indexes

# Enable the rewrite engine
RewriteEngine On

# Prevent access to sensitive files
<FilesMatch "\.(htaccess|htpasswd|ini|log|sh|inc|bak)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Basic security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# PHP error handling
php_flag display_errors off
php_value error_reporting 0

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
```

#### .htaccess for API Directory

Create a `.htaccess` file in your `api` directory:

```
# Restrict access to API endpoints
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Allow access only from your domain
    RewriteCond %{HTTP_REFERER} !^https?://(www\.)?yourdomain\.com [NC]
    RewriteCond %{REQUEST_URI} !authenticate\.php$ [NC]
    RewriteRule .* - [F,L]
</IfModule>

# Set content type to JSON
<IfModule mod_headers.c>
    Header set Content-Type "application/json"
</IfModule>
```

#### .htaccess for Private Directory

Create a `.htaccess` file in your `private` directory:

```
# Completely deny access to this directory
Order deny,allow
Deny from all
```

### 3.5 PHP Configuration (.user.ini)

Create a `.user.ini` file in the `public_html` directory:

```
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 128M
max_execution_time = 300
session.save_path = /home/username/private/sessions
date.timezone = "Europe/London"
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

Replace `/home/username` with your actual home directory path on GoDaddy.

## 4. Database Setup

### 4.1 Create MySQL Database Tables

Run the following SQL queries in phpMyAdmin (accessible via GoDaddy cPanel) to create the necessary tables:

```sql
-- Set character set
SET NAMES utf8mb4;

-- Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    is_custom BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    sku VARCHAR(50),
    stock INT DEFAULT 0,
    UNIQUE(sku),
    FOREIGN KEY (category_id) REFERENCES categories (id),
    UNIQUE(category_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Sales Table
CREATE TABLE product_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation VARCHAR(255) NOT NULL,
    delivery_address TEXT NOT NULL,
    delivery_charge DECIMAL(10,2) NOT NULL,
    rep_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order History Table
CREATE TABLE order_history (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    organisation VARCHAR(255),
    delivery_address TEXT,
    delivery_charge DECIMAL(10,2) DEFAULT 0,
    order_datetime DATETIME NOT NULL,
    order_text TEXT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.2 Import Initial Data

#### Method 1: Using phpMyAdmin CSV Import

1. Log in to phpMyAdmin
2. Select your database
3. Select the target table (e.g., products)
4. Click on the "Import" tab
5. Choose your CSV file and set appropriate import settings:
   - Character set: UTF-8
   - Format: CSV
   - Column delimiter: comma (,)
   - Replace table data: Yes
6. Click "Go" to import the data

#### Method 2: Using the Application's Auto-Import

Our application has built-in functionality to import products and users from CSV files:

1. Place your `products.csv` file in an accessible location (e.g., `private/data/` directory)
2. Modify the path in `db_connect.php` to point to this file
3. Access the application - it will automatically attempt to import the data

## 5. Security Considerations

### 5.1 Password Security

1. All passwords in the `users.csv` file should be hashed before uploading
2. For improved security, update passwords after initial setup
3. Ensure all admin users have strong, unique passwords

### 5.2 SSL Configuration

Enable SSL for your domain through GoDaddy's cPanel. This ensures all traffic between users and your site is encrypted.

1. Log in to GoDaddy cPanel
2. Find the "SSL/TLS" or "Security" section
3. Install an SSL certificate (GoDaddy often provides free certificates)
4. Ensure your site forces HTTPS by adding these rules to your main .htaccess (already included above)

### 5.3 File Security

1. Keep sensitive files (config files, database files) outside the web root
2. Use the private directory for all sensitive operations
3. Regularly back up your database and files

## 6. Testing and Verification

### 6.1 Deployment Verification Checklist

Complete this checklist after migration to verify all functionality:

#### Database Connection
- [ ] MySQL connection established successfully
- [ ] All tables created with correct structure
- [ ] Data imported correctly (check record counts)

#### User Authentication
- [ ] Login page loads correctly
- [ ] Login with valid credentials works
- [ ] Invalid login attempts are rejected
- [ ] User sessions persist correctly
- [ ] Logout functionality works

#### Product Management
- [ ] All categories display correctly
- [ ] Products appear in appropriate categories
- [ ] Product details (price, name) display correctly
- [ ] Product images load properly (if applicable)

#### Order Processing
- [ ] Add items to cart works correctly
- [ ] Remove items from cart works
- [ ] Order total calculates correctly
- [ ] Delivery charges apply as expected
- [ ] Order confirmation displays properly
- [ ] Order is saved to database

#### Security Testing
- [ ] Private directory files are inaccessible directly
- [ ] API endpoints reject unauthorized access
- [ ] SSL certificate works (https:// loads correctly)
- [ ] Sensitive data not exposed in browser source
- [ ] Error messages don't reveal sensitive info

#### Performance Check
- [ ] Page load times are acceptable
- [ ] Database queries execute efficiently
- [ ] No PHP timeout errors occur
- [ ] Static assets (CSS/JS) load correctly

Record any issues encountered during verification and address them before considering the migration complete.

## 7. Maintenance and Updates

### 7.1 Updating the Application

When updating the application:

1. Back up all files and the database
2. Upload new files to the appropriate directories
3. Run any necessary database migrations
4. Test thoroughly before making the updated site live

### 7.2 Regular Backups

Set up regular backups for your:

1. MySQL database (through GoDaddy cPanel):
   - Go to cPanel > Backup Wizard
   - Schedule automated MySQL backups
   - Set to download to your email or store on the server
   - Recommended frequency: daily or weekly

2. Application files:
   - Use cPanel's Backup feature
   - Schedule full account backups
   - Consider automating with a script if large

3. Configuration files:
   - Backup separately any time a configuration changes
   - Store secure copies offline

### 7.3 Monitoring

Monitor your application for:

1. Error logs (check PHP and server logs)
   - Path: cPanel > Logs > Error Log
   - Also check: private/logs/app_errors.log

2. Performance issues:
   - Use cPanel > Metrics > Resource Usage
   - Monitor for peak usage times

3. Security alerts:
   - Enable cPanel security notifications
   - Set up email alerts for login attempts (if available)

## 8. Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Check your MySQL credentials in `config.php`
   - Verify the database user has proper permissions
   - Test connection with the db_test.php script

2. **Permission Issues**
   - Ensure directories have 755 permissions
   - Ensure PHP files have 644 permissions
   - Config files should have 600 permissions
   - Session directory needs 733 permissions

3. **Import Problems**
   - Check CSV file formatting and encoding (use UTF-8)
   - Verify file paths in import scripts
   - Try manual import through phpMyAdmin

4. **API Endpoint Errors**
   - Check .htaccess rules
   - Verify JSON formatting in responses
   - Check that domain restrictions match your actual domain

5. **PHP Version Compatibility**
   - Confirm GoDaddy's PHP version is 7.4+
   - Adjust code if needed for compatibility
   - You can select PHP version in cPanel > PHP Version Manager

6. **Session Handling Issues**
   - Check session directory permissions
   - Verify session.save_path in .user.ini
   - Test with a simple session script

For any persistent issues, check the PHP error logs in your GoDaddy cPanel.

## 9. MySQL vs SQLite Differences

Key differences to address:

1. **Auto-increment Syntax**:
   - SQLite: `INTEGER PRIMARY KEY AUTOINCREMENT`
   - MySQL: `INT AUTO_INCREMENT PRIMARY KEY`

2. **Insert Behavior**:
   - SQLite: `INSERT OR IGNORE INTO`
   - MySQL: `INSERT IGNORE INTO`

3. **Boolean Storage**:
   - SQLite: Uses 0/1 internally
   - MySQL: TRUE/FALSE or 0/1 both work

4. **Text Types**:
   - SQLite: Generic `TEXT` type
   - MySQL: Use appropriate `VARCHAR(n)` or `TEXT`

5. **Date Functions**:
   - SQLite: `datetime('now')`
   - MySQL: `NOW()`

6. **Transaction Support**:
   - If using transactions, update syntax to MySQL standards

7. **Character Set Considerations**:
   - SQLite: Unicode support varies
   - MySQL: Use utf8mb4 charset and collation explicitly

8. **Case Sensitivity**:
   - SQLite: Case-insensitive by default
   - MySQL: Case-sensitive for table names on some systems
