<?php
session_start();
require_once "db_connect.php";
require_once "includes/csrf.php";

$token = $_GET['token'] ?? '';
if (!$token) die('Invalid token');

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $pass = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if ($pass !== $confirm) {
    $msg = 'Passwords do not match';
  } else {
    require_once __DIR__ . '/includes/password_policy.php';
    $pwCheck = validate_password_strength($pass);

    if ($pwCheck !== true) {
      $msg = $pwCheck;
    } else {
      // Step 1: Validate token
      $stmt = $conn->prepare("SELECT id, password, reset_expires FROM users WHERE reset_token=? LIMIT 1");
      $stmt->bind_param("s", $token);
      $stmt->execute();
      $res = $stmt->get_result();

      if ($res->num_rows === 0) {
        $msg = 'Invalid token';
      } else {
        $row = $res->fetch_assoc();

        // Step 2: Check if token expired
        if (strtotime($row['reset_expires']) < time()) {
          $msg = 'Token expired';
        } else {
          // Step 3: Prevent using old password
          if (password_verify($pass, $row['password'])) {
            $msg = 'New password cannot be the same as your current password';
          } else {
            // Step 4: Update to new password
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $u = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
            $u->bind_param("si", $hash, $row['id']);
            $u->execute();

            header('Location: login.php?msg=password_updated');
            exit;
          }
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #dc3545;
      --primary-hover: #c82333;
      --primary-light: rgba(220, 53, 69, 0.1);
      --primary-lighter: rgba(220, 53, 69, 0.05);
    }
    
    body {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0;
      padding: 20px;
    }
    
    .reset-container {
      max-width: 450px;
      width: 100%;
    }
    
    .reset-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      padding: 40px;
      border: none;
      position: relative;
      overflow: hidden;
    }
    
    .reset-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    }
    
    .brand-logo {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .logo-icon {
      width: 60px;
      height: 60px;
      background: var(--primary-color);
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 15px;
      color: white;
      font-size: 24px;
      box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }
    
    .brand-text {
      font-size: 24px;
      font-weight: 700;
      color: var(--primary-color);
      margin: 0;
    }
    
    .page-title {
      color: #333;
      font-weight: 700;
      margin-bottom: 8px;
      text-align: center;
      font-size: 1.8rem;
    }
    
    .page-subtitle {
      color: #666;
      text-align: center;
      margin-bottom: 30px;
      font-size: 1rem;
    }
    
    .form-label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
    }
    
    .form-control {
      border: 2px solid #e9ecef;
      border-radius: 10px;
      padding: 12px 15px;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
    }
    
    .input-group {
      position: relative;
    }
    
    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #6c757d;
      cursor: pointer;
      z-index: 3;
    }
    
    .password-toggle:hover {
      color: var(--primary-color);
    }
    
    .btn-reset {
      background: var(--primary-color);
      border: none;
      color: white;
      padding: 14px 20px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s ease;
      width: 100%;
      margin-top: 10px;
    }
    
    .btn-reset:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }
    
    .btn-reset:active {
      transform: translateY(0);
    }
    
    .alert {
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 20px;
      border: none;
    }
    
    .alert-danger {
      background: rgba(220, 53, 69, 0.1);
      color: var(--primary-color);
      border-left: 4px solid var(--primary-color);
    }
    
    .password-strength {
      height: 4px;
      background: #e9ecef;
      border-radius: 2px;
      margin-top: 8px;
      overflow: hidden;
    }
    
    .password-strength-bar {
      height: 100%;
      width: 0%;
      transition: all 0.3s ease;
      border-radius: 2px;
    }
    
    .password-requirements {
      font-size: 0.875rem;
      color: #666;
      margin-top: 10px;
    }
    
    .requirement {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 4px;
    }
    
    .requirement.valid {
      color: #28a745;
    }
    
    .requirement.invalid {
      color: #666;
    }
    
    .requirement i {
      font-size: 0.5rem;
    }
    
    .login-link {
      text-align: center;
      margin-top: 25px;
      color: #666;
    }
    
    .login-link a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
    }
    
    .login-link a:hover {
      text-decoration: underline;
    }
    
    /* Responsive Design */
    @media (max-width: 576px) {
      body {
        padding: 15px;
      }
      
      .reset-card {
        padding: 30px 25px;
        border-radius: 15px;
      }
      
      .page-title {
        font-size: 1.5rem;
      }
      
      .page-subtitle {
        font-size: 0.95rem;
      }
      
      .brand-text {
        font-size: 20px;
      }
      
      .logo-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
      }
    }
    
    @media (max-width: 400px) {
      .reset-card {
        padding: 25px 20px;
      }
      
      .page-title {
        font-size: 1.3rem;
      }
    }
  </style>
