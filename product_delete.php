<?php
require 'db.php';
require 'auth.php';
check_login();

// Validate ID
if (!isset($_GET['id'])) {
    header("Location: products.php?error=missing_id");
    exit;
}

$id = (int)$_GET['id'];
if ($id <= 0) {
    header("Location: products.php?error=invalid_id");
    exit;
}

try {
    // Check if product exists
    $stmt = $pdo->prepare("SELECT id, code, name FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: products.php?error=notfound");
        exit;
    }

    // Check if product is used in any sales
    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM sale_items WHERE product_id = ?");
    $checkStmt->execute([$id]);
    $usageCount = $checkStmt->fetchColumn();

    if ($usageCount > 0) {
        // Product has sales history - cannot delete
        // Redirect with detailed error message
        header("Location: products.php?error=inuse&product_name=" . urlencode($product['name']) . "&sales_count=" . $usageCount);
        exit;
    }

    // Safe to delete - no sales history
    $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $deleteStmt->execute([$id]);

    // Success
    header("Location: products.php?deleted=1&product_name=" . urlencode($product['name']));
    exit;

} catch (PDOException $e) {
    // Log the error (in production, log to file instead of showing)
    error_log("Product deletion error: " . $e->getMessage());
    
    // Check if it's a foreign key constraint error
    if (strpos($e->getMessage(), 'foreign key constraint') !== false || 
        strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
        header("Location: products.php?error=constraint");
    } else {
        header("Location: products.php?error=db");
    }
    exit;
}
?>