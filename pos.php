<?php
require 'db.php';
require 'auth.php';
require_once __DIR__ . '/includes/functions.php';
check_login();

// ---------- HANDLE REDIRECTS FIRST ---------- //

// Remove item from cart
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    $cart = $_SESSION['cart'] ?? [];
    if (isset($cart[$remove_id])) {
        unset($cart[$remove_id]);
        $_SESSION['cart'] = $cart;
    }
    header("Location: pos.php");
    exit;
}

// Clear entire cart
if (isset($_GET['clear_cart'])) {
    unset($_SESSION['cart']);
    header("Location: pos.php");
    exit;
}

// ---------- LOAD DATA ---------- //

$searchQuery = trim($_GET['search'] ?? '');

$customers = $pdo->query("SELECT id, name FROM customers ORDER BY name ASC")
                 ->fetchAll(PDO::FETCH_ASSOC);

if ($searchQuery !== '') {
    $stmt = $pdo->prepare("
        SELECT id, code, name, price, stock, weight
        FROM products
        WHERE name LIKE :s1
           OR code LIKE :s2
           OR description LIKE :s3
        ORDER BY name ASC
    ");
    $like = '%' . addcslashes($searchQuery, '%_\\') . '%';
    $stmt->execute([':s1' => $like, ':s2' => $like, ':s3' => $like]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $products = $pdo->query("SELECT id, code, name, price, stock, weight FROM products ORDER BY name ASC")
                    ->fetchAll(PDO::FETCH_ASSOC);
}

$cart   = $_SESSION['cart'] ?? [];
$errors = [];

// ---------- ADD TO CART ---------- //

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

    $pstmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
    $pstmt->execute([$product_id]);
    $product = $pstmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $errors[] = "Product not found.";
    } else {
        // Count what is already in the cart, otherwise repeated adds could exceed stock
        $inCart = isset($cart[$product_id]) ? (int)$cart[$product_id]['quantity'] : 0;

        if ($product['stock'] < $inCart + $quantity) {
            $errors[] = "Not enough stock for " . $product['name']
                      . " (available: " . (int)$product['stock'] . ", already in cart: " . $inCart . ")";
        } else {
            if (!isset($cart[$product_id])) {
                $cart[$product_id] = [
                    'id'       => (int)$product['id'],
                    'name'     => $product['name'],
                    'price'    => $product['price'],
                    'quantity' => 0,
                ];
            }
            $cart[$product_id]['price']     = $product['price'];   // keep price current
            $cart[$product_id]['quantity'] += $quantity;
            $_SESSION['cart'] = $cart;

            // Post/Redirect/Get: a browser refresh must not add the item again
            header("Location: pos.php" . ($searchQuery !== '' ? '?search=' . urlencode($searchQuery) : ''));
            exit;
        }
    }
}

// ---------- TOTAL AMOUNT (display) ---------- //

