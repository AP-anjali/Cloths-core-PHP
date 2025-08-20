<?php
require_once('include/config.php');

// Fetch slider data
$slider_q = $conn->query("SELECT * FROM home_slider ORDER BY id ASC");
$sliders = $slider_q->fetchAll(PDO::FETCH_ASSOC);

// Fetch banner data
$stmt = $conn->query("SELECT * FROM category_banners ORDER BY id ASC");
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group banners by position
$left = array_filter($banners, fn($b) => $b['position'] === 'left');
$topLeft = array_filter($banners, fn($b) => $b['position'] === 'top-left');
$topRight = array_filter($banners, fn($b) => $b['position'] === 'top-right');
$bottom = array_filter($banners, fn($b) => $b['position'] === 'bottom');
$sql = "
SELECT 
    p.id,
    p.name,
    p.price,
    p.discount_type,
    p.discount_value,
    pi.image_url AS main_image
FROM products p
LEFT JOIN product_images pi 
    ON pi.product_id = p.id AND pi.is_default = 1
WHERE p.stock > 0 
ORDER BY p.created_at DESC 
LIMIT 8
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html class="no-js" lang="">

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

<body>

    <!-- preloader  -->

    <button class="scroll-top scroll-to-target" data-target="html">
            <i class="fas fa-angle-up"></i>
        </button>
 
 <main>
<!-- Slider Area Start -->
<section class="slider-area position-relative">
    <div class="container-fluid p-0 fix">
        <div class="main-slider-active">
            <?php foreach ($sliders as $slider): ?>
                <div class="single-slider">
                    <div class="row no-gutters">
                        <div class="col-md-6">
                            <div class="slider-main-img" data-background="uploads/banners/<?= htmlspecialchars($slider['main_image']) ?>"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="slider-bg" data-background="uploads/banners/<?= htmlspecialchars($slider['bg_image']) ?>">
                                <div class="slider-content">
                                    <h3 class="sub-title animated" data-animation-in="fadeInUp" data-delay-in=".2">
                                        <?= htmlspecialchars($slider['sub_title']) ?>
                                    </h3>
                                    <h2 class="title animated" data-animation-in="fadeInUp" data-delay-in=".4">
                                        <?= htmlspecialchars($slider['title']) ?>
                                    </h2>
                                    <p class="animated" data-animation-in="fadeInUp" data-delay-in=".6">
                                        <?= htmlspecialchars($slider['description']) ?>
                                    </p>
                                    <a href="<?= htmlspecialchars($slider['button_link']) ?>" class="btn animated" data-animation-in="fadeInUp" data-delay-in=".8">
                                        <?= htmlspecialchars($slider['button_text']) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Slider Bottom Navigation -->
        <div class="slider-bottom-nav">
            <div class="main-slider-nav">
                <?php foreach ($sliders as $slider): ?>
                    <div class="slider-nav-item">
                        <img src="uploads/banners/<?= htmlspecialchars($slider['bottom_image']) ?>" alt="">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Category Banners -->
