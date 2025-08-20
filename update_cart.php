<?php
require_once 'include/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($cart_id > 0 && $quantity >= 1) {
        $checkStmt = $conn->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$cart_id, $user_id]);

        if ($checkStmt->rowCount() > 0) {
            $updateStmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $updateStmt->execute([$quantity, $cart_id]);
            $_SESSION['success'] = "Cart updated successfully!";
        }
    }
}

header("Location: cart.php");
exit();
 