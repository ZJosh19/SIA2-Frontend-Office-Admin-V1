<?php
// seed.php
require 'includes/db.php';

$sample_products = [
    ['sku' => 'GPU-RTX-80', 'name' => 'RTX Series Graphics Card', 'rp' => 5, 'loc' => 'WH1-A', 'on_hand' => 12, 'alloc' => 2],
    ['sku' => 'MEM-32GB-D5', 'name' => '32GB DDR5 RAM Kit', 'rp' => 15, 'loc' => 'WH1-B', 'on_hand' => 45, 'alloc' => 10],
    ['sku' => 'NET-GATE-7100', 'name' => 'Netgate pfSense Security Gateway', 'rp' => 3, 'loc' => 'SEC-Z', 'on_hand' => 5, 'alloc' => 5]
];

foreach ($sample_products as $p) {
    try {
        // Insert Product
        $stmt = $pdo->prepare("INSERT INTO products (sku, name, reorder_point) VALUES (?, ?, ?)");
        $stmt->execute([$p['sku'], $p['name'], $p['rp']]);
        $product_id = $pdo->lastInsertId();

        // Insert Linked Stock Level
        $stmt2 = $pdo->prepare("INSERT INTO stock_levels (product_id, location_code, quantity_on_hand, quantity_allocated) VALUES (?, ?, ?, ?)");
        $stmt2->execute([$product_id, $p['loc'], $p['on_hand'], $p['alloc']]);

    } catch (PDOException $e) {}
}
echo "<h3>Database Seed Complete! Added Products and Stock Levels.</h3>";
echo "<a href='index.php'>Return to Dashboard</a>";
?>