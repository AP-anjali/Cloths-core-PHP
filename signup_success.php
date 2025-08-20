<?php

include('include/header.php');

if (!isset($_SESSION['registered'])) {
    header("Location: signup.php");
    exit;
}
unset($_SESSION['registered']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registration Success</title>
</head>
<body style="text-align:center;padding:40px;">
    <h2>✅ Registered successfully!</h2>
    <p>Please check your email to verify your account.</p>
    <p>Redirecting to <b>signin</b> page shortly...</p>
    <div style="margin:30px auto;width:100px;height:100px;">
        <img src="assets/loading.gif" width="100" alt="Loading..." />
    </div>

    <script>
        setTimeout(function() {
            window.location.href = 'signin.php';
        }, 25000); // 20 seconds
    </script>
</body>
</html>
