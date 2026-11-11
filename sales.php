<?php
require 'db.php';
require 'auth.php';
check_login();

// Get date filters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$quickFilter = $_GET['quick'] ?? '';

// Only accept real YYYY-MM-DD dates
foreach (['dateFrom', 'dateTo'] as $__v) {
    $d = DateTime::createFromFormat('Y-m-d', $$__v);
    if (!$d || $d->format('Y-m-d') !== $$__v) { $$__v = ''; }
}
unset($__v, $d);

// Apply quick filters
if ($quickFilter) {
    switch ($quickFilter) {
        case 'today':
            $dateFrom = date('Y-m-d');
            $dateTo = date('Y-m-d');
            break;
        case 'yesterday':
            $dateFrom = date('Y-m-d', strtotime('-1 day'));
            $dateTo = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'this_week':
            $dateFrom = date('Y-m-d', strtotime('monday this week'));
            $dateTo = date('Y-m-d');
            break;
        case 'last_week':
            $dateFrom = date('Y-m-d', strtotime('monday last week'));
            $dateTo = date('Y-m-d', strtotime('sunday last week'));
            break;
        case 'this_month':
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-d');
            break;
        case 'last_month':
            $dateFrom = date('Y-m-01', strtotime('first day of last month'));
            $dateTo = date('Y-m-t', strtotime('last day of last month'));
            break;
        case 'this_year':
            $dateFrom = date('Y-01-01');
            $dateTo = date('Y-m-d');
            break;
    }
}

// Build SQL query with date filter
$sql = "
    SELECT 
        s.id,
        s.invoice_no,
        s.created_at,
        COALESCE(c.name, 'Walk-in') AS customer_name,
        CASE WHEN COUNT(si.id) > 0 THEN SUM(si.quantity * si.price) ELSE s.total END AS total_amount
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN sale_items si ON si.sale_id = s.id
";

$params = [];
$whereConditions = [];

if ($dateFrom && $dateTo) {
    $whereConditions[] = "DATE(s.created_at) BETWEEN :date_from AND :date_to";
    $params[':date_from'] = $dateFrom;
    $params[':date_to'] = $dateTo;
} elseif ($dateFrom) {
    $whereConditions[] = "DATE(s.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
} elseif ($dateTo) {
    $whereConditions[] = "DATE(s.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(' AND ', $whereConditions);
}

$sql .= " GROUP BY s.id, s.invoice_no, s.created_at, s.total, customer_name ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_sales = count($sales);
$total_revenue = array_sum(array_column($sales, 'total_amount'));
$average_sale = $total_sales > 0 ? $total_revenue / $total_sales : 0;

// Get date range for display
$dateRangeText = 'All Time';
if ($dateFrom && $dateTo) {
    if ($dateFrom === $dateTo) {
        $dateRangeText = date('F d, Y', strtotime($dateFrom));
    } else {
        $dateRangeText = date('M d, Y', strtotime($dateFrom)) . ' - ' . date('M d, Y', strtotime($dateTo));
    }
} elseif ($dateFrom) {
    $dateRangeText = 'From ' . date('M d, Y', strtotime($dateFrom));
} elseif ($dateTo) {
    $dateRangeText = 'Until ' . date('M d, Y', strtotime($dateTo));
}

require 'header.php';
?>

<style>
.filter-container {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.08), rgba(212, 175, 55, 0.04));
    border: 2px solid rgba(212, 175, 55, 0.2);
    border-radius: 20px;
    padding: 24px 28px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(212, 175, 55, 0.1);
}

.filter-title {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.quick-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(212, 175, 55, 0.2);
}

.quick-filter-btn {
    padding: 10px 18px;
    border-radius: 10px;
    border: 2px solid rgba(212, 175, 55, 0.3);
    background: #ffffff;
    color: #4b5563;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.quick-filter-btn:hover {
    background: rgba(212, 175, 55, 0.1);
    border-color: rgba(212, 175, 55, 0.5);
    color: #d4af37;
    transform: translateY(-2px);
}

