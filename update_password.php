<?php
session_start();
require_once 'include/config.php';

$token = $_POST['token'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($new_password !== $confirm_password) {
    echo "<div class='msg error'>❌ Passwords do not match.</div>";
    exit;
}
if (strlen($new_password) < 8 || !preg_match("/[A-Z]/", $new_password) || !preg_match("/[a-z]/", $new_password) || !preg_match("/[0-9]/", $new_password)) {
    echo "<div class='msg error'>❌ Weak password. Use uppercase, lowercase, and number.</div>";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expire > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='msg error'>❌ Invalid or expired token.</div>";
    exit;
}

$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE id = ?");
$stmt->execute([$hashed, $user['id']]);

echo "<div class='msg success'>✅ Password updated! <a href='signin.php'>Login</a></div>";
?>
