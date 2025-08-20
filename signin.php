<?php include('include/header.php'); ?>
<style>
/* Main layout */
/* Main layout with animation and attractive cloth shop style */
.signup-area2 {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f6f6f6 url('https://images.unsplash.com/photo-1521337582788-0a2f135c169c?auto=format&fit=crop&w=1350&q=80') no-repeat center center/cover;
  position: relative;
  padding: 20px;
  animation: fadeInBg 1.2s ease;
}

@keyframes fadeInBg {
  from { opacity: 0; }
  to { opacity: 1; }
}

.signup-area2::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(255, 255, 255, 0.92);
  z-index: 1;
  animation: fadeInOverlay 1.2s ease;
  margin-top: -20px;
}

@keyframes fadeInOverlay {
  from { opacity: 0; }
  to { opacity: 0.92; }
}

.container2 {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 420px;
  background: linear-gradient(135deg, #fff 80%, #ffe6f0 100%);
  border-radius: 18px;
  box-shadow: 0 6px 36px rgba(33, 33, 33, 0.12);
  padding: 48px 28px 32px 28px;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  box-sizing: border-box;
  animation: slideUp 1s cubic-bezier(.68,-0.55,.27,1.55);
}

@keyframes slideUp {
  from { transform: translateY(60px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.signup-form h2 {
  font-family: 'Poppins', sans-serif;
  text-align: center;
  font-size: 2rem;
  margin-bottom: 20px;
  color: #ef476f;
  font-weight: 700;
  letter-spacing: 1px;
  text-shadow: 0 2px 8px #ffe6f0;
  animation: fadeInTitle 1.2s;
}

@keyframes fadeInTitle {
  from { opacity: 0; transform: scale(0.95);}
  to { opacity: 1; transform: scale(1);}
}

/* Error/Success message */
.msg.error {
  color: #d63031;
  background: #ffe6e6;
  padding: 10px;
  border-radius: 6px;
  font-size: 14px;
  text-align: center;
  margin-bottom: 18px;
  box-shadow: 0 2px 8px rgba(214,48,49,0.08);
  animation: shake 0.5s;
}

.msg.success {
  color: #2e7d32;
  background: #e6ffe6;
  padding: 10px;
  border-radius: 6px;
  font-size: 14px;
  text-align: center;
  margin-bottom: 18px;
  box-shadow: 0 2px 8px rgba(46,125,50,0.08);
  animation: popIn 0.5s;
}

@keyframes shake {
  0% { transform: translateX(0);}
  20% { transform: translateX(-5px);}
  40% { transform: translateX(5px);}
  60% { transform: translateX(-5px);}
  80% { transform: translateX(5px);}
  100% { transform: translateX(0);}
}

@keyframes popIn {
  0% { transform: scale(0.9);}
  80% { transform: scale(1.05);}
  100% { transform: scale(1);}
}

/* Form styles */
.form-group {
  margin-bottom: 24px;
  position: relative;
}

label {
  display: block;
  margin-bottom: 7px;
  font-size: 15px;
  color: #222;
  font-weight: 500;
  letter-spacing: 0.5px;
}

input[type="email"],
input[type="password"] {
  width: 100%;
  padding: 13px 15px;
  font-size: 16px;
  border: 1.5px solid #e0e0e0;
  border-radius: 9px;
  background: #fafafa;
  outline: none;
  transition: border 0.2s, box-shadow 0.2s;
  margin-top: 3px;
  box-sizing: border-box;
  box-shadow: 0 1px 6px rgba(239,71,111,0.07);
}

input[type="email"]:focus,
input[type="password"]:focus {
  border-color: #ef476f;
  background: #fff;
  box-shadow: 0 2px 12px rgba(239,71,111,0.13);
  animation: inputPulse 0.5s;
}

@keyframes inputPulse {
  0% { box-shadow: 0 0 0 0 rgba(239,71,111,0.15);}
  70% { box-shadow: 0 0 0 8px rgba(239,71,111,0.08);}
  100% { box-shadow: 0 0 0 0 rgba(239,71,111,0.15);}
}

/* Button */
.btn {
  margin-top: 20px;
  display: block;
  width: 100%;
  background: linear-gradient(90deg, #ef476f 70%, #ffb6b9 100%);
  color: #fff;
  border: none;
  font-size: 1.1rem;
  font-weight: 600;
  padding: 15px 0;
  border-radius: 10px;
  cursor: pointer;
  letter-spacing: 1px;
  transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
  box-shadow: 0 2px 18px rgba(239, 71, 111, 0.10);
  animation: fadeInBtn 1.3s;
}

@keyframes fadeInBtn {
  from { opacity: 0; transform: scale(0.95);}
  to { opacity: 1; transform: scale(1);}
}

.btn:hover {
  background: linear-gradient(90deg, #d7375a 70%, #ffb6b9 100%);
  transform: translateY(-2px) scale(1.03);
  box-shadow: 0 4px 24px rgba(239,71,111,0.18);
}

/* Error messages below input */
.error-message {
  color: #d63031;
  font-size: 13px;
  margin-top: 4px;
  min-height: 17px;
  animation: shake 0.5s;
}

/* Extra links */
.extra-links {
  margin-top: 25px;
  text-align: center;
}
.extra-links a {
  color: #ef476f;
  text-decoration: none;
  font-size: 14px;
  margin: 0 7px;
  transition: color 0.2s, text-shadow 0.2s;
  text-shadow: 0 1px 6px #ffe6f0;
  font-weight: 500;
}
.extra-links a:hover {
  text-decoration: underline;
  color: #d7375a;
  text-shadow: 0 2px 12px #ffb6b9;
}

/* Decorative cloth shop accent */
.container2::after {
  content: '';
  display: block;
  position: absolute;
  top: -32px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 80px;
  background: url('https://cdn-icons-png.flaticon.com/512/892/892458.png') no-repeat center center/contain;
  opacity: 0.18;
  pointer-events: none;
  z-index: 0;
}

/* Responsive styles */
@media (max-width: 768px) {
  .container2 {
    padding: 36px 20px 28px 20px;
    max-width: 90vw;
    margin: 16px 0;
  }

  .signup-form h2 {
    font-size: 1.5rem;
    margin-bottom: 20px;
  }

  .btn {
    padding: 13px 0;
    font-size: 1rem;
  }
}

@media (max-width: 480px) {
  .signup-form h2 {
    font-size: 1.3rem;
  }

  input[type="email"],
  input[type="password"] {
    font-size: 15px;
    padding: 12px 14px;
  }

  .btn {
    font-size: 0.95rem;
    padding: 12px 0;
  }

  .extra-links a {
    font-size: 13px;
  }
}
</style>

<div class="signup-area2">
  <div class="container2">
    <div class="signup-form">
      <h2>Sign In</h2>

      <?php // Ensure session is started
      $message = '';
      if (isset($_SESSION['error'])) {
          $message = "<div class='msg error'>" . $_SESSION['error'] . "</div>";
          unset($_SESSION['error']);
      } elseif (isset($_SESSION['success'])) {
          $message = "<div class='msg success'>" . $_SESSION['success'] . "</div>";
          unset($_SESSION['success']);
      }
      echo $message;
      ?>

      <form id="signinForm" method="POST" action="login_process.php" novalidate>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required>
          <div class="error-message" id="emailError"></div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
          <div class="error-message" id="passwordError"></div>
        </div>

        <button type="submit" class="btn">Sign In</button>
      </form>

      <div class="extra-links">
        <a href="forgot_password.php">Forgot Password?</a> |
        <a href="signup.php">Register Now</a>
      </div>

    </div>
  </div>
</div>

<script>
document.getElementById("signinForm").addEventListener("submit", function (e) {
  let isValid = true;

  document.querySelectorAll(".error-message").forEach(el => el.textContent = "");

  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    document.getElementById("emailError").textContent = "Please enter a valid email address.";
    isValid = false;
  }

  if (password.length < 1) {
    document.getElementById("passwordError").textContent = "Password is required.";
    isValid = false;
  }

  if (!isValid) {
    e.preventDefault();
  }
});
</script>

<?php include('include/footer.php'); ?>
