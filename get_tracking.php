<?php
require_once 'include/config.php';

if (!isset($_GET['order_id'])) {
    echo json_encode([]);
    exit;
}

$order_id = $_GET['order_id'];

$stmt = $conn->prepare("SELECT status, location, message, updated_at FROM order_tracking WHERE order_id = ? ORDER BY updated_at DESC");
$stmt->execute([$order_id]);

$trackingData = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($trackingData);
?>
