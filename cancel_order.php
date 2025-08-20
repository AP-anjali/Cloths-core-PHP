<?php
require_once 'include/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_POST['order_id'] ?? 0);

// Check if order exists and belongs to user
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit;
}

$allowed_status = ['Pending', 'Confirmed'];

if (!in_array($order['order_status'], $allowed_status)) {
    echo json_encode(['status' => 'error', 'message' => 'Only Pending or Confirmed orders can be cancelled']);
    exit;
}

if ($order['cancel_request'] !== 'None') {
    echo json_encode(['status' => 'error', 'message' => 'Cancel already requested']);
    exit;
}

// Option 1: Just mark as requested (safe way)
$update = $conn->prepare("UPDATE orders SET cancel_request = 'Requested' WHERE id = ?");
$update->execute([$order_id]);

// Option 2: If you also want to DELETE confirmed orders (risky), uncomment this:
// if ($order['order_status'] === 'Confirmed') {
//     $delete = $conn->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
//     $delete->execute([$order_id, $user_id]);
//     echo json_encode(['status' => 'deleted']);
//     exit;
// }

echo json_encode(['status' => 'success']);
