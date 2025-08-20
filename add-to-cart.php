<?php
require_once 'include/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ If not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in.";
    header("Location: signin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_variant_id = isset($_POST['product_variant_id']) ? (int)$_POST['product_variant_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_variant_id <= 0 || $quantity <= 0) {
        $_SESSION['error'] = "Invalid input.";
        header("Location: cart.php");
        exit();
    }

    // ✅ Check if product variant exists
    $stmt = $conn->prepare("SELECT id, quantity FROM product_variants WHERE id = ?");
    $stmt->execute([$product_variant_id]);
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$variant) {
        $_SESSION['error'] = "Product not found.";
        header("Location: cart.php");
        exit();
    }

    // ✅ Check if already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_variant_id = ?");
    $stmt->execute([$user_id, $product_variant_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // ✅ Update quantity (not exceeding stock)
        $newQty = min($variant['quantity'], $existing['quantity'] + $quantity);
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update->execute([$newQty, $existing['id']]);
    } else {
        // ✅ Insert new
        $insert = $conn->prepare("INSERT INTO cart (user_id, product_variant_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
        $insert->execute([$user_id, $product_variant_id, min($quantity, $variant['quantity'])]);
    }

    $_SESSION['success'] = "Item added to cart!";
    header("Location: cart.php");
    exit();
}

header("Location: index.php");
exit();