$total_amount = 0;
foreach ($cart as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// ---------- COMPLETE SALE ---------- //

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_sale'])) {
    if (empty($cart)) {
        $errors[] = "Cart is empty.";
    } else {
        $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
        $cash        = round((float)($_POST['cash'] ?? 0), 2);

        try {
            $pdo->beginTransaction();

            // Customer must exist (otherwise treat as an error, not an orphaned sale)
            if ($customer_id !== null) {
                $c = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
                $c->execute([$customer_id]);
                if (!$c->fetchColumn()) {
                    throw new RuntimeException("Selected customer no longer exists.");
                }
            }

            // Lock the product rows and re-check stock + price against the DATABASE,
            // not against what was stored in the session when the item was added.
            $ids = array_map('intval', array_keys($cart));
            $in  = implode(',', array_fill(0, count($ids), '?'));
            $lock = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($in) FOR UPDATE");
            $lock->execute($ids);
            $dbProducts = [];
            foreach ($lock->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $dbProducts[(int)$row['id']] = $row;
            }

            $lines = [];
            $total = 0.0;
            foreach ($cart as $pid => $item) {
                $pid = (int)$pid;
                $qty = (int)$item['quantity'];
                if (!isset($dbProducts[$pid])) {
                    throw new RuntimeException($item['name'] . " is no longer available. Remove it from the cart.");
                }
                if ($qty < 1) {
                    continue;
                }
                $row = $dbProducts[$pid];
                if ((int)$row['stock'] < $qty) {
                    throw new RuntimeException("Not enough stock for " . $row['name'] . " (available: " . (int)$row['stock'] . ").");
                }
                $lines[] = ['id' => $pid, 'qty' => $qty, 'price' => (float)$row['price']];
                $total  += (float)$row['price'] * $qty;
            }
            $total = round($total, 2);

            if (empty($lines)) {
                throw new RuntimeException("Cart is empty.");
            }
            if ($cash < $total) {
                throw new RuntimeException("Cash received (₱" . number_format($cash, 2) . ") is not enough. Total: ₱" . number_format($total, 2));
            }

            // Unique invoice number; retry if two sales race for the same number
            $saleStmt = $pdo->prepare("INSERT INTO sales (invoice_no, customer_id, total, created_at) VALUES (?, ?, ?, NOW())");
            $sale_id  = null;
            for ($attempt = 0; $attempt < 5 && $sale_id === null; $attempt++) {
                try {
                    $saleStmt->execute([generate_invoice_number($pdo), $customer_id, $total]);
                    $sale_id = (int)$pdo->lastInsertId();
                } catch (PDOException $e) {
                    if (($e->errorInfo[1] ?? 0) != 1062) { throw $e; }
                }
            }
            if ($sale_id === null) {
                throw new RuntimeException("Could not generate an invoice number. Please try again.");
            }

            $itemStmt  = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            // The "AND stock >= ?" guard makes overselling impossible even without the lock
            $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

            foreach ($lines as $l) {
                $itemStmt->execute([$sale_id, $l['id'], $l['qty'], $l['price']]);
                $stockStmt->execute([$l['qty'], $l['id'], $l['qty']]);
                if ($stockStmt->rowCount() !== 1) {
                    throw new RuntimeException("Stock changed while completing the sale. Please review the cart and try again.");
                }
            }

            $pdo->commit();
            unset($_SESSION['cart']);

            header("Location: sale_view.php?id=" . $sale_id);
            exit;

        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("pos complete_sale: " . $e->getMessage());
            $errors[] = "Error completing sale. Nothing was saved; please try again.";
        }
    }
}

require 'header.php';
?>

<style>
.search-bar-container {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(212, 175, 55, 0.05));
    border: 2px solid rgba(212, 175, 55, 0.3);
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.1);
}

.search-form {
    display: flex;
    gap: 12px;
    align-items: center;
}

.search-input-wrapper {
    flex: 1;
    position: relative;
}

.search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 18px;
    color: #d4af37;
}

.search-input {
    width: 100%;
    padding: 14px 20px 14px 48px;
    border: 2px solid rgba(212, 175, 55, 0.3);
    border-radius: 12px;
    font-size: 15px;
    font-weight: 500;
    background: #ffffff;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
}

.search-btn {
    padding: 14px 28px;
    border: none;
    border-radius: 12px;
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

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(212, 175, 55, 0.4);
}

.clear-search-btn {
    padding: 14px 20px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: #ffffff;
    color: #6b7280;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.clear-search-btn:hover {
    border-color: #d1d5db;
    background: #f9fafb;
}

.search-results-info {
    margin-top: 12px;
    padding: 10px 16px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 10px;
    font-size: 13px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-results-info strong {
    color: #d4af37;
    font-weight: 700;
}

.product-highlight {
    background: linear-gradient(90deg, rgba(212, 175, 55, 0.1), transparent);
    animation: highlightFade 2s ease-out;
}

@keyframes highlightFade {
    from { background: linear-gradient(90deg, rgba(212, 175, 55, 0.2), transparent); }
    to { background: transparent; }
}

.no-results {
    text-align: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, rgba(255, 200, 87, 0.05), rgba(255, 200, 87, 0.02));
    border-radius: 16px;
    border: 2px dashed rgba(212, 175, 55, 0.3);
}

.no-results-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.no-results-text {
    font-size: 16px;
    color: #6b7280;
    margin-bottom: 8px;
    font-weight: 600;
}

.no-results-hint {
    font-size: 13px;
    color: #9ca3af;
}
</style>

<h1 class="page-title">Point of Sale (POS)</h1>
<p class="page-subtitle">
    Add items to the cart, accept payment, and complete customer transactions.
</p>

