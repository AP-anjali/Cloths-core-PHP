<?php

require_once 'include/config.php';  // Your PDO connection in $conn
require_once 'include/header.php';  // Your header for layout and Bootstrap CSS/JS

// Protect page
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Helper: show alert messages
function showAlert($msg, $type = 'success') {
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>"
        . htmlspecialchars($msg) .
        "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
    </div>";
}

$errors = [];
$success = '';

// -------------------------
// Handle Category CRUD
// -------------------------

// Delete Category (and its subcategories)
if (isset($_GET['delete_cat']) && is_numeric($_GET['delete_cat'])) {
    $cat_id = (int)$_GET['delete_cat'];

    $conn->beginTransaction();
    try {
        // Delete all subcategories linked to this category
        $stmt = $conn->prepare("DELETE FROM subcategories WHERE category_id = ?");
        $stmt->execute([$cat_id]);

        // Delete the category itself
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$cat_id]);

        $conn->commit();
        $success = "Category and its subcategories deleted successfully.";
    } catch (Exception $e) {
        $conn->rollBack();
        $errors[] = "Failed to delete category: " . $e->getMessage();
    }
}

// Add or Update Category
if (isset($_POST['save_category'])) {
    $cat_id = isset($_POST['category_id']) && is_numeric($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $category_name = trim($_POST['category_name'] ?? '');

    if ($category_name === '') {
        $errors[] = "Category name is required.";
    } else {
        // Check unique name
        if ($cat_id) {
            $stmt = $conn->prepare("SELECT id FROM categories WHERE category_name = ? AND id != ?");
            $stmt->execute([$category_name, $cat_id]);
        } else {
            $stmt = $conn->prepare("SELECT id FROM categories WHERE category_name = ?");
            $stmt->execute([$category_name]);
        }
        if ($stmt->fetch()) {
            $errors[] = "Category name already exists.";
        }
    }

    if (!$errors) {
        if ($cat_id) {
            $stmt = $conn->prepare("UPDATE categories SET category_name = ? WHERE id = ?");
            $stmt->execute([$category_name, $cat_id]);
            $success = "Category updated successfully.";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmt->execute([$category_name]);
            $success = "Category added successfully.";
        }
    }
}

// -------------------------
// Handle Subcategory CRUD
// -------------------------

// Delete Subcategory
if (isset($_GET['delete_subcat']) && is_numeric($_GET['delete_subcat'])) {
    $subcat_id = (int)$_GET['delete_subcat'];
    $stmt = $conn->prepare("DELETE FROM subcategories WHERE id = ?");
    if ($stmt->execute([$subcat_id])) {
        $success = "Subcategory deleted successfully.";
    } else {
        $errors[] = "Failed to delete subcategory.";
    }
}

// Add or Update Subcategory
if (isset($_POST['save_subcategory'])) {
    $subcat_id = isset($_POST['subcategory_id']) && is_numeric($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
    $category_id = $_POST['subcat_category_id'] ?? null;
    $subcategory_name = trim($_POST['subcategory_name'] ?? '');

    if (!is_numeric($category_id)) {
        $errors[] = "Please select a valid category for the subcategory.";
    }
    if ($subcategory_name === '') {
        $errors[] = "Subcategory name is required.";
    } else {
        // Check unique subcategory name within the same category
        if ($subcat_id) {
            $stmt = $conn->prepare("SELECT id FROM subcategories WHERE subcategory_name = ? AND category_id = ? AND id != ?");
            $stmt->execute([$subcategory_name, $category_id, $subcat_id]);
        } else {
            $stmt = $conn->prepare("SELECT id FROM subcategories WHERE subcategory_name = ? AND category_id = ?");
            $stmt->execute([$subcategory_name, $category_id]);
        }
        if ($stmt->fetch()) {
            $errors[] = "Subcategory already exists in selected category.";
        }
    }

    if (!$errors) {
        if ($subcat_id) {
            $stmt = $conn->prepare("UPDATE subcategories SET subcategory_name = ?, category_id = ? WHERE id = ?");
            $stmt->execute([$subcategory_name, $category_id, $subcat_id]);
            $success = "Subcategory updated successfully.";
        } else {
            $stmt = $conn->prepare("INSERT INTO subcategories (subcategory_name, category_id) VALUES (?, ?)");
            $stmt->execute([$subcategory_name, $category_id]);
            $success = "Subcategory added successfully.";
        }
    }
}

// -------------------------
// Fetch all categories and subcategories for display
// -------------------------
$categories = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM subcategories s WHERE s.category_id = c.id) AS subcount FROM categories c ORDER BY c.category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$subcategories = $conn->query("SELECT s.*, c.category_name FROM subcategories s JOIN categories c ON s.category_id = c.id ORDER BY c.category_name ASC, s.subcategory_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// For edit forms
$editing_category = null;
$editing_subcategory = null;

if (isset($_GET['edit_cat']) && is_numeric($_GET['edit_cat'])) {
    $cat_id = (int)$_GET['edit_cat'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$cat_id]);
    $editing_category = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_GET['edit_subcat']) && is_numeric($_GET['edit_subcat'])) {
    $subcat_id = (int)$_GET['edit_subcat'];
    $stmt = $conn->prepare("SELECT * FROM subcategories WHERE id = ?");
    $stmt->execute([$subcat_id]);
    $editing_subcategory = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<div class="container">
    <h1 class="mb-4">Manage Categories & Subcategories</h1>

    <?php
    // Show alerts
    if ($errors) {
        foreach ($errors as $e) {
            showAlert($e, 'danger');
        }
    }
    if ($success) {
        showAlert($success, 'success');
    }
    ?>

    <div class="row g-4">

        <!-- Categories Section -->
        <div class="col-md-6">

            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold">
                    <?php echo $editing_category ? 'Edit Category' : 'Add New Category'; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="category_id" value="<?php echo $editing_category['id'] ?? ''; ?>">
                        <div class="col-12">
                            <label for="category_name" class="form-label">Category Name *</label>
                            <input type="text" name="category_name" id="category_name" class="form-control" required maxlength="100"
                                value="<?php echo htmlspecialchars($editing_category['category_name'] ?? ''); ?>">
                        </div>
                        <div class="col-12 text-end">
                            <?php if ($editing_category): ?>
                                <a href="admin_categories.php" class="btn btn-secondary me-2">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" name="save_category" class="btn btn-primary">
                                <?php echo $editing_category ? 'Update Category' : 'Add Category'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-light fw-bold">Categories List (<?php echo count($categories); ?>)</div>
                <div class="card-body p-0 table-responsive" style="max-height: 460px; overflow-y:auto;">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Subcategories</th>
                                <th class="text-center" style="width:120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$categories): ?>
                            <tr><td colspan="4" class="text-center text-muted">No categories found.</td></tr>
                        <?php else: foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                                <td class="text-center"><?php echo $cat['subcount']; ?></td>
                                <td class="text-center">
                                    <a href="?edit_cat=<?php echo $cat['id']; ?>" class="btn btn-sm btn-warning me-1" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="?delete_cat=<?php echo $cat['id']; ?>"
                                       onclick="return confirm('Delete category & all its subcategories?')"
                                       class="btn btn-sm btn-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Subcategories Section -->
        <div class="col-md-6">

            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold">
                    <?php echo $editing_subcategory ? 'Edit Subcategory' : 'Add New Subcategory'; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="subcategory_id" value="<?php echo $editing_subcategory['id'] ?? ''; ?>">

                        <div class="col-12">
                            <label for="subcat_category_id" class="form-label">Select Category *</label>
                            <select name="subcat_category_id" id="subcat_category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php if (($editing_subcategory['category_id'] ?? '') == $cat['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="subcategory_name" class="form-label">Subcategory Name *</label>
                            <input type="text" name="subcategory_name" id="subcategory_name" class="form-control" required maxlength="100"
                                value="<?php echo htmlspecialchars($editing_subcategory['subcategory_name'] ?? ''); ?>">
                        </div>
                        <div class="col-12 text-end">
                            <?php if ($editing_subcategory): ?>
                                <a href="admin_categories.php" class="btn btn-secondary me-2">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" name="save_subcategory" class="btn btn-primary">
                                <?php echo $editing_subcategory ? 'Update Subcategory' : 'Add Subcategory'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-light fw-bold">Subcategories List (<?php echo count($subcategories); ?>)</div>
                <div class="card-body p-0 table-responsive" style="max-height: 460px; overflow-y:auto;">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>ID</th>
                                <th>Subcategory Name</th>
                                <th>Category</th>
                                <th class="text-center" style="width:120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$subcategories): ?>
                            <tr><td colspan="4" class="text-center text-muted">No subcategories found.</td></tr>
                        <?php else: foreach($subcategories as $subcat): ?>
                            <tr>
                                <td><?php echo $subcat['id']; ?></td>
                                <td><?php echo htmlspecialchars($subcat['subcategory_name']); ?></td>
                                <td><?php echo htmlspecialchars($subcat['category_name']); ?></td>
                                <td class="text-center">
                                    <a href="?edit_subcat=<?php echo $subcat['id']; ?>" class="btn btn-sm btn-warning me-1" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="?delete_subcat=<?php echo $subcat['id']; ?>" class="btn btn-sm btn-danger" title="Delete"
                                       onclick="return confirm('Delete subcategory?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif;?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap Icons CDN for nice icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<?php require_once 'include/footer.php'; ?>
