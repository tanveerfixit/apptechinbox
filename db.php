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

// Self-healing session check: if logged in but business configuration is not fully loaded in legacy active sessions
if (isset($_SESSION['user_id']) && (!isset($_SESSION['business_id']) || !isset($_SESSION['tenant_db_user']))) {
    try {
        $stmt = $masterDb->prepare("SELECT assigned_business_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $uData = $stmt->fetch();
        
        if ($uData && !empty($uData['assigned_business_id'])) {
            $bizStmt = $masterDb->prepare("SELECT * FROM businesses WHERE id = ?");
            $bizStmt->execute([$uData['assigned_business_id']]);
            $bizDetails = $bizStmt->fetch();
            if ($bizDetails) {
                $_SESSION['tenant_db_name'] = $bizDetails['db_name'];
                $_SESSION['tenant_db_user'] = $bizDetails['db_user'];
                $_SESSION['tenant_db_password'] = $bizDetails['db_password'];
                $_SESSION['business_name'] = $bizDetails['name'];
                $_SESSION['business_id'] = $bizDetails['id'];
            }
        }
    } catch (Exception $e) {}
}

// 2. Connect to Tenant Database if session is active
$tenantDbName = $_SESSION['tenant_db_name'] ?? null;
$tenantDbUser = $_SESSION['tenant_db_user'] ?? $user;
$tenantDbPass = $_SESSION['tenant_db_password'] ?? $password;
$db = null;
$tenantDbConnected = false;
$tenantDbError = '';

if ($tenantDbName) {
    try {
        $db = new PDO("mysql:host=$host;port=$port;dbname=$tenantDbName;charset=utf8mb4", $tenantDbUser, $tenantDbPass, [
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_PERSISTENT => true
        ]);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $tenantDbConnected = true;
    } catch (PDOException $e) {
        $db = null;
        $tenantDbConnected = false;
        $tenantDbError = "Could not connect to database '{$tenantDbName}'. Make sure it is created in Hostinger and user privileges are assigned.";
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

// Check if users table needs migration to VARCHAR id
$needsUserMigration = false;
try {
    $stmtCheckUser = $masterDb->query("DESCRIBE users id");
    $colCheckUser = $stmtCheckUser->fetch();
    if ($colCheckUser && strpos(strtolower($colCheckUser['Type']), 'varchar') === false) {
        $needsUserMigration = true;
    }
} catch (Exception $e) {
    // If users doesn't exist but duty history has INT, we still need to recreate it
    $needsUserMigration = true;
}

// Check if user_duty_history needs migration (e.g. if user_id is INT instead of VARCHAR)
try {
    $stmtCheckDuty = $masterDb->query("DESCRIBE user_duty_history user_id");
    $colCheckDuty = $stmtCheckDuty->fetch();
    if ($colCheckDuty && strpos(strtolower($colCheckDuty['Type']), 'varchar') === false) {
        $needsUserMigration = true;
    }
} catch (Exception $e) {
    // Table doesn't exist, no migration needed
}

if ($needsMigration || $needsUserMigration) {
    $masterDb->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $masterDb->exec("DROP TABLE IF EXISTS user_duty_history");
    if ($needsUserMigration) {
        $masterDb->exec("DROP TABLE IF EXISTS users");
    }
    if ($needsMigration) {
        $masterDb->exec("DROP TABLE IF EXISTS businesses");
    }
    $masterDb->exec("SET FOREIGN_KEY_CHECKS = 1;");
}

// Get the collation of businesses.id dynamically to avoid foreign key collation mismatch
$collation = 'utf8mb4_general_ci';
try {
    $colStmt = $masterDb->query("
        SELECT COLLATION_NAME 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'businesses' 
          AND COLUMN_NAME = 'id'
    ");
    $colRes = $colStmt->fetchColumn();
    if ($colRes) {
        $collation = $colRes;
    }
} catch (Exception $e) {
    // Fallback
}

// 3. Initialize Master Schema on $masterDb
$masterDb->exec("
    CREATE TABLE IF NOT EXISTS businesses (
        id VARCHAR(100) PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL,
        db_name VARCHAR(255) DEFAULT NULL,
        db_user VARCHAR(255) DEFAULT NULL,
        db_password VARCHAR(255) DEFAULT NULL,
        contact VARCHAR(255) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$masterDb->exec("
    CREATE TABLE IF NOT EXISTS users (
        id VARCHAR(100) CHARACTER SET utf8mb4 COLLATE {$collation} PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(255) DEFAULT NULL,
        contact VARCHAR(255) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        assigned_business_id VARCHAR(100) CHARACTER SET utf8mb4 COLLATE {$collation} DEFAULT NULL,
        FOREIGN KEY (assigned_business_id) REFERENCES businesses(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={$collation};
");

$masterDb->exec("
    CREATE TABLE IF NOT EXISTS user_duty_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(100) CHARACTER SET utf8mb4 COLLATE {$collation} NOT NULL,
        business_id VARCHAR(100) CHARACTER SET utf8mb4 COLLATE {$collation} NOT NULL,
        work_date DATE NOT NULL,
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY idx_user_biz_date (user_id, business_id, work_date),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={$collation};
");

// Apply master-level migrations
try {
    $q = $masterDb->query("SELECT db_name FROM businesses LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $masterDb->exec("ALTER TABLE businesses ADD COLUMN db_name VARCHAR(255) DEFAULT NULL");
}
try {
    $q = $masterDb->query("SELECT db_user FROM businesses LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $masterDb->exec("ALTER TABLE businesses ADD COLUMN db_user VARCHAR(255) DEFAULT NULL, ADD COLUMN db_password VARCHAR(255) DEFAULT NULL");
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
    $stmt = $masterDb->prepare("INSERT INTO users (id, username, password, is_admin, assigned_business_id) VALUES (?, ?, ?, ?, ?)");
    
    $defaultUsers = [
        ['tanveer', 'Tanveer', $hashedPassword, 1, 'phone-lab'],
        ['suhail-saif', 'Suhail Saif', $hashedPassword, 0, 'phone-lab'],
        ['rutvik', 'Rutvik', $hashedPassword, 0, 'phone-lab'],
        ['umar', 'Umar', $hashedPassword, 0, 'phone-lab']
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
    $stmtBiz = $masterDb->prepare("INSERT INTO businesses (id, name, db_name, db_user, db_password, contact, email, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $defaultBusinesses = [
        ['phone-lab', 'Phone Lab', sanitizeTenantDbName('Phone Lab', $masterDbName), 'u583652021_phone_lab', 'Techinbox@8877', '(065) 672 4192', 'phone.lab.ennis@gmail.com', '32 O\'Connell Street, Clonroad Beg, Ennis, Co. Clare, V95 EW74'],
        ['fixd-gort', 'FIXD GORT', sanitizeTenantDbName('FIXD GORT', $masterDbName), null, null, '(089) 981 5157', 'fixd.gort@gmail.com', '1 Bridge St, Ballyhugh, Gort, Co. Galway, H91 FRC8'],
        ['gadget-repair', 'Gadget Repair & Vape shop', sanitizeTenantDbName('Gadget Repair & Vape shop', $masterDbName), null, null, '(089) 961 7473', 'istoreirl@gmail.com', 'Apartment 1, Unit 1, Millennium House, Loughrea, Co. Galway, H62 H573'],
        ['ipear-ennis', 'iPear Ennis', sanitizeTenantDbName('iPear Ennis', $masterDbName), null, null, '(065) 682 2900', '', '6 Parnell St, Clonroad Beg, Ennis, Co. Clare, V95 X073'],
        ['ipear-tesco', 'iPear in Tesco', sanitizeTenantDbName('iPear in Tesco', $masterDbName), null, null, '(065) 672 4446', 'ipear.ennis@gmail.com', 'Unit 20, Francis St, Clonroad Beg, Ennis, Co. Clare, V95 EP8K'],
        ['phone-shop-loughrea', 'Phone Shop Town Loughrea', sanitizeTenantDbName('Phone Shop Town Loughrea', $masterDbName), null, null, '', '', '']
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
    // Force set the custom db credentials for phone-lab on Hostinger
    $masterDb->exec("UPDATE businesses SET db_user = 'u583652021_phone_lab', db_password = 'Techinbox@8877' WHERE id = 'phone-lab'");
}


// 4. Initialize Tenant Schema on $db (if connected to a distinct tenant database)
if ($db !== null && $db !== $masterDb) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS daily_closures (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100) DEFAULT NULL,
            business_name VARCHAR(255) NOT NULL,
            closure_date DATE UNIQUE,
            cash_sale DECIMAL(10, 2) DEFAULT 0.00,
            card_boi DECIMAL(10, 2) DEFAULT 0.00,
            card_fixed DECIMAL(10, 2) DEFAULT 0.00,
            other_payment DECIMAL(10, 2) DEFAULT 0.00,
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

    // Migration: Update daily_closures.user_id to VARCHAR(100) if it is INT
    try {
        $colType = $db->query("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'daily_closures' AND COLUMN_NAME = 'user_id'")->fetchColumn();
        if ($colType && strtolower($colType) === 'int') {
            $db->exec("ALTER TABLE daily_closures MODIFY user_id VARCHAR(100) DEFAULT NULL");
        }
    } catch (Exception $e) {}

    // Create indexes to optimize query speeds (Silent fail if index already exists or syntax differences)
    try {
        $db->exec("CREATE INDEX idx_daily_closures_date ON daily_closures (closure_date)");
    } catch (Exception $e) {}

    // Migration: Add other_payment column to daily_closures table
    try {
        $cols = $db->query("SHOW COLUMNS FROM daily_closures LIKE 'other_payment'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE daily_closures ADD COLUMN other_payment DECIMAL(10, 2) DEFAULT 0.00 AFTER card_fixed");
        }
    } catch (Exception $e) {}

    // Migration: Add status column to bookings table
    try {
        $cols = $db->query("SHOW COLUMNS FROM bookings LIKE 'status'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE bookings ADD COLUMN status VARCHAR(50) DEFAULT 'Pending' AFTER booked_by");
            $db->exec("CREATE INDEX idx_bookings_status ON bookings (status)");
        }
    } catch (Exception $e) {}

    // Migration: Add notes column to bookings table
    try {
        $cols = $db->query("SHOW COLUMNS FROM bookings LIKE 'notes'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE bookings ADD COLUMN notes TEXT DEFAULT NULL AFTER status");
        }
    } catch (Exception $e) {}

    // Migration: Create booking_payments ledger table
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS booking_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            ticket_id VARCHAR(50) NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_type VARCHAR(50) NOT NULL,
            reference_code VARCHAR(100) DEFAULT NULL,
            received_by VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Backfill historical payments
        $bookings = $db->query("SELECT id, ticket_id, deposit_paid, booked_by, created_at FROM bookings WHERE deposit_paid > 0")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bookings as $bk) {
            $check = $db->prepare("SELECT COUNT(*) FROM booking_payments WHERE booking_id = ?");
            $check->execute([$bk['id']]);
            if (intval($check->fetchColumn()) === 0) {
                $ins = $db->prepare("INSERT INTO booking_payments (booking_id, ticket_id, amount, payment_method, payment_type, reference_code, received_by, created_at) VALUES (?, ?, ?, 'Cash', 'Deposit', 'BACKFILL', ?, ?)");
                $ins->execute([$bk['id'], $bk['ticket_id'], $bk['deposit_paid'], $bk['booked_by'] ?: 'System', $bk['created_at']]);
            }
        }
    } catch (Exception $e) {}

    // Migration: Create printer_settings table and seed defaults
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS printer_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            font_size INT DEFAULT 12,
            font_family VARCHAR(255) DEFAULT \"'Courier New', Courier, monospace\",
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $pCount = $db->query("SELECT COUNT(*) FROM printer_settings")->fetchColumn();
        if (intval($pCount) === 0) {
            $db->exec("INSERT INTO printer_settings (font_size, font_family) VALUES (12, \"'Courier New', Courier, monospace\")");
        }
    } catch (Exception $e) {}
}


