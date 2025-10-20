<?php
session_start();
require_once "includes/auth_check.php";
require_once "includes/csrf.php";
require_once "db_connect.php";

$user_id = $_SESSION['user_id'];
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $current = $_POST['current'] ?? '';
  $new = $_POST['new'] ?? '';
  $confirm = $_POST['confirm'] ?? '';
  if (empty($new) || $new !== $confirm) $msg = 'New password and confirmation do not match.';
  else {
    // simplistic password change - assumes passwords hashed with password_hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || !password_verify($current, $row['password'])) $msg = 'Current password incorrect.';
    else {
      $hp = password_hash($new, PASSWORD_DEFAULT);
      $u = $conn->prepare("UPDATE users SET password=? WHERE id=?"); $u->bind_param("si", $hp, $user_id); $u->execute();
      $msg = 'Password changed successfully!';
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Change Password | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #dc3545;
      --primary-hover: #c82333;
      --primary-light: rgba(220, 53, 69, 0.1);
    }
    
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .container {
      max-width: 600px;
    }
    
    .password-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      padding: 30px;
      margin-top: 20px;
    }
    
    .page-title {
      color: var(--primary-color);
      font-weight: 700;
      margin-bottom: 5px;
    }
    
    .page-subtitle {
      color: #666;
      font-size: 1.1rem;
      margin-bottom: 30px;
    }
    
    /* Button Styles */
    .btn-primary {
      background: var(--primary-color);
      border: none;
      height: 45px;
      font-weight: 600;
      color: white;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 25px;
    }

    .btn-primary:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }

    .btn-secondary {
      background: #6c757d;
      border: none;
      height: 45px;
      font-weight: 600;
      color: white;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 25px;
    }

    .btn-secondary:hover {
      background: #545b62;
      transform: translateY(-2px);
    }

    .btn-back {
      background: var(--primary-color);
      border: none;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      margin-bottom: 30px;
    }
    
    .btn-back:hover {
      background: var(--primary-hover);
      color: white;
      transform: translateY(-2px);
    }

    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .alert-info {
      border-left: 4px solid var(--primary-color);
      background: var(--primary-light);
    }
    
    .alert-danger {
      border-left: 4px solid var(--primary-color);
      background: var(--primary-light);
    }
    
    .form-label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
    }
    
    .password-strength {
      height: 4px;
      background: #e9ecef;
      border-radius: 2px;
      margin-top: 5px;
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
      gap: 5px;
      margin-bottom: 3px;
    }
    
    .requirement.valid {
      color: #28a745;
    }
    
    .requirement.invalid {
      color: #666;
    }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4">
  <!-- Header Section -->
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <h1 class="page-title"><i class="fas fa-key me-2"></i>Change Password</h1>
      <p class="page-subtitle">Secure your account with a new password</p>
    </div>
    <?php $ret = $_GET['return_to'] ?? 'dashboard.php'; ?>
    <a href="<?=htmlspecialchars($ret)?>" class="btn-back">
      <i class="fas fa-arrow-left"></i>
      Back
    </a>
  </div>

  <!-- Password Card -->
  <div class="password-card">
    <?php if($msg): ?>
      <div class="alert <?= strpos($msg, 'successfully') !== false ? 'alert-info' : 'alert-danger' ?> d-flex align-items-center">
        <i class="fas <?= strpos($msg, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
        <?=htmlspecialchars($msg)?>
      </div>
    <?php endif; ?>
    
    <form method="post" id="passwordForm">
      <?=csrf_input_field()?>
      
      <!-- Current Password -->
      <div class="mb-4">
        <label class="form-label">Current Password</label>
        <div class="input-group">
          <input name="current" type="password" class="form-control" id="currentPassword" required>
          <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('currentPassword')">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <!-- New Password -->
      <div class="mb-4">
        <label class="form-label">New Password</label>
        <div class="input-group">
          <input name="new" type="password" class="form-control" id="newPassword" required oninput="checkPasswordStrength()">
          <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('newPassword')">
            <i class="fas fa-eye"></i>
          </button>
        </div>
        <div class="password-strength mt-2">
          <div class="password-strength-bar" id="passwordStrengthBar"></div>
        </div>
        <div class="password-requirements">
          <div class="requirement invalid" id="reqLength">
            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
            At least 8 characters
          </div>
          <div class="requirement invalid" id="reqUpper">
            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
            One uppercase letter
          </div>
          <div class="requirement invalid" id="reqLower">
            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
            One lowercase letter
          </div>
          <div class="requirement invalid" id="reqNumber">
            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
            One number
          </div>
        </div>
      </div>

      <!-- Confirm New Password -->
      <div class="mb-4">
        <label class="form-label">Confirm New Password</label>
        <div class="input-group">
          <input name="confirm" type="password" class="form-control" id="confirmPassword" required oninput="checkPasswordMatch()">
          <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirmPassword')">
            <i class="fas fa-eye"></i>
          </button>
        </div>
        <div class="mt-2" id="passwordMatch"></div>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-2"></i>Change Password
        </button>
        <?php $ret = $_GET['return_to'] ?? 'dashboard.php'; ?>
        <a href="<?=htmlspecialchars($ret)?>" class="btn btn-secondary">
          <i class="fas fa-times me-2"></i>Cancel
        </a>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
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
    const password = document.getElementById('newPassword').value;
    const strengthBar = document.getElementById('passwordStrengthBar');
    
    // Reset requirements
    const requirements = {
      length: document.getElementById('reqLength'),
      upper: document.getElementById('reqUpper'),
      lower: document.getElementById('reqLower'),
      number: document.getElementById('reqNumber')
    };
    
    let strength = 0;
    let totalRequirements = 0;
    let metRequirements = 0;
    
    // Check length
    if (password.length >= 8) {
      requirements.length.classList.add('valid');
      requirements.length.classList.remove('invalid');
      strength += 25;
      metRequirements++;
    } else {
      requirements.length.classList.remove('valid');
      requirements.length.classList.add('invalid');
    }
    totalRequirements++;
    
    // Check uppercase
    if (/[A-Z]/.test(password)) {
      requirements.upper.classList.add('valid');
      requirements.upper.classList.remove('invalid');
      strength += 25;
      metRequirements++;
    } else {
      requirements.upper.classList.remove('valid');
      requirements.upper.classList.add('invalid');
    }
    totalRequirements++;
    
    // Check lowercase
    if (/[a-z]/.test(password)) {
      requirements.lower.classList.add('valid');
      requirements.lower.classList.remove('invalid');
      strength += 25;
      metRequirements++;
    } else {
      requirements.lower.classList.remove('valid');
      requirements.lower.classList.add('invalid');
    }
    totalRequirements++;
    
    // Check numbers
    if (/[0-9]/.test(password)) {
      requirements.number.classList.add('valid');
      requirements.number.classList.remove('invalid');
      strength += 25;
      metRequirements++;
    } else {
      requirements.number.classList.remove('valid');
      requirements.number.classList.add('invalid');
    }
    totalRequirements++;
    
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
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirmPassword === '') {
      matchDiv.innerHTML = '';
      return;
    }
    
    if (newPassword === confirmPassword) {
      matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Passwords match</small>';
    } else {
      matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle me-1"></i>Passwords do not match</small>';
    }
  }
</script>
</body>
</html>