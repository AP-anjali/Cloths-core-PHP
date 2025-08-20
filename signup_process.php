<?php
session_start();
require_once 'include/config.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $pincode  = trim($_POST['pincode'] ?? '');
    $token    = bin2hex(random_bytes(32));

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($address) || empty($city) || empty($pincode)) {
        $_SESSION['error'] = "❌ All fields are required.";
        header("Location: signup.php");
        exit;
    }
    if (!preg_match("/^[a-zA-Z ]+$/", $name) || !preg_match("/^[a-zA-Z ]+$/", $city)) {
        $_SESSION['error'] = "❌ Name and City must contain only letters and spaces.";
        header("Location: signup.php");
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "❌ Invalid email format.";
        header("Location: signup.php");
        exit;
    }
    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $_SESSION['error'] = "❌ Password must be at least 8 characters, with uppercase, lowercase, and a number.";
        header("Location: signup.php");
        exit;
    }
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $_SESSION['error'] = "❌ Invalid phone number.";
        header("Location: signup.php");
        exit;
    }
    if (!preg_match("/^[0-9]{6}$/", $pincode)) {
        $_SESSION['error'] = "❌ Invalid pincode.";
        header("Location: signup.php");
        exit;
    }

    // Check duplicates
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "❌ Email already registered.";
        header("Location: signup.php");
        exit;
    }
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "❌ Phone already registered.";
        header("Location: signup.php");
        exit;
    }

    // Save user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, city, pincode, verification_token) 
                            VALUES (:name, :email, :password, :phone, :address, :city, :pincode, :token)");
    $success = $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':phone' => $phone,
        ':address' => $address,
        ':city' => $city,
        ':pincode' => $pincode,
        ':token' => $token
    ]);

    if ($success) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'rahul38865@gmail.com';
            $mail->Password   = 'jqeivufpxgdnbakr'; // App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('rahul38865@gmail.com', 'Clothing Store');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = "Verify Your Email - Clothing Store";
            $verifyLink = "http://" . $_SERVER['HTTP_HOST'] . "/Cloths/verify.php?token=$token";
            $mail->Body = "
                Hi <strong>" . htmlspecialchars($name) . "</strong>,<br><br>
                Please verify your email by clicking the link below:<br><br>
                <a href='$verifyLink'>Click to Verify</a><br><br>
                Thanks for joining us!<br>~ Team Clothing Store
            ";

            $mail->send();
            $_SESSION['registered'] = true;
            header("Location: signup_success.php");
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = "⚠️ Registered, but email could not be sent. {$mail->ErrorInfo}";
            header("Location: signup.php");
            exit;
        }

    } else {
        $_SESSION['error'] = "❌ Registration failed. Please try again.";
        header("Location: signup.php");
        exit;
    }
}
?>
