<?php
require 'db.php';
require 'auth.php';
check_login();

if (!isset($_GET['id'])) {
    header("Location: sales.php");
    exit;
}

$sale_id = (int)$_GET['id'];

// ---- Fetch main sale info ----
$sqlSale = "
    SELECT 
        s.id,
        s.invoice_no,
        s.created_at,
        s.total,
        COALESCE(c.name, 'Walk-in') AS customer_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.id = ?
";
$stmt = $pdo->prepare($sqlSale);
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    header("Location: sales.php");
    exit;
}

// ---- Fetch items + computed total ----
$sqlItems = "
    SELECT 
        si.product_id,
        COALESCE(p.name, 'Deleted product') AS name,
        si.quantity,
        si.price,
        (si.quantity * si.price) AS subtotal
    FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
";
$stmtItems = $pdo->prepare($sqlItems);
$stmtItems->execute([$sale_id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

$total_amount = 0;
foreach ($items as $it) {
    $total_amount += $it['subtotal'];
}
// Older sales have no line items; fall back to the stored sale total
if (empty($items)) {
    $total_amount = (float)$sale['total'];
}

require 'header.php';
?>

<div style="max-width:900px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h1 class="page-title" style="margin-bottom:4px;">Sale Receipt</h1>
            <p class="page-subtitle">
                Transaction completed successfully
            </p>
        </div>
        <div style="text-align:right;">
            <button onclick="window.print()" class="btn primary" style="margin-bottom:6px;">
                🖨️ Print Receipt
            </button>
            <div>
                <a href="pos.php" class="btn" style="font-size:12px;padding:6px 12px;">New Sale</a>
                <a href="sales.php" class="btn" style="font-size:12px;padding:6px 12px;">View All Sales</a>
            </div>
        </div>
    </div>

    <!-- Receipt Card -->
    <div class="card" style="border:2px solid #e5e7eb;">
        <div style="text-align:center;padding-bottom:16px;border-bottom:2px dashed #d1d5db;">
            <div style="font-size:24px;font-weight:700;color:#111827;margin-bottom:4px;">💎 Megan's Jewelry</div>
            <div style="font-size:12px;color:#6b7280;">POS & Inventory System</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;padding:20px 0;border-bottom:2px dashed #d1d5db;">
            <div>
                <div style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Invoice Number</div>
                <div style="font-size:18px;font-weight:700;color:#111827;">
                    <?= htmlspecialchars($sale['invoice_no']) ?>
                </div>
            </div>

            <div>
                <div style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Date & Time</div>
                <div style="font-size:14px;font-weight:600;color:#4b5563;">
                    <?= date('M d, Y - h:i A', strtotime($sale['created_at'])) ?>
                </div>
            </div>

            <div>
                <div style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Customer</div>
                <div style="font-size:14px;font-weight:600;color:#4b5563;">
                    <?= htmlspecialchars($sale['customer_name']) ?>
                </div>
            </div>

            <div>
                <div style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Total Amount</div>
                <div style="font-size:22px;font-weight:700;color:#059669;">
                    ₱<?= number_format($total_amount, 2) ?>
                </div>
            </div>
        </div>

        <div style="padding:20px 0;">
            <div style="font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:12px;">Items Purchased</div>

            <?php if (empty($items)): ?>
                <div class="placeholder">No items for this sale.</div>
            <?php else: ?>
                <table class="table" style="width:100%;">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th>Product</th>
                            <th style="width:80px;text-align:center;">Qty</th>
                            <th style="width:120px;text-align:right;">Unit Price</th>
                            <th style="width:140px;text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td style="font-weight:500;"><?= htmlspecialchars($it['name']) ?></td>
                                <td style="text-align:center;"><?= (int)$it['quantity'] ?></td>
                                <td style="text-align:right;">₱<?= number_format($it['price'], 2) ?></td>
                                <td style="text-align:right;font-weight:600;">₱<?= number_format($it['subtotal'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid #e5e7eb;">
                            <td colspan="3" style="text-align:right;font-weight:700;font-size:16px;padding-top:12px;">TOTAL:</td>
                            <td style="text-align:right;font-weight:700;font-size:20px;color:#059669;padding-top:12px;">
                                ₱<?= number_format($total_amount, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>

        <div style="text-align:center;padding-top:16px;border-top:2px dashed #d1d5db;">
            <div style="font-size:12px;color:#6b7280;margin-bottom:8px;">Thank you for your purchase!</div>
            <div style="font-size:11px;color:#9ca3af;">This is a computer-generated receipt</div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="margin-top:20px;display:flex;gap:12px;justify-content:center;">
        <a href="pos.php" class="btn primary" style="padding:10px 24px;">
            🛍️ New Sale
        </a>
        <a href="sales.php" class="btn" style="padding:10px 24px;">
            📊 View All Sales
        </a>
    </div>
</div>

<style media="print">
    .app-card-header, .app-card-footer, .page-subtitle, button, a.btn {
        display: none !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #000 !important;
    }
</style>

<?php require 'footer.php'; ?>