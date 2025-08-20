<?php
require 'include/header.php';
require 'include/config.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$redirect = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='msg error'>❌ Enter a valid email.</div>";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expire = NOW() + INTERVAL 30 MINUTE WHERE email = ?");
            $stmt->execute([$token, $email]);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'rahul38865@gmail.com';
                $mail->Password   = 'jqeivufpxgdnbakr'; // App password
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('rahul38865@gmail.com', 'Clothing Store');
                $mail->addAddress($email, $user['name']);
                $mail->isHTML(true);
                $mail->Subject = "Reset Password - Clothing Store";
                $link = "http://" . $_SERVER['HTTP_HOST'] . "/Cloths/reset_password.php?token=$token";
                $mail->Body = "
                    Hi <strong>" . htmlspecialchars($user['name']) . "</strong>,<br><br>
                    Click below to reset your password:<br><br>
                    <a href='$link'>Reset Password</a><br><br>
                    Link valid for 30 minutes.<br>~ Team Clothing Store
                ";
                $mail->send();
                $message = "<div class='msg success'>✅ Reset link sent! Redirecting to login page...</div>";
                $redirect = true;
            } catch (Exception $e) {
                $message = "<div class='msg error'>❌ Failed to send email. {$mail->ErrorInfo}</div>";
            }
        } else {
            $message = "<div class='msg error'>❌ Email not registered.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            animation: slideIn 0.5s ease;
            width: 350px;
            text-align: center;
            margin: 80px auto;
        }
        @keyframes slideIn {
            from { transform: translateY(-40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        input[type="email"] {
            padding: 10px;
            width: 100%;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            transition: border-color 0.3s ease;
        }
        input[type="email"]:focus {
            border-color: #2a9fd6;
            outline: none;
        }
        button {
            background: #2a9fd6;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #2188c9;
        }
        .msg {
            margin: 10px 0;
            padding: 10px;
            border-radius: 8px;
            animation: fadeIn 0.3s ease;
        }
        .success {
            background: #e0f8e9;
            color: #218838;
        }
        .error {
            background: #fdecea;
            color: #e55353;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>

<form method="POST">
  <h2>Forgot Password</h2>
  <?= $message ?>
  <input type="email" name="email" placeholder="Enter your email" required>
  <button type="submit">Send Reset Link</button>
</form>

<?php if ($redirect): ?>
<script>
    setTimeout(function() {
        window.location.href = "signin.php";
    }, 4000); // 4 seconds delay
</script>
<?php endif; ?>

</body>
</html>
