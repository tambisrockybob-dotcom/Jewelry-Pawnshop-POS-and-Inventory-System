<?php
require 'db.php';
require 'auth.php';
check_login();

$errors = [];

// Validate ID
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$id = (int)$_GET['id'];
if ($id <= 0) {
    header("Location: products.php");
    exit;
}

// Load existing product
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        require 'header.php';
        echo '<div style="background:#fee2e2;color:#b91c1c;padding:12px;border-radius:8px;margin-bottom:12px;">Product not found.</div>';
        require 'footer.php';
        exit;
    }
} catch (PDOException $e) {
    require 'header.php';
    echo '<div style="background:#fee2e2;color:#b91c1c;padding:12px;border-radius:8px;margin-bottom:12px;">Error loading product.</div>';
    require 'footer.php';
    exit;
}

// Initialize form values from DB
$code        = $product['code'];
$name        = $product['name'];
$description = $product['description'] ?? '';
$price       = $product['price'];
$stock       = $product['stock'];
$weight      = $product['weight'];

// Handle POST (update) - BEFORE requiring header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code        = trim($_POST['code'] ?? '');
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $stock       = trim($_POST['stock'] ?? '');
    $weight      = trim($_POST['weight'] ?? '');

    // Validation
    if ($code === '') {
        $errors[] = "Product code is required.";
    }
    if ($name === '') {
        $errors[] = "Product name is required.";
    }
    if (strlen($code) > 50) {
        $errors[] = "Product code is too long (max 50 characters).";
    }
    if ($price === '' || !is_numeric($price) || (float)$price < 0 || (float)$price > 9999999.99) {
        $errors[] = "Price must be a number between 0 and 9,999,999.99.";
    }
    if ($stock === '' || filter_var($stock, FILTER_VALIDATE_INT) === false || (int)$stock < 0) {
        $errors[] = "Stock must be a whole number, 0 or more.";
    }
    if ($weight !== '' && (!is_numeric($weight) || (float)$weight < 0)) {
        $errors[] = "Weight must be a number, 0 or more.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE products
                SET code = :code,
                    name = :name,
                    description = :description,
                    price = :price,
                    stock = :stock,
                    weight = :weight
                WHERE id = :id
            ");

            $stmt->execute([
                ':code'        => $code,
                ':name'        => $name,
                ':description' => $description,
                ':price'       => (float)$price,
                ':stock'       => (int)$stock,
                ':weight'      => $weight !== '' ? (float)$weight : 0,
                ':id'          => $id,
            ]);

            // Redirect BEFORE any HTML output
            header("Location: products.php?updated=1");
            exit;
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) == 1062) {
                $errors[] = "Product code \"$code\" already exists.";
            } else {
                error_log("product_edit: " . $e->getMessage());
                $errors[] = "Could not update the product. Please try again.";
            }
        }
    }
}

// NOW load the header - after all redirect logic
require 'header.php';
?>

<h3>Edit Product</h3>

<?php foreach ($errors as $e): ?>
    <div style="background:#fee2e2;color:#b91c1c;padding:12px;border-radius:8px;margin-bottom:12px;">
        <?= htmlspecialchars($e) ?>
    </div>
<?php endforeach; ?>

<div class="card" style="max-width:800px;">
    <div class="card-body">
        <form method="post">
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label class="form-label" style="display:block;margin-bottom:6px;font-weight:500;">Product Code</label>
                    <input name="code" class="form-control" placeholder="e.g. P001" required
                           style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"
                           value="<?= htmlspecialchars($code) ?>">
                </div>

                <div>
                    <label class="form-label" style="display:block;margin-bottom:6px;font-weight:500;">Product Name</label>
                    <input name="name" class="form-control" placeholder="e.g. Gold Ring" required
                           style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"
                           value="<?= htmlspecialchars($name) ?>">
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label class="form-label" style="display:block;margin-bottom:6px;font-weight:500;">Description</label>
                <textarea name="description" class="form-control" placeholder="Product description" rows="3"
                          style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"><?= htmlspecialchars($description) ?></textarea>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;margin-bottom:16px;">
                <div>
                    <label class="form-label" style="display:block;margin-bottom:6px;font-weight:500;">Price (₱)</label>
                    <input name="price" type="number" step="0.01" class="form-control" placeholder="0.00" required
                           style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"
                           value="<?= htmlspecialchars($price) ?>">
                </div>

                <div>
                    <label class="form-label" style="display:block;margin-bottom:6px;font-weight:500;">Stock Quantity</label>
                    <input name="stock" type="number" class="form-control" placeholder="0" required
                           style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"
                           value="<?= htmlspecialchars($stock) ?>">
                </div>

                <div>
                    <label class="form-label" style="display:block;margin-bottom:6px;font-weight:500;">Weight (grams)</label>
                    <input name="weight" type="number" step="0.01" class="form-control" placeholder="0.00"
                           style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"
                           value="<?= htmlspecialchars($weight) ?>">
                </div>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require 'footer.php'; ?>