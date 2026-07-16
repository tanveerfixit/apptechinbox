<?php
// db.php

// Simple helper to load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            // Strip quotes
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
                $value = $matches[1];
            }
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/.env');

// Ensure session is started to read tenant database name
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = getenv('DB_HOST') ?: 'srv2113.hstgr.io';
$port = getenv('DB_PORT') ?: '3306';
$masterDbName = getenv('DB_DATABASE') ?: 'u583652021_apps';
$user = getenv('DB_USERNAME') ?: 'u583652021_techinbox';
$password = getenv('DB_PASSWORD') ?: 'Techinbox@8877';

// 1. Connect to Master Database
try {
    $masterDb = new PDO("mysql:host=$host;port=$port;dbname=$masterDbName;charset=utf8mb4", $user, $password, [
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_PERSISTENT => true
    ]);
    $masterDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $masterDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Master Database connection failed: " . $e->getMessage());
}

// Helper to determine tenant database name dynamically if not stored explicitly
function sanitizeTenantDbName($businessName, $masterDbName) {
    $dbPrefix = '';
    if (strpos($masterDbName, '_') !== false) {
        $dbPrefix = explode('_', $masterDbName)[0] . '_';
    }
    $sanitized = preg_replace('/[^a-z0-9]/', '', strtolower($businessName));
    return $dbPrefix . 'biz_' . $sanitized;
}

// 2. Connect to Tenant Database if session is active
$tenantDbName = $_SESSION['tenant_db_name'] ?? null;
$db = null;

if ($tenantDbName) {
    try {
        $db = new PDO("mysql:host=$host;port=$port;dbname=$tenantDbName;charset=utf8mb4", $user, $password, [
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_PERSISTENT => true
        ]);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback to master if tenant database does not exist or connection fails
        $db = $masterDb;
    }
} else {
    $db = $masterDb;
}

// Check if businesses table needs migration to VARCHAR id
$needsMigration = false;
try {
    $stmtCheck = $masterDb->query("DESCRIBE businesses id");
    $colCheck = $stmtCheck->fetch();
    if ($colCheck && strpos(strtolower($colCheck['Type']), 'varchar') === false) {
        $needsMigration = true;
    }
} catch (Exception $e) {
    // Table doesn't exist yet, no migration needed
}

if ($needsMigration) {
    $masterDb->exec("DROP TABLE IF EXISTS user_duty_history");
    $masterDb->exec("DROP TABLE IF EXISTS businesses");
}

