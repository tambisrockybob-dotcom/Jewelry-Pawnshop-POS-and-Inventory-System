<?php
require 'db.php';
require 'auth.php';
check_login();
require 'header.php';

// Dashboard stats
$totalProducts  = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$totalSales     = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$totalRevenue   = $pdo->query("SELECT IFNULL(SUM(total),0) FROM sales")->fetchColumn();

// Sales data for chart (last 7 days)
$salesChartData = $pdo->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as sales_count,
        SUM(total) as daily_revenue
    FROM sales
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Sales data for monthly chart (last 30 days)
$monthlySalesData = $pdo->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as sales_count,
        SUM(total) as daily_revenue
    FROM sales
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for JavaScript
$chartLabels = [];
$chartSalesData = [];
$chartRevenueData = [];

foreach ($salesChartData as $row) {
    $chartLabels[] = date('M d', strtotime($row['date']));
    $chartSalesData[] = (int)$row['sales_count'];
    $chartRevenueData[] = (float)$row['daily_revenue'];
}

// Monthly data
$monthlyLabels = [];
$monthlySales = [];
$monthlyRevenue = [];

foreach ($monthlySalesData as $row) {
    $monthlyLabels[] = date('M d', strtotime($row['date']));
    $monthlySales[] = (int)$row['sales_count'];
    $monthlyRevenue[] = (float)$row['daily_revenue'];
}

// Recent sales
$recentSalesStmt = $pdo->query("
    SELECT s.id, s.invoice_no, s.total, s.created_at, COALESCE(c.name, 'Walk-in') AS customer_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    ORDER BY s.created_at DESC, s.id DESC
    LIMIT 5
");
$recentSales = $recentSalesStmt->fetchAll(PDO::FETCH_ASSOC);

// Product highlights
$productStmt = $pdo->query("
    SELECT id, name, price, stock
    FROM products
    ORDER BY id DESC
    LIMIT 5
");
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

// Low Stock Items (stock <= 5)
$lowStockStmt = $pdo->query("
    SELECT id, code, name, price, stock
    FROM products
    WHERE stock <= 5
    ORDER BY stock ASC, name ASC
");
$lowStockItems = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);

// Out of Stock Items (stock = 0)
$outOfStockStmt = $pdo->query("
    SELECT COUNT(*) as count
    FROM products
    WHERE stock = 0
");
$outOfStockCount = $outOfStockStmt->fetchColumn();

// Top selling products
$topProductsStmt = $pdo->query("
    SELECT 
        p.name,
        SUM(si.quantity) as total_sold,
        SUM(si.quantity * si.price) as total_revenue
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    GROUP BY si.product_id, p.name
    ORDER BY total_sold DESC
    LIMIT 5
");
$topProducts = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);

// Product categories data for bar chart
// Group products by their category (extracted from product name)
$categoriesStmt = $pdo->query("
    SELECT 
        CASE 
            WHEN LOWER(p.name) LIKE '%ring%' THEN 'Rings'
            WHEN LOWER(p.name) LIKE '%necklace%' OR LOWER(p.name) LIKE '%chain%' OR LOWER(p.name) LIKE '%pendant%' THEN 'Necklaces'
            WHEN LOWER(p.name) LIKE '%earring%' THEN 'Earrings'
            WHEN LOWER(p.name) LIKE '%bracelet%' THEN 'Bracelets'
            WHEN LOWER(p.name) LIKE '%brooch%' THEN 'Brooches'
            ELSE 'Other Jewelry'
        END as category,
        COUNT(DISTINCT p.id) as product_count,
        COALESCE(SUM(si.quantity), 0) as total_sold,
        COALESCE(SUM(si.quantity * si.price), 0) as total_revenue
    FROM products p
    LEFT JOIN sale_items si ON p.id = si.product_id
    GROUP BY category
    ORDER BY total_revenue DESC
");
$categoriesData = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare category data for JavaScript
$categoryLabels = [];
$categoryProducts = [];
$categorySales = [];
$categoryRevenue = [];

foreach ($categoriesData as $cat) {
    $categoryLabels[] = $cat['category'];
    $categoryProducts[] = (int)$cat['product_count'];
    $categorySales[] = (int)$cat['total_sold'];
    $categoryRevenue[] = (float)$cat['total_revenue'];
}
?>

<style>
.chart-container {
    position: relative;
    width: 100%;
    height: 320px;
    margin-top: 20px;
}

.chart-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    border-bottom: 2px solid rgba(212, 175, 55, 0.1);
    padding-bottom: 12px;
}

.chart-tab {
    padding: 8px 20px;
    border-radius: 8px;
    border: 1px solid transparent;
    background: transparent;
    color: #6b7280;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.chart-tab:hover {
    background: rgba(212, 175, 55, 0.1);
    color: #d4af37;
}

.chart-tab.active {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(212, 175, 55, 0.1));
    border-color: rgba(212, 175, 55, 0.3);
    color: #d4af37;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-top: 16px;
}

.top-products-card {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 18px;
    padding: 24px;
    color: #ffffff;
    border: 1px solid rgba(212, 175, 55, 0.2);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.category-chart-container {
    position: relative;
    width: 100%;
    height: 350px;
    margin-top: 20px;
}

.chart-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 16px;
    padding: 16px;
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.05), rgba(212, 175, 55, 0.02));
    border-radius: 12px;
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #4b5563;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.top-products-card h3 {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    color: #d4af37;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.product-rank-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px;
    margin-bottom: 10px;
    background: rgba(212, 175, 55, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(212, 175, 55, 0.1);
    transition: all 0.3s ease;
}

.product-rank-item:hover {
    background: rgba(212, 175, 55, 0.1);
    border-color: rgba(212, 175, 55, 0.2);
    transform: translateX(4px);
}

.rank-badge {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d4af37, #f4d03f);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #1a1a2e;
    font-size: 14px;
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
}

.product-rank-info {
    flex: 1;
}

.product-rank-name {
    font-weight: 600;
    color: #ffffff;
    font-size: 14px;
    margin-bottom: 4px;
}

.product-rank-stats {
    font-size: 11px;
    color: #9ca3af;
}

.product-rank-revenue {
    font-weight: 700;
    color: #d4af37;
    font-size: 14px;
}

/* LOW STOCK ALERTS */
.low-stock-alert-card {
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.95) 0%, rgba(185, 28, 28, 0.95) 100%);
    border-radius: 18px;
    padding: 24px;
    color: #ffffff;
    border: 1px solid rgba(254, 202, 202, 0.3);
    box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}

