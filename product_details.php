<?php
session_start();
require_once 'include/config.php';
include 'include/header.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    echo "<p>Invalid product ID.</p>";
    exit;
}

// Handle review POST submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && !empty($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $rating = filter_var($_POST['rating'], FILTER_VALIDATE_INT, ["options" => ["min_range"=>1, "max_range"=>5]]);
    $review = trim($_POST['review'] ?? '');

    if (!$rating) {
        echo '<div class="alert alert-danger">Please provide a valid rating between 1 and 5.</div>';
    } else {
        try {
            // Check if user already reviewed this product
            $stmt = $conn->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?");
            $stmt->execute([$product_id, $user_id]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Update review
                $update = $conn->prepare("UPDATE product_reviews SET rating = ?, review = ?, created_at = NOW() WHERE id = ?");
                $update->execute([$rating, $review, $row['id']]);
            } else {
                // Insert new review
                $insert = $conn->prepare("INSERT INTO product_reviews (user_id, product_id, rating, review) VALUES (?, ?, ?, ?)");
                $insert->execute([$user_id, $product_id, $rating, $review]);
            }
            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Fetch product details
$stmt = $conn->prepare("
    SELECT p.*, c.category_name, s.subcategory_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN subcategories s ON p.subcategory_id = s.id
    WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    echo "<p>Product not found.</p>";
    exit;
}

// Fetch images: order by is_default desc
$stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_default DESC, id ASC");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch variants for sizes & colors
$sizes = $conn->prepare("SELECT DISTINCT size FROM product_variants WHERE product_id = ?");
$sizes->execute([$product_id]);
$sizes = $sizes->fetchAll(PDO::FETCH_ASSOC);

$colors = $conn->prepare("SELECT DISTINCT color FROM product_variants WHERE product_id = ?");
$colors->execute([$product_id]);
$colors = $colors->fetchAll(PDO::FETCH_ASSOC);

// Related products - same subcategory, exclude current product
$related = $conn->prepare("SELECT * FROM products WHERE subcategory_id = ? AND id != ? LIMIT 4");
$related->execute([$product['subcategory_name'] ?? $product['subcategory_id'], $product_id]);
$related = $related->fetchAll(PDO::FETCH_ASSOC);

// Aggregate review data
$review_stats = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count_reviews FROM product_reviews WHERE product_id = ?");
$review_stats->execute([$product_id]);
$review_stats = $review_stats->fetch(PDO::FETCH_ASSOC);
$avg_rating = $review_stats['avg_rating'] ? round($review_stats['avg_rating'], 1) : 0;
$total_reviews = (int)$review_stats['count_reviews'];

// Fetch all reviews and commenters' names
$stmt = $conn->prepare("
    SELECT pr.*, u.name AS user_name
    FROM product_reviews pr
    LEFT JOIN users u ON pr.user_id = u.id
    WHERE pr.product_id = ?
    ORDER BY pr.created_at DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's own review if logged in
$user_review = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM product_reviews WHERE product_id = ? AND user_id = ?");
    $stmt->execute([$product_id, $_SESSION['user_id']]);
    $user_review = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($product['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
       /* Star rating container */
.rating-stars {
    cursor: pointer;
    user-select: none;
}

/* Each star icon */
.rating-stars i {
    font-size: 1.5rem;
    color: #FFD700;       /* Gold color for filled stars */
    margin-right: 5px;
    transition: color 0.2s ease-in-out;
}

/* Empty star */
.rating-stars i.fa-star-o {
    color: #ccc;          /* Gray color for empty stars */
}

/* Hover effect on stars */
.rating-stars i:hover,
.rating-stars i:hover ~ i {
    color: #ffc107;       /* Slightly lighter gold on hover */
}

/* Container for each review */
.review-item {
    border-bottom: 1px solid #ddd;
    padding: 15px 0;
    margin-bottom: 15px;
}

/* Header within each review: username and date */
.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    margin-bottom: 6px;
    color: #333;
}

/* Date styling */
.review-date {
    font-size: 0.875rem;
    color: #666;
}

/* Review text */
.review-text {
    margin-top: 8px;
    white-space: pre-line; /* preserves line breaks */
    color: #444;
    font-size: 1rem;
    line-height: 1.4;
}

/* Optional: You can also add styles to the container of all reviews */
.review-list {
    padding-left: 0;
    margin: 0;
    list-style: none;
}

/* Responsive image thumbnails in rating stars (optional) */
.rating-stars i {
    cursor: pointer;
}

/* Accessibility: Focus styles */
.rating-stars i:focus,
.rating-stars i:hover {
    outline: none;
    color: #ffc107;
}

    </style>
</head>
<body>
<main class="container my-5">

    <div class="row">
        <!-- Images and thumbnails -->
        <div class="col-md-6">
            <ul class="nav nav-tabs" id="imageTab" role="tablist">
                <?php foreach ($images as $i => $img): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $i == 0 ? 'active' : '' ?>" id="tab-<?= $i ?>" data-bs-toggle="tab" data-bs-target="#img-<?= $i ?>" type="button" role="tab">
                        <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Thumbnail" class="img-thumbnail" style="height:60px" />
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="tab-content mt-3">
                <?php foreach ($images as $i => $img): ?>
                <div class="tab-pane fade <?= $i == 0 ? 'show active' : '' ?>" id="img-<?= $i ?>" role="tabpanel">
                    <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Product Image" class="img-fluid rounded" />
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product details -->
        <div class="col-md-6">
            <h2><?= htmlspecialchars($product['name']) ?></h2>

            <div class="mb-3" title="Average Rating <?= $avg_rating ?>">
                <?php
                for ($i = 1; $i <= 5; $i++) {
                    if ($avg_rating >= $i) {
                        echo '<i class="fa fa-star"></i>';
                    } elseif ($avg_rating >= $i - 0.5) {
                        echo '<i class="fa fa-star-half-alt"></i>';
                    } else {
                        echo '<i class="fa fa-star-o"></i>';
                    }
                }
                ?>
                <span class="ms-2"><?= $total_reviews ?> review<?= $total_reviews !== 1 ? 's' : '' ?></span>
            </div>

            <?php
            $price = $product['price'];
            if ($product['discount_type'] === 'percentage' || $product['discount_type'] === 'percent') {
                $discounted = $price - ($price * $product['discount_value'] / 100);
                echo '<h4><del>₹' . number_format($price, 2) . '</del> <span class="text-danger fw-bold">₹' . number_format($discounted, 2) . '</span></h4>';
            } elseif ($product['discount_type'] === 'flat') {
                $discounted = $price - $product['discount_value'];
                echo '<h4><del>₹' . number_format($price, 2) . '</del> <span class="text-danger fw-bold">₹' . number_format($discounted, 2) . '</span></h4>';
            } else {
                echo '<h4>₹' . number_format($price, 2) . '</h4>';
            }
            ?>

            <p><strong>SKU:</strong> <?= htmlspecialchars($product['sku_code']) ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($product['category_name']) ?></p>
            <p><strong>Subcategory:</strong> <?= htmlspecialchars($product['subcategory_name']) ?></p>
            <p><strong>Material:</strong> <?= htmlspecialchars($product['material']) ?></p>
            <p><strong>Fit:</strong> <?= htmlspecialchars($product['fit']) ?></p>
            <p><strong>Occasion:</strong> <?= htmlspecialchars($product['occasion']) ?></p>
            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

            <form id="addToCartForm" method="POST" action="add-to-cart.php" class="mb-4">
                <input type="hidden" name="product_id" value="<?= $product_id ?>">
                <input type="hidden" name="product_variant_id" id="variantId">

                <div class="mb-3">
                    <label for="sizeSelect" class="form-label">Size</label>
                    <select class="form-select" id="sizeSelect" name="size" required>
                        <option value="">Select Size</option>
                        <?php foreach ($sizes as $size): ?>
                        <option value="<?= htmlspecialchars($size['size']) ?>"><?= htmlspecialchars($size['size']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="colorSelect" class="form-label">Color</label>
                    <select class="form-select" id="colorSelect" name="color" required>
                        <option value="">Select Color</option>
                        <?php foreach ($colors as $color): ?>
                        <option value="<?= htmlspecialchars($color['color']) ?>"><?= htmlspecialchars($color['color']) ?></option>
                        <?php endforeach; ?>
                    </select>

                </div>
               

                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" name="quantity" value="1" min="1" class="form-control" required>
                </div>
                 <p id="stockInfo" class="<?= ($product['stock'] > 0) ? 'text-success' : 'text-danger' ?>">
    <?= ($product['stock'] > 0) ? "In Stock: " . (int)$product['stock'] : "Out of Stock" ?>
</p>
                <button type="submit" class="btn btn-primary" <?= ($product['stock'] <= 0) ? 'disabled' : '' ?>>Add to Cart</button>

            </form>

        </div>
    </div>

    <div class="mt-5">
        <ul class="nav nav-tabs" id="reviewTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">
                    Description
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">
                    Reviews (<?= $total_reviews ?>)
                </button>
            </li>
        </ul>
        <div class="tab-content p-3 border border-top-0" id="reviewTabContent">
            <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>
            <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">

                <?php if (!empty($_SESSION['user_id'])): ?>
                <form method="POST" class="mb-4">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <div class="mb-2">
                        <label class="form-label">Your Rating</label><br />
                        <div id="ratingStars" class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa <?= (!empty($user_review['rating']) && $user_review['rating'] >= $i) ? 'fa-star' : 'fa-star-o' ?>" data-star="<?= $i ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="<?= !empty($user_review['rating']) ? $user_review['rating'] : 0 ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="reviewText" class="form-label">Your Review (optional)</label>
                        <textarea class="form-control" id="reviewText" rows="3" name="review"><?= htmlspecialchars($user_review['review'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-success">Submit Review</button>
                </form>
                <?php else: ?>
                    <p>Please <a href="login.php">login</a> to leave a review.</p>
                <?php endif; ?>

                <?php if ($total_reviews === 0): ?>
                    <p>No reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <span class="review-username"><?= htmlspecialchars($rev['user_name'] ?? 'Anonymous') ?></span>
                                <span class="review-date"><?= date('d M, Y', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa <?= $rev['rating'] >= $i ? 'fa-star' : 'fa-star-o' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="review-text"><?= nl2br(htmlspecialchars($rev['review'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h3 class="mt-5">You May Also Like</h3>
    <div class="row row-cols-2 row-cols-md-4 g-3">
        <?php foreach ($related as $r): ?>
        <div class="col text-center">
            <a href="product-details.php?id=<?= $r['id'] ?>" class="text-decoration-none text-dark">
                <img src="<?= htmlspecialchars($r['main_image']) ?>" alt="<?= htmlspecialchars($r['name']) ?>" class="img-fluid rounded" style="max-height:180px; object-fit:contain;">
                <h6 class="mt-2"><?= htmlspecialchars($r['name']) ?></h6>
                <div class="fw-bold text-primary">₹<?= number_format($r['price'], 2) ?></div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const addToCartForm = document.getElementById('addToCartForm');
    addToCartForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const size = document.getElementById('sizeSelect').value.trim();
        const color = document.getElementById('colorSelect').value.trim();

        if (!size || !color) {
            alert('Please select size and color.');
            return;
        }

        // AJAX to get variant id
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'get-variant-id.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if(xhr.status === 200) {
                const variantId = xhr.responseText.trim();
                if (variantId === '0' || variantId === '') {
                    alert('Selected variant not available.');
                } else {
                    document.getElementById('variantId').value = variantId;
                    // Now submit real form
                    addToCartForm.submit();
                }
            } else {
                alert('Error checking product variant.');
            }
        };
        xhr.send(`product_id=<?= $product_id ?>&size=` + encodeURIComponent(size) + `&color=` + encodeURIComponent(color));
    });

    // Review rating stars widget
    document.addEventListener('DOMContentLoaded', function() {
        const starsContainer = document.getElementById('ratingStars');
        const ratingInput = document.getElementById('ratingInput');
        if (starsContainer) {
            starsContainer.addEventListener('click', function(e) {
                if(e.target.tagName === 'I' && e.target.dataset.star) {
                    let rating = parseInt(e.target.dataset.star);
                    ratingInput.value = rating;
                    [...starsContainer.children].forEach((star, idx) => {
                        star.className = 'fa ' + (idx < rating ? 'fa-star' : 'fa-star-o');
                    });
                }
            });
        }
    });
</script>

<?php include 'include/footer.php'; ?>
</body>
</html>
