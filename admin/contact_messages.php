<?php

require_once 'include/config.php'; // Your PDO connection, $conn
require_once 'include/header.php'; // Include header for session management
// --- Basic Auth check ---
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle logout if needed
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

// Initialize variables for alerts
$alert = '';
$alert_type = 'success';

// Handle Delete operation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    if ($stmt->execute([$id])) {
        $alert = "Message #$id deleted successfully.";
        $alert_type = 'success';
    } else {
        $alert = "Failed to delete message #$id.";
        $alert_type = 'danger';
    }
}

// Handle Add New Message (optional; admin-only for demo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['email'], $_POST['message'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $email, $message])) {
            $alert = "Message from '$name' added successfully.";
            $alert_type = 'success';
        } else {
            $alert = "Failed to add message.";
            $alert_type = 'danger';
        }
    } else {
        $alert = "Please provide valid name, email and message.";
        $alert_type = 'warning';
    }
}

// Fetch all messages (latest first)
$messages = [];
$stmt = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
if ($stmt !== false) {
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin - Manage Contact Messages</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
    body { background: #f8f9fa; }
    .fade-alert {
        transition: opacity 0.6s ease, transform 0.3s ease;
        opacity: 1;
    }
    .fade-alert.hide {
        opacity: 0;
        transform: translateY(-20px);
    }
    .table-scroll {
        max-height: 460px;
        overflow-y: auto;
    }
</style>
</head>
<body>

<div class="container">
    <h2 class="mb-4">Manage Contact Messages</h2>

    <!-- Alert -->
    <?php if ($alert): ?>
    <div id="alertBox" class="alert alert-<?php echo $alert_type; ?> fade-alert">
        <?php echo htmlspecialchars($alert); ?>
        <button type="button" class="btn-close float-end" aria-label="Close" onclick="closeAlert()"></button>
    </div>
    <?php endif; ?>

    <!-- Add New Message Form (optional for admin test; remove if not needed) -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Add New Message</div>
        <div class="card-body">
            <form method="post" class="row g-3" id="addMessageForm">
                <div class="col-md-4">
                    <input type="text" name="name" class="form-control" placeholder="Name" required />
                </div>
                <div class="col-md-4">
                    <input type="email" name="email" class="form-control" placeholder="Email" required />
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Add Message</button>
                </div>
                <div class="col-12">
                    <textarea name="message" rows="3" class="form-control" placeholder="Message" required></textarea>
                </div>
            </form>
        </div>
    </div>

    <!-- Messages Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-light fw-bold">All Messages (<?php echo count($messages); ?>)</div>
        <div class="card-body table-responsive table-scroll p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-secondary sticky-top">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>Received At</th>
                        <th class="text-center" style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($messages) == 0): ?>
                    <tr><td colspan="6" class="text-center text-muted">No messages found.</td></tr>
                <?php else: foreach ($messages as $msg): ?>
                    <tr>
                        <td><?php echo (int)$msg['id']; ?></td>
                        <td><?php echo htmlspecialchars($msg['name']); ?></td>
                        <td><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>"><?php echo htmlspecialchars($msg['email']); ?></a></td>
                        <td style="max-width:400px; white-space: pre-wrap;"><?php echo htmlspecialchars($msg['message']); ?></td>
                        <td><?php echo date("d/M/Y H:i", strtotime($msg['created_at'])); ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo (int)$msg['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Alert close with fade
function closeAlert() {
    const box = document.getElementById('alertBox');
    if (!box) return;
    box.classList.add('hide');
    setTimeout(() => box.style.display = 'none', 600);
}

// Confirm before delete
function confirmDelete(id) {
    if (confirm("Are you sure you want to delete message #" + id + "?")) {
        window.location.href = "?delete=" + id;
    }
}
</script>
</body>
</html>