.low-stock-alert-card.warning {
    background: linear-gradient(135deg, rgba(234, 88, 12, 0.95) 0%, rgba(194, 65, 12, 0.95) 100%);
    box-shadow: 0 8px 24px rgba(234, 88, 12, 0.4);
}

.low-stock-alert-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.low-stock-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    position: relative;
    z-index: 1;
}

.low-stock-title {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.low-stock-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.low-stock-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: relative;
    z-index: 1;
}

.low-stock-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.low-stock-item:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.4);
    transform: translateX(4px);
}

.low-stock-item-info {
    flex: 1;
}

.low-stock-item-name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
}

.low-stock-item-code {
    font-size: 11px;
    opacity: 0.8;
    font-family: monospace;
}

.low-stock-item-stock {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stock-indicator {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
    border: 2px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.stock-indicator.critical {
    animation: pulse-critical 2s ease-in-out infinite;
}

@keyframes pulse-critical {
    0%, 100% {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        transform: scale(1);
    }
    50% {
        box-shadow: 0 4px 20px rgba(255, 255, 255, 0.4);
        transform: scale(1.05);
    }
}

.stock-label {
    font-size: 11px;
    opacity: 0.9;
}

.low-stock-action {
    margin-left: 12px;
}

.low-stock-action .btn {
    font-size: 11px;
    padding: 6px 12px;
    background: rgba(255, 255, 255, 0.9);
    color: #dc2626;
    border: none;
}

.low-stock-action .btn:hover {
    background: #ffffff;
    transform: translateY(-2px);
}

.no-low-stock {
    text-align: center;
    padding: 30px 20px;
    position: relative;
    z-index: 1;
}

.no-low-stock-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.no-low-stock-text {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 6px;
}

.no-low-stock-subtext {
    font-size: 13px;
    opacity: 0.8;
}

@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<h1 class="page-title">Dashboard</h1>
<p class="page-subtitle">
    Overview of your jewelry products, customers, and recent sales activity.
</p>

<!-- Low Stock Alert Banner -->
<?php if (!empty($lowStockItems)): ?>
    <div class="low-stock-alert-card <?= $outOfStockCount > 0 ? '' : 'warning' ?>">
        <div class="low-stock-header">
            <div class="low-stock-title">
                <?php if ($outOfStockCount > 0): ?>
                    🚨 Critical Stock Alert
                <?php else: ?>
                    ⚠️ Low Stock Warning
                <?php endif; ?>
            </div>
            <div class="low-stock-badge">
                <?= count($lowStockItems) ?> Item(s)
            </div>
        </div>

        <?php if (count($lowStockItems) <= 5): ?>
            <!-- Show all items if 5 or less -->
            <div class="low-stock-items">
                <?php foreach ($lowStockItems as $item): ?>
                    <div class="low-stock-item">
                        <div class="low-stock-item-info">
                            <div class="low-stock-item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="low-stock-item-code">Code: <?= htmlspecialchars($item['code']) ?></div>
                        </div>
                        <div class="low-stock-item-stock">
                            <div>
                                <div class="stock-indicator <?= $item['stock'] == 0 ? 'critical' : '' ?>">
                                    <?= (int)$item['stock'] ?>
                                </div>
                                <div class="stock-label" style="text-align:center;margin-top:4px;">
                                    <?= $item['stock'] == 0 ? 'Out' : 'Left' ?>
                                </div>
                            </div>
                        </div>
                        <div class="low-stock-action">
                            <a href="product_edit.php?id=<?= $item['id'] ?>" class="btn">
                                Restock
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Show only first 5 with "View All" link -->
            <div class="low-stock-items">
                <?php foreach (array_slice($lowStockItems, 0, 5) as $item): ?>
                    <div class="low-stock-item">
                        <div class="low-stock-item-info">
                            <div class="low-stock-item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="low-stock-item-code">Code: <?= htmlspecialchars($item['code']) ?></div>
                        </div>
                        <div class="low-stock-item-stock">
                            <div>
                                <div class="stock-indicator <?= $item['stock'] == 0 ? 'critical' : '' ?>">
                                    <?= (int)$item['stock'] ?>
                                </div>
                                <div class="stock-label" style="text-align:center;margin-top:4px;">
                                    <?= $item['stock'] == 0 ? 'Out' : 'Left' ?>
                                </div>
                            </div>
                        </div>
                        <div class="low-stock-action">
                            <a href="product_edit.php?id=<?= $item['id'] ?>" class="btn">
                                Restock
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align:center;margin-top:16px;">
                <a href="inventory.php" class="btn" style="background:rgba(255,255,255,0.9);color:#dc2626;font-weight:600;">
                    View All <?= count($lowStockItems) ?> Low Stock Items
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Stats row -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Products</div>
        <div class="stat-value"><?= (int)$totalProducts ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Customers</div>
        <div class="stat-value"><?= (int)$totalCustomers ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Sales</div>
        <div class="stat-value"><?= (int)$totalSales ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value">₱<?= number_format($totalRevenue, 2) ?></div>
    </div>
</div>

<!-- Sales Chart -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <div style="font-size:18px;font-weight:700;color:#111827;font-family:'Playfair Display',serif;margin-bottom:4px;">
                📈 Sales Analytics
            </div>
            <div style="font-size:12px;color:#6b7280;">Track your daily sales and revenue performance</div>
        </div>
    </div>

    <div class="chart-tabs">
        <button class="chart-tab active" onclick="switchChart('week')">Last 7 Days</button>
        <button class="chart-tab" onclick="switchChart('month')">Last 30 Days</button>
    </div>

    <div class="chart-container">
        <canvas id="salesChart"></canvas>
    </div>
</div>

<!-- Product Categories Bar Chart -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <div style="font-size:18px;font-weight:700;color:#111827;font-family:'Playfair Display',serif;margin-bottom:4px;">
                💎 Product Categories Performance
            </div>
            <div style="font-size:12px;color:#6b7280;">Sales and revenue breakdown by jewelry category</div>
        </div>
    </div>

    <div class="category-chart-container">
        <canvas id="categoryChart"></canvas>
    </div>
</div>

<!-- Dashboard Grid -->
<div class="dashboard-grid">
    <!-- Recent Sales -->
    <div>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <div style="font-size:16px;font-weight:600;">Recent Sales</div>
                <a href="sales.php" class="btn" style="font-size:12px;padding:6px 14px;">View all</a>
            </div>

            <?php if (empty($recentSales)): ?>
                <div class="placeholder">No sales recorded yet. Create a new sale from the POS screen.</div>
            <?php else: ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentSales as $sale): ?>
                        <tr>
                            <td style="font-family:monospace;"><?= htmlspecialchars($sale['invoice_no']) ?></td>
                            <td><?= $sale['customer_name'] ? htmlspecialchars($sale['customer_name']) : '<span style="color:#9ca3af;">Walk-in</span>' ?></td>
                            <td style="font-weight:600;color:#059669;">₱<?= number_format($sale['total'], 2) ?></td>
                            <td style="font-size:12px;color:#6b7280;"><?= date('M d, h:i A', strtotime($sale['created_at'])) ?></td>
                            <td>
                                <a href="sale_view.php?id=<?= (int)$sale['id'] ?>"
                                   class="btn"
                                   style="font-size:11px;padding:5px 10px;">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Product Highlights -->
        <div class="card" style="margin-top:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <div style="font-size:16px;font-weight:600;">Product Highlights</div>
                <a href="products.php" class="btn" style="font-size:12px;padding:6px 14px;">Manage products</a>
            </div>

            <?php if (empty($products)): ?>
                <div class="placeholder">No products yet. Add your first jewelry item in the Products page.</div>
            <?php else: ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td>₱<?= number_format($p['price'], 2) ?></td>
                            <td><?= (int)$p['stock'] ?></td>
                            <td>
                                <a href="product_edit.php?id=<?= (int)$p['id'] ?>"
                                   class="btn"
                                   style="font-size:11px;padding:5px 10px;">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Top Selling Products -->
        <div class="top-products-card">
            <h3>🏆 Top Selling Products</h3>
            
            <?php if (empty($topProducts)): ?>
                <div style="text-align:center;padding:20px;color:#9ca3af;">
                    No sales data yet
                </div>
            <?php else: ?>
                <?php $rank = 1; foreach ($topProducts as $tp): ?>
                    <div class="product-rank-item">
                        <div class="rank-badge"><?= $rank ?></div>
                        <div class="product-rank-info">
                            <div class="product-rank-name"><?= htmlspecialchars($tp['name']) ?></div>
                            <div class="product-rank-stats">
                                <?= (int)$tp['total_sold'] ?> units sold
                            </div>
                        </div>
                        <div class="product-rank-revenue">
                            ₱<?= number_format($tp['total_revenue'], 0) ?>
                        </div>
                    </div>
                <?php $rank++; endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="card" style="margin-top:20px;">
            <div style="font-size:14px;font-weight:600;margin-bottom:12px;">⚡ Quick Actions</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <a href="pos.php" class="btn primary" style="width:100%;justify-content:center;">
                    🛍️ New Sale (POS)
                </a>
                <a href="customer_add.php" class="btn" style="width:100%;justify-content:center;">
                    👤 Add Customer
                </a>
                <a href="product_add.php" class="btn" style="width:100%;justify-content:center;">
                    💎 Add Product
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Data from PHP
const weeklyData = {
    labels: <?= json_encode($chartLabels) ?>,
    sales: <?= json_encode($chartSalesData) ?>,
    revenue: <?= json_encode($chartRevenueData) ?>
};

const monthlyData = {
    labels: <?= json_encode($monthlyLabels) ?>,
    sales: <?= json_encode($monthlySales) ?>,
    revenue: <?= json_encode($monthlyRevenue) ?>
};

const categoryData = {
    labels: <?= json_encode($categoryLabels) ?>,
    products: <?= json_encode($categoryProducts) ?>,
    sales: <?= json_encode($categorySales) ?>,
    revenue: <?= json_encode($categoryRevenue) ?>
};

let currentChart;
let categoryChart;
let currentView = 'week';

// Luxury color palette for categories
const categoryColors = [
    { bg: 'rgba(212, 175, 55, 0.8)', border: '#d4af37' },      // Gold
    { bg: 'rgba(99, 102, 241, 0.8)', border: '#6366f1' },      // Indigo
    { bg: 'rgba(236, 72, 153, 0.8)', border: '#ec4899' },      // Pink
    { bg: 'rgba(20, 184, 166, 0.8)', border: '#14b8a6' },      // Teal
    { bg: 'rgba(251, 146, 60, 0.8)', border: '#fb923c' },      // Orange
    { bg: 'rgba(168, 85, 247, 0.8)', border: '#a855f7' }       // Purple
];

// Create gradient
function createGradient(ctx, color1, color2) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, color1);
    gradient.addColorStop(1, color2);
    return gradient;
}

