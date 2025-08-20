<?php
require_once 'include/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'include/config.php';

$cart_count = 0;
$cart_total = 0;
$cart_items = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch cart items with product info
    $stmt = $conn->prepare("
    SELECT 
        cart.id AS cart_id,
        cart.quantity,
        products.name,
        product_variants.image,
        products.price,
        products.discount_type,
        products.discount_value,
        CASE
            WHEN products.discount_type = 'percent' THEN products.price - (products.price * products.discount_value / 100)
            WHEN products.discount_type = 'flat' THEN products.price - products.discount_value
            ELSE products.price
        END AS final_price
    FROM cart
    JOIN product_variants ON cart.product_variant_id = product_variants.id
    JOIN products ON product_variants.product_id = products.id
    WHERE cart.user_id = ?
");

    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $cart_count += $item['quantity'];
        $cart_total += $item['final_price'] * $item['quantity'];
    }
}
$offer_stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'top_offer'");
$offer_stmt->execute();
$top_offer = $offer_stmt->fetchColumn();

?>


<!doctype html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>ClothWings - Modern & Multipurpose eCommerce Template</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

		<link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">
        <!-- Place favicon.ico in the root directory -->

		<!-- CSS here -->
        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/animate.min.css">
        <link rel="stylesheet" href="css/magnific-popup.css">
        <link rel="stylesheet" href="css/fontawesome-all.min.css">
        <link rel="stylesheet" href="css/jquery.mCustomScrollbar.min.css">
        <link rel="stylesheet" href="css/bootstrap-datepicker.min.css">
        <link rel="stylesheet" href="css/swiper-bundle.min.css">
        <link rel="stylesheet" href="css/jquery-ui.min.css">
        <link rel="stylesheet" href="css/nice-select.css">
        <link rel="stylesheet" href="css/jarallax.css">
        <link rel="stylesheet" href="css/flaticon.css">
        <link rel="stylesheet" href="css/slick.css">
        <link rel="stylesheet" href="css/default.css">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/responsive.css">
    </head>
    <style>
        /* Modal content */
.search-modal-content {
  position: relative;
  padding: 40px 30px;
  border-radius: 15px;
  background-color: white;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  text-align: center;
  border: none;
}

/* Close button styling */
.search-close-btn {
  position: absolute;
  top: 15px;
  right: 20px;
  font-size: 24px;
  border: none;
  background: none;
  color: #333;
  z-index: 1;
}

/* Input field */
.search-input {
  width: 100%;
  font-size: 30px;
  padding: 20px 30px;
  border-radius: 50px;
  border: 1px solid #ccc;
  outline: none;
  transition: all 0.3s ease-in-out;
}

/* Input focus effect */
.search-input:focus {
  box-shadow: 0 0 10px rgba(255, 89, 0, 0.5);
  border-color: #ff5c00;
}

/* Search button */
.search-btn {
  position: absolute;
  right: 40px;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: #fff;
  box-shadow: 0 10px 20px rgba(255, 89, 0, 0.3);
  padding: 15px;
  border-radius: 8px;
  transition: all 0.3s ease-in-out;
}

.search-btn i {
  font-size: 26px;
  color: #ff5c00;
}

    </style>
    <body>
          <div id="preloader">
        <div id="ctn-preloader" class="ctn-preloader">
            <div class="animation-preloader">
                <div class="spinner"></div>
            </div>
            <div class="loader">
                <div class="row">
                    <div class="col-3 loader-section section-left">
                        <div class="bg"></div>
                    </div>
                    <div class="col-3 loader-section section-left">
                        <div class="bg"></div>
                    </div>
                    <div class="col-3 loader-section section-right">
                        <div class="bg"></div>
                    </div>
                    <div class="col-3 loader-section section-right">
                        <div class="bg"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <button class="scroll-top scroll-to-target" data-target="html">
            <i class="fas fa-angle-up"></i>
        </button>
      
        <header>
            <div class="header-top-wrap">
                <div class="container custom-container">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-xl-3 col-lg-4 d-none d-lg-block">
                            <div class="logo">
                                <a href="index.php"><img src="img/logo/logo.png" alt=""></a>
                            </div>
                        </div>
                        <div class="col-xl-6 col-lg-5 col-md-6">
                           <div class="header-top-offer">
  <p><?= $top_offer ?></p>
