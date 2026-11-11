<?php
require 'db.php';
require 'auth.php';
check_login();

$errors = [];

// Ensure ID is present
if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit;
}

$id = (int)$_GET['id'];
if ($id <= 0) {
    header("Location: customers.php");
    exit;
}

// Load existing customer
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        require 'header.php';
        echo '<div style="background:#fee2e2;color:#b91c1c;padding:12px;border-radius:8px;margin-bottom:12px;">Customer not found.</div>';
        require 'footer.php';
        exit;
    }
} catch (PDOException $e) {
    require 'header.php';
    echo '<div style="background:#fee2e2;color:#b91c1c;padding:12px;border-radius:8px;margin-bottom:12px;">Error loading customer.</div>';
    require 'footer.php';
    exit;
}

// Initialize form values from DB by default
$name  = $customer['name'];
$phone = $customer['phone'] ?? '';
$email = $customer['email'] ?? '';

// Handle POST (update) - BEFORE requiring header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '') {
        $errors[] = "Name is required.";
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE customers
                SET name = :name,
                    phone = :phone,
                    email = :email
                WHERE id = :id
            ");

            $stmt->execute([
                ':name'  => $name,
                ':phone' => $phone,
                ':email' => $email !== '' ? $email : null,
                ':id'    => $id,
            ]);

            // redirect back to list with success message
            header("Location: customers.php?updated=1");
            exit;
        } catch (PDOException $e) {
            error_log("customer_edit: " . $e->getMessage());
            $errors[] = "Could not update the customer. Please try again.";
        }
    }
}

// NOW load the header - after all redirect logic
require 'header.php';
?>

<h3>Edit Customer</h3>

<?php foreach ($errors as $e): ?>
    <div style="background:#fee2e2;color:#b91c1c;padding:12px;border-radius:8px;margin-bottom:12px;">
        <?= htmlspecialchars($e) ?>
    </div>
<?php endforeach; ?>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form method="post">
            <div class="mb-2" style="margin-bottom:16px;">
                <label class="form-label" style="display:block;margin-bottom:6px;font-weight:500;">Name *</label>
                <input type="text" name="name" class="form-control" 
                       style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"
                       value="<?= htmlspecialchars($name) ?>" required>
            </div>

            <div class="mb-2" style="margin-bottom:16px;">
                <label class="form-label" style="display:block;margin-bottom:6px;font-weight:500;">Phone</label>
                <input type="text" name="phone" class="form-control" 
                       style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"
                       value="<?= htmlspecialchars($phone) ?>">
            </div>

            <div class="mb-2" style="margin-bottom:20px;">
                <label class="form-label" style="display:block;margin-bottom:6px;font-weight:500;">Email</label>
                <input type="email" name="email" class="form-control" 
                       style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"
                       value="<?= htmlspecialchars($email) ?>">
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="customers.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require 'footer.php'; ?>