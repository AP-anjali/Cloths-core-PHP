<?php
session_start();
require_once 'include/config.php';

$email = trim($_POST['email']);
$password = trim($_POST['password']);

if ($email == "" || $password == "") {
    $_SESSION['error'] = "All fields are required!";
    header("Location: signin.php");
    exit;
}

try {
    // ✅ 1. Check in admin table
    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $admin['password'])) {
    // ✅ Admin Login
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_logged_in'] = true; // ✅ Add this line

    header("Location: admin/dashboard.php");
    exit;
        } else {
            $_SESSION['error'] = "Invalid admin password.";
            header("Location: signin.php");
            exit;
        }
    }

    // ✅ 2. Check in users table
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['is_verified'] != 1) {
            $_SESSION['error'] = "Please verify your email before logging in.";
            header("Location: signin.php");
            exit;
        }

        if (password_verify($password, $user['password'])) {
            // ✅ User Login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];

            // Optional: Remember Me
            if (!empty($_POST['remember'])) {
                setcookie("user_email", $user['email'], time() + (86400 * 7), "/");
                setcookie("user_id", $user['id'], time() + (86400 * 7), "/");
            }

            header("Location: index.php");
            exit;
        } else {
            $_SESSION['error'] = "Invalid password.";
            header("Location: signin.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Account not found.";
        header("Location: signin.php");
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: signin.php");
    exit;
}
?>
