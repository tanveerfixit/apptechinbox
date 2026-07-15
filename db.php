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

$host = getenv('DB_HOST') ?: 'srv2113.hstgr.io';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_DATABASE') ?: 'u583652021_apps';
$user = getenv('DB_USERNAME') ?: 'u583652021_techinbox';
$password = getenv('DB_PASSWORD') ?: 'Techinbox@8877';

try {
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password, [
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_PERSISTENT => true
    ]);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize Schema and Seed
$db->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        brand VARCHAR(255) NOT NULL,
        line VARCHAR(255) NOT NULL,
        UNIQUE (category_id, brand, line),
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS flavors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(50) DEFAULT 'active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        flavor_id INT NOT NULL,
        quantity INT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (flavor_id) REFERENCES flavors(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(255) DEFAULT NULL,
        contact VARCHAR(255) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS businesses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL,
        contact VARCHAR(255) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Apply migration to existing databases if column doesn't exist
// Note: In MySQL, check and alter column structure can be wrapped, but we ensure these exist.
// Since we initialized them in the script, these columns are already in users and order_items.
// To prevent mysql errors from ADD COLUMN IF NOT EXISTS (which is not standard MySQL syntax unless newer version), we can check if they exist or run safely.
try {
    $q = $db->query("SELECT status FROM order_items LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $db->exec("ALTER TABLE order_items ADD COLUMN status VARCHAR(50) DEFAULT 'pending'");
}
try {
    $q = $db->query("SELECT name FROM users LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $db->exec("ALTER TABLE users ADD COLUMN name VARCHAR(255) DEFAULT NULL");
}
try {
    $q = $db->query("SELECT contact FROM users LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $db->exec("ALTER TABLE users ADD COLUMN contact VARCHAR(255) DEFAULT NULL");
}
try {
    $q = $db->query("SELECT email FROM users LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $db->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL");
}
try {
    $q = $db->query("SELECT address FROM users LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $db->exec("ALTER TABLE users ADD COLUMN address VARCHAR(255) DEFAULT NULL");
}

$db->exec("UPDATE users SET name = 'Phone Lab' WHERE name IS NULL OR name = ''");

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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
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

try {
    $q = $db->query("SELECT email FROM booking_intakes LIMIT 1");
    if ($q) $q->closeCursor();
} catch (Exception $e) {
    $db->exec("ALTER TABLE booking_intakes ADD COLUMN email VARCHAR(255) DEFAULT NULL");
}

// Check if categories is empty to decide if seeding is needed
$count = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();

if ($count == 0) {
    // Seed categories and default products
    $defaultProducts = [
        ["Disposable Vape", "Lost Mary", "BM6000"],
        ["Prefilled Pods", "Lost Mary", "BM6000 Replacement Prefilled Pod"],
        ["Disposable Vape", "Lost Mary", "4in1"],
        ["Disposable Vape", "IVG", "Smart 5500"],
        ["Disposable Vape", "IVG", "2400"],
        ["Disposable Vape", "Elf Bar", "AF5000"],
        ["Disposable Vape", "Elf Bar", "4in1"],
        ["E-Liquid (Nic Salt)", "ELFLIQ", "Nic Salts Series"],
        ["E-Liquid (Nic Salt)", "MaryLiq", "Nic Salts Series"],
        ["Nicotine Pouches", "Cuba", "Ninja / White"],
        ["Nicotine Pouches", "Killa", "Extra Strong"],
        ["Nicotine Pouches", "Pablo", "Exclusive"],
        ["Caffeine Pouches", "Booster", "Strong"]
    ];

    // Extract unique categories
    $categories = array_unique(array_column($defaultProducts, 0));
    $catMap = [];

    $db->beginTransaction();
    
    $insertCat = $db->prepare("INSERT INTO categories (name) VALUES (?)");
    foreach ($categories as $cat) {
        $insertCat->execute([$cat]);
        $catMap[$cat] = $db->lastInsertId();
    }

    $insertProd = $db->prepare("INSERT INTO products (category_id, brand, line) VALUES (?, ?, ?)");
    foreach ($defaultProducts as $p) {
        $insertProd->execute([$catMap[$p[0]], $p[1], $p[2]]);
    }

    // Seed flavor list
    $flavourMasterList = [
        "Banana", "Banana Ice", "Banana Strawberry", "Berry Ice", "Blackcurrant",
        "Blackcurrant Apple", "Blackcurrant Ice", "Blood Orange", "Blue Razz Cherry",
        "Blue Razz Lemonade", "Blue Sour Raspberry", "Blueberry", "Blueberry Ice",
        "Blueberry Kiwi", "Blueberry Raspberry", "Blueberry Sour Raspberry", "Bubblegum",
        "Cherry Cola", "Cherry Ice", "Cherry Raspberry Lime", "Citrus Ice", "Cola",
        "Cola Ice", "Cool Mint", "Cotton Candy", "Custard", "Double Apple", "Energy Drink",
        "Fizzy Cherry", "Forest Berries", "Fresh Mint", "Grape", "Grape Ice", "Grapefruit",
        "Green Apple", "Green Apple Ice", "Guava Ice", "Gummy Bear", "Honeydew Melon",
        "Ice Mint", "Juicy Peach", "Kiwi Passion Fruit Guava", "Lemon & Lime", "Lemon Ice",
        "Lemon Lime", "Lemon Tart", "Lychee Ice", "Mango", "Mango Ice", "Menthol", "Mint Ice",
        "Mixed Berries", "Orange", "Orange Ice", "Papaya", "Passion Fruit", "Peach",
        "Peach Ice", "Peach Mango", "Peppermint", "Pineapple", "Pineapple Cola", "Pineapple Ice",
        "Pink Energy", "Pink Lemonade", "Rainbow Candy", "Raspberry", "Raspberry Ice",
        "Raspberry Peach", "Sour Apple", "Sour Blue Razz", "Sour Cherry", "Spearmint",
        "Strawberry", "Strawberry Banana", "Strawberry Cherry", "Strawberry Grape",
        "Strawberry Ice", "Strawberry Kiwi", "Strawberry Lemonade", "Strawberry Raspberry",
        "Strawberry Raspberry Cherry Ice", "Strawberry Watermelon", "Summer Grape",
        "Tropical Ice", "Tropical Punch", "Triple Melon", "Vanilla Ice Cream", "Watermelon",
        "Watermelon Ice"
    ];

    $insertFlavor = $db->prepare("INSERT INTO flavors (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name");
    foreach ($flavourMasterList as $f) {
        $insertFlavor->execute([$f]);
    }

    // Ensure at least one active order exists
    $orderCount = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'active'")->fetchColumn();
    if ($orderCount == 0) {
        $db->exec("INSERT INTO orders (status) VALUES ('active')");
    }

    $db->commit();
} else {
    // Check if active order exists even if seeded
    $orderCount = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'active'")->fetchColumn();
    if ($orderCount == 0) {
        $db->exec("INSERT INTO orders (status) VALUES ('active')");
    }
}

// Seed users table if empty
$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($userCount == 0) {
    $hashedPassword = password_hash('lab@123', PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    
    $defaultUsers = [
        ['Tanveer', $hashedPassword],
        ['Suhail Saif', $hashedPassword],
        ['Rutvik', $hashedPassword],
        ['Umar', $hashedPassword]
    ];

    foreach ($defaultUsers as $u) {
        $stmt->execute($u);
    }
}

// Seed businesses table if empty
$bizCount = $db->query("SELECT COUNT(*) FROM businesses")->fetchColumn();
if ($bizCount == 0) {
    $stmtBiz = $db->prepare("INSERT INTO businesses (name, contact, email, address) VALUES (?, ?, ?, ?)");
    
    $defaultBusinesses = [
        ['Phone Lab', '(065) 672 4192', 'phone.lab.ennis@gmail.com', '32 O\'Connell Street, Clonroad Beg, Ennis, Co. Clare, V95 EW74'],
        ['FIXD GORT', '(089) 981 5157', 'fixd.gort@gmail.com', '1 Bridge St, Ballyhugh, Gort, Co. Galway, H91 FRC8'],
        ['Gadget Repair & Vape shop', '(089) 961 7473', 'istoreirl@gmail.com', 'Apartment 1, Unit 1, Millennium House, Loughrea, Co. Galway, H62 H573'],
        ['iPear Ennis', '(065) 682 2900', '', '6 Parnell St, Clonroad Beg, Ennis, Co. Clare, V95 X073'],
        ['iPear in Tesco', '(065) 672 4446', 'ipear.ennis@gmail.com', 'Unit 20, Francis St, Clonroad Beg, Ennis, Co. Clare, V95 EP8K'],
        ['Phone Shop Town Loughrea', '', '', '']
    ];

    foreach ($defaultBusinesses as $b) {
        $stmtBiz->execute($b);
    }
}

// Create indexes to optimize query speeds (Silent fail if index already exists or syntax differences)
try {
    $db->exec("CREATE INDEX idx_orders_status_created ON orders (status, created_at)");
} catch (Exception $e) {}
try {
    $db->exec("CREATE INDEX idx_order_items_order ON order_items (order_id)");
} catch (Exception $e) {}
try {
    $db->exec("CREATE INDEX idx_order_items_product ON order_items (product_id)");
} catch (Exception $e) {}
try {
    $db->exec("CREATE INDEX idx_order_items_flavor ON order_items (flavor_id)");
} catch (Exception $e) {}
try {
    $db->exec("CREATE INDEX idx_daily_closures_date ON daily_closures (closure_date)");
} catch (Exception $e) {}

