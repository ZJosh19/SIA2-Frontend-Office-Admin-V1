<?php
// index.php
session_start();
require 'includes/db.php';

// 1. Simulate User Session Data (Office Admin)
$_SESSION['user'] = [
    'role' => 'Office Admin',
    'department' => 'Sales/Purchasing',
    'name' => 'Alex Mercer'
];
$user = $_SESSION['user'];

// 2. Handle Form Submissions (Only applies to Products)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'add') {
        $sku = trim($_POST['sku']);
        $name = trim($_POST['name']);
        $rp = (int)$_POST['reorder_point'];
        if ($rp > 0) {
            $stmt = $pdo->prepare("INSERT INTO products (sku, name, reorder_point) VALUES (?, ?, ?)");
            $stmt->execute([$sku, $name, $rp]);
        }
    } 
    elseif ($action == 'edit') {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $rp = (int)$_POST['reorder_point'];
        if ($rp > 0) {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, reorder_point = ? WHERE id = ?");
            $stmt->execute([$name, $rp, $id]);
        }
    } 
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
    }

    header("Location: index.php");
    exit();
}

// 3. Fetch Data for Tabs
// Fetch Active Products
$stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Stock Levels (Joined with Products to get SKU/Name)
$stmtStock = $pdo->query("
    SELECT p.sku, p.name, s.location_code, s.quantity_on_hand, s.quantity_allocated,
           (s.quantity_on_hand - s.quantity_allocated) AS available_qty
    FROM stock_levels s
    JOIN products p ON s.product_id = p.id
    WHERE p.status = 'active'
");
$stock_levels = $stmtStock->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory | Office Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body class="container py-4">

    <header class="navbar-custom mb-4">
        <h4 class="m-0">Inventory Dashboard</h4>
        <div>
            <span class="me-3">Welcome, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
            <span class="user-badge"><?= htmlspecialchars($user['role']) ?></span>
        </div>
    </header>

    <ul class="nav nav-tabs" id="inventoryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#products-tab" type="button">Product List (CRU)</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#stock-tab" type="button">Stock Levels (Read-Only)</button>
        </li>
    </ul>

    <div class="tab-content bg-white p-4 border border-top-0 rounded-bottom shadow-sm">
        
        <div class="tab-pane fade show active" id="products-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="m-0">Master Product List</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Product</button>
            </div>

            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>SKU</th><th>Name</th><th>Reorder Point</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['sku']) ?></strong></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= $p['reorder_point'] ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>">Edit</button>
                                <form method="POST" onsubmit="return confirm('Archive this item?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Archive</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit: <?= htmlspecialchars($p['sku']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Product Name</label>
                                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Reorder Point (Must be > 0)</label>
                                            <input type="number" name="reorder_point" class="form-control" value="<?= $p['reorder_point'] ?>" min="1" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save Updates</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="stock-tab">
            <h5 class="mb-3">Live Stock Availability</h5>
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Location</th>
                        <th>On Hand</th>
                        <th>Allocated (Pending Sales)</th>
                        <th class="text-success">Available to Quote</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($stock_levels) > 0): ?>
                        <?php foreach ($stock_levels as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['sku']) ?></strong></td>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($s['location_code']) ?></span></td>
                            <td><?= $s['quantity_on_hand'] ?></td>
                            <td class="text-danger"><?= $s['quantity_allocated'] ?></td>
                            <td><strong><?= $s['available_qty'] ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-3 text-muted">No stock data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <small class="text-muted">* Notice: Stock adjustments must be handled by Warehouse or Receiving staff per corporate policy.</small>
        </div>

    </div>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reorder Point</label>
                            <input type="number" name="reorder_point" class="form-control" min="1" value="10" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Create Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>