<?php
/**
 * SIMPLIFIED GOLD-BASED JEWELRY PRICE CALCULATOR
 * 
 * This script helps calculate realistic jewelry prices based on:
 * 1. Current gold price per gram in Philippines
 * 2. Gold karat (purity)
 * 3. Weight of the jewelry
 * 4. Markup percentage
 */

require 'db.php';
require 'auth.php';
check_login();

// CURRENT GOLD PRICES IN PHILIPPINES (₱ per gram) - Update these regularly
// Single source of truth lives in config.php
$gold_prices = GOLD_PRICES;

// Handle form submission for bulk price update
$message = '';
$updated_products = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_prices'])) {
    $markup_percentage = (float)($_POST['markup_percentage'] ?? 30);
    $markup_percentage = max(0, min(1000, $markup_percentage));
    $skipped_products = [];
    $selected_products = (array)($_POST['selected_products'] ?? []);
    
    foreach ($selected_products as $product_id) {
        $product_id = (int)$product_id;
        
        // Get product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $weight = (float)$product['weight'];
            $karat = $_POST['karat_' . $product_id] ?? '18k';

            // An unknown karat or a zero weight would have set the price to 0 - skip instead
            if (!isset($gold_prices[$karat]) || $weight <= 0) {
                $skipped_products[] = $product['name'];
                continue;
            }

            // Calculate new price (simplified formula)
            $gold_value = $weight * $gold_prices[$karat];
            $new_price = $gold_value * (1 + ($markup_percentage / 100));
            $new_price = round($new_price, 2);
            
            // Update database
            $updateStmt = $pdo->prepare("UPDATE products SET price = ? WHERE id = ?");
            $updateStmt->execute([$new_price, $product_id]);
            
            $updated_products[] = [
                'name' => $product['name'],
                'old_price' => $product['price'],
                'new_price' => $new_price,
                'difference' => $new_price - $product['price']
            ];
        }
    }
    
    $message = count($updated_products) . ' product(s) updated successfully!';
    if (!empty($skipped_products)) {
        $message .= ' Skipped (no weight or invalid karat): ' . implode(', ', $skipped_products) . '.';
    }
}

// Get all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

require 'header.php';
?>

<style>
.calculator-section {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(212, 175, 55, 0.05));
    border: 2px solid rgba(212, 175, 55, 0.3);
    border-radius: 20px;
    padding: 28px 32px;
    margin-bottom: 24px;
}

.gold-prices-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin: 20px 0;
}

.gold-price-card {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    padding: 20px;
    border-radius: 16px;
    text-align: center;
    border: 2px solid rgba(212, 175, 55, 0.3);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}

.gold-price-karat {
    font-size: 24px;
    font-weight: 700;
    color: #d4af37;
    margin-bottom: 8px;
    font-family: 'Playfair Display', serif;
}

.gold-price-value {
    font-size: 18px;
    font-weight: 600;
    color: #ffffff;
}

.gold-price-label {
    font-size: 11px;
    color: #b8b8b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 4px;
}

.product-calculator-row {
    background: #ffffff;
    padding: 20px;
    border-radius: 16px;
    margin-bottom: 16px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
}

.product-calculator-row:hover {
    border-color: rgba(212, 175, 55, 0.5);
    box-shadow: 0 4px 16px rgba(212, 175, 55, 0.1);
}

.product-calculator-row.selected {
    border-color: #d4af37;
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.05), rgba(212, 175, 55, 0.02));
}

.calculator-grid {
    display: grid;
    grid-template-columns: 40px 2fr 1fr 1fr 1.5fr;
    gap: 16px;
    align-items: center;
}

.calc-input {
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.calc-input:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
}

.price-preview {
    background: linear-gradient(135deg, rgba(5, 150, 105, 0.1), rgba(5, 150, 105, 0.05));
    padding: 12px 16px;
    border-radius: 12px;
    border: 2px solid rgba(5, 150, 105, 0.3);
    text-align: right;
}

.price-preview-label {
    font-size: 11px;
    color: #059669;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 4px;
}

.price-preview-value {
    font-size: 20px;
    font-weight: 700;
    color: #059669;
    font-family: 'Playfair Display', serif;
}

.formula-card {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(99, 102, 241, 0.05));
    padding: 20px 24px;
    border-radius: 16px;
    border: 2px solid rgba(99, 102, 241, 0.3);
    margin-bottom: 24px;
}