// Initialize sales chart
function initChart(data) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    if (currentChart) {
        currentChart.destroy();
    }

    currentChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Revenue (₱)',
                    data: data.revenue,
                    borderColor: '#d4af37',
                    backgroundColor: createGradient(ctx, 'rgba(212, 175, 55, 0.3)', 'rgba(212, 175, 55, 0.05)'),
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#d4af37',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: '#f4d03f',
                    pointHoverBorderColor: '#d4af37',
                    yAxisID: 'y-revenue',
                },
                {
                    label: 'Sales Count',
                    data: data.sales,
                    borderColor: '#6366f1',
                    backgroundColor: createGradient(ctx, 'rgba(99, 102, 241, 0.2)', 'rgba(99, 102, 241, 0.05)'),
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: '#818cf8',
                    pointHoverBorderColor: '#6366f1',
                    yAxisID: 'y-sales',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 13,
                            weight: 600,
                            family: "'Inter', sans-serif"
                        },
                        color: '#1a1a2e'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(26, 26, 46, 0.95)',
                    titleColor: '#d4af37',
                    bodyColor: '#ffffff',
                    borderColor: 'rgba(212, 175, 55, 0.3)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.datasetIndex === 0) {
                                    label += '₱' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                } else {
                                    label += context.parsed.y + ' sales';
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                'y-revenue': {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(212, 175, 55, 0.1)',
                        drawBorder: false,
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 11,
                            weight: 500
                        },
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    },
                    title: {
                        display: true,
                        text: 'Revenue (₱)',
                        color: '#d4af37',
                        font: {
                            size: 12,
                            weight: 600
                        }
                    }
                },
                'y-sales': {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 11,
                            weight: 500
                        }
                    },
                    title: {
                        display: true,
                        text: 'Sales Count',
                        color: '#6366f1',
                        font: {
                            size: 12,
                            weight: 600
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 11,
                            weight: 500
                        }
                    }
                }
            }
        }
    });
}

