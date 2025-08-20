<?php
require_once 'include/config.php';
session_start();

if (!isset($_POST['coupon'])) {
    header("Location: cart.php");
    exit();
}

$coupon_code = trim($_POST['coupon']);

$stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active'");
$stmt->execute([$coupon_code]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if ($coupon) {
    // Store in session
    $_SESSION['coupon'] = [
        'code' => $coupon['code'],
        'type' => $coupon['discount_type'],
        'value' => $coupon['discount_value']
    ];
    $_SESSION['coupon_success'] = "Coupon applied successfully!";
} else {
    unset($_SESSION['coupon']);
    $_SESSION['coupon_error'] = "Invalid or expired coupon code.";
}

header("Location: cart.php");
exit();
