<?php
// includes/db.php
$db_dir = __DIR__ . '/../data';
if (!is_dir($db_dir)) {
    mkdir($db_dir, 0777, true);
}

$db_file = $db_dir . '/inventory.db';
$pdo = new PDO("sqlite:" . $db_file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Products Table
$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sku TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    reorder_point INTEGER NOT NULL,
    status TEXT DEFAULT 'active'
)");

// 2. Stock Levels Table (New)
$pdo->exec("CREATE TABLE IF NOT EXISTS stock_levels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER,
    location_code TEXT,
    quantity_on_hand INTEGER DEFAULT 0,
    quantity_allocated INTEGER DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id)
)");
?>