<?php if (!empty($errors)): ?>
    <div style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:12px;margin-bottom:16px;border:1px solid #fecaca;">
        <?php foreach ($errors as $e): ?>
            <div style="margin-bottom:4px;">⚠ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-top: 8px;">
    <div style="display:flex;flex-wrap:wrap;gap:16px;">

        <!-- LEFT: PRODUCTS LIST -->
        <div style="flex: 1 1 55%; min-width:320px;">
            <!-- SEARCH BAR -->
            <div class="search-bar-container">
                <form method="get" class="search-form">
                    <div class="search-input-wrapper">
                        <span class="search-icon">🔍</span>
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Search by product name, code, or description..." 
                            value="<?= htmlspecialchars($searchQuery) ?>"
                            autofocus
                        >
                    </div>
                    <button type="submit" class="search-btn">Search</button>
                    <?php if ($searchQuery): ?>
                        <a href="pos.php" class="clear-search-btn">Clear</a>
                    <?php endif; ?>
                </form>

                <?php if ($searchQuery): ?>
                    <div class="search-results-info">
                        <span>📊</span>
                        <span>Found <strong><?= count($products) ?></strong> product(s) matching "<strong><?= htmlspecialchars($searchQuery) ?></strong>"</span>
                    </div>
                <?php endif; ?>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <div style="font-size:16px;font-weight:600;color:#111827;">
                    <?= $searchQuery ? 'Search Results' : 'All Products' ?>
                </div>
                <span style="font-size:12px;color:#6b7280;">
                    <?= count($products) ?> item(s) available
                </span>
            </div>

            <?php if (empty($products)): ?>
                <?php if ($searchQuery): ?>
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <div class="no-results-text">No products found</div>
                        <div class="no-results-hint">
                            Try a different search term or 
                            <a href="pos.php" style="color:#d4af37;font-weight:600;">view all products</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="background:#fef3c7;color:#92400e;padding:16px;border-radius:12px;border:1px solid #fde68a;">
                        No products available. <a href="product_add.php" style="color:#92400e;text-decoration:underline;">Add products</a> first.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="max-height:500px;overflow-y:auto;border-radius:14px;border:1px solid #e5e7eb;background:#f9fafb;">
                    <table class="table" style="min-width:700px;">
                        <thead>
                            <tr>
                                <th style="width:100px;">Code</th>
                                <th>Name</th>
                                <th style="width:120px;">Price</th>
                                <th style="width:90px;">Stock</th>
                                <th style="width:90px;">Weight</th>
                                <th style="width:150px;">Add</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr class="<?= $searchQuery ? 'product-highlight' : '' ?>" <?= $p['stock'] <= 0 ? 'style="opacity:0.5;"' : '' ?>>
                                    <td style="font-family:monospace;font-weight:600;color:#d4af37;">
                                        <?= htmlspecialchars($p['code']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($p['name']) ?>
                                        <?php if ($p['stock'] <= 0): ?>
                                            <span style="color:#dc2626;font-size:11px;font-weight:600;"> (Out of Stock)</span>
                                        <?php elseif ($p['stock'] <= 5): ?>
                                            <span style="color:#ea580c;font-size:11px;font-weight:600;"> (Low Stock)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight:600;">₱<?= number_format($p['price'], 2) ?></td>
                                    <td><?= (int)$p['stock'] ?></td>
                                    <td><?= number_format($p['weight'], 2) ?> g</td>
                                    <td>
                                        <?php if ($p['stock'] > 0): ?>
                                            <form method="post" style="display:flex;gap:6px;align-items:center;justify-content:flex-end;">
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <input type="number" name="quantity" value="1" min="1" max="<?= $p['stock'] ?>"
                                                       style="width:60px;padding:6px 8px;border-radius:10px;border:1px solid #d1d5db;font-size:12px;">
                                                <button type="submit" name="add_to_cart" class="btn primary" style="font-size:12px;padding:6px 12px;">
                                                    Add
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#9ca3af;font-size:12px;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: CART + PAYMENT -->
        <div style="flex: 1 1 40%; min-width:280px;">
            <div style="font-size:16px;font-weight:600;color:#111827;margin-bottom:8px;">Cart &amp; Payment</div>

            <!-- CART CARD -->
            <div class="card" style="padding:14px;margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <div style="font-size:14px;font-weight:600;color:#111827;">Current Cart</div>
                    <?php if (!empty($cart)): ?>
                        <a href="pos.php?clear_cart=1" class="btn"
                           style="font-size:11px;padding:4px 8px;background:#fee2e2;border-color:#fecaca;color:#b91c1c;"
                           onclick="return confirm('Clear the entire cart?');">
                            Clear Cart
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($cart)): ?>
                    <p class="placeholder" style="margin:4px 0 0;text-align:center;padding:20px 0;color:#9ca3af;">
                        🛒 No items added yet.<br>
                        <span style="font-size:12px;">Start adding products from the left.</span>
                    </p>
                <?php else: ?>
                    <div style="max-height:220px;overflow-y:auto;">
                        <table class="table" style="min-width:100%;font-size:13px;">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th style="width:50px;">Qty</th>
                                    <th style="width:80px;">Price</th>
                                    <th style="width:90px;">Subtotal</th>
                                    <th style="width:70px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= (int)$item['quantity'] ?></td>
                                        <td>₱<?= number_format($item['price'], 2) ?></td>
                                        <td style="font-weight:600;">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                        <td>
                                            <a href="pos.php?remove=<?= $item['id'] ?>" class="btn"
                                               style="font-size:11px;padding:4px 8px;background:#fee2e2;border-color:#fecaca;color:#b91c1c;"
                                               title="Remove item">
                                                ✕
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:2px solid #e5e7eb;">
                        <div style="font-size:14px;font-weight:600;color:#6b7280;">Total Amount</div>
                        <div style="font-size:22px;font-weight:700;color:#111827;">₱<?= number_format($total_amount, 2) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PAYMENT CARD -->
            <div class="card" style="padding:14px;background:linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);">
                <div style="font-size:14px;font-weight:600;color:#111827;margin-bottom:10px;">💳 Payment</div>
                
                <form method="post" id="paymentForm">
                    <div style="margin-bottom:10px;">
                        <label style="display:block;font-size:12px;font-weight:500;color:#4b5563;margin-bottom:4px;">Customer</label>
                        <select name="customer_id"
                                style="width:100%;padding:8px 10px;border-radius:10px;border:1px solid #d1d5db;font-size:13px;background:#ffffff;">
                            <option value="">Walk-in Customer</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom:10px;">
                        <label style="display:block;font-size:12px;font-weight:500;color:#4b5563;margin-bottom:4px;">
                            Cash Received *
                            <?php if (!empty($cart)): ?>
                                <span style="color:#9ca3af;font-weight:400;">(Min: ₱<?= number_format($total_amount, 2) ?>)</span>
                            <?php endif; ?>
                        </label>
                        <input type="number" step="0.01" name="cash" id="cashInput" required
                               placeholder="0.00"
                               min="<?= $total_amount ?>"
                               oninput="calculateChange()"
                               style="width:100%;padding:10px 12px;border-radius:10px;border:2px solid #d1d5db;font-size:16px;font-weight:600;">
                    </div>

                    <!-- CHANGE DISPLAY -->
                    <div id="changeDisplay" style="display:none;margin-bottom:12px;padding:16px;background:linear-gradient(135deg, rgba(5, 150, 105, 0.1), rgba(16, 185, 129, 0.05));border-radius:12px;border:2px solid rgba(5, 150, 105, 0.3);">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <div style="font-size:13px;font-weight:600;color:#059669;text-transform:uppercase;letter-spacing:0.05em;">
                                💵 Change:
                            </div>
                            <div id="changeAmount" style="font-size:28px;font-weight:700;color:#059669;font-family:'Playfair Display',serif;">
                                ₱0.00
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($cart)): ?>
                        <button type="submit" name="complete_sale" class="btn primary" style="width:100%;margin-top:6px;font-size:14px;padding:10px;">
                            💰 Complete Sale
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn" disabled style="width:100%;margin-top:6px;opacity:0.5;cursor:not-allowed;">
                            Add items to cart first
                        </button>
                    <?php endif; ?>
                </form>

                <script>
                function calculateChange() {
                    const totalAmount = <?= $total_amount ?>;
                    const cashInput = document.getElementById('cashInput');
                    const changeDisplay = document.getElementById('changeDisplay');
                    const changeAmount = document.getElementById('changeAmount');
                    
                    const cash = parseFloat(cashInput.value) || 0;
                    
                    if (cash >= totalAmount && cash > 0) {
                        const change = cash - totalAmount;
                        changeAmount.textContent = '₱' + change.toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        changeDisplay.style.display = 'block';
                        
                        // Add animation
                        changeAmount.style.animation = 'none';
                        setTimeout(() => {
                            changeAmount.style.animation = 'changeGlow 0.5s ease-out';
                        }, 10);
                    } else {
                        changeDisplay.style.display = 'none';
                    }
                }
                </script>

                <style>
                @keyframes changeGlow {
                    0% {
                        transform: scale(1);
                        text-shadow: 0 0 0 rgba(5, 150, 105, 0);
                    }
                    50% {
                        transform: scale(1.1);
                        text-shadow: 0 0 20px rgba(5, 150, 105, 0.5);
                    }
                    100% {
                        transform: scale(1);
                        text-shadow: 0 0 0 rgba(5, 150, 105, 0);
                    }
                }
                </style>
            </div>

            <!-- Quick Actions -->
            <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                <a href="customer_add.php" class="btn" style="flex:1;font-size:12px;padding:8px;text-align:center;">
                    + New Customer
                </a>
                <a href="products.php" class="btn" style="flex:1;font-size:12px;padding:8px;text-align:center;">
                    📦 Products
                </a>
            </div>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>