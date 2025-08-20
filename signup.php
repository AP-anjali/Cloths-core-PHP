<?php
include('include/header.php');

// Session message handling
$message = '';
if (isset($_SESSION['error'])) {
    $message = "<div class='msg error'>" . $_SESSION['error'] . "</div>";
    unset($_SESSION['error']);
} elseif (isset($_SESSION['success'])) {
    $message = "<div class='msg success'>" . $_SESSION['success'] . "</div>";
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Create Account</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
  <style>
    .msg.success { color: green; padding: 10px; text-align: center; }
    .msg.error { color: red; padding: 10px; text-align: center; }

    .signup-area2 {
      padding: 60px 15px;
      background: #f6f6f6;
      background-image: url('https://images.unsplash.com/photo-1521337582788-0a2f135c169c?auto=format&fit=crop&w=1350&q=80');
      background-size: cover;
      background-position: center;
      position: relative;
      min-height: 100vh;
      animation: bgFadeIn 1.5s ease;
    }
    @keyframes bgFadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .signup-area2::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(255, 255, 255, 0.92);
      z-index: 1;
      animation: fadeIn 1.2s;
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 0.92; }
    }
    .container2 {
      position: relative;
      z-index: 2;
      max-width: 500px;
      background: #fff;
      padding: 40px 35px;
      margin: 0 auto;
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(239, 71, 111, 0.18);
      border: 2px solid #ef476f;
      animation: slideUp 1s cubic-bezier(.68,-0.55,.27,1.55);
    }
    @keyframes slideUp {
      from { transform: translateY(60px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    .signup-form h2 {
      text-align: center;
      font-size: 32px;
      margin-bottom: 25px;
      color: #ef476f;
      font-family: 'Poppins', sans-serif;
      letter-spacing: 1px;
      font-weight: 700;
      animation: fadeInText 1.2s;
    }
    @keyframes fadeInText {
      from { opacity: 0; transform: scale(0.9);}
      to { opacity: 1; transform: scale(1);}
    }
    .form-group {
      margin-bottom: 20px;
      animation: fadeInForm 1.2s;
    }
    @keyframes fadeInForm {
      from { opacity: 0; transform: translateY(20px);}
      to { opacity: 1; transform: translateY(0);}
    }
    label {
      display: block;
      margin-bottom: 6px;
      font-size: 15px;
      color: #ef476f;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    input[type="text"],
    input[type="password"],
    input[type="email"],
    input[type="tel"] {
      width: 100%;
      padding: 12px 15px;
      font-size: 15px;
      border: 1.5px solid #ef476f;
      border-radius: 10px;
      background: #fafafa;
      transition: border-color 0.3s, box-shadow 0.3s;
      box-shadow: 0 2px 8px rgba(239, 71, 111, 0.08);
      font-family: 'Roboto', sans-serif;
    }
    input:focus {
      border-color: #06d6a0;
      background: #fff;
      box-shadow: 0 0 0 2px #06d6a033;
      outline: none;
    }
    .btn {
      width: 100%;
      background: linear-gradient(90deg, #ef476f 0%, #ffd166 100%);
      color: #fff;
      font-size: 18px;
      padding: 14px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 700;
      box-shadow: 0 4px 16px rgba(239, 71, 111, 0.13);
      transition: background 0.3s, transform 0.2s;
      margin-top: 10px;
      letter-spacing: 1px;
      animation: btnPop 1.2s;
    }
    @keyframes btnPop {
      from { transform: scale(0.95); opacity: 0;}
      to { transform: scale(1); opacity: 1;}
    }
    .btn:hover {
      background: linear-gradient(90deg, #06d6a0 0%, #ef476f 100%);
      transform: translateY(-2px) scale(1.03);
    }
    .error-message {
      color: #d63031;
      font-size: 13px;
      margin-top: 4px;
      font-weight: 500;
      letter-spacing: 0.2px;
      animation: shake 0.3s;
    }
    @keyframes shake {
      10%, 90% { transform: translateX(-2px); }
      20%, 80% { transform: translateX(4px); }
      30%, 50%, 70% { transform: translateX(-8px); }
      40%, 60% { transform: translateX(8px); }
    }
    .extra-links {
      text-align: center;
      margin-top: 18px;
      animation: fadeInText 1.2s;
    }
    .extra-links a {
      color: #06d6a0;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }
    .extra-links a:hover {
      color: #ef476f;
      text-decoration: underline;
    }
    @media (max-width: 600px) {
      .container2 {
      padding: 30px 12px;
      }
      .signup-form h2 {
      font-size: 24px;
      }
    }
  </style>
</head>
<body>
  <div class="signup-area2">
    <div class="container2">
      <div class="signup-form">
        <?= $message ?>
        <h2>Create an Account</h2>
        <form id="signupForm" action="signup_process.php" method="POST" novalidate>
          <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>
            <div class="error-message" id="nameError"></div>
          </div>
          <div class="form-group">
            <label for="mobileNo">Mobile No</label>
            <input type="tel" id="mobileNo" name="phone" required pattern="[0-9]{10}">
            <div class="error-message" id="mobileError"></div>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
            <div class="error-message" id="emailError"></div>
          </div>
          <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" required>
            <div class="error-message" id="addressError"></div>
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <div class="error-message" id="passwordError"></div>
          </div>
          <div class="form-group">
            <label for="confirmPassword">Confirm Password</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required>
            <div class="error-message" id="confirmPasswordError"></div>
          </div>
          <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city" required>
          </div>
          <div class="form-group">
            <label for="pincode">Pincode</label>
            <input type="text" id="pincode" name="pincode" pattern="[0-9]{6}" required>
          </div>
          <button type="submit" class="btn">Sign Up</button>
        </form>
        <br>
        <div class="extra-links">
        <a href="signin.php">Already have an account? Sign In</a>
      </div>
      </div>
    </div>
  </div>
  <script>
    document.getElementById("signupForm").addEventListener("submit", function(e) {
      let isValid = true;
      document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
      const name = document.getElementById("name").value.trim();
      const city = document.getElementById("city").value.trim();
      const mobile = document.getElementById("mobileNo").value.trim();
      const email = document.getElementById("email").value.trim();
      const address = document.getElementById("address").value.trim();
      const password = document.getElementById("password").value.trim();
      const confirmPassword = document.getElementById("confirmPassword").value.trim();

      if (name === "" || !/^[a-zA-Z ]+$/.test(name)) {
        document.getElementById("nameError").textContent = "Enter valid name (letters only).";
        isValid = false;
      }

      if (!/^\d{10}$/.test(mobile)) {
        document.getElementById("mobileError").textContent = "Enter valid 10-digit mobile.";
        isValid = false;
      }

      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        document.getElementById("emailError").textContent = "Enter a valid email.";
        isValid = false;
      }

      if (address === "") {
        document.getElementById("addressError").textContent = "Address is required.";
        isValid = false;
      }

      if (password.length < 8 || 
          !/[A-Z]/.test(password) || 
          !/[a-z]/.test(password) || 
          !/[0-9]/.test(password)) {
        document.getElementById("passwordError").textContent = "Password must be 8+ chars with uppercase, lowercase & digit.";
        isValid = false;
      }

      if (confirmPassword !== password) {
        document.getElementById("confirmPasswordError").textContent = "Passwords do not match.";
        isValid = false;
      }

      if (!isValid) e.preventDefault();
    });
  </script>
</body>
</html>

<?php include('include/footer.php'); ?>
