<?php
include 'include/header.php';
require_once 'include/config.php';

// Fetch categories
$cat_stmt = $conn->prepare("SELECT * FROM categories");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter & Sort Logic
$where = [];
$params = [];
$sortSql = " ORDER BY p.created_at DESC";

if (!empty($_GET['category'])) {
    $where[] = "p.category_id = :category_id";
    $params[':category_id'] = $_GET['category'];
}
if (!empty($_GET['subcategory'])) {
    $where[] = "p.subcategory_id = :subcategory_id";
    $params[':subcategory_id'] = $_GET['subcategory'];
}
if (!empty($_GET['search'])) {
    $where[] = "p.name LIKE :search";
    $params[':search'] = "%" . $_GET['search'] . "%";
}

if (!empty($_GET['sort'])) {
    if ($_GET['sort'] == "price_asc") {
        $sortSql = " ORDER BY final_price ASC";
    } elseif ($_GET['sort'] == "price_desc") {
        $sortSql = " ORDER BY final_price DESC";
    }
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Products Query
$product_stmt = $conn->prepare("SELECT 
    p.*, 
    c.category_name,
    sc.subcategory_name,
    (CASE 
        WHEN p.discount_type = 'flat' THEN p.price - p.discount_value
        WHEN p.discount_type = 'percent' THEN p.price - (p.price * p.discount_value / 100)
        ELSE p.price
    END) AS final_price
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
$whereSql
$sortSql");
$product_stmt->execute($params);
$products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <section class="shop-area pt-100 pb-100">
        <div class="container">
            <div class="row">
                <!-- Products -->
                <div class="col-xl-9 col-lg-8">
                    <div class="shop-top-meta mb-35">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="shop-top-left">
                                    <ul>
                              
                                        <li>Showing <?= count($products) ?> items</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="shop-top-right">
                                    <form action="product.php" method="get">
                                        <input type="hidden" name="category" value="<?= $_GET['category'] ?? '' ?>">
                                        <input type="hidden" name="subcategory" value="<?= $_GET['subcategory'] ?? '' ?>">
                                        <input type="hidden" name="search" value="<?= $_GET['search'] ?? '' ?>">
                                        <select name="sort" onchange="this.form.submit()">
                                            <option value="">Sort by newness</option>
                                            <option value="price_asc" <?= ($_GET['sort'] ?? '') == 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                            <option value="price_desc" <?= ($_GET['sort'] ?? '') == 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-xl-4 col-sm-6">
                                <div class="new-arrival-item text-center mb-50">
                                    <div class="thumb mb-25">
                                        <?php if ($product['discount_value'] > 0): ?>
                                            <div class="discount-tag">
                                                <?= $product['discount_type'] == 'percent' ? '- ' . $product['discount_value'] . '%' : '- ₹' . $product['discount_value'] ?>
                                            </div>
                                        <?php endif; ?>
                                        <a href="product_details.php?id=<?= $product['id'] ?>">
                                            <img src="img/product/<?= htmlspecialchars($product['main_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                        </a>
                                        <div class="product-overlay-action">
                                            <ul>
                                                <li><a href="cart.php?action=add&id=<?= $product['id'] ?>"><i class="far fa-heart"></i></a></li>
                                                <li><a href="product_details.php?id=<?= $product['id'] ?>"><i class="far fa-eye"></i></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="content">
                                        <h5><a href="product_details.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a></h5>
                                        <span class="price">₹<?= number_format($product['final_price'], 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-xl-3 col-lg-4">
                    <aside class="shop-sidebar">
                        <!-- Search -->
                        <div class="widget side-search-bar">
                            <form action="product.php" method="get">
                                <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Search products">
                                <button><i class="flaticon-search"></i></button>
                            </form>
                        </div>

                        <!-- Categories -->
                        <div class="widget">
                            <h4 class="widget-title">Product Categories</h4>
                            <div class="shop-cat-list">
                                <ul>
                                    <?php foreach ($categories as $cat): ?>
                                        <li><a href="product.php?category=<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Subcategories -->
                        <?php if (!empty($_GET['category'])): ?>
                        <div class="widget">
                            <h4 class="widget-title">Subcategories</h4>
                            <div class="shop-cat-list">
                                <ul>
                                    <?php
                                    $sub_stmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ?");
                                    $sub_stmt->execute([$_GET['category']]);
                                    $subcategories = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($subcategories as $sub): ?>
                                        <li><a href="product.php?category=<?= $_GET['category'] ?>&subcategory=<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subcategory_name']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </div>
    </section>
</main>