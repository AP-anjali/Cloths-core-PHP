<?php

require_once 'include/config.php';
require_once 'include/header.php';

// Security: Only allow logged-in admins
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// --- HELPER for showing alerts ---
function showAlert($msg, $type = 'success') {
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
            $msg
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}
$errors = [];
$success = '';
$editing = null;

// --- Handle DELETE ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id == 1) {
        $errors[] = "The master admin cannot be deleted.";
    } else {
        $stmt = $conn->prepare("DELETE FROM admin WHERE id=?");
        if ($stmt->execute([$del_id])) $success = "Admin deleted successfully.";
        else $errors[] = "Failed to delete admin.";
    }
}

// --- Handle CREATE and UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['admin_id']) && is_numeric($_POST['admin_id']) ? (int)$_POST['admin_id'] : null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name) $errors[] = "Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";

    if ($id) {
        // On edit, only check unique email if changing
        $stmt = $conn->prepare("SELECT id FROM admin WHERE email=? AND id<>?");
        $stmt->execute([$email,$id]);
        if ($stmt->fetch()) $errors[] = "Another admin with this email exists.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM admin WHERE email=?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = "Email already used.";
    }

    $set_pass = false;
    if ($id && $password) $set_pass = true; // On edit, only set if filled
    if (!$id && !$password) $errors[] = "Password required for new admin.";
    if ($password && strlen($password) < 6) $errors[] = "Password too short (min 6 chars).";

    if (!$errors) {
        $query = $id ?
            ($set_pass
                ? "UPDATE admin SET name=?, email=?, password=? WHERE id=?"
                : "UPDATE admin SET name=?, email=? WHERE id=?")
            : "INSERT INTO admin (name,email,password) VALUES (?,?,?)";
        $stmt = $conn->prepare($query);
        if ($id) {
            if ($set_pass) $ok = $stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$id]);
            else $ok = $stmt->execute([$name,$email,$id]);
        } else {
            $ok = $stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
        }
        $success = $id ? "Admin updated." : "New admin added.";
    }
}

// --- Handle EDIT: fetch details
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM admin WHERE id=?");
    $stmt->execute([$edit_id]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- FETCH all admins for list ---
$stmt = $conn->query("SELECT * FROM admin ORDER BY id ASC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h2 class="mb-4">Manage Admin Users</h2>
    <?php if ($errors) foreach($errors as $e) showAlert($e, 'danger'); ?>
    <?php if ($success) showAlert($success, 'success'); ?>

    <!-- ADMIN FORM -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold"><?php echo $editing ? "Edit Admin #".$editing['id'] : "Add New Admin"; ?></div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="admin_id" value="<?php echo $editing['id'] ?? ''; ?>">
                <div class="col-md-4">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" required class="form-control"
                        value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" required class="form-control"
                        value="<?php echo htmlspecialchars($editing['email'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">
                        <?php echo $editing ? 'New Password (leave blank to keep unchanged)' : 'Password *'; ?>
                    </label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-control" minlength="6">
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="let f=this.previousElementSibling;f.type=(f.type=='password'?'text':'password');this.blur();">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 text-end">
                    <?php if ($editing): ?>
                        <a href="admin_admins.php" class="btn btn-secondary me-2">Cancel</a>
                    <?php endif;?>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editing ? 'Update Admin' : 'Add Admin'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- ADMIN TABLE -->
    <div class="card shadow-sm">
        <div class="card-header bg-light fw-bold">Admin List (<?php echo count($admins); ?>)</div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th>
                        <th>Password (hash)</th>
                        <th class="text-center" style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$admins): ?>
                    <tr><td colspan="5" class="text-center text-muted">No admin users found.</td></tr>
                <?php else: foreach($admins as $a): ?>
                    <tr>
                        <td><?php echo $a['id']; ?></td>
                        <td><?php echo htmlspecialchars($a['name']); ?></td>
                        <td><?php echo htmlspecialchars($a['email']); ?></td>
                        <td style="font-size:11px; max-width:240px; word-break:break-all;">
                            <?php echo htmlspecialchars($a['password']); ?>
                        </td>
                        <td class="text-center">
                            <a href="?edit=<?php echo $a['id'];?>" class="btn btn-sm btn-warning me-1" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <?php if ($a['id'] != 1): ?>
                            <a href="?delete=<?php echo $a['id'];?>" class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete admin <?php echo htmlspecialchars($a['email']); ?>?')" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif;?>
                        </td>
                    </tr>
                <?php endforeach; endif;?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap Icons for button icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
<?php require_once 'include/footer.php'; ?>
