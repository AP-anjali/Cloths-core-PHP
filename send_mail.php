<?php
require_once 'include/config.php'; // Assumes $conn is your mysqli connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

if (isset($_POST['send'])) {
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $message = $_POST['message'];

    // Store in database
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $message);
    
    if ($stmt->execute()) {
        // Proceed with sending email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'rahul38865@gmail.com';
            $mail->Password   = 'jqeivufpxgdnbakr'; // App password only
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('rahul38865@gmail.com', 'Clothing Store Contact');
            $mail->addAddress('rahul38865@gmail.com'); // Inbox
            $mail->isHTML(true);
            $mail->Subject = "New Contact Form Message";
            $mail->Body    = "
                <strong>Name:</strong> " . htmlspecialchars($name) . "<br>
                <strong>Email:</strong> " . htmlspecialchars($email) . "<br>
                <strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "
            ";

            $mail->send();
            header("Location: contact.php?success=1");
            exit;
        } catch (Exception $e) {
            header("Location: contact.php?error=1");
            exit;
        }
    } else {
        header("Location: contact.php?error=2"); // DB error
        exit;
    }
} else {
    header("Location: contact.php");
    exit;
}
