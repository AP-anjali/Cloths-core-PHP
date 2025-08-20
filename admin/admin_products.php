<?php
require_once 'include/config.php';
require_once 'include/header.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Helper for alert messages
function showAlert($msg, $type = 'success') {
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>"
        . htmlspecialchars($msg) .
        "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
    </div>";
}

$errors = [];
$success = '';

// Handle DELETE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];

    // get main image path to delete physical file
    $stmt = $conn->prepare("SELECT main_image FROM products WHERE id = ?");
    $stmt->execute([$del_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // delete main image file if exists and not empty
        if (!empty($product['main_image']) && file_exists($product['main_image'])) {
            @unlink($product['main_image']);
        }
        // Delete the product record
        $delSt = $conn->prepare("DELETE FROM products WHERE id = ?");
        if ($delSt->execute([$del_id])) {
            $success = "Product #$del_id deleted successfully.";
        } else {
            $errors[] = "Failed to delete product.";
        }
    } else {
        $errors[] = "Product not found.";
    }
}

// Handle CREATE & UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['product_id']) && is_numeric($_POST['product_id']) ? (int)$_POST['product_id'] : null;

    // Sanitize Inputs
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $subcategory_id = $_POST['subcategory_id'] ?? null;
    $price = $_POST['price'] ?? null;
    $discount_type = $_POST['discount_type'] ?? null;
    $discount_value = $_POST['discount_value'] ?? 0;
    $description = trim($_POST['description'] ?? '');
    $stock = $_POST['stock'] ?? 0;
    $brand = trim($_POST['brand'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $sku_code = trim($_POST['sku_code'] ?? '');
    $fabric = trim($_POST['fabric'] ?? '');
    $fit = trim($_POST['fit'] ?? '');
    $sleeve_type = trim($_POST['sleeve_type'] ?? '');
    $occasion = trim($_POST['occasion'] ?? '');
    $pattern = trim($_POST['pattern'] ?? '');

    // Validate required fields
    if ($name === '') $errors[] = "Product name is required.";
    if (!is_numeric($category_id)) $errors[] = "Category must be selected.";
    if (!is_numeric($subcategory_id)) $errors[] = "Subcategory must be selected.";
    if (!is_numeric($price) || $price < 0) $errors[] = "Price must be a positive number.";
    if (!in_array($discount_type, ['flat','percentage','percentage', 'percent', 'flat'], true)) {
      // Some inconsistency in your data: your DB has varchar for discount_type, sample has 'percentage' and 'flat'
      // Let's allow 'percentage' and 'flat' for discount type values
      // So let's check for 'percentage' or 'flat'
      if (!in_array($discount_type, ['flat', 'percentage'])) {
        $errors[] = "Discount type must be 'flat' or 'percentage'.";
      }
    }
    if (!is_numeric($discount_value) || $discount_value < 0) $errors[] = "Discount value must be a positive number.";
    if (!is_numeric($stock) || $stock < 0) $errors[] = "Stock must be a non-negative integer.";
    if ($sku_code === '') $errors[] = "SKU code is required.";

    // Handle image upload if provided
    $main_image_path = null;
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['main_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if ($file['error'] === 0 && in_array(mime_content_type($file['tmp_name']), $allowed_types)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = 'uploads/products/' . uniqid('prod_', true) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $newFileName)) {
                $errors[] = "Failed to upload product image.";
            } else {
                $main_image_path = $newFileName;
            }
        } else {
            $errors[] = "Invalid main image file type. Only JPG, PNG, WEBP allowed.";
        }
    }

    if (!$errors) {
        try {
            if ($id) {
                // For update, if new image uploaded, delete old image
                if ($main_image_path) {
                    // get old image path
                    $stmt = $conn->prepare("SELECT main_image FROM products WHERE id = ?");
                    $stmt->execute([$id]);
                    $old = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($old && !empty($old['main_image']) && file_exists($old['main_image'])) {
                        @unlink($old['main_image']);
                    }
                } else {
                    // keep old image path if no new uploaded
                    $stmt = $conn->prepare("SELECT main_image FROM products WHERE id = ?");
                    $stmt->execute([$id]);
                    $old = $stmt->fetch(PDO::FETCH_ASSOC);
                    $main_image_path = $old['main_image'] ?? null;
                }

                // normalize discount_type to 'percentage' or 'flat'
                if ($discount_type === 'percent') $discount_type = 'percentage';

                $stmt = $conn->prepare("UPDATE products SET 
                    name=?, category_id=?, subcategory_id=?, price=?, discount_type=?, discount_value=?, description=?, stock=?, brand=?, material=?, gender=?, main_image=?, sku_code=?, fabric=?, fit=?, sleeve_type=?, occasion=?, pattern=? 
                    WHERE id=?");
                $stmt->execute([
                    $name, $category_id, $subcategory_id, $price, $discount_type, $discount_value,
                    $description, $stock, $brand, $material, $gender, $main_image_path,
                    $sku_code, $fabric, $fit, $sleeve_type, $occasion, $pattern, $id
                ]);
                $success = "Product #$id updated successfully.";
            } else {
                // normalize discount_type to 'percentage' or 'flat'
                if ($discount_type === 'percent') $discount_type = 'percentage';

                $stmt = $conn->prepare("INSERT INTO products 
                    (name, category_id, subcategory_id, price, discount_type, discount_value, description, stock, brand, material, gender, main_image, sku_code, fabric, fit, sleeve_type, occasion, pattern) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $name, $category_id, $subcategory_id, $price, $discount_type, $discount_value,
                    $description, $stock, $brand, $material, $gender, $main_image_path,
                    $sku_code, $fabric, $fit, $sleeve_type, $occasion, $pattern
                ]);
                $success = "New product added successfully.";
            }
        } catch (Exception $ex) {
            $errors[] = "Database error: " . $ex->getMessage();
            if ($main_image_path && file_exists($main_image_path)) {
                @unlink($main_image_path); // clean up uploaded file on failure
            }
        }
    }
}

