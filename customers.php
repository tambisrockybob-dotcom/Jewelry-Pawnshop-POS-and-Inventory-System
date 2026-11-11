<?php
require 'db.php';
require 'auth.php';
check_login();
require 'header.php';

try {
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error loading customers: " . htmlspecialchars($e->getMessage()));
}
?>

<h1 class="page-title">Customers</h1>
<p class="page-subtitle">
    Manage your customers for faster POS and better sales tracking.
</p>

<?php if (isset($_GET['added'])): ?>
    <div style="background:#d1fae5;color:#065f46;padding:12px 16px;border-radius:12px;margin-bottom:16px;border:1px solid #6ee7b7;">
        ✓ Customer added successfully!
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div style="background:#d1fae5;color:#065f46;padding:12px 16px;border-radius:12px;margin-bottom:16px;border:1px solid #6ee7b7;">
        ✓ Customer updated successfully!
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div style="background:#d1fae5;color:#065f46;padding:12px 16px;border-radius:12px;margin-bottom:16px;border:1px solid #6ee7b7;">
        ✓ Customer deleted successfully!
    </div>
<?php endif; ?>

<div class="card" style="margin-top: 8px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div style="font-size:16px;font-weight:600;color:#111827;">Customer List</div>
        <a href="customer_add.php" class="btn primary">Add Customer</a>
    </div>

    <?php if (empty($customers)): ?>
        <p class="placeholder">No customers found. Add your first customer.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table" style="min-width:850px;">
                <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Name</th>
                    <th style="width:180px;">Phone</th>
                    <th style="width:220px;">Email</th>
                    <th style="width:150px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?= (int)$c['id'] ?></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['phone'] ?? '') ?></td>
                        <td><?= htmlspecialchars($c['email'] ?? '') ?></td>
                        <td>
                            <a href="customer_edit.php?id=<?= $c['id'] ?>" class="btn" style="font-size:12px;padding:6px 10px;">Edit</a>
                            <a href="customer_delete.php?id=<?= $c['id'] ?>"
                               class="btn"
                               style="font-size:12px;padding:6px 10px;background:#fee2e2;border-color:#fecaca;color:#b91c1c;"
                               onclick="return confirm('Delete this customer?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>