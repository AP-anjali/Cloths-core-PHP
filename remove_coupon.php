<?php
session_start();
unset($_SESSION['coupon']);
header("Location: cart.php");
exit();
