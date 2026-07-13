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

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_DATABASE') ?: 'defaultdb';
$user = getenv('DB_USERNAME') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $db = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require", $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize Schema and Seed
$db->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL
    );

    CREATE TABLE IF NOT EXISTS products (
        id SERIAL PRIMARY KEY,
        category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
        brand VARCHAR(255) NOT NULL,
        line VARCHAR(255) NOT NULL,
        UNIQUE (category_id, brand, line)
    );

    CREATE TABLE IF NOT EXISTS flavors (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL
    );

    CREATE TABLE IF NOT EXISTS orders (
        id SERIAL PRIMARY KEY,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(50) DEFAULT 'active'
    );

    CREATE TABLE IF NOT EXISTS order_items (
        id SERIAL PRIMARY KEY,
        order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
        product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
        flavor_id INTEGER NOT NULL REFERENCES flavors(id) ON DELETE CASCADE,
        quantity INTEGER NOT NULL,
        status VARCHAR(50) DEFAULT 'pending'
    );

    CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL
    );
");

// Apply migration to existing databases if column doesn't exist
$db->exec("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending'");
$db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(255) DEFAULT NULL");
$db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS contact VARCHAR(255) DEFAULT NULL");
$db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL");
$db->exec("UPDATE users SET name = 'Phone Lab' WHERE name IS NULL OR name = ''");

$db->exec("
    CREATE TABLE IF NOT EXISTS daily_closures (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
        business_name VARCHAR(255) NOT NULL,
        closure_date DATE UNIQUE DEFAULT CURRENT_DATE,
        cash_sale NUMERIC(10, 2) DEFAULT 0.00,
        card_boi NUMERIC(10, 2) DEFAULT 0.00,
        card_fixed NUMERIC(10, 2) DEFAULT 0.00,
        total_sale NUMERIC(10, 2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");

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

    $insertFlavor = $db->prepare("INSERT INTO flavors (name) VALUES (?) ON CONFLICT (name) DO NOTHING");
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
    $stmt = $db->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
    $stmt->execute(['phonelab', $hashedPassword, 'Phone Lab']);
}

// Seed specific requested users if they do not exist
$newUsers = [
    ['username' => 'Tanveer', 'name' => 'Phone Lab'],
    ['username' => 'Suhail Saif', 'name' => 'Gadgets Shop'],
    ['username' => 'Rutvik', 'name' => 'iPear Tesco']
];

foreach ($newUsers as $u) {
    $check = $db->prepare("SELECT COUNT(*) FROM users WHERE LOWER(username) = LOWER(?)");
    $check->execute([$u['username']]);
    if ($check->fetchColumn() == 0) {
        $hashed = password_hash('lab@123', PASSWORD_BCRYPT);
        $insert = $db->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
        $insert->execute([$u['username'], $hashed, $u['name']]);
    }
}