<section class="category-banner-area pt-30">
    <div class="container custom-container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-12 col-sm-10">
                <?php foreach ($left as $banner): ?>
                    <div class="cat-banner-item banner-animation mb-20">
                        <a href="<?= $banner['link'] ?>">
                            <img src="uploads/banners/<?= htmlspecialchars($banner['image']) ?>" alt="">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-6 col-md-12 col-sm-10">
                <div class="row">
                    <?php foreach ($topLeft as $banner): ?>
                        <div class="col-md-6">
                            <div class="cat-banner-item banner-animation mb-20">
                                <div class="thumb">
                                    <a href="<?= $banner['link'] ?>">
                                        <img src="uploads/banners/<?= htmlspecialchars($banner['image']) ?>" alt="">
                                    </a>
                                </div>
                                <div class="content">
                                    <span><?= $banner['title'] ?></span>
                                    <h3><a href="<?= $banner['link'] ?>"><?= $banner['subtitle'] ?></a></h3>
                                    <a href="<?= $banner['link'] ?>" class="btn">shop now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($topRight as $banner): ?>
                        <div class="col-md-6">
                            <div class="cat-banner-item banner-animation mb-20">
                                <div class="thumb">
                                    <a href="<?= $banner['link'] ?>">
                                        <img src="uploads/banners/<?= htmlspecialchars($banner['image']) ?>" alt="">
                                    </a>
                                </div>
                                <div class="content">
                                    <span><?= $banner['title'] ?></span>
                                    <h3><a href="<?= $banner['link'] ?>"><?= $banner['subtitle'] ?></a></h3>
                                    <a href="<?= $banner['link'] ?>" class="btn">shop now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($bottom as $banner): ?>
                        <div class="col-12">
                            <div class="cat-banner-item style-two banner-animation mb-20">
                                <div class="thumb">
                                    <a href="<?= $banner['link'] ?>">
                                        <img src="uploads/banners/<?= htmlspecialchars($banner['image']) ?>" alt="">
                                    </a>
                                </div>
                                <div class="content">
                                    <span><?= $banner['title'] ?></span>
                                    <h3><a href="<?= $banner['link'] ?>"><?= $banner['subtitle'] ?></a></h3>
                                    <a href="<?= $banner['link'] ?>" class="btn">shop now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    </div>
</section>
 <section class="promo-services pt-50 pb-25">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-3 col-md-6 col-sm-8">
                        <div class="promo-services-item mb-40">
                            <div class="icon"><img src="img/icon/promo_icon01.png" alt=""></div>
                            <div class="content">
                                <h6>payment & delivery</h6>
                                <p>Delivered, when you receive arrives</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-8">
                        <div class="promo-services-item mb-40">
                            <div class="icon"><img src="img/icon/promo_icon02.png" alt=""></div>
                            <div class="content">
                                <h6>Return Product</h6>
                                <p>Retail, a Product Return Process</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-8">
                        <div class="promo-services-item mb-40">
                            <div class="icon"><img src="img/icon/promo_icon03.png" alt=""></div>
                            <div class="content">
                                <h6>money back guarantee</h6>
                                <p>Options Including 24/7</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-8">
                        <div class="promo-services-item mb-40">
                            <div class="icon"><img src="img/icon/promo_icon04.png" alt=""></div>
                            <div class="content">
                                <h6>Quality support</h6>
                                <p>Support Options Including 24/7</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- promo-services-end -->
<section class="features-products gray-bg pt-95 pb-100">
    <div class="container custom-container">
        <div class="row justify-content-center">
            <div class="col-xl-4 col-lg-6">
                <div class="section-title text-center mb-25">
                    <h3 class="title">FEATURED PRODUCTS</h3>
                </div>
            </div>
        </div>

        <div class="row features-product-active">
            <?php foreach ($result as $row): ?>
                <?php
                    $final_price = $row['price'];
                    if ($row['discount_type'] == 'percent') {
                        $final_price -= ($row['price'] * $row['discount_value'] / 100);
                    } elseif ($row['discount_type'] == 'flat') {
                        $final_price -= $row['discount_value'];
                    }
                ?>
                <div class="col">
                    <div class="features-product-item mb-30">
                        <div class="features-product-thumb">
                            <?php if (!empty($row['discount_type']) && $row['discount_value'] > 0): ?>
                                <div class="discount-tag">
                                    -<?= $row['discount_type'] == 'percent' ? $row['discount_value'].'%' : '₹'.$row['discount_value'] ?>
                                </div>
                            <?php endif; ?>
                            <a href="product_details.php?id=<?= $row['id'] ?>">
                                <img src="uploads/<?= htmlspecialchars($row['main_image']) ?>" alt="">
                            </a>
                            <div class="product-overlay-action">
                                <ul>
                                    <li><a href="#"><i class="far fa-heart"></i></a></li>
                                    <li><a href="product_details.php?id=<?= $row['id'] ?>"><i class="far fa-eye"></i></a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="features-product-content">
                            <h5><a href="product_details.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></a></h5>
                            <p class="price">₹<?= number_format($final_price, 2) ?></p>
                        </div>
                        <div class="features-product-cart">
                            <a href="product_details.php?id=<?= $row['id']  ?>">See Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

</main>


    <!-- JS here -->
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