.formula-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.formula-text {
    font-family: 'Courier New', monospace;
    background: rgba(255, 255, 255, 0.8);
    padding: 16px;
    border-radius: 10px;
    font-size: 14px;
    color: #4b5563;
    border-left: 4px solid #6366f1;
}

.update-summary {
    background: linear-gradient(135deg, rgba(5, 150, 105, 0.15), rgba(5, 150, 105, 0.08));
    border: 2px solid rgba(5, 150, 105, 0.4);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.summary-item {
    background: rgba(255, 255, 255, 0.9);
    padding: 16px;
    border-radius: 12px;
}

@media (max-width: 1200px) {
    .calculator-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}
</style>

<h1 class="page-title">💰 Gold-Based Price Calculator</h1>
<p class="page-subtitle">
    Calculate jewelry prices based on current gold market rates in the Philippines
</p>

<?php if ($message): ?>
    <div style="background:#d1fae5;color:#065f46;padding:16px 20px;border-radius:16px;margin-bottom:20px;border:2px solid #6ee7b7;">
        <strong>✓ <?= htmlspecialchars($message) ?></strong>
    </div>
<?php endif; ?>

<?php if (!empty($updated_products)): ?>
    <div class="update-summary">
        <div style="font-size:20px;font-weight:700;color:#059669;margin-bottom:16px;font-family:'Playfair Display',serif;">
            📊 Update Summary
        </div>
        <div class="summary-grid">
            <?php foreach ($updated_products as $up): ?>
                <div class="summary-item">
                    <div style="font-size:14px;font-weight:600;color:#1a1a2e;margin-bottom:8px;">
                        <?= htmlspecialchars($up['name']) ?>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280;margin-bottom:4px;">
                        <span>Old Price:</span>
                        <span>₱<?= number_format($up['old_price'], 2) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:15px;font-weight:700;color:#059669;margin-bottom:4px;">
                        <span>New Price:</span>
                        <span>₱<?= number_format($up['new_price'], 2) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;color:<?= $up['difference'] >= 0 ? '#059669' : '#dc2626' ?>;">
                        <span>Change:</span>
                        <span><?= $up['difference'] >= 0 ? '+' : '' ?>₱<?= number_format($up['difference'], 2) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Current Gold Prices -->
<div class="calculator-section">
    <div style="font-size:22px;font-weight:700;color:#1a1a2e;margin-bottom:16px;font-family:'Playfair Display',serif;">
        📈 Current Gold Prices (Philippines)
    </div>
    <div style="font-size:13px;color:#6b7280;margin-bottom:20px;">
        Last updated: <?= date('F d, Y - h:i A') ?> • Source: Live Market Data
    </div>
    
    <div class="gold-prices-grid">
        <?php foreach ($gold_prices as $karat => $price): ?>
            <div class="gold-price-card">
                <div class="gold-price-karat"><?= strtoupper($karat) ?></div>
                <div class="gold-price-value">₱<?= number_format($price, 2) ?></div>
                <div class="gold-price-label">per gram</div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Simplified Pricing Formula -->
<div class="formula-card">
    <div class="formula-title">
        <span>🧮</span>
        <span>Simplified Pricing Formula</span>
    </div>
    <div class="formula-text">
        <strong>Final Price = Gold Value × (1 + Markup%)</strong><br><br>
        Where:<br>
        • Gold Value = Weight (grams) × Gold Price per Gram (based on karat)<br>
        • Markup% = Your profit margin (typically 20-50%)<br><br>
        <em>Note: This calculator focuses purely on gold value pricing.</em>
    </div>
</div>

<!-- Product Calculator Form -->
<form method="post">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
            <div>
                <div style="font-size:18px;font-weight:700;color:#1a1a2e;">Calculate & Update Prices</div>
                <div style="font-size:13px;color:#6b7280;margin-top:4px;">
                    Select products and configure their pricing parameters
                </div>
            </div>
            <div style="display:flex;gap:12px;align-items:center;">
                <div>
                    <label style="display:block;font-size:12px;color:#6b7280;margin-bottom:4px;">
                        Markup Percentage
                    </label>
                    <input type="number" name="markup_percentage" value="30" min="0" max="200" step="5"
                           class="calc-input" style="width:100px;"
                           onchange="updateAllPreviews()">
                </div>
            </div>
        </div>

        <!-- Column Headers -->
        <div class="calculator-grid" style="padding:0 20px 12px;border-bottom:2px solid #e5e7eb;margin-bottom:16px;font-weight:600;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">
            <div></div>
            <div>Product</div>
            <div>Weight (g)</div>
            <div>Karat</div>
            <div>New Price</div>
        </div>

        <?php if (empty($products)): ?>
            <div class="placeholder">No products found. Add products first.</div>
        <?php else: ?>
            <?php foreach ($products as $p): ?>
                <div class="product-calculator-row" id="row_<?= $p['id'] ?>">
                    <div class="calculator-grid">
                        <!-- Checkbox -->
                        <div>
                            <input type="checkbox" name="selected_products[]" value="<?= $p['id'] ?>"
                                   style="width:20px;height:20px;cursor:pointer;accent-color:#d4af37;"
                                   onchange="toggleRowSelection(<?= $p['id'] ?>); updatePreview(<?= $p['id'] ?>)">
                        </div>

                        <!-- Product Name -->
                        <div>
                            <div style="font-weight:600;color:#1a1a2e;margin-bottom:4px;">
                                <?= htmlspecialchars($p['name']) ?>
                            </div>
                            <div style="font-size:12px;color:#9ca3af;">
                                Code: <?= htmlspecialchars($p['code']) ?> • 
                                Current: ₱<?= number_format($p['price'], 2) ?>
                            </div>
                        </div>

                        <!-- Weight -->
                        <div>
                            <div style="font-size:18px;font-weight:700;color:#d4af37;">
                                <?= number_format($p['weight'], 2) ?>g
                            </div>
                        </div>

                        <!-- Karat Selection -->
                        <div>
                            <select name="karat_<?= $p['id'] ?>" class="calc-input" 
                                    onchange="updatePreview(<?= $p['id'] ?>)" 
                                    id="karat_<?= $p['id'] ?>">
                                <option value="18k" selected>18K</option>
                                <option value="14k">14K</option>
                                <option value="21k">21K</option>
                                <option value="22k">22K</option>
                                <option value="24k">24K</option>
                                <option value="10k">10K</option>
                            </select>
                        </div>

                        <!-- Price Preview -->
                        <div class="price-preview" id="preview_<?= $p['id'] ?>">
                            <div class="price-preview-label">New Price</div>
                            <div class="price-preview-value">₱0.00</div>
                        </div>
                    </div>
                </div>

                <script>
                // Initialize preview for product <?= $p['id'] ?>
                updatePreview(<?= $p['id'] ?>);
                </script>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top:24px;display:flex;gap:12px;justify-content:space-between;flex-wrap:wrap;">
            <div>
                <button type="button" onclick="selectAll()" class="btn" style="margin-right:8px;">
                    Select All
                </button>
                <button type="button" onclick="deselectAll()" class="btn">
                    Deselect All
                </button>
            </div>
            <button type="submit" name="update_prices" class="btn primary" style="padding:14px 32px;">
                💰 Update Selected Prices
            </button>
        </div>
    </div>
</form>

<script>
const goldPrices = <?= json_encode($gold_prices) ?>;

function updatePreview(productId) {
    const weight = <?= json_encode(array_column($products, 'weight', 'id')) ?>[productId] || 0;
    const karat = document.getElementById('karat_' + productId).value;
    const markup = parseFloat(document.querySelector('input[name="markup_percentage"]').value) || 30;
    
    // Simplified calculation: Gold Value × (1 + Markup%)
    const goldValue = weight * goldPrices[karat];
    const finalPrice = goldValue * (1 + (markup / 100));
    
    const previewElement = document.getElementById('preview_' + productId);
    const valueElement = previewElement.querySelector('.price-preview-value');
    valueElement.textContent = '₱' + finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function updateAllPreviews() {
    const products = <?= json_encode(array_column($products, 'id')) ?>;
    products.forEach(id => updatePreview(id));
}

function toggleRowSelection(productId) {
    const row = document.getElementById('row_' + productId);
    const checkbox = row.querySelector('input[type="checkbox"]');
    if (checkbox.checked) {
        row.classList.add('selected');
    } else {
        row.classList.remove('selected');
    }
}

function selectAll() {
    document.querySelectorAll('input[name="selected_products[]"]').forEach(cb => {
        cb.checked = true;
        toggleRowSelection(parseInt(cb.value));
    });
}

function deselectAll() {
    document.querySelectorAll('input[name="selected_products[]"]').forEach(cb => {
        cb.checked = false;
        toggleRowSelection(parseInt(cb.value));
    });
}
</script>

<?php require 'footer.php'; ?>