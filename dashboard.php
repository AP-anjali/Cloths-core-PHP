<?php
require_once 'include/config.php';
require_once 'include/header.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name']);
    $new_phone = trim($_POST['phone']);
    $new_city = trim($_POST['city']);

    if (!empty($new_name) && !empty($new_phone) && !empty($new_city)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, city = ? WHERE id = ?");
        $stmt->execute([$new_name, $new_phone, $new_city, $user_id]);

        // Update local $user variable to reflect changes
        $user['name'] = $new_name;
        $user['phone'] = $new_phone;
        $user['city'] = $new_city;

        $success_message = "Profile updated successfully.";
    } else {
        $error_message = "Please fill out all fields.";
    }
}

// Fetch user data
$stmt = $conn->prepare("SELECT name, email, phone, city FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch order stats
$total_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id")->fetchColumn();
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND order_status = 'Pending'")->fetchColumn();
$delivered_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND order_status = 'Delivered'")->fetchColumn();

// Fetch recent orders
$recent_orders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5");
$recent_orders->execute([$user_id]);
$orders = $recent_orders->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>User Dashboard</title>
  <style>
   /* Responsive Dashboard Styling */
.container2 {
  width: 90%;
  max-width: 1200px;
  margin: 40px auto;
  padding: 0 10px;
}

h1 {
  text-align: center;
  margin-bottom: 30px;
  color: #007bff;
  animation: fadeDown 1s ease;
}

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
  animation: fadeUp 0.8s ease forwards;
  transform: translateY(20px);
  opacity: 0;
}

.card:hover {
  transform: scale(1.02);
}

.card h2 {
  font-size: 24px;
  color: #007bff;
}

.card p {
  font-size: 16px;
  color: #555;
}

.section {
  background: #fff;
  padding: 25px;
  border-radius: 16px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.07);
  margin-bottom: 30px;
  animation: fadeUp 1s ease forwards;
}

.section h3 {
  margin-bottom: 15px;
  color: #333;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  overflow-x: auto;
}

th, td {
  padding: 12px 16px;
  border-bottom: 1px solid #ddd;
  text-align: left;
  font-size: 14px;
}

th {
  background: #007bff;
  color: white;
}

.track-btn {
  padding: 6px 12px;
  background: #28a745;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
}

.track-btn:hover {
  background: #218838;
}

.logout {
  display: block;
  text-align: right;
  margin: 10px;
}

.logout a {
  background: #dc3545;
  color: white;
  padding: 8px 16px;
  border-radius: 6px;
  text-decoration: none;
}

.logout a:hover {
  background: #c82333;
}

#trackModal,
#orderDetailsModal {
  display: none;
  position: fixed;
  top: 50px;
  left: 50%;
  transform: translateX(-50%);
  width: 90%;
  max-width: 500px;
  background: #fff;
  border: 1px solid #ccc;
  padding: 20px;
  z-index: 1000;
  box-shadow: 0 0 10px #000;
  max-height: 80vh;
  overflow-y: auto;
  border-radius: 10px;
}

/* Animations */
@keyframes fadeUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive Design for Mobile */
@media (max-width: 768px) {
  .cards {
    grid-template-columns: 1fr;
  }

  .card h2 {
    font-size: 20px;
  }

  .card p {
    font-size: 14px;
  }

  th, td {
    padding: 10px;
    font-size: 13px;
  }

  table {
    display: block;
    overflow-x: auto;
  }

  .track-btn {
    font-size: 13px;
    padding: 5px 10px;
  }

  .logout {
    text-align: center;
    margin-bottom: 20px;
  }
}

  </style>
</head>
<body>

<div class="container2">
  <h1>Welcome, <?= htmlspecialchars($user['name']) ?> 👋</h1>

  <div class="cards">
    <div class="card">
      <h2><?= $total_orders ?></h2>
      <p>Total Orders</p>
    </div>
    <div class="card">
      <h2><?= $pending_orders ?></h2>
      <p>Pending Orders</p>
    </div>
    <div class="card">
      <h2><?= $delivered_orders ?></h2>
      <p>Delivered Orders</p>
    </div>
  </div>

<div class="section">
  <h3>Your Profile</h3>

  <?php if (isset($success_message)): ?>
    <p style="color: green; font-weight: bold;"><?= $success_message ?></p>
  <?php elseif (isset($error_message)): ?>
    <p style="color: red; font-weight: bold;"><?= $error_message ?></p>
  <?php endif; ?>

  <form method="POST" style="margin-top: 15px; max-width: 500px;">
    <label for="name"><strong>Name:</strong></label><br>
    <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name']) ?>" required
           style="padding: 8px; width: 100%; border: 1px solid #ccc; border-radius: 6px;"><br><br>

    <label for="phone"><strong>Phone:</strong></label><br>
    <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($user['phone']) ?>" required
           style="padding: 8px; width: 100%; border: 1px solid #ccc; border-radius: 6px;"><br><br>

    <label for="city"><strong>Address:</strong></label><br>
    <input type="text" name="city" id="city" value="<?= htmlspecialchars($user['city']) ?>" required
           style="padding: 8px; width: 100%; border: 1px solid #ccc; border-radius: 6px;"><br><br>

    <button type="submit" name="update_profile"
            style="padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer;">
      Update Profile
    </button>
  </form>
