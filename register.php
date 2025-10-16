<?php
session_start();
require_once "db_connect.php";
require_once "includes/csrf.php";

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $first = trim($_POST['first_name'] ?? '');
  $middle = trim($_POST['middle_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $pass = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  // Basic validations
  if ($pass !== $confirm) {
    $msg = 'Passwords do not match.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $msg = 'Invalid email.';
  } elseif (strlen($phone) < 6) {
    $msg = 'Phone number is required.';
  } elseif (!preg_match('/^[0-9+()\-\s]{6,20}$/', $phone) || preg_match_all('/[0-9]/', $phone) < 7) {
    $msg = 'Invalid phone number format.';
  } elseif (strlen($first) < 1 || strlen($last) < 1) {
    $msg = 'Name required.';
  } else {
    // Password strength check
    require_once __DIR__ . '/includes/password_policy.php';
    $pwCheck = validate_password_strength($pass);
    if ($pwCheck !== true) {
      $msg = $pwCheck; // descriptive error from validator
    } else {
      // Check email uniqueness
      $check = $conn->prepare("SELECT id FROM users WHERE email=?");
      $check->bind_param("s", $email);
      $check->execute();
      $check->store_result();
      if ($check->num_rows > 0) {
        $msg = 'Email already registered.';
      } else {
        // Insert user
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $role = 2; // customer
        $token = bin2hex(random_bytes(32));
        $stmt = $conn->prepare("INSERT INTO users (first_name,middle_name,last_name,email,password,role_id,verification_token,email_verified) VALUES (?,?,?,?,?,?,?,0)");
        $stmt->bind_param("sssssis", $first, $middle, $last, $email, $hash, $role, $token);
        if ($stmt->execute()) {
          $userId = $conn->insert_id;
          // create profile row with phone
          $p = $conn->prepare("INSERT INTO profiles (user_id, phone) VALUES (?, ?)");
          $p->bind_param("is", $userId, $phone);
          $p->execute();

          // send verification email (best-effort)
          $link = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/verify_email.php?token=" . urlencode($token);
          $subject = 'Verify your CheckIn account';
          $body = "Please verify your account by visiting: <a href=\"$link\">$link</a>";
          require_once __DIR__.'/includes/mail.php';
          @send_mail($email, $subject, $body, true);

          $msg = 'Registration successful. Please check your email to verify your account.';
        } else {
          $msg = 'Registration failed.';
        }
      }
    }
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Register | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 col-md-6">
  <h3>Create an account</h3>
  <?php if ($msg): ?><div class="alert alert-danger"><?=$msg?></div><?php endif; ?>
  <form method="post" novalidate>
    <?=csrf_input_field()?>
    <div class="row">
      <div class="col"><input name="first_name" class="form-control mb-2" placeholder="First name" required></div>
      <div class="col"><input name="middle_name" class="form-control mb-2" placeholder="Middle name"></div>
      <div class="col"><input name="last_name" class="form-control mb-2" placeholder="Last name" required></div>
    </div>
    <input name="email" type="email" class="form-control mb-2" placeholder="Email" required>
    <input name="phone" id="phoneInput" type="text" pattern="[0-9+()\-\s]{6,20}" title="Enter your phone number (digits and +()- allowed)" class="form-control mb-2" placeholder="Phone number" required>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        var f = document.querySelector('form');
        f.addEventListener('submit', function(e){
          var phone = document.getElementById('phoneInput').value || '';
          var digits = phone.replace(/\D/g,'');
          if (digits.length < 7) { e.preventDefault(); alert('Please enter a valid phone number (at least 7 digits).'); }
        });
      });
    </script>
    <input name="password" type="password" class="form-control mb-2" placeholder="Password" required>
    <input name="confirm_password" type="password" class="form-control mb-2" placeholder="Confirm password" required>
    <button class="btn btn-primary w-100">Register</button>
    <p class="mt-2 text-center">Already have account? <a href="login.php">Login</a></p>
  </form>
</div>
</body>
</html>
