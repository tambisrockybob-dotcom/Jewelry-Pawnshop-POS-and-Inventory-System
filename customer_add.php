<?php
require 'db.php';
require 'auth.php';
check_login();

$errors = [];

// Handle form submission BEFORE loading header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Basic validation
    if ($name === '') {
        $errors[] = "Name is required.";
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO customers (name, phone, email)
                VALUES (:name, :phone, :email)
            ");

            $stmt->execute([
                ':name'  => $name,
                ':phone' => $phone,
                ':email' => $email !== '' ? $email : null,
            ]);

            // Redirect back to list with success flag
            header("Location: customers.php?added=1");
            exit;
        } catch (PDOException $e) {
            error_log("customer_add: " . $e->getMessage());
            $errors[] = "Could not save the customer. Please try again.";
        }
    }
}

// NOW load the header - after all redirect logic
require 'header.php';
?>

<h3>Add Customer</h3>

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
                <input name="name" class="form-control" placeholder="Customer Name" required
                       style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="mb-2" style="margin-bottom:16px;">
                <label class="form-label" style="display:block;margin-bottom:6px;font-weight:500;">Phone</label>
                <input name="phone" class="form-control" placeholder="Phone Number"
                       style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div class="mb-2" style="margin-bottom:20px;">
                <label class="form-label" style="display:block;margin-bottom:6px;font-weight:500;">Email</label>
                <input name="email" type="email" class="form-control" placeholder="Email Address"
                       style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div>
                <button class="btn btn-primary">Save Customer</button>
                <a href="customers.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require 'footer.php'; ?>