</div>


  <div class="section">
    <h3>Recent Orders</h3>
    <?php if (count($orders) > 0): ?>
      <table>
        <tr>
          <th>Order ID</th>
          <th>Amount</th>
          <th>Status</th>
         
          <th>Date</th>
          <th>Cancel Order</th>
        </tr>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td><a href="javascript:void(0);" onclick="showOrderDetails(<?= $order['id'] ?>)">#<?= $order['id'] ?></a></td>

            <td>₹<?= $order['total_amount'] ?></td>
            <td><?= $order['order_status'] ?></td>
           
            <td><?= date("d M Y", strtotime($order['created_at'])) ?></td>

  <td>
  <?php if (in_array($order['order_status'], ['Pending', 'Confirmed']) && ($order['cancel_request'] === 'None' || is_null($order['cancel_request']))): ?>
    <button class="track-btn" style="background:#dc3545;" onclick="cancelOrder(<?= $order['id'] ?>)">Cancel</button>
  <?php elseif ($order['cancel_request'] === 'Requested'): ?>
    <p style="color:orange; font-weight:bold;">Cancel Requested</p>
  <?php endif; ?>
</td>

          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>You have no orders yet.</p>
    <?php endif; ?>

    <!-- Modal for tracking -->
    <div id="trackModal">
      <div id="trackingContent"></div>
      <button onclick="closeModal()" style="margin-top:10px; background:#f44336; color:#fff; border:none; padding:8px 12px; cursor:pointer;">
        Close
      </button>
    </div>
<!-- Modal for Order Product Details -->
<div id="orderDetailsModal" style="display:none; position:fixed; top:50px; left:50%; transform:translateX(-50%); width:500px; background:white; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.2); z-index:1000;">
  <div id="orderDetailsContent"></div>
  <button onclick="document.getElementById('orderDetailsModal').style.display='none'" style="margin-top:10px; background:#f44336; color:#fff; border:none; padding:8px 12px; cursor:pointer;">Close</button>
</div>

  </div>
</div>

<script>
function trackOrder(orderId) {
    fetch('get_tracking.php?order_id=' + orderId)
    .then(res => res.json())
    .then(data => {
        let html = '';
        if (data.length > 0) {
            html = '<ul style="list-style:none; padding:0;">';
            data.forEach(item => {
                html += `<li style="margin-bottom:10px;">
                    <strong>Status:</strong> ${item.status} <br>
                    <strong>Location:</strong> ${item.location} <br>
                    <strong>Message:</strong> ${item.message} <br>
                    <small><strong>Time:</strong> ${item.updated_at}</small>
                </li><hr>`;
            });
            html += '</ul>';
        } else {
            html = '<p>No tracking information found.</p>';
        }

        document.getElementById("trackingContent").innerHTML = html;
        document.getElementById("trackModal").style.display = 'block';
    })
    .catch(() => {
        document.getElementById("trackingContent").innerHTML = '<p>Error fetching tracking info.</p>';
        document.getElementById("trackModal").style.display = 'block';
    });
}

function closeModal() {
    document.getElementById("trackModal").style.display = 'none';
}
</script>
<script>
  function cancelOrder(orderId) {
    console.log("Cancel button clicked with ID:", orderId); // Debug

    fetch('cancel_order.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: 'order_id=' + encodeURIComponent(orderId)
    })
    .then(response => response.json())
    .then(data => {
      console.log("Server response:", data); // Debug

      if (data.status === 'success') {
        alert('Cancellation request sent successfully.');
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Fetch error:', error);
      alert('Something went wrong.');
    });
  }
</script>


<script>
function showOrderDetails(orderId) {
  fetch('get_order_details.php?order_id=' + orderId)
    .then(res => res.json())
    .then(data => {
      let html = `<h3>Order #${orderId} - Product Details</h3>`;
      if (data.length > 0) {
        html += `<ul style="list-style:none; padding:0;">`;
        data.forEach(item => {
          html += `<li style="margin-bottom:15px; border-bottom:1px solid #ccc; padding-bottom:10px;">
            <img src="${item.image}" alt="${item.product_name}" style="width:60px; height:auto; vertical-align:middle; margin-right:10px;">
            <strong>${item.product_name}</strong><br>
            <small>Size: ${item.size}, Color: ${item.color}, Qty: ${item.quantity}, Price: ₹${item.price}</small>
          </li>`;
        });
        html += `</ul>`;
      } else {
        html += `<p>No product details found.</p>`;
      }

      document.getElementById("orderDetailsContent").innerHTML = html;
      document.getElementById("orderDetailsModal").style.display = "block";
    })
    .catch(err => {
      console.error(err);
      document.getElementById("orderDetailsContent").innerHTML = "<p>Error fetching order details.</p>";
      document.getElementById("orderDetailsModal").style.display = "block";
    });
}
</script>

</body>
</html>