</div>

                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="header-top-action">
                                <ul>
                                    <?php if (isset($_SESSION['user_name'])): ?>
                <li class="user-info" style="color:BLUE; font-weight:600; padding-right:10px;">
                  <i class="fas fa-user-circle"></i> <a href="dashboard.php" style="color:BLUE; font-weight:600;"><?= htmlspecialchars($_SESSION['user_name']) ?> Account
                
                </li>
                <li><a href="logout.php">Log Out</a></li>
              <?php else: ?>
                <li class="sign-in"><a href="signin.php">Sign In</a></li>
              <?php endif; ?>
                                   </a></li>
                                    <li class="header-shop-cart">
    <a href="cart.php">
        <i class="flaticon-shopping-bag"></i>
        <span><?= $cart_count ?></span>
    </a>
    <ul class="minicart">
        <?php if (!empty($cart_items)): ?>
            <?php foreach ($cart_items as $item): ?>
                <li class="d-flex align-items-start">
                    <div class="cart-img">
                        <a href="#"><img src="img/product/<?= htmlspecialchars($item['image']) ?>" alt=""></a>
                    </div>
                    <div class="cart-content">
                        <h4><a href="#"><?= htmlspecialchars($item['name']) ?></a></h4>
                        <div class="cart-price">
                           <span class="new">₹<?= number_format($item['final_price'], 2) ?></span>
                        </div>
                    </div>
                    <div class="del-icon">
                        <a href="delete_cart.php?id=<?= $item['cart_id'] ?>"><i class="far fa-trash-alt"></i></a>
                    </div>
                </li>
            <?php endforeach; ?>
            <li>
                <div class="total-price">
                    <span class="f-left">Total:</span>
                    <span class="f-right">₹<?= number_format($cart_total, 2) ?></span>
                </div>
            </li>
            <li>
                <div class="checkout-link">
                    <a href="cart.php">Shopping Cart</a>
                    <a class="black-color" href="checkout.php">Checkout</a>
                </div>
            </li>
        <?php else: ?>
            <li class="text-center p-2">Your cart is empty.</li>
        <?php endif; ?>
    </ul>
