<?php
include('include/header.php');
require_once 'include/config.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $message = "<div class='msg error'>❌ Passwords do not match.</div>";
    } elseif (strlen($new_password) < 8 || 
             !preg_match("/[A-Z]/", $new_password) || 
             !preg_match("/[a-z]/", $new_password) || 
             !preg_match("/[0-9]/", $new_password)) {
        $message = "<div class='msg error'>❌ Weak password. Use uppercase, lowercase, and number.</div>";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expire > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = "<div class='msg error'>❌ Invalid or expired token.</div>";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE id = ?");
            $stmt->execute([$hashed, $user['id']]);
            $message = "<div class='msg success'>✅ Password updated! <a href='signin.php'>Click here to Login</a></div>";
        }
    }
} else {
    // On first visit, validate token
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expire > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = "<div class='msg error'>❌ Invalid or expired reset link.</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        
        form {
            background: white;
            padding: 30px 35px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.6s ease;
            margin: 80px auto;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 14px;
            border-radius: 8px;
            border: 1px solid #ccc;
            transition: border-color 0.3s;
        }

        input[type="password"]:focus {
            border-color: #2a9fd6;
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #2a9fd6;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #1b7fb8;
        }

        .msg {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            font-size: 15px;
            animation: fadeIn 0.4s ease;
            text-align: center;
        }

        .msg.success {
            background-color: #e0f8e0;
            color: #2b7a2b;
        }

        .msg.error {
            background-color: #fde0e0;
            color: #cc0000;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<form method="POST">
  <h2>Reset Your Password</h2>
  <?= $message ?>
  <?php if (!$message || strpos($message, '✅') === false): ?>
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <input type="password" name="new_password" placeholder="New Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <button type="submit">Update Password</button>
  <?php endif; ?>
</form>

</body>
</html>
