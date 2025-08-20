<?php
require_once 'include/config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['pid'])) {
    die("Invalid access.");
}

$payment_id = $_GET['pid'];
$user_id = $_SESSION['user_id'];

// Fetch Cart Items and calculate totals
$total = 0;
$cart_stmt = $conn->prepare("
    SELECT 
        cart.quantity,
        product_variants.id AS variant_id,
        product_variants.product_id,
        products.price,
        products.discount_type,
        products.discount_value,
        CASE
            WHEN products.discount_type = 'flat' THEN (products.price - products.discount_value)
            WHEN products.discount_type = 'percent' THEN (products.price - (products.price * products.discount_value / 100))
            ELSE products.price
        END AS final_price
    FROM cart
    JOIN product_variants ON cart.product_variant_id = product_variants.id
    JOIN products ON product_variants.product_id = products.id
    WHERE cart.user_id = ?
");
$cart_stmt->execute([$user_id]);
$items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($items as $item) {
    $total += $item['final_price'] * $item['quantity'];
}
$shipping = 50;
$grand_total = $total + $shipping;

// Apply Coupon
if (isset($_SESSION['coupon'])) {
    $coupon = $_SESSION['coupon'];
    if ($coupon['type'] === 'flat') {
        $grand_total -= $coupon['value'];
    } elseif ($coupon['type'] === 'percent') {
        $grand_total -= ($total * $coupon['value'] / 100);
    }
}

// Begin transaction for safe order and stock update
$conn->beginTransaction();

try {
    // Insert Order
    $insert_order = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, payment_method, payment_status, order_status, tracking_number, courier_service)
        VALUES (?, ?, 'Online', 'Success', 'Confirmed', ?, ?)
    ");
    $tracking = 'TRK' . rand(100000, 999999);
    $courier = 'BlueDart';
    $insert_order->execute([$user_id, $grand_total, $tracking, $courier]);
    $order_id = $conn->lastInsertId();

    // Insert Order Items
    $insert_order_item = $conn->prepare("
        INSERT INTO order_items (order_id, product_variant_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");
    // Prepare stock update statement (products table)
    $update_stock_stmt = $conn->prepare("
        UPDATE products
        SET stock = stock - ?
        WHERE id = ? AND stock >= ?
    ");

    foreach ($items as $item) {
        $insert_order_item->execute([
            $order_id,
            $item['variant_id'],
            $item['quantity'],
            $item['final_price']
        ]);

        // Decrease stock from products table by product_id and quantity
        $update_stock_stmt->execute([
            $item['quantity'],
            $item['product_id'],
            $item['quantity']
        ]);
        // Optionally, check if row was updated to detect insufficient stock
        if ($update_stock_stmt->rowCount() === 0) {
            // Rollback and throw exception if stock insufficient for this product
            throw new Exception("Insufficient stock for product ID: " . $item['product_id']);
        }
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollBack();
    die("Order processing failed: " . htmlspecialchars($e->getMessage()));
}

// Fetch user details for email
$user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch order items for email table
$item_stmt = $conn->prepare("
    SELECT 
        products.name AS product_name,
        CONCAT(product_variants.size, ' / ', product_variants.color) AS variant_name,
        order_items.quantity,
        order_items.price
    FROM order_items
    JOIN product_variants ON order_items.product_variant_id = product_variants.id
    JOIN products ON product_variants.product_id = products.id
    WHERE order_items.order_id = ?
");
$item_stmt->execute([$order_id]);
$order_items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build order items table HTML for email
$order_table = '<table cellpadding="10" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; font-size: 15px;">
<thead style="background-color:#007bff;color:white;"><tr>
<th>Product</th><th>Variant</th><th>Qty</th><th>Price (₹)</th>
</tr></thead><tbody>';
foreach ($order_items as $item) {
    $order_table .= "<tr>
        <td>" . htmlspecialchars($item['product_name']) . "</td>
        <td>" . htmlspecialchars($item['variant_name']) . "</td>
        <td>" . (int)$item['quantity'] . "</td>
        <td>" . number_format($item['price'], 2) . "</td>
    </tr>";
}
$order_table .= "</tbody></table>";

// Build email body content
$email_body = "
<div style='font-family:Segoe UI, sans-serif; max-width:600px; margin:auto; border:1px solid #ddd; border-radius:12px; padding:30px;'>
    <h2 style='color:#28a745;'>🎉 Order Confirmation</h2>
    <p>Hi <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
    <p>Thank you for shopping with <strong>Clothing Store</strong>! Your order has been successfully placed.</p>
    <p><strong>Tracking Number:</strong> <span style='color:#007bff;'>$tracking</span><br>
    <strong>Courier Service:</strong> $courier</p>
    
    <h3 style='margin-top:30px;'>🛒 Order Summary</h3>
    $order_table
    
    <p style='margin-top:20px;'><strong>Subtotal:</strong> ₹" . number_format($total, 2) . "</p>
    <p><strong>Shipping Charges:</strong> ₹" . number_format($shipping, 2) . "</p>";

if (isset($coupon)) {
    $email_body .= "<p><strong>Coupon Discount:</strong> ";
    if ($coupon['type'] === 'flat') {
        $email_body .= "₹" . number_format($coupon['value'], 2);
    } elseif ($coupon['type'] === 'percent') {
        $email_body .= htmlspecialchars($coupon['value']) . "% off";
    }
    $email_body .= "</p>";
}

$email_body .= "<p><strong>Grand Total Paid:</strong> ₹" . number_format($grand_total, 2) . "</p>

    <p style='margin-top:30px;'>📦 Your order will be shipped shortly. You will receive another email once it’s on the way.</p>

    <p style='margin-top:40px;'>Warm regards,<br><strong>Clothing Store Team</strong></p>
</div>
";

// Send confirmation email using PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'rahul38865@gmail.com';
    $mail->Password = 'jqeivufpxgdnbakr'; // Use your app password or SMTP credentials
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('rahul38865@gmail.com', 'Clothing Store');
    $mail->addAddress($user['email'], $user['name']);
    $mail->isHTML(true);
    $mail->Subject = "🧾 Order Confirmation - #{$order_id}";
    $mail->Body = $email_body;

    $mail->send();
} catch (Exception $e) {
    error_log("Email failed: {$mail->ErrorInfo}");
}

// Clear Cart and Coupon from session
$conn->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
unset($_SESSION['coupon']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful</title>
    <style>
        body {
            background: #f5f9ff;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            animation: fadeIn 1s ease-in-out;
        }
        .success-container {
            text-align: center;
            background: #fff;
            padding: 40px 60px;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            animation: popUp 0.6s ease-out;
        }
        h2 {
            color: #28a745;
            font-size: 28px;
            margin-bottom: 10px;
        }
        p {
            font-size: 18px;
            color: #333;
        }
        .tracking {
            font-weight: bold;
            color: #007bff;
        }
        a {
            margin-top: 20px;
            display: inline-block;
            background: #007bff;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        a:hover {
            background: #0056b3;
        }
        @keyframes popUp {
            0% {
                transform: scale(0.7);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        @keyframes fadeIn {
            from { opacity: 0 }
            to { opacity: 1 }
        }
    </style>
    <script>
        // Auto redirect to dashboard after 5 seconds
        setTimeout(() => {
            window.location.href = 'dashboard.php';
        }, 5000);
    </script>
</head>
<body>
    <div class="success-container">
        <h2>🎉 Payment Successful!</h2>
        <p>Your order has been placed.</p>
        <p>Tracking Number: <span class="tracking"><?= htmlspecialchars($tracking) ?></span></p>
        <a href="dashboard.php">View My Orders</a>
        <p style="margin-top: 10px; font-size: 14px; color: #555;">Redirecting to dashboard in 5 seconds...</p>
    </div>
</body>
</html>