// Load categories and subcategories from DB for selects
$categories = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $conn->query("SELECT id, category_id, subcategory_name FROM subcategories ORDER BY subcategory_name")->fetchAll(PDO::FETCH_ASSOC);

// Load products list
$products = $conn->query("
    SELECT p.*, c.category_name, s.subcategory_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN subcategories s ON p.subcategory_id = s.id
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// If editing product (via GET param)
$editing_product = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$edit_id]);
    $editing_product = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<div class="container">
    <h1 class="mb-4">Manage Products</h1>

    <?php
    if ($errors) foreach ($errors as $err) showAlert($err, 'danger');
    if ($success) showAlert($success, 'success');
    ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header fw-bold bg-light">
            <?php echo $editing_product ? "Edit Product ID #" . htmlspecialchars($editing_product['id']) : "Add New Product"; ?>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="row g-3" novalidate>
                <input type="hidden" name="product_id" value="<?php echo $editing_product['id'] ?? ''; ?>">

                <div class="col-md-6">
                    <label class="form-label" for="name">Product Name <span class="text-danger">*</span></label>
                    <input id="name" type="text" name="name" class="form-control" required maxlength="255"
                        value="<?php echo htmlspecialchars($editing_product['name'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="category_id">Category <span class="text-danger">*</span></label>
                    <select id="category_id" name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id']?>" <?= (isset($editing_product['category_id']) && $editing_product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_name'])?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="subcategory_id">Subcategory <span class="text-danger">*</span></label>
                    <select id="subcategory_id" name="subcategory_id" class="form-select" required>
                        <option value="">Select Subcategory</option>
                        <?php foreach ($subcategories as $subcat): ?>
                            <option value="<?= $subcat['id']?>" 
                                <?= (isset($editing_product['subcategory_id']) && $editing_product['subcategory_id'] == $subcat['id']) ? 'selected' : '' ?>
                                data-category="<?= $subcat['category_id'] ?>">
                                <?= htmlspecialchars($subcat['subcategory_name'])?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="price">Price ₹ <span class="text-danger">*</span></label>
                    <input id="price" type="number" min="0" step="0.01" name="price" class="form-control" required
                        value="<?php echo htmlspecialchars($editing_product['price'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="discount_type">Discount Type</label>
                    <select id="discount_type" name="discount_type" class="form-select">
                        <option value="">None</option>
                        <option value="flat" <?= (isset($editing_product['discount_type']) && $editing_product['discount_type']=='flat') ? 'selected' : '' ?>>Flat</option>
                        <option value="percentage" <?= (isset($editing_product['discount_type']) && $editing_product['discount_type']=='percentage') ? 'selected' : '' ?>>Percentage</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="discount_value">Discount Value</label>
                    <input id="discount_value" type="number" min="0" step="0.01" name="discount_value" class="form-control"
                        value="<?= htmlspecialchars($editing_product['discount_value'] ?? '0'); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="stock">Stock <span class="text-danger">*</span></label>
                    <input id="stock" type="number" min="0" name="stock" class="form-control" required
                        value="<?= htmlspecialchars($editing_product['stock'] ?? '0'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="sku_code">SKU Code <span class="text-danger">*</span></label>
                    <input id="sku_code" type="text" name="sku_code" maxlength="100" class="form-control" required
                        value="<?= htmlspecialchars($editing_product['sku_code'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="brand">Brand</label>
                    <input id="brand" type="text" name="brand" class="form-control" maxlength="100"
                        value="<?= htmlspecialchars($editing_product['brand'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="material">Material</label>
                    <input id="material" type="text" name="material" class="form-control" maxlength="100"
                        value="<?= htmlspecialchars($editing_product['material'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="gender">Gender</label>
                    <select id="gender" name="gender" class="form-select">
                        <option value="">Choose...</option>
                        <option value="Male" <?= (isset($editing_product['gender']) && $editing_product['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= (isset($editing_product['gender']) && $editing_product['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                        <option value="Unisex" <?= (isset($editing_product['gender']) && $editing_product['gender'] == 'Unisex') ? 'selected' : '' ?>>Unisex</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="fabric">Fabric</label>
                    <input id="fabric" type="text" name="fabric" class="form-control" maxlength="100"
                        value="<?= htmlspecialchars($editing_product['fabric'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="fit">Fit</label>
                    <input id="fit" type="text" name="fit" class="form-control" maxlength="50"
                        value="<?= htmlspecialchars($editing_product['fit'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="sleeve_type">Sleeve Type</label>
                    <input id="sleeve_type" type="text" name="sleeve_type" class="form-control" maxlength="50"
                        value="<?= htmlspecialchars($editing_product['sleeve_type'] ?? ''); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="occasion">Occasion</label>
                    <input id="occasion" type="text" name="occasion" class="form-control" maxlength="100"
                        value="<?= htmlspecialchars($editing_product['occasion'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="pattern">Pattern</label>
                    <input id="pattern" type="text" name="pattern" class="form-control" maxlength="100"
                        value="<?= htmlspecialchars($editing_product['pattern'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" rows="4" class="form-control"><?= htmlspecialchars($editing_product['description'] ?? ''); ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="main_image">Main Image <?= $editing_product ? '(Upload to replace)' : '(Required)' ?></label>
                    <input id="main_image" type="file" name="main_image" class="form-control" accept="image/*" <?= (!$editing_product ? 'required' : ''); ?>>
                    <?php if ($editing_product && !empty($editing_product['main_image']) && file_exists($editing_product['main_image'])): ?>
                        <img src="<?= htmlspecialchars($editing_product['main_image']); ?>" alt="Product Image" class="img-thumbnail mt-2" style="max-width:180px;">
                    <?php endif; ?>
                </div>

                <div class="col-12 text-end mt-4">
                    <?php if ($editing_product): ?>
                        <a href="admin_products.php" class="btn btn-secondary me-2">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">
                        <?= $editing_product ? 'Update Product' : 'Add Product' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card shadow-sm">
        <div class="card-header fw-bold bg-light">
            Products List (<?= count($products); ?>)
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-secondary text-center">
                    <tr>
                        <th>ID</th>
                        <th>Main Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Subcategory</th>
                        <th>Price (₹)</th>
                        <th>Discount</th>
                        <th>Stock</th>
                        <th>SKU Code</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="11" class="text-center text-muted">No products found.</td></tr>
                <?php else: foreach ($products as $p): ?>
                    <tr class="align-middle text-center">
                        <td><?= $p['id']; ?></td>
                        <td>
                            <?php if (!empty($p['main_image']) && file_exists($p['main_image'])): ?>
                                <img src="<?= htmlspecialchars($p['main_image']); ?>" alt="Main Image" style="height:60px; max-width:60px; object-fit:cover; border-radius:4px;">
                            <?php else: ?>
                                <small class="text-muted">No image</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-start"><?= htmlspecialchars($p['name']); ?></td>
                        <td><?= htmlspecialchars($p['category_name'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($p['subcategory_name'] ?? ''); ?></td>
                        <td><?= number_format($p['price'], 2); ?></td>
                        <td>
                            <?php 
                                if (!empty($p['discount_type']) && !empty($p['discount_value'])) {
                                    $dispVal = $p['discount_type'] === 'flat' ? '₹' . number_format($p['discount_value'], 2) : number_format($p['discount_value']) . '%';
                                    echo "<span class='badge bg-info text-dark'>$dispVal</span>";
                                } else {
                                    echo '-';
                                }
                            ?>
                        </td>
                        <td><?= (int)$p['stock']; ?></td>
                        <td><?= htmlspecialchars($p['sku_code']); ?></td>
                        <td><?= date('d/M/Y', strtotime($p['created_at'])); ?></td>
                        <td>
                            <a href="?edit=<?= $p['id']; ?>" class="btn btn-sm btn-warning me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>
                            <a href="?delete=<?= $p['id']; ?>" class="btn btn-sm btn-danger" title="Delete"
                               onclick="return confirm('Delete product #<?= $p['id']; ?>? This action cannot be undone.')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<!-- JS: Filter subcategory by category -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const catSelect = document.getElementById('category_id');
    const subSelect = document.getElementById('subcategory_id');
    const allOptions = [...subSelect.options];

    function filterSubcategories() {
        const catVal = catSelect.value;
        subSelect.innerHTML = '<option value="">Select Subcategory</option>';
        allOptions.forEach(option => {
            if (!option.getAttribute('data-category') || option.getAttribute('data-category') === catVal) {
                subSelect.appendChild(option);
            }
        });
    }

    catSelect.addEventListener('change', filterSubcategories);
    filterSubcategories(); // Initial filter

    // Retain selected subcategory after filter if exists
    const selectedSubVal = '<?= $editing_product['subcategory_id'] ?? '' ?>';
    if (selectedSubVal) subSelect.value = selectedSubVal;
});
</script>

<?php require_once 'include/footer.php'; ?>
