 <?php
require_once 'include/config.php';
include('include/header.php');

$verified = false;
$error = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->execute([$token]);

    if ($stmt->rowCount() === 1) {
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $update->execute([$token]);
        $verified = true;
    } else {
        $error = "❌ Invalid or already used token.";
    }
} else {
    $error = "❌ No token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding-top: 50px;
            background-color: #f6f8fa;
        }
        .message-box {
            background: white;
            padding: 30px;
            display: inline-block;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .loader {
            margin-top: 20px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .redirecting {
            margin-top: 20px;
            color: #555;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="message-box">
        <?php if ($verified): ?>
            <h2>🎉 Email verified successfully!</h2>
           <p class="redirecting">Redirecting to login page in 3 seconds...</p>
            <div class="loader"></div>
            <script>
                setTimeout(function() {
                    window.location.href = 'signin.php';
                }, 3000);
            </script>
        <?php else: ?>
            <h2><?= $error ?></h2>
        <?php endif; ?>
    </div>
</body>
</html>