<?php
require 'razorpay-php/Razorpay.php';
use Razorpay\Api\Api;

require_once 'include/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Calculate total
$stmt = $conn->prepare("
    SELECT 
        cart.id AS cart_id,
        cart.quantity,
        product_variants.id AS variant_id,
        products.name,
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
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['final_price'] * $item['quantity'];
}

$shipping = 50;
$total = $subtotal + $shipping;

// Coupon
if (isset($_SESSION['coupon'])) {
    $coupon = $_SESSION['coupon'];
    if ($coupon['type'] === 'flat') {
        $total -= $coupon['value'];
    } else if ($coupon['type'] === 'percent') {
        $total -= ($subtotal * $coupon['value'] / 100);
    }
}

// Razorpay Order Creation
$api = new Api('rzp_test_9TB3asShG3RvdV', 'zrpWBMrytnHq5UMUeVikNgfn');
$orderData = [
    'receipt' => 'order_rcptid_' . time(),
    'amount' => $total * 100, // in paise
    'currency' => 'INR',
    'payment_capture' => 1
];

$razorpayOrder = $api->order->create($orderData);
$_SESSION['razorpay_order_id'] = $razorpayOrder['id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: white;
        }

        .checkout-card {
            background-color: white;
            color: #333;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 90%;
            max-width: 400px;
        }

        .checkout-card h2 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        #rzp-button {
            background-color: #3399cc;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        #rzp-button:hover {
            background-color: #287a9d;
        }
        #rzp-button {
    display: none;
}

    </style>
</head>
<body>
     <div class="checkout-card">
        <h2>Pay ₹<?= number_format($total, 2) ?></h2>
        <button id="rzp-button">Pay Now</button>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    var options = {
        "key": "rzp_test_9TB3asShG3RvdV",
        "amount": "<?= $total * 100 ?>",
        "currency": "INR",
        "name": "Your Clothing Store",
        "description": "Order Payment",
        "order_id": "<?= $razorpayOrder['id'] ?>",
        "handler": function (response){
            window.location.href = "payment-success.php?pid=" + response.razorpay_payment_id;
        },
        "prefill": {
            "name": "<?= $user_name ?>",
            "email": "<?= $user_email ?>"
        },
        "theme": {
            "color": "#3399cc"
        }
    };
    var rzp1 = new Razorpay(options);

    window.onload = function() {
        rzp1.open();
    };
</script>

</body>
</html>
