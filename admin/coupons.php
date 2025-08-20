<?php

require_once 'include/config.php'; // Your PDO connection
require_once 'include/header.php'; // Your header file

// Protect page:
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Helper alert function
function showAlert($msg, $type = 'success') {
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
            $msg
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
}

// Initialize vars
$errors = [];
$success = '';

// Handle coupon deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Coupon #$id deleted successfully.";
    } else {
        $errors[] = "Failed to delete coupon #$id.";
    }
}

// Handle coupon creation and update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['coupon_id']) && is_numeric($_POST['coupon_id']) ? (int)$_POST['coupon_id'] : null;
    $code = trim($_POST['code'] ?? '');
    $discount_type = $_POST['discount_type'] ?? '';
    $discount_value = trim($_POST['discount_value'] ?? '');
    $status = $_POST['status'] ?? 'active';

    // Server-side validation
    if (!$code) $errors[] = "Coupon code is required.";
    if (!in_array($discount_type, ['flat', 'percent'])) $errors[] = "Invalid discount type.";
    if (!is_numeric($discount_value) || $discount_value <= 0) $errors[] = "Discount value must be a positive number.";
    if (!in_array($status, ['active', 'expired'])) $errors[] = "Invalid status.";

    // Check for uniqueness of code on insert or if code changed on update
    if (!$errors) {
        if ($id) {
            $stmt = $conn->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
            $stmt->execute([$code, $id]);
            if ($stmt->fetch()) $errors[] = "Coupon code already exists.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM coupons WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) $errors[] = "Coupon code already exists.";
        }
    }

    if (!$errors) {
        try {
            if ($id) {
                $stmt = $conn->prepare("UPDATE coupons SET code = ?, discount_type = ?, discount_value = ?, status = ? WHERE id = ?");
                $stmt->execute([$code, $discount_type, $discount_value, $status, $id]);
                $success = "Coupon #$id updated successfully.";
            } else {
                $stmt = $conn->prepare("INSERT INTO coupons (code, discount_type, discount_value, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$code, $discount_type, $discount_value, $status]);
                $success = "New coupon added successfully.";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch coupons list for display
$stmt = $conn->query("SELECT * FROM coupons ORDER BY id DESC");
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Editing coupon check
$editing = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$edit_id]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editing) {
        $errors[] = "Coupon not found for editing.";
    }
}

?>

<div class="container">
    <h1 class="mb-4">Manage Coupons</h1>

    <?php
    // Show alerts
    if ($errors) foreach ($errors as $err) showAlert(htmlspecialchars($err), 'danger');
    if ($success) showAlert(htmlspecialchars($success), 'success');
    ?>

    <!-- Add/Edit Coupon Form -->
    <div class="card mb-5 shadow-sm">
        <div class="card-header bg-light fw-bold">
            <?php echo $editing ? "Edit Coupon #{$editing['id']}" : "Add New Coupon"; ?>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="coupon_id" value="<?php echo $editing['id'] ?? ''; ?>">
                <div class="col-md-4">
                    <label for="code" class="form-label">Coupon Code *</label>
                    <input type="text" id="code" name="code" class="form-control" required 
                        value="<?php echo htmlspecialchars($editing['code'] ?? ''); ?>" maxlength="50" />
                </div>
                <div class="col-md-4">
                    <label for="discount_type" class="form-label">Discount Type *</label>
                    <select id="discount_type" name="discount_type" class="form-select" required>
                        <option value="">Select type</option>
                        <option value="flat" <?php if (($editing['discount_type'] ?? '') == 'flat') echo 'selected'; ?>>Flat</option>
                        <option value="percent" <?php if (($editing['discount_type'] ?? '') == 'percent') echo 'selected'; ?>>Percent</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="discount_value" class="form-label">Discount Value *</label>
                    <input type="number" id="discount_value" name="discount_value" min="0.01" step="0.01" class="form-control" required
                        value="<?php echo htmlspecialchars($editing['discount_value'] ?? ''); ?>" />
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status *</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="active" <?php if (($editing['status'] ?? 'active') == 'active') echo 'selected'; ?>>Active</option>
                        <option value="expired" <?php if (($editing['status'] ?? '') == 'expired') echo 'selected'; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-12 text-end">
                    <?php if ($editing): ?>
                        <a href="admin_coupons.php" class="btn btn-secondary me-2">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editing ? 'Update Coupon' : 'Add Coupon'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Coupon List Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-light fw-bold">All Coupons (<?php echo count($coupons); ?>)</div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Discount Type</th>
                        <th>Discount Value</th>
                        <th>Status</th>
                        <th class="text-center" style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$coupons): ?>
                        <tr><td colspan="6" class="text-center text-muted">No coupons found.</td></tr>
                    <?php else: ?>
                        <?php foreach($coupons as $c): ?>
                        <tr>
                            <td><?php echo (int)$c['id']; ?></td>
                            <td><?php echo htmlspecialchars($c['code']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($c['discount_type'])); ?></td>
                            <td>
                                <?php 
                                if ($c['discount_type'] === 'flat') 
                                    echo "₹" . number_format($c['discount_value'], 2);
                                else 
                                    echo number_format($c['discount_value'], 2) . "%"; 
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $c['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($c['status']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="?edit=<?php echo $c['id']; ?>" class="btn btn-sm btn-warning me-1" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger" title="Delete"
                                    onclick="return confirm('Delete coupon #<?php echo $c['id']; ?>? This action cannot be undone.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap Icons (for edit/delete icons) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
<?php require_once 'include/footer.php'; ?>
