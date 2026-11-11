<?php
require 'db.php';
require 'auth.php';
check_login();

// Validate ID
if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit;
}

$id = (int)$_GET['id'];
if ($id <= 0) {
    header("Location: customers.php");
    exit;
}

try {
    // Check if customer exists
    $stmt = $pdo->prepare("SELECT id, name FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        header("Location: customers.php?error=notfound");
        exit;
    }

    // Detach sales explicitly (do not rely on a foreign key; the old MyISAM tables had none)
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE sales SET customer_id = NULL WHERE customer_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
    $pdo->commit();

    header("Location: customers.php?deleted=1");
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log("customer_delete: " . $e->getMessage());
    header("Location: customers.php?error=db");
    exit;
}