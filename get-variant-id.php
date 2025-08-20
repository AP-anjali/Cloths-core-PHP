<?php
require_once 'include/config.php';

$product_id = $_POST['product_id'] ?? 0;
$size = $_POST['size'] ?? '';
$color = $_POST['color'] ?? '';

$stmt = $conn->prepare("SELECT id FROM product_variants WHERE product_id = ? AND size = ? AND color = ?");
$stmt->execute([$product_id, $size, $color]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo $row ? $row['id'] : '0';
?>