.quick-filter-btn.active {
    background: linear-gradient(135deg, #d4af37, #f4d03f);
    border-color: #d4af37;
    color: #1a1a2e;
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
}

.custom-date-filter {
    display: grid;
    grid-template-columns: 1fr 1fr auto auto;
    gap: 12px;
    align-items: end;
}

.date-input-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.date-label {
    font-size: 12px;
    font-weight: 600;
    color: #4b5563;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.date-input {
    padding: 12px 16px;
    border: 2px solid rgba(212, 175, 55, 0.3);
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    background: #ffffff;
    transition: all 0.3s ease;
    color: #1a1a2e;
}

.date-input:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
}

.filter-btn {
    padding: 12px 28px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #d4af37, #f4d03f);
    color: #1a1a2e;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.filter-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(212, 175, 55, 0.4);
}

.clear-btn {
    padding: 12px 24px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    background: #ffffff;
    color: #6b7280;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.clear-btn:hover {
    border-color: #d1d5db;
    background: #f9fafb;
}

.results-summary {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(212, 175, 55, 0.05));
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 20px;
    border: 1px solid rgba(212, 175, 55, 0.2);
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.results-title {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 700;
    color: #1a1a2e;
}

.results-period {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.results-period strong {
    color: #d4af37;
    font-weight: 700;
}

.export-btn {
    padding: 10px 20px;
    border-radius: 10px;
    background: linear-gradient(135deg, #059669, #10b981);
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-decoration: none;
    display: inline-block;
}

.export-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(5, 150, 105, 0.4);
}

.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.summary-stat {
    background: rgba(255, 255, 255, 0.8);
    padding: 16px;
    border-radius: 12px;
    border: 1px solid rgba(212, 175, 55, 0.15);
}

.summary-stat-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7280;
    margin-bottom: 8px;
    font-weight: 600;
}

.summary-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a2e;
    font-family: 'Playfair Display', serif;
}

.summary-stat-value.highlight {
    color: #d4af37;
}

.no-data-message {
    text-align: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.05), rgba(212, 175, 55, 0.02));
    border-radius: 20px;
    border: 2px dashed rgba(212, 175, 55, 0.3);
}

.no-data-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.no-data-text {
    font-size: 18px;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 8px;
}

.no-data-hint {
    font-size: 14px;
    color: #9ca3af;
}

@media (max-width: 768px) {
    .custom-date-filter {
        grid-template-columns: 1fr;
    }

    .quick-filters {
        gap: 6px;
    }

    .quick-filter-btn {
        font-size: 12px;
        padding: 8px 14px;
    }
}
</style>

<h1 class="page-title">Sales History</h1>
<p class="page-subtitle">
    View completed transactions and access detailed invoices with date filters.
</p>

<!-- Filter Container -->
<div class="filter-container">
    <div class="filter-title">📅 Filter Sales Reports</div>
    
    <!-- Quick Filters -->
    <div class="quick-filters">
        <a href="?quick=today" class="quick-filter-btn <?= $quickFilter === 'today' ? 'active' : '' ?>">
            Today
        </a>
        <a href="?quick=yesterday" class="quick-filter-btn <?= $quickFilter === 'yesterday' ? 'active' : '' ?>">
            Yesterday
        </a>
        <a href="?quick=this_week" class="quick-filter-btn <?= $quickFilter === 'this_week' ? 'active' : '' ?>">
            This Week
        </a>
        <a href="?quick=last_week" class="quick-filter-btn <?= $quickFilter === 'last_week' ? 'active' : '' ?>">
            Last Week
        </a>
        <a href="?quick=this_month" class="quick-filter-btn <?= $quickFilter === 'this_month' ? 'active' : '' ?>">
            This Month
        </a>
        <a href="?quick=last_month" class="quick-filter-btn <?= $quickFilter === 'last_month' ? 'active' : '' ?>">
            Last Month
        </a>
        <a href="?quick=this_year" class="quick-filter-btn <?= $quickFilter === 'this_year' ? 'active' : '' ?>">
            This Year
        </a>
    </div>

    <!-- Custom Date Range -->
    <form method="get" class="custom-date-filter">
        <div class="date-input-group">
            <label class="date-label">From Date</label>
            <input type="date" name="date_from" class="date-input" value="<?= htmlspecialchars($dateFrom) ?>" max="<?= date('Y-m-d') ?>">
        </div>
        
        <div class="date-input-group">
            <label class="date-label">To Date</label>
            <input type="date" name="date_to" class="date-input" value="<?= htmlspecialchars($dateTo) ?>" max="<?= date('Y-m-d') ?>">
        </div>
        
        <button type="submit" class="filter-btn">Apply Filter</button>
        
        <?php if ($dateFrom || $dateTo || $quickFilter): ?>
            <a href="sales.php" class="clear-btn">Clear All</a>
        <?php endif; ?>
    </form>
