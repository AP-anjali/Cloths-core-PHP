<?php
require_once 'include/config.php'; // Path to your PDO connection
require_once'include/header.php'; // Include header for session management

// --- PDO helper function ---
function fetchVal($sql, $conn) {
    $stmt = $conn->query($sql);
    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_NUM); // Use PDO::FETCH_NUM instead of fetch_row()
        return $row ? $row[0] : 0;
    }
    return 0;
}

// --- Dashboard Stats ---
$stat_users    = fetchVal("SELECT COUNT(*) FROM users", $conn);
$stat_sales    = fetchVal("SELECT IFNULL(SUM(total_amount),0) FROM orders WHERE payment_status='Success'", $conn);
$stat_orders   = fetchVal("SELECT COUNT(*) FROM orders", $conn);
$stat_stockout = fetchVal("SELECT COUNT(*) FROM products WHERE stock < 10", $conn);

// --- Recent Orders ---
$recent_orders = [];
$stmt = $conn->query("
    SELECT o.id, u.name AS customer, o.total_amount, o.order_status, o.created_at 
    FROM orders o 
    JOIN users u ON o.user_id=u.id 
    ORDER BY o.id DESC LIMIT 6
");
if ($stmt) {
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) $recent_orders[] = $row;
}

// --- Sales Chart Data (last 7 days) ---
$sales_days = [];
$sales_totals = [];
$stmt = $conn->query("
    SELECT DATE(created_at) AS d, SUM(total_amount) as total 
    FROM orders WHERE payment_status='Success' 
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY d ORDER BY d ASC
");
if ($stmt) {
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sales_days[] = $row['d'];
        $sales_totals[] = (float)$row['total'];
    }
}
?>
<div class="container fade-in">
    <!-- Animated Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card grow bg-info text-white shadow-sm">
                <div class="card-body text-center">
                    <div class="stat-counter" id="users-count">0</div>
                    <div>Users</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card grow bg-success text-white shadow-sm">
                <div class="card-body text-center">
                    <div class="stat-counter" id="sales-count">₹0</div>
                    <div>Total Sales</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card grow bg-primary text-white shadow-sm">
                <div class="card-body text-center">
                    <div class="stat-counter" id="orders-count">0</div>
                    <div>Orders</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card grow bg-danger text-white shadow-sm">
                <div class="card-body text-center">
                    <div class="stat-counter" id="stockout-count">0</div>
                    <div>Low Stock</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Sales Chart -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Sales (Last 7 Days)</div>
        <div class="card-body"><canvas id="salesChart" height="70"></canvas></div>
    </div>
    <!-- Recent Orders -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light fw-bold">Recent Orders</div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>Order ID</th><th>Customer</th><th>Amount (₹)</th>
                    <th>Status</th><th>Date</th>
                </tr></thead>
                <tbody>
                <?php foreach($recent_orders as $o): ?>
                <tr>
                    <td>#<?php echo $o['id']; ?></td>
                    <td><?php echo htmlspecialchars($o['customer']); ?></td>
                    <td><?php echo number_format($o['total_amount'],2); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $o['order_status']=='Confirmed'?'success':'secondary'; ?>">
                            <?php echo $o['order_status']; ?>
                        </span>
                    </td>
                    <td><?php echo date("d/M/Y H:i", strtotime($o['created_at'])); ?></td>
                </tr>
                <?php endforeach; if(!$recent_orders): ?>
                    <tr><td colspan="5" class="text-center text-muted">No orders yet</td></tr>
                <?php endif;?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- JS for Counter Animations and Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
<script>
function animateCounter(id, target, prefix = '', duration = 700) {
    let start = 0, step = target / (duration/18);
    function go() {
        start += step;
        if (start >= target) start = target;
        document.getElementById(id).innerText = prefix + (id=='sales-count' ? start.toFixed(2) : Math.round(start));
        if (start < target) setTimeout(go, 18);
    }
    go();
}
animateCounter('users-count', <?php echo $stat_users;?>);
animateCounter('sales-count', <?php echo $stat_sales;?>, '₹');
animateCounter('orders-count', <?php echo $stat_orders;?>);
animateCounter('stockout-count', <?php echo $stat_stockout;?>);

var ctx = document.getElementById('salesChart').getContext('2d');
var salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($sales_days); ?>,
        datasets: [{
            label: 'Sales (₹)',
            data: <?php echo json_encode($sales_totals); ?>,
            fill: true,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,.07)',
            tension: .4
        }]
    },
    options: {
        animation: { duration: 1200 },
        scales: { y: { beginAtZero: true, grid:{color:'#eee'}}, x:{ grid:{color:'#f4f4f4'}} },
        plugins: { legend: {display:false} }
    }
});
</script>
</body>
</html>
<?php
include 'include/footer.php'; // Include footer
?>