// Initialize category bar chart
function initCategoryChart() {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    
    if (categoryChart) {
        categoryChart.destroy();
    }

    const backgroundColors = categoryData.labels.map((_, i) => categoryColors[i % categoryColors.length].bg);
    const borderColors = categoryData.labels.map((_, i) => categoryColors[i % categoryColors.length].border);

    categoryChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categoryData.labels,
            datasets: [
                {
                    label: 'Revenue (₱)',
                    data: categoryData.revenue,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(26, 26, 46, 0.95)',
                    titleColor: '#d4af37',
                    bodyColor: '#ffffff',
                    borderColor: 'rgba(212, 175, 55, 0.3)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            return context[0].label;
                        },
                        label: function(context) {
                            return [
                                'Revenue: ₱' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2}),
                                'Units Sold: ' + categoryData.sales[context.dataIndex].toLocaleString(),
                                'Products: ' + categoryData.products[context.dataIndex]
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(212, 175, 55, 0.1)',
                        drawBorder: false,
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 11,
                            weight: 500
                        },
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    },
                    title: {
                        display: true,
                        text: 'Total Revenue (₱)',
                        color: '#d4af37',
                        font: {
                            size: 12,
                            weight: 600
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#1a1a2e',
                        font: {
                            size: 12,
                            weight: 600
                        }
                    }
                }
            }
        }
    });
}

// Switch between week and month view
function switchChart(view) {
    currentView = view;
    
    // Update tab styling
    document.querySelectorAll('.chart-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update chart
    if (view === 'week') {
        initChart(weeklyData);
    } else {
        initChart(monthlyData);
    }
}

// Initialize both charts
initChart(weeklyData);
initCategoryChart();
</script>

<?php require 'footer.php'; ?>