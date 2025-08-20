<?php include 'include/header.php'; ?>
<?php
require_once 'include/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch cart items from DB (with proper final_price and main_image)
$stmt = $conn->prepare("
    SELECT 
        cart.id AS cart_id,
        cart.quantity,
        products.name,
        products.main_image,
        product_variants.size,
        product_variants.color,
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
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['final_price'] * $item['quantity'];
}
$shipping = 50;
$total = $subtotal + $shipping;
?>
<?php
$coupon_discount = 0;
if (isset($_SESSION['coupon'])) {
    $coupon = $_SESSION['coupon'];
    if ($coupon['type'] === 'flat') {
        $coupon_discount = $coupon['value'];
    } elseif ($coupon['type'] === 'percent') {
        $coupon_discount = $subtotal * ($coupon['value'] / 100);
    }
    $total -= $coupon_discount;
}

?>


<main>
    <section class="breadcrumb-area breadcrumb-bg" data-background="img/bg/breadcrumb_bg03.jpg">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="breadcrumb-content">
                        <h2>Cart Page</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Cart</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="cart-area pt-100 pb-100">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="cart-wrapper">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th class="product-thumbnail"></th>
                                        <th class="product-name">Product</th>
                                        <th class="product-price">Price</th>
                                        <th class="product-quantity">Quantity</th>
                                        <th class="product-subtotal">Subtotal</th>
                                        <th class="product-delete"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td class="product-thumbnail">
                                                <img src="img/product/<?= htmlspecialchars($item['main_image']) ?>" alt="" width="70">
                                            </td>
                                            <td class="product-name">
                                                <h4><?= htmlspecialchars($item['name']) ?></h4>
                                                <small>Size: <?= $item['size'] ?> | Color: <?= $item['color'] ?></small>
                                            </td>
                                            <td class="product-price">
                                                ₹<?= number_format($item['final_price'], 2) ?>
                                            </td>
                                            <td class="product-quantity">
                                                <form method="post" action="update_cart.php">
                                                    <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1">
                                                    <button type="submit">Update</button>
                                                </form>
                                            </td>
                                            <td class="product-subtotal">
                                                ₹<?= number_format($item['final_price'] * $item['quantity'], 2) ?>
                                            </td>
                                            <td class="product-delete">
                                                <a href="delete_cart.php?id=<?= $item['cart_id'] ?>"><i class="flaticon-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <div class="shop-cart-bottom mt-20">
    <div class="cart-coupon">
        <?php if (!isset($_SESSION['coupon'])): ?>
    <form action="apply_coupon.php" method="post">
        <input type="text" name="coupon" placeholder="Enter Coupon Code..." required>
        <button class="btn" type="submit">Apply Coupon</button>
    </form>
<?php else: ?>
   <br><br>
    <form action="remove_coupon.php" method="post" style="margin-top:10px;">
        <button class="btn" type="submit">Remove Coupon</button>
    </form>
    <br>
     <p><strong>Applied Coupon:</strong> <?= htmlspecialchars($_SESSION['coupon']['code']) ?></p>
<?php endif; ?>

    </div>
      <div class="continue-shopping">
                                <a href="product.php" class="btn">Continue Shopping</a>
                            </div>
                           
</div>

                          
                        </div>
<br>

  <a href="dashboard.php" class="btn">Change Address</a>

                    </div>


                    
                    <?php if (isset($_SESSION['coupon_success'])): ?>
    <div style="color: green;"><?= $_SESSION['coupon_success'] ?></div>
    <?php unset($_SESSION['coupon_success']); ?>
<?php elseif (isset($_SESSION['coupon_error'])): ?>
    <div style="color: red;"><?= $_SESSION['coupon_error'] ?></div>
    <?php unset($_SESSION['coupon_error']); ?>
<?php endif; ?>    
<div class="cart-total pt-95">
                    <h3 class="title">CART TOTALS</h3>
                        <div class="shop-cart-widget" style="width:600px;">
                            <form>
                               <ul>
    <li class="sub-total d-flex justify-content-between">
        <span>SUBTOTAL</span>
        <span>₹<?= number_format($subtotal, 2) ?></span>
    </li>
    <li class="d-flex justify-content-between">
        <span>SHIPPING</span>
        <span>Flat Rate: ₹<?= number_format($shipping, 2) ?></span>
    </li>

    <?php if ($coupon_discount > 0): ?>
    <li class="d-flex justify-content-between" style="color: green;">
        <span>DISCOUNT (<?= htmlspecialchars($_SESSION['coupon']['code']) ?>)</span>
        <span>-₹<?= number_format($coupon_discount, 2) ?></span>
    </li>
    <?php endif; ?>

    <li class="cart-total-amount d-flex justify-content-between" style="font-weight: bold;">
        <span>TOTAL</span>
        <span class="amount">₹<?= number_format($total, 2) ?></span>
    </li>
</ul>


                                <a href="checkout.php" class="btn">Proceed to Checkout</a>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'include/footer.php'; ?>
