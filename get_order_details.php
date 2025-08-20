<?php
require_once 'include/config.php';

if (!isset($_GET['order_id'])) {
    echo json_encode([]);
    exit;
}

$order_id = intval($_GET['order_id']);

$sql = "
SELECT 
    p.name AS product_name,
    p.main_image AS image,
    pv.size,
    pv.color,
    oi.quantity,
    oi.price
FROM order_items oi
JOIN product_variants pv ON oi.product_variant_id = pv.id
JOIN products p ON pv.product_id = p.id
WHERE oi.order_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$order_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert image path to full path (optional, depends on your project structure)
foreach ($data as &$item) {
    if (!empty($item['image']) && !str_starts_with($item['image'], 'http')) {
        $item['image'] = $item['image']; // Update if needed
    }
}

header('Content-Type: application/json');
echo json_encode($data);
