<?php

require_once 'include/config.php';
require_once 'include/header.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// Helpers for alert messages
function showAlert($msg, $type = 'success') {
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
            $msg
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
}

// Pagination params
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset = ($page-1)*$perPage;

$errors = [];
$success = '';

// Handle Delete User
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];

    // OPTIONAL: Prevent deleting admin user if you want to protect
    if ($del_id == 1) {
        $errors[] = "Master user cannot be deleted.";
    } else {
        // Delete user orders first to maintain DB integrity if needed (optional)
        /*
        $stmtOrdersDel = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
        $stmtOrdersDel->execute([$del_id]);

        $stmtMsgDel = $conn->prepare("DELETE FROM contact_messages WHERE email = (SELECT email FROM users WHERE id = ?)");
        $stmtMsgDel->execute([$del_id]);
        */

        // Or just delete user (if your DB is configured with cascade, related orders might be auto deleted)
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$del_id])) {
            $success = "User ID #$del_id deleted successfully.";
        } else {
            $errors[] = "Failed to delete user ID #$del_id.";
        }
    }
}

// Search filter
$search = trim($_GET['search'] ?? '');

// Fetch total user count with search condition
$count_sql = "SELECT COUNT(*) FROM users";
$params = [];
if ($search) {
    $count_sql .= " WHERE name LIKE :search OR email LIKE :search OR phone LIKE :search";
    $params[':search'] = "%$search%";
}
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();

// Fetch users with pagination and search
$sql = "SELECT * FROM users";
if ($search) {
    $sql .= " WHERE name LIKE :search OR email LIKE :search OR phone LIKE :search";
}
$sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);

// Bind parameters
if ($search) $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle View User Details with orders and messages
$view_user = null;
$user_orders = [];
$user_messages = [];

if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $view_id = (int)$_GET['view'];
    // User details
    $stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$view_id]);
    $view_user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($view_user) {
        // User orders
        $stmtOrders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmtOrders->execute([$view_id]);
        $user_orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

        // User messages (by email match)
        $stmtMsgs = $conn->prepare("SELECT * FROM contact_messages WHERE email = ? ORDER BY created_at DESC");
        $stmtMsgs->execute([$view_user['email']]);
        $user_messages = $stmtMsgs->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $errors[] = "User not found.";
    }
}

// Calculate total pages
$total_pages = ceil($total_users / $perPage);

?>

<div class="container">
    <h1 class="mb-4">User Management</h1>

    <?php
    if ($errors) {
        foreach ($errors as $e) showAlert(htmlspecialchars($e), 'danger');
    }
    if ($success) {
        showAlert(htmlspecialchars($success), 'success');
    }
    ?>

    <?php if ($view_user): ?>
        <!-- View User Details -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>User Details - #<?php echo $view_user['id']; ?> (<?php echo htmlspecialchars($view_user['name']); ?>)</h5>
                <a href="admin_users.php" class="btn btn-secondary btn-sm">Back to Users</a>
            </div>
            <div class="card-body">
                <dl class="row mb-4">
                    <dt class="col-sm-3">Name:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($view_user['name']); ?></dd>

                    <dt class="col-sm-3">Email:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($view_user['email']); ?></dd>

                    <dt class="col-sm-3">Phone:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($view_user['phone']); ?></dd>

                    <dt class="col-sm-3">Address:</dt>
                    <dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($view_user['address'])); ?></dd>

                    <dt class="col-sm-3">City:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($view_user['city']); ?></dd>

                    <dt class="col-sm-3">Pincode:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($view_user['pincode']); ?></dd>

                    <dt class="col-sm-3">Created At:</dt>
                    <dd class="col-sm-9"><?php echo date('d/M/Y H:i', strtotime($view_user['created_at'])); ?></dd>

                    <dt class="col-sm-3">Verified:</dt>
                    <dd class="col-sm-9"><?php echo $view_user['is_verified'] ? 'Yes' : 'No'; ?></dd>
                </dl>

                <h5>User Orders (<?php echo count($user_orders); ?>)</h5>
                <?php if (!$user_orders): ?>
                    <p>No orders found for this user.</p>
                <?php else: ?>
                    <table class="table table-bordered table-striped text-center mb-4">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th><th>Total Amount (₹)</th><th>Payment Status</th><th>Order Status</th><th>Created At</th><th>Cancel Request</th><th>Tracking Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_orders as $o): ?>
                                <tr>
                                    <td><?php echo $o['id']; ?></td>
                                    <td><?php echo number_format($o['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($o['payment_status']); ?></td>
                                    <td><?php echo htmlspecialchars($o['order_status']); ?></td>
                                    <td><?php echo date('d/M/Y', strtotime($o['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($o['cancel_request']); ?></td>
                                    <td><?php echo htmlspecialchars($o['tracking_number'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <h5>User Messages (<?php echo count($user_messages); ?>)</h5>
                <?php if (!$user_messages): ?>
                    <p>No messages found.</p>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr><th>ID</th><th>Message</th><th>Sent At</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_messages as $msg): ?>
                                    <tr>
                                        <td><?php echo $msg['id']; ?></td>
                                        <td style="white-space: pre-wrap;"><?php echo htmlspecialchars($msg['message']); ?></td>
                                        <td><?php echo date('d/M/Y H:i', strtotime($msg['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    <?php else: ?>
        <!-- User List with Search and Pagination -->
        <div class="mb-3">
            <form method="get" class="d-flex" role="search">
                <input type="search" class="form-control me-2" placeholder="Search by name/email/phone" name="search" value="<?php echo htmlspecialchars($search); ?>" />
                <button class="btn btn-primary" type="submit">Search</button>
                <?php if($search): ?>
                    <a href="admin_users.php" class="btn btn-outline-secondary ms-2">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-bold bg-light">Users List (<?php echo $total_users; ?>)</div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-secondary text-center">
                        <tr>
                            <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>City</th><th>Verified</th><th>Registered At</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$users): ?>
                        <tr><td colspan="8" class="text-center text-muted">No users found.</td></tr>
                    <?php else: foreach ($users as $user): ?>
                        <tr>
                            <td class="text-center"><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($user['city'] ?? '-'); ?></td>
                            <td class="text-center">
                                <?php if($user['is_verified']): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">No</span>
                                <?php endif; ?>
                             </td>
                            <td><?php echo date('d/M/Y', strtotime($user['created_at'])); ?></td>
                            <td class="text-center">
                                <a href="?view=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary me-1">View</a>
                                <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete user #<?php echo $user['id']; ?>? This action cannot be undone.')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="my-3" aria-label="User pagination">
                <ul class="pagination justify-content-center mb-0">
                    <?php
                    $base_url = strtok($_SERVER['REQUEST_URI'], '?');
                    $queryParams = $_GET;
                    for ($p = 1; $p <= $total_pages; $p++) {
                        $queryParams['page'] = $p;
                        $url = $base_url . '?' . http_build_query($queryParams);
                        $active = $p == $page ? 'active' : '';
                        echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
                    }
                    ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />

<?php require_once 'include/footer.php'; ?>