</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="sticky-header" class="main-header menu-area black-bg">
                <div class="container custom-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="mobile-nav-toggler"><i class="fas fa-bars"></i></div>
                            <div class="menu-wrap">
                                <nav class="menu-nav show">
                                    <div class="logo d-block d-lg-none">
                                        <a href="/" class="main-logo"><img src="img/logo/w_logo.png" alt="Logo"></a>
                                        <a href="/" class="sticky-logo"><img src="img/logo/logo.png" alt="Logo"></a>
                                    </div>
                     
                                    <div class="navbar-wrap main-menu d-none d-lg-flex">
                                        <ul class="navigation">
                                            <li class="active menu-item-has-children has--mega--menu"><a href="index.php">Home</a>
                                                
                                            </li>
                                            <li class="has--mega--menu"><a href="product.php">Shop</a>
                                              <ul class="mega-menu">
    <li class="mega-menu-wrap d-flex flex-wrap">
        <?php
        $cat_stmt = $conn->prepare("
            SELECT c.id AS cat_id, c.category_name, s.id AS sub_id, s.subcategory_name 
            FROM categories c 
            LEFT JOIN subcategories s ON c.id = s.category_id 
            ORDER BY c.category_name, s.subcategory_name
        ");
        $cat_stmt->execute();
        $results = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organize subcategories under categories
        $menu = [];
        foreach ($results as $row) {
            $catId = $row['cat_id'];
            $menu[$catId]['name'] = $row['category_name'];
            if (!empty($row['sub_id'])) {
                $menu[$catId]['subcategories'][] = [
                    'id' => $row['sub_id'],
                    'name' => $row['subcategory_name']
                ];
            }
        }

        // Render each category + its subcategories
        foreach ($menu as $catId => $cat):
        ?>
            <ul class="mega-menu-col" style="min-width: 200px; margin-right: 20px;">
                <li class="mega-title">
                    <a href="product.php?category=<?= $catId ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                </li>
                <?php if (!empty($cat['subcategories'])): ?>
                    <?php foreach ($cat['subcategories'] as $sub): ?>
                        <li>
                            <a href="product.php?category=<?= $catId ?>&subcategory=<?= $sub['id'] ?>">
                                <?= htmlspecialchars($sub['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li><em>No Subcategories</em></li>
                <?php endif; ?>
            </ul>
        <?php endforeach; ?>
    </li>
</ul>

                                            </li>
                                            <li><a href="about.php">About Us</a></li>
                                            <li class="menu-item-has-children"><a href="blog.php">blog</a>
                            
                                            </li>
                                            <li><a href="contact.php">Contact Us</a></li>
                                        </ul>
                                    </div>
                                    <div class="header-action d-none d-md-block">
                                        <ul>
                                           
                                            <li class="header-search">
  <a href="#" data-toggle="modal" data-target="#search-modal">
    <i class="flaticon-search-interface-symbol"></i>
  </a>
</li>
 <li class="sidebar-toggle-btn"><a href="#" class="navSidebar-button"><img src="img/icon/sidebar_toggle_icon.png" alt=""></a></li>
                                        </ul>
                                    </div>
                                </nav>
                            </div>
                            <!-- Mobile Menu  -->
                            <div class="mobile-menu">
                                <div class="close-btn"><i class="flaticon-targeting-cross"></i></div>
                                <nav class="menu-box">
                                    <?php if (isset($_SESSION['user_name'])): ?>
    <div style="padding: 10px; color: blue;">
        <i class="fas fa-user-circle"></i> <a href="dashboard.php" style="color:BLUE; font-weight:600;"><?= htmlspecialchars($_SESSION['user_name']) ?> Account</a>
        <br><a href="logout.php">Log Out</a>
    </div>
<?php else: ?>
    <div style="padding: 10px;">
        <a href="signin.php">Sign In</a>
    </div>
<?php endif; ?>

                                    <div class="nav-logo"><a href="index.html"><img src="img/logo/logo.png" alt="" title=""></a>
                                    </div>
                                    <div class="menu-outer">
                                        <ul class="navigation">
    <li><a href="index.php">Home</a></li>
    <li class="menu-item-has-children"><a href="product.php">Shop</a>
        <ul class="submenu">
            <?php
            foreach ($menu as $catId => $cat):
            ?>
                <li class="menu-item-has-children">
                    <a href="product.php?category=<?= $catId ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                    <?php if (!empty($cat['subcategories'])): ?>
                        <ul class="submenu">
                            <?php foreach ($cat['subcategories'] as $sub): ?>
                                <li>
                                    <a href="product.php?category=<?= $catId ?>&subcategory=<?= $sub['id'] ?>">
                                        <?= htmlspecialchars($sub['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </li>
    <li><a href="about.php">About Us</a></li>
    <li><a href="blog.php">Blog</a></li>
    <li><a href="contact.php">Contact Us</a></li>
</ul>

                                    </div>
                                    <div class="social-links">
                                        <ul class="clearfix">
                                            <li><a href="#"><span class="fab fa-twitter"></span></a></li>
                                            <li><a href="#"><span class="fab fa-facebook-square"></span></a></li>
                                            <li><a href="#"><span class="fab fa-pinterest-p"></span></a></li>
                                            <li><a href="#"><span class="fab fa-instagram"></span></a></li>
                                            <li><a href="#"><span class="fab fa-youtube"></span></a></li>
                                        </ul>
                                    </div>
                                </nav>
                            </div>
                            <div class="menu-backdrop"></div>
                            <!-- End Mobile Menu -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Search -->
        <!-- Search Modal Start -->
<!-- Search Modal Start -->
<div class="modal fade" id="search-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content search-modal-content">
      <button type="button" class="close search-close-btn" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      <h5 class="modal-title text-center mb-3 font-weight-bold">Search Products</h5>
      <form action="product.php" method="get" class="search-form">
        <input
          type="text"
          name="search"
          class="form-control search-input"
          placeholder="Search here..."
          value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
          required
        >
        <button type="submit" class="search-btn">
          <i class="flaticon-search-interface-symbol"></i>
        </button>
      </form>
    </div>
  </div>
</div>


            <!-- Modal Search-end -->

            <!-- off-canvas-start -->
            
      

</header>
         <script src="js/vendor/jquery-3.5.0.min.js"></script>
        <script src="js/popper.min.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/isotope.pkgd.min.js"></script>
        <script src="js/imagesloaded.pkgd.min.js"></script>
        <script src="js/jquery.magnific-popup.min.js"></script>
        <script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
        <script src="js/bootstrap-datepicker.min.js"></script>
        <script src="js/jquery.nice-select.min.js"></script>
        <script src="js/jquery.countdown.min.js"></script>
        <script src="js/swiper-bundle.min.js"></script>
        <script src="js/jarallax.min.js"></script>
        <script src="js/slick.min.js"></script>
        <script src="js/wow.min.js"></script>
        <script src="js/nav-tool.js"></script>
        <script src="js/plugins.js"></script>
        <script src="js/main.js"></script>
              </body>
              </html>