</head>
<body>
  <div class="reset-container">
    <div class="reset-card">
      <!-- Brand Header -->
      <div class="brand-logo">
        <div class="logo-icon">
          <i class="fas fa-hotel"></i>
        </div>
        <h1 class="brand-text">CheckIn</h1>
      </div>
      
      <!-- Page Header -->
      <h2 class="page-title">Reset Your Password</h2>
      <p class="page-subtitle">Create a new secure password for your account</p>
      
      <!-- Error Message -->
      <?php if($msg): ?>
        <div class="alert alert-danger d-flex align-items-center">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <?=htmlspecialchars($msg)?>
        </div>
      <?php endif; ?>
      
      <!-- Reset Form -->
      <form method="post" id="resetForm">
        <?=csrf_input_field()?>
        
        <!-- New Password -->
        <div class="mb-4">
          <label class="form-label">New Password</label>
          <div class="input-group">
            <input name="password" type="password" class="form-control" id="password" placeholder="Enter new password" required oninput="checkPasswordStrength()">
            <button type="button" class="password-toggle" onclick="togglePassword('password')">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="password-strength">
            <div class="password-strength-bar" id="passwordStrengthBar"></div>
          </div>
          <div class="password-requirements">
            <div class="requirement invalid" id="reqLength">
              <i class="fas fa-circle"></i>
              At least 8 characters
            </div>
            <div class="requirement invalid" id="reqUpper">
              <i class="fas fa-circle"></i>
              One uppercase letter
            </div>
            <div class="requirement invalid" id="reqLower">
              <i class="fas fa-circle"></i>
              One lowercase letter
            </div>
            <div class="requirement invalid" id="reqNumber">
              <i class="fas fa-circle"></i>
              One number
            </div>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
          <label class="form-label">Confirm Password</label>
          <div class="input-group">
            <input name="confirm_password" type="password" class="form-control" id="confirmPassword" placeholder="Confirm your password" required oninput="checkPasswordMatch()">
            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="mt-2" id="passwordMatch"></div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-reset">
          <i class="fas fa-key me-2"></i>Reset Password
        </button>
      </form>
      
      <!-- Login Link -->
      <div class="login-link">
        Remember your password? <a href="login.php">Back to Login</a>
      </div>
    </div>
  </div>

  <script>
    function togglePassword(inputId) {
      const input = document.getElementById(inputId);
      const button = input.parentNode.querySelector('.password-toggle');
      const icon = button.querySelector('i');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }

    function checkPasswordStrength() {
      const password = document.getElementById('password').value;
      const strengthBar = document.getElementById('passwordStrengthBar');
      
      // Reset requirements
      const requirements = {
        length: document.getElementById('reqLength'),
        upper: document.getElementById('reqUpper'),
        lower: document.getElementById('reqLower'),
        number: document.getElementById('reqNumber')
      };
      
      let strength = 0;
      
      // Check length
      if (password.length >= 8) {
        requirements.length.classList.add('valid');
        requirements.length.classList.remove('invalid');
        strength += 25;
      } else {
        requirements.length.classList.remove('valid');
        requirements.length.classList.add('invalid');
      }
      
      // Check uppercase
      if (/[A-Z]/.test(password)) {
        requirements.upper.classList.add('valid');
        requirements.upper.classList.remove('invalid');
        strength += 25;
      } else {
        requirements.upper.classList.remove('valid');
        requirements.upper.classList.add('invalid');
      }
      
      // Check lowercase
      if (/[a-z]/.test(password)) {
        requirements.lower.classList.add('valid');
        requirements.lower.classList.remove('invalid');
        strength += 25;
      } else {
        requirements.lower.classList.remove('valid');
        requirements.lower.classList.add('invalid');
      }
      
      // Check numbers
      if (/[0-9]/.test(password)) {
        requirements.number.classList.add('valid');
        requirements.number.classList.remove('invalid');
        strength += 25;
      } else {
        requirements.number.classList.remove('valid');
        requirements.number.classList.add('invalid');
      }
      
      // Update strength bar
      strengthBar.style.width = strength + '%';
      
      // Set color based on strength
      if (strength < 50) {
        strengthBar.style.background = '#dc3545';
      } else if (strength < 75) {
        strengthBar.style.background = '#ffc107';
      } else {
        strengthBar.style.background = '#28a745';
      }
    }

    function checkPasswordMatch() {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirmPassword').value;
      const matchDiv = document.getElementById('passwordMatch');
      
      if (confirmPassword === '') {
        matchDiv.innerHTML = '';
        return;
      }
      
      if (password === confirmPassword) {
        matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Passwords match</small>';
      } else {
        matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle me-1"></i>Passwords do not match</small>';
      }
    }
  </script>
</body>
</html>