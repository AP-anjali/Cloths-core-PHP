<?php
include('include/header.php');
require_once 'include/config.php';
?>
<!-- contact.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us - Clothing Store</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
      
        .contact-form {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 600px;
            position: relative;
            margin: 80px auto;
            animation: slideDown 1s cubic-bezier(.68,-0.55,.27,1.55);
        }
        @keyframes slideDown {
            0% {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .form-group {
            position: relative;
            margin-bottom: 25px;
            animation: fadeInUp 0.8s ease both;
        }
        .form-group:nth-child(1) { animation-delay: 0.2s; }
        .form-group:nth-child(2) { animation-delay: 0.4s; }
        .form-group:nth-child(3) { animation-delay: 0.6s; }
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .form-control {
            width: 100%;
            padding: 10px 10px 10px 5px;
            font-size: 16px;
            border: none;
            border-bottom: 2px solid #ccc;
            background: transparent;
            outline: none;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-bottom: 2px solid #4facfe;
            box-shadow: 0 2px 8px rgba(79, 172, 254, 0.15);
            animation: inputPulse 0.4s;
        }
        @keyframes inputPulse {
            0% { box-shadow: 0 0 0 0 rgba(79, 172, 254, 0.3); }
            70% { box-shadow: 0 0 0 10px rgba(79, 172, 254, 0); }
            100% { box-shadow: 0 2px 8px rgba(79, 172, 254, 0.15); }
        }
        label {
            position: absolute;
            top: 10px;
            left: 5px;
            font-size: 16px;
            color: #999;
            transition: 0.3s ease;
            pointer-events: none;
        }
        .form-control:focus ~ label,
        .form-control:valid ~ label {
            top: -15px;
            font-size: 12px;
            color: #4facfe;
            letter-spacing: 1px;
        }
        button {
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            box-shadow: 0 4px 14px rgba(79, 172, 254, 0.15);
            transition: background 0.3s, transform 0.2s;
            animation: bounceIn 1s 0.8s both;
        }
        button:hover {
            background: linear-gradient(90deg, #00f2fe 0%, #4facfe 100%);
            transform: scale(1.05);
        }
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            60% {
                opacity: 1;
                transform: scale(1.05);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
    
        .contact-form::before {
            content: "";
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 40px;
            background: url('https://img.icons8.com/color/96/000000/t-shirt.png') no-repeat center/contain;
            animation: swing 2s infinite ease-in-out;
        }
        @keyframes swing {
            0%, 100% { transform: translateX(-50%) rotate(-10deg); }
            50% { transform: translateX(-50%) rotate(10deg); }
        }
    </style>
</head>
<body>
    <form class="contact-form" action="send_mail.php" method="POST">
        <center><h2>Contact Us</h2></center>
        <div class="form-group">
            <input type="text" name="name" class="form-control" required>
            <label>Your Name</label>
        </div>
        <div class="form-group">
            <input type="email" name="email" class="form-control" required>
            <label>Your Email</label>
        </div>
        <div class="form-group">
            <textarea name="message" class="form-control" rows="4" required></textarea>
            <label>Your Message</label>
        </div>
        <button type="submit" name="send">Send Message</button>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (isset($_GET['success'])): ?>
        <script>
            Swal.fire("Sent!", "Your message was sent successfully!", "success");
        </script>
    <?php elseif (isset($_GET['error'])): ?>
        <script>
            Swal.fire("Oops!", "Failed to send your message. Try again.", "error");
        </script>
    <?php endif; ?>
</body>
</html>