// 3. Initialize Master Schema on $masterDb
$masterDb->exec("
    CREATE TABLE IF NOT EXISTS businesses (
        id VARCHAR(100) PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL,
        db_name VARCHAR(255) DEFAULT NULL,
        contact VARCHAR(255) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$masterDb->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(255) DEFAULT NULL,
        contact VARCHAR(255) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        assigned_business_id VARCHAR(100) DEFAULT NULL,
        FOREIGN KEY (assigned_business_id) REFERENCES businesses(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$masterDb->exec("
    CREATE TABLE IF NOT EXISTS user_duty_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        business_id VARCHAR(100) NOT NULL,
        work_date DATE NOT NULL,
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY idx_user_biz_date (user_id, business_id, work_date),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Apply master-level migrations
try {
    $q = $masterDb->query("SELECT db_name FROM businesses LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $masterDb->exec("ALTER TABLE businesses ADD COLUMN db_name VARCHAR(255) DEFAULT NULL");
}
try {
    $q = $masterDb->query("SELECT name FROM users LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $masterDb->exec("ALTER TABLE users ADD COLUMN name VARCHAR(255) DEFAULT NULL");
}
try {
    $q = $masterDb->query("SELECT contact FROM users LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $masterDb->exec("ALTER TABLE users ADD COLUMN contact VARCHAR(255) DEFAULT NULL");
}
try {
    $q = $masterDb->query("SELECT email FROM users LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $masterDb->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL");
}
try {
    $q = $masterDb->query("SELECT address FROM users LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $masterDb->exec("ALTER TABLE users ADD COLUMN address VARCHAR(255) DEFAULT NULL");
}
try {
    $q = $masterDb->query("SELECT is_admin FROM users LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $masterDb->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
}
try {
    $q = $masterDb->query("SELECT assigned_business_id FROM users LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $masterDb->exec("ALTER TABLE users ADD COLUMN assigned_business_id VARCHAR(100) DEFAULT NULL");
}

$masterDb->exec("UPDATE users SET name = 'Phone Lab' WHERE name IS NULL OR name = ''");

// Seed users table if empty on Master
$userCount = $masterDb->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($userCount == 0) {
    $hashedPassword = password_hash('lab@123', PASSWORD_BCRYPT);
    $stmt = $masterDb->prepare("INSERT INTO users (username, password, is_admin, assigned_business_id) VALUES (?, ?, ?, ?)");
    
    $defaultUsers = [
        ['Tanveer', $hashedPassword, 1, 'phone-lab'],
        ['Suhail Saif', $hashedPassword, 0, 'phone-lab'],
        ['Rutvik', $hashedPassword, 0, 'phone-lab'],
        ['Umar', $hashedPassword, 0, 'phone-lab']
    ];

    foreach ($defaultUsers as $u) {
        $stmt->execute($u);
    }
} else {
    // Force promote 'Tanveer' to admin and assign default business if not done already
    $masterDb->exec("UPDATE users SET is_admin = 1, assigned_business_id = 'phone-lab' WHERE LOWER(username) = 'tanveer'");
}

// Seed businesses table if empty on Master
$bizCount = $masterDb->query("SELECT COUNT(*) FROM businesses")->fetchColumn();
if ($bizCount == 0) {
    $stmtBiz = $masterDb->prepare("INSERT INTO businesses (id, name, db_name, contact, email, address) VALUES (?, ?, ?, ?, ?, ?)");
    
    $defaultBusinesses = [
        ['phone-lab', 'Phone Lab', sanitizeTenantDbName('Phone Lab', $masterDbName), '(065) 672 4192', 'phone.lab.ennis@gmail.com', '32 O\'Connell Street, Clonroad Beg, Ennis, Co. Clare, V95 EW74'],
        ['fixd-gort', 'FIXD GORT', sanitizeTenantDbName('FIXD GORT', $masterDbName), '(089) 981 5157', 'fixd.gort@gmail.com', '1 Bridge St, Ballyhugh, Gort, Co. Galway, H91 FRC8'],
        ['gadget-repair', 'Gadget Repair & Vape shop', sanitizeTenantDbName('Gadget Repair & Vape shop', $masterDbName), '(089) 961 7473', 'istoreirl@gmail.com', 'Apartment 1, Unit 1, Millennium House, Loughrea, Co. Galway, H62 H573'],
        ['ipear-ennis', 'iPear Ennis', sanitizeTenantDbName('iPear Ennis', $masterDbName), '(065) 682 2900', '', '6 Parnell St, Clonroad Beg, Ennis, Co. Clare, V95 X073'],
        ['ipear-tesco', 'iPear in Tesco', sanitizeTenantDbName('iPear in Tesco', $masterDbName), '(065) 672 4446', 'ipear.ennis@gmail.com', 'Unit 20, Francis St, Clonroad Beg, Ennis, Co. Clare, V95 EP8K'],
        ['phone-shop-loughrea', 'Phone Shop Town Loughrea', sanitizeTenantDbName('Phone Shop Town Loughrea', $masterDbName), '', '', '']
    ];

    foreach ($defaultBusinesses as $b) {
        $stmtBiz->execute($b);
    }
} else {
    // Backfill db_name column for existing rows if they are null
    $allBizs = $masterDb->query("SELECT id, name, db_name FROM businesses")->fetchAll();
    $updateBizDb = $masterDb->prepare("UPDATE businesses SET db_name = ? WHERE id = ?");
    foreach ($allBizs as $bz) {
        if (empty($bz['db_name'])) {
            $updateBizDb->execute([sanitizeTenantDbName($bz['name'], $masterDbName), $bz['id']]);
        }
    }
}


// 4. Initialize Tenant Schema on $db (which is either a tenant DB or falls back to master DB)
$db->exec("
    CREATE TABLE IF NOT EXISTS daily_closures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        business_name VARCHAR(255) NOT NULL,
        closure_date DATE UNIQUE,
        cash_sale DECIMAL(10, 2) DEFAULT 0.00,
        card_boi DECIMAL(10, 2) DEFAULT 0.00,
        card_fixed DECIMAL(10, 2) DEFAULT 0.00,
        total_sale DECIMAL(10, 2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS booking_intakes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(100) NOT NULL UNIQUE,
        name VARCHAR(255),
        phone VARCHAR(255),
        device_name VARCHAR(255),
        email VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id VARCHAR(50) UNIQUE NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        phone_number VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        device_model VARCHAR(255) NOT NULL,
        problem_description TEXT NOT NULL,
        total_quote DECIMAL(10, 2) DEFAULT 0.00,
        deposit_paid DECIMAL(10, 2) DEFAULT 0.00,
        balance_due DECIMAL(10, 2) DEFAULT 0.00,
        business_name VARCHAR(255) NOT NULL,
        booked_by VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Apply tenant-level migrations
try {
    $q = $db->query("SELECT email FROM booking_intakes LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $db->exec("ALTER TABLE booking_intakes ADD COLUMN email VARCHAR(255) DEFAULT NULL");
}

// Create indexes to optimize query speeds (Silent fail if index already exists or syntax differences)
try {
    $db->exec("CREATE INDEX idx_daily_closures_date ON daily_closures (closure_date)");
} catch (Exception $e) {}