</div>

<!-- Results Summary -->
<div class="results-summary">
    <div class="results-header">
        <div>
            <div class="results-title">Sales Report</div>
            <div class="results-period">
                Period: <strong><?= $dateRangeText ?></strong>
            </div>
        </div>
        <?php if (!empty($sales)): ?>
            <button onclick="window.print()" class="export-btn">
                🖨️ Print Report
            </button>
        <?php endif; ?>
    </div>

    <div class="summary-stats">
        <div class="summary-stat">
            <div class="summary-stat-label">Total Sales</div>
            <div class="summary-stat-value"><?= number_format($total_sales) ?></div>
        </div>
        <div class="summary-stat">
            <div class="summary-stat-label">Total Revenue</div>
            <div class="summary-stat-value highlight">₱<?= number_format($total_revenue, 2) ?></div>
        </div>
        <div class="summary-stat">
            <div class="summary-stat-label">Average Sale</div>
            <div class="summary-stat-value">₱<?= number_format($average_sale, 2) ?></div>
        </div>
    </div>
</div>

<!-- Sales Table -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:10px;">
        <div style="font-size:16px;font-weight:600;color:#111827;">
            <?= $total_sales ?> Transaction(s)
        </div>
        <a href="pos.php" class="btn primary">
            🛍️ New Sale (POS)
        </a>
    </div>

    <?php if (empty($sales)): ?>
        <div class="no-data-message">
            <div class="no-data-icon">📊</div>
            <div class="no-data-text">No sales found for this period</div>
            <div class="no-data-hint">
                <?php if ($dateFrom || $dateTo || $quickFilter): ?>
                    Try adjusting your date range or <a href="sales.php" style="color:#d4af37;font-weight:600;">view all sales</a>
                <?php else: ?>
                    Start selling by going to the <a href="pos.php" style="color:#d4af37;font-weight:600;">POS page</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table" style="min-width:800px;">
                <thead>
                    <tr>
                        <th style="width:180px;">Invoice #</th>
                        <th>Customer</th>
                        <th style="width:140px;">Total</th>
                        <th style="width:200px;">Date</th>
                        <th style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td style="font-family:monospace;font-weight:600;"><?= htmlspecialchars($sale['invoice_no']) ?></td>
                            <td>
                                <?php if ($sale['customer_name'] === 'Walk-in'): ?>
                                    <span style="color:#9ca3af;"><?= htmlspecialchars($sale['customer_name']) ?></span>
                                <?php else: ?>
                                    <?= htmlspecialchars($sale['customer_name']) ?>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600;color:#059669;">₱<?= number_format($sale['total_amount'], 2) ?></td>
                            <td style="font-size:13px;color:#6b7280;">
                                <?= date('M d, Y - h:i A', strtotime($sale['created_at'])) ?>
                            </td>
                            <td>
                                <a href="sale_view.php?id=<?= $sale['id'] ?>" class="btn" style="font-size:12px;padding:6px 12px;">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style media="print">
    .filter-container, .btn, .app-card-header, .app-card-footer, .page-subtitle {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #000 !important;
    }
    
    body {
        background: white !important;
    }
    
    .page-title {
        color: #000 !important;
    }
</style>

<?php require 'footer.php'; ?>