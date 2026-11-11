<?php
require 'db.php';
require 'auth.php';
check_login();
require 'header.php';

// Fetch all products
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error loading products: " . htmlspecialchars($e->getMessage()));
}
?>

<style>
.alert-box {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alert-error {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.alert-warning {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
    border: 1px solid #fcd34d;
}

.alert-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.alert-content {
    flex: 1;
}

.alert-title {
    font-weight: 700;
    font-size: 15px;
    margin-bottom: 4px;
}

.alert-message {
    font-size: 13px;
    opacity: 0.9;
}

.delete-confirm-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease-out;
}

.delete-confirm-modal.active {
    display: flex;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: linear-gradient(135deg, #ffffff, #f9fafb);
    border-radius: 20px;
    padding: 32px;
    max-width: 480px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    border: 2px solid rgba(220, 38, 38, 0.3);
    animation: scaleUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes scaleUp {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.modal-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    margin: 0 auto 20px;
    border: 3px solid #dc2626;
}

.modal-title {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a2e;
    text-align: center;
    margin-bottom: 12px;
    font-family: 'Playfair Display', serif;
}

.modal-message {
    font-size: 15px;
    color: #4b5563;
    text-align: center;
    margin-bottom: 24px;
    line-height: 1.6;
}

.modal-product-info {
    background: rgba(220, 38, 38, 0.1);
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 24px;
    border: 1px solid rgba(220, 38, 38, 0.2);
}

.modal-product-name {
    font-weight: 700;
    font-size: 16px;
    color: #dc2626;
    margin-bottom: 8px;
}

.modal-product-details {
    font-size: 13px;
    color: #6b7280;
}

.modal-actions {
    display: flex;
    gap: 12px;
}

.modal-btn {
    flex: 1;
    padding: 14px 24px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}

.modal-btn-cancel {
    background: #f3f4f6;
    color: #4b5563;
    border: 2px solid #e5e7eb;
}

.modal-btn-cancel:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
}

.modal-btn-delete {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.modal-btn-delete:hover {
    background: linear-gradient(135deg, #b91c1c, #991b1b);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4);
}
</style>

<h1 class="page-title">Products</h1>
<p class="page-subtitle">
    Manage your jewelry products, pricing, stock, and descriptions.
</p>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert-box alert-success">
        <div class="alert-icon">✓</div>
        <div class="alert-content">
            <div class="alert-title">Product Updated</div>
            <div class="alert-message">Product information has been successfully updated.</div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['added'])): ?>
    <div class="alert-box alert-success">
        <div class="alert-icon">✓</div>
        <div class="alert-content">
            <div class="alert-title">Product Added</div>
            <div class="alert-message">New product has been successfully added to inventory.</div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert-box alert-success">
        <div class="alert-icon">✓</div>
        <div class="alert-content">
            <div class="alert-title">Product Deleted</div>
            <div class="alert-message">
                <?php if (isset($_GET['product_name'])): ?>
                    "<?= htmlspecialchars($_GET['product_name']) ?>" has been successfully deleted.
                <?php else: ?>
                    Product has been successfully deleted.
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <?php
    $errorType = $_GET['error'];
    $errorTitle = 'Error';
    $errorMessage = 'An unknown error occurred.';
    $errorClass = 'alert-error';
    
    switch ($errorType) {
        case 'inuse':
            $errorTitle = 'Cannot Delete Product';
            $salesCount = isset($_GET['sales_count']) ? (int)$_GET['sales_count'] : 0;
            $productName = isset($_GET['product_name']) ? htmlspecialchars($_GET['product_name']) : 'this product';
            $errorMessage = "\"$productName\" cannot be deleted because it has been sold $salesCount time(s). Products with sales history cannot be deleted to maintain accurate records.";
            $errorClass = 'alert-warning';
            break;
        case 'notfound':
            $errorTitle = 'Product Not Found';
            $errorMessage = 'The product you are trying to delete does not exist.';
            break;
        case 'constraint':
            $errorTitle = 'Database Constraint';
            $errorMessage = 'This product cannot be deleted due to database constraints. It may be referenced in sales records.';
            break;
        case 'db':
            $errorTitle = 'Database Error';
            $errorMessage = 'A database error occurred. Please try again.';
            break;
        case 'invalid_id':
            $errorTitle = 'Invalid Product ID';
            $errorMessage = 'The product ID provided is invalid.';
            break;
        case 'missing_id':
            $errorTitle = 'Missing Product ID';
            $errorMessage = 'No product ID was provided for deletion.';
            break;
    }
    ?>
    <div class="alert-box <?= $errorClass ?>">
        <div class="alert-icon">
            <?= $errorClass === 'alert-warning' ? '⚠️' : '✕' ?>
        </div>
        <div class="alert-content">
            <div class="alert-title"><?= $errorTitle ?></div>
            <div class="alert-message"><?= $errorMessage ?></div>
        </div>
    </div>
<?php endif; ?>

<div class="card" style="margin-top:8px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div style="font-size:16px;font-weight:600;color:#111827;">Product List</div>
        <a href="product_add.php" class="btn primary">+ Add Product</a>
    </div>

    <?php if (empty($products)): ?>
        <p class="placeholder">No products found. Add your first jewelry item.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table" style="min-width:1000px;">
                <thead>
                    <tr>
                        <th style="width:90px;">Code</th>
                        <th>Name</th>
                        <th style="width:250px;">Description</th>
                        <th style="width:120px;">Price</th>
                        <th style="width:80px;">Stock</th>
                        <th style="width:100px;">Weight (g)</th>
                        <th style="width:150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td style="font-family:monospace;font-weight:600;"><?= htmlspecialchars($p['code']) ?></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td style="font-size:12px;color:#6b7280;">
                                <?= htmlspecialchars(substr($p['description'] ?? '', 0, 80)) ?>
                                <?= strlen($p['description'] ?? '') > 80 ? '...' : '' ?>
                            </td>
                            <td style="font-weight:600;">₱<?= number_format($p['price'], 2) ?></td>
                            <td>
                                <?php if ($p['stock'] <= 0): ?>
                                    <span style="color:#dc2626;font-weight:600;"><?= (int)$p['stock'] ?></span>
                                <?php elseif ($p['stock'] <= 5): ?>
                                    <span style="color:#ea580c;font-weight:600;"><?= (int)$p['stock'] ?></span>
                                <?php else: ?>
                                    <?= (int)$p['stock'] ?>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($p['weight'] ?? 0, 2) ?></td>
                            <td>
                                <a href="product_edit.php?id=<?= $p['id'] ?>" 
                                   class="btn" 
                                   style="font-size:12px;padding:6px 10px;">
                                    Edit
                                </a>
                                <button 
                                    onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p['code'], ENT_QUOTES) ?>', <?= $p['price'] ?>)" 
                                    class="btn"
                                    style="font-size:12px;padding:6px 10px;background:#fee2e2;border-color:#fecaca;color:#b91c1c;">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="delete-confirm-modal">
    <div class="modal-content">
        <div class="modal-icon">⚠️</div>
        <h2 class="modal-title">Delete Product?</h2>
        <p class="modal-message">
            This action cannot be undone. Are you sure you want to permanently delete this product?
        </p>
        <div class="modal-product-info">
            <div class="modal-product-name" id="modalProductName"></div>
            <div class="modal-product-details">
                <span>Code: <strong id="modalProductCode"></strong></span> • 
                <span>Price: <strong id="modalProductPrice"></strong></span>
            </div>
        </div>
        <div class="modal-actions">
            <button onclick="closeDeleteModal()" class="modal-btn modal-btn-cancel">
                Cancel
            </button>
            <a href="#" id="confirmDeleteBtn" class="modal-btn modal-btn-delete">
                Delete Product
            </a>
        </div>
    </div>
</div>

<script>
function confirmDelete(productId, productName, productCode, productPrice) {
    // Populate modal with product info
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('modalProductCode').textContent = productCode;
    document.getElementById('modalProductPrice').textContent = '₱' + parseFloat(productPrice).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    // Set the delete link
    document.getElementById('confirmDeleteBtn').href = 'product_delete.php?id=' + productId;
    
    // Show modal
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});

// Auto-hide success/error messages after 8 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert-box');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(function() {
            alert.remove();
        }, 500);
    });
}, 8000);
</script>

<?php require 'footer.php'; ?>