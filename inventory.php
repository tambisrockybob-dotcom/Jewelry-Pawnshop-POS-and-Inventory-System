<?php
require 'db.php';
require 'auth.php';
check_login();
require 'header.php';

// Fetch inventory (usually same as products table with stock)
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error loading inventory: " . htmlspecialchars($e->getMessage()));
}
?>

<h1 class="page-title">Inventory</h1>
<p class="page-subtitle">
    Track available stock levels and details for each jewelry item.
</p>

<div class="card" style="margin-top: 8px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div style="font-size:16px;font-weight:600;color:#111827;">Inventory Overview</div>
        <a href="products.php" class="btn">Manage Products</a>
    </div>

    <?php if (empty($items)): ?>
        <p class="placeholder">No inventory items found.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table" style="min-width:900px;">
                <thead>
                <tr>
                    <th style="width:90px;">Code</th>
                    <th>Name</th>
                    <th style="width:130px;">Price</th>
                    <th style="width:80px;">Stock</th>
                    <th style="width:100px;">Weight (g)</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['code']) ?></td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td>₱<?= number_format($item['price'], 2) ?></td>
                        <td><?= (int)$item['stock'] ?></td>
                        <td><?= number_format($item['weight'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>
