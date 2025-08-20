<?php

require_once 'include/config.php';
require_once 'include/header.php';

// Protect page: only logged-in admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Helper: Flash alert messages
function showAlert($msg, $type='success'){
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
    $msg
    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}

// Variables
$errors = [];
$success = '';

// --- Handle Order Status Update ---
if (isset($_POST['update_order_status'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = $_POST['order_status'] ?? '';
    $new_payment_status = $_POST['payment_status'] ?? '';
    $cancel_request = $_POST['cancel_request'] ?? 'None';

    if (!$order_id || !$new_status || !$new_payment_status) {
        $errors[] = "Missing order or status info.";
    } else {
        // Update order table
        $stmt = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = ?, cancel_request = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $new_payment_status, $cancel_request, $order_id])) {
            // Insert into order_status_history
            $hist_stmt = $conn->prepare("INSERT INTO order_status_history (order_id, status) VALUES (?, ?)");
            $hist_stmt->execute([$order_id, $new_status]);
            $success = "Order #$order_id updated successfully.";
        } else {
            $errors[] = "Failed to update order #$order_id.";
        }
    }
}

// --- Pagination setup ---
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page'])? (int)$_GET['page']:1;
$offset = ($page-1)*$perPage;

// --- Handle View Order Details ---
$view_order = null;
$order_items = [];
$order_status_history = [];
$order_tracking = [];
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $order_id = (int)$_GET['view'];

    // Get order with user name
    $stmt = $conn->prepare("
        SELECT o.*, u.name AS user_name, u.email, u.phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $view_order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($view_order) {
        // Get order items with product variant info
        $items_stmt = $conn->prepare("
            SELECT oi.*, pv.size, pv.color, p.name AS product_name
            FROM order_items oi
            LEFT JOIN product_variants pv ON oi.product_variant_id = pv.id
            LEFT JOIN products p ON pv.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $items_stmt->execute([$order_id]);
        $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get order status history
        $hist_stmt = $conn->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY updated_at DESC");
        $hist_stmt->execute([$order_id]);
        $order_status_history = $hist_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get order tracking logs
        $track_stmt = $conn->prepare("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY updated_at DESC");
        $track_stmt->execute([$order_id]);
        $order_tracking = $track_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $errors[] = "Order not found.";
    }
}

// --- Fetch total orders count for pagination ---
$total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// --- Fetch paginated orders list ---
$orders_stmt = $conn->prepare("
    SELECT o.*, u.name AS user_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT :limit OFFSET :offset
");
$orders_stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$orders_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper arrays for dropdown options
$order_statuses = ['Pending', 'Confirmed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'];
$payment_statuses = ['Pending', 'Success', 'Failed', 'Refunded'];
$cancel_requests = ['None', 'Requested', 'Approved', 'Rejected'];

?>

<div class="container">

    <h1 class="mb-4">Order Management</h1>

    <?php
    if ($errors) {
        foreach ($errors as $err) showAlert(htmlspecialchars($err), 'danger');
    }
    if ($success) {
        showAlert(htmlspecialchars($success), 'success');
    }
    ?>

<?php if ($view_order): ?>
    <!-- ORDER DETAILS VIEW -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Order Details - #<?php echo $view_order['id']; ?></h5>
            <a href="order.php" class="btn btn-secondary btn-sm">Back to Orders</a>
        </div>
        <div class="card-body">
            <dl class="row mb-3">
                <dt class="col-sm-3">User</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($view_order['user_name'] ?? 'Guest'); ?> (<?php echo htmlspecialchars($view_order['email'] ?? 'No email'); ?>)</dd>
                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($view_order['phone'] ?? 'N/A'); ?></dd>
                <dt class="col-sm-3">Total Amount</dt>
                <dd class="col-sm-9">₹<?php echo number_format($view_order['total_amount'], 2); ?></dd>
                <dt class="col-sm-3">Payment Method</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($view_order['payment_method']); ?></dd>
                <dt class="col-sm-3">Payment Status</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($view_order['payment_status']); ?></dd>
                <dt class="col-sm-3">Order Status</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($view_order['order_status']); ?></dd>
                <dt class="col-sm-3">Cancel Request</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($view_order['cancel_request']); ?></dd>
                <dt class="col-sm-3">Tracking Number</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($view_order['tracking_number'] ?? 'N/A'); ?></dd>
                <dt class="col-sm-3">Courier Service</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($view_order['courier_service'] ?? 'N/A'); ?></dd>
                <dt class="col-sm-3">Estimated Delivery Date</dt>
                <dd class="col-sm-9"><?php echo $view_order['estimated_delivery_date'] ? date('d/M/Y', strtotime($view_order['estimated_delivery_date'])) : 'N/A'; ?></dd>
                <dt class="col-sm-3">Order Created At</dt>
                <dd class="col-sm-9"><?php echo date('d/M/Y H:i', strtotime($view_order['created_at'])); ?></dd>
            </dl>

            <h5>Order Items</h5>
            <?php if (empty($order_items)): ?>
                <p>No items found for this order.</p>
            <?php else: ?>
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th>#</th><th>Product Name</th><th>Size</th><th>Color</th><th>Quantity</th><th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($order_items as $i => $item): ?>
                        <tr class="text-center">
                            <td><?php echo $i+1; ?></td>
                            <td class="text-start"><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['size']); ?></td>
                            <td><?php echo htmlspecialchars($item['color']); ?></td>
                            <td><?php echo (int)$item['quantity']; ?></td>
                            <td>₹<?php echo number_format($item['price'],2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h5 class="mt-4">Status History</h5>
            <?php if (empty($order_status_history)): ?>
                <p>No status updates available.</p>
            <?php else: ?>
                <ul class="list-group mb-4">
                    <?php foreach($order_status_history as $history): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($history['status']); ?>
                            <span class="badge bg-primary rounded-pill"><?php echo date('d/M/Y H:i', strtotime($history['updated_at'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h5>Tracking Logs</h5>
            <?php if (empty($order_tracking)): ?>
                <p>No tracking logs.</p>
            <?php else: ?>
                <table class="table table-bordered mb-4">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Message</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($order_tracking as $track): ?>
                        <tr class="text-center">
                            <td><?php echo htmlspecialchars($track['status']); ?></td>
                            <td><?php echo htmlspecialchars($track['location']); ?></td>
                            <td class="text-start"><?php echo nl2br(htmlspecialchars($track['message'])); ?></td>
                            <td><?php echo date('d/M/Y H:i', strtotime($track['updated_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Update order status form -->
            <form method="post" class="mt-4">
                <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="order_status" class="form-label">Order Status</label>
                        <select name="order_status" id="order_status" class="form-select" required>
                            <?php foreach($order_statuses as $status): ?>
                            <option value="<?php echo $status; ?>"
                                <?php if ($view_order['order_status'] === $status) echo 'selected'; ?>>
                                <?php echo $status; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select name="payment_status" id="payment_status" class="form-select" required>
                            <?php foreach($payment_statuses as $pstatus): ?>
                            <option value="<?php echo $pstatus; ?>"
                                <?php if ($view_order['payment_status'] === $pstatus) echo 'selected'; ?>>
                                <?php echo $pstatus; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="cancel_request" class="form-label">Cancel Request</label>
                        <select name="cancel_request" id="cancel_request" class="form-select" required>
                            <?php foreach($cancel_requests as $creq): ?>
                            <option value="<?php echo $creq; ?>"
                                <?php if ($view_order['cancel_request'] === $creq) echo 'selected'; ?>>
                                <?php echo $creq; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" name="update_order_status" class="btn btn-success btn-lg">Update Order</button>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- ORDERS LIST -->
    <div class="card shadow-sm">
        <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
            Orders List (<?php echo $total_orders; ?>)
            <span>
                Page <?php echo $page; ?> of <?php echo ceil($total_orders/$perPage); ?>
            </span>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-secondary text-center">
                    <tr>
                        <th>Order ID</th>
                        <th>User</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                        <th>Cancel Request</th>
                        <th>Tracking Number</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="10" class="text-center text-muted">No orders found.</td></tr>
                <?php else: foreach ($orders as $order): ?>
                    <tr class="text-center">
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['user_name'] ?? 'Guest'); ?></td>
                        <td>₹<?php echo number_format($order['total_amount'],2); ?></td>
                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                        <td>
                            <span class="badge bg-<?php
                            switch(strtolower($order['payment_status'])) {
                                case 'success': echo 'success'; break;
                                case 'pending': echo 'warning'; break;
                                case 'failed': echo 'danger'; break;
                                default: echo 'secondary';
                            }
                            ?>">
                                <?php echo htmlspecialchars($order['payment_status']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php
                            switch(strtolower($order['order_status'])) {
                                case 'confirmed': echo 'info'; break;
                                case 'shipped': echo 'primary'; break;
                                case 'delivered': echo 'success'; break;
                                case 'pending': echo 'warning'; break;
                                case 'cancelled': echo 'danger'; break;
                                default: echo 'secondary';
                            }
                            ?>">
                            <?php echo htmlspecialchars($order['order_status']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php
                            switch(strtolower($order['cancel_request'])) {
                                case 'requested': echo 'warning'; break;
                                case 'approved': echo 'success'; break;
                                case 'rejected': echo 'danger'; break;
                                default: echo 'secondary';
                            }
                            ?>">
                            <?php echo htmlspecialchars($order['cancel_request']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($order['tracking_number'] ?? '-'); ?></td>
                        <td><?php echo date('d/M/Y H:i', strtotime($order['created_at'])); ?></td>
                        <td>
                            <a href="?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary" title="View Details">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav class="my-3" aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0">
                <?php
                $total_pages = ceil($total_orders/$perPage);
                $base_url = strtok($_SERVER["REQUEST_URI"], '?');
                for ($p=1; $p<=$total_pages; $p++) {
                    $active = $p == $page ? 'active': '';
                    echo "<li class='page-item $active'>
                        <a class='page-link' href='$base_url?page=$p'>$p</a>
                    </li>";
                }
                ?>
            </ul>
        </nav>
    </div>
<?php endif; ?>

</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />

<?php require_once 'include/footer.php'; ?>
