<?php
session_start();
require_once "db_connect.php";
require_once "includes/csrf.php";

$msg = "";
$alertClass = "alert-danger";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $first = trim($_POST['first_name'] ?? '');
  $middle = trim($_POST['middle_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $pass = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if ($pass !== $confirm) $msg = 'Passwords do not match.';
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $msg = 'Invalid email.';
  elseif (strlen($phone) < 6) $msg = 'Phone number is required.';
  elseif (!preg_match('/^[0-9+()\-\s]{6,20}$/', $phone) || preg_match_all('/[0-9]/', $phone) < 7) $msg = 'Invalid phone number format.';
  elseif (strlen($first) < 1 || strlen($last) < 1) $msg = 'Name required.';
  else {
    require_once __DIR__ . '/includes/password_policy.php';
    $pwCheck = validate_password_strength($pass);
    if ($pwCheck !== true) $msg = $pwCheck;
    else {
      $check = $conn->prepare("SELECT id FROM users WHERE email=?");
      $check->bind_param("s", $email);
      $check->execute();
      $check->store_result();
      if ($check->num_rows > 0) $msg = 'Email already registered.';
      else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $role = 2;
        $token = bin2hex(random_bytes(32));
        $stmt = $conn->prepare("INSERT INTO users (first_name,middle_name,last_name,email,password,role_id,verification_token,email_verified) VALUES (?,?,?,?,?,?,?,0)");
        $stmt->bind_param("sssssis", $first, $middle, $last, $email, $hash, $role, $token);
        if ($stmt->execute()) {
          $userId = $conn->insert_id;
          $p = $conn->prepare("INSERT INTO profiles (user_id, phone) VALUES (?, ?)");
          $p->bind_param("is", $userId, $phone);
          $p->execute();

          $link = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/verify_email.php?token=" . urlencode($token);
          $subject = 'Verify your CheckIn account';
          $body = "Please verify your account by visiting: <a href=\"$link\">$link</a>";
          require_once __DIR__.'/includes/mail.php';
          @send_mail($email, $subject, $body, true);

          $msg = 'Registration successful. Please check your email to verify your account.';
          $alertClass = "alert-success";
        } else $msg = 'Registration failed.';
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register | CheckIn</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

  <style>
    * { box-sizing: border-box; }

    body {
      font-family: 'Poppins', sans-serif;
      background: #fff;
      overflow-x: hidden;
    }

    .register-wrapper {
      display: flex;
      flex-wrap: wrap;
      width: 100%;
      min-height: 100vh;
    }

    .register-left {
      flex: 1;
      min-width: 45%;
      position: relative;
      background: url('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
      color: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 80px;
      z-index: 1;
    }

    .register-left::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.55);
      z-index: 0;
    }

    .register-left h1, .register-left p {
      position: relative;
      z-index: 1;
    }

    .register-left h1 {
      font-weight: 700;
      margin-bottom: 15px;
      font-size: 2.5rem;
    }

    .register-left p {
      font-size: 1rem;
      line-height: 1.6;
      max-width: 400px;
      opacity: 0.95;
    }

    .register-right {
      flex: 1;
      min-width: 55%;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px 120px;
    }

    .register-right h3 {
      font-weight: 700;
      margin-bottom: 25px;
      color: #b32030;
    }

    .form-control {
      border-radius: 8px;
      margin-bottom: 15px;
      height: 45px;
      border: 1px solid #ccc;
      font-size: 15px;
    }

    .btn-register {
      background: #dc3545;
      border: none;
      height: 45px;
      font-weight: 600;
      color: white;
      transition: 0.3s;
    }

    .btn-register:hover {
      background: #b32030;
    }

    .text-center a {
      color: #dc3545;
      text-decoration: none;
      font-weight: 500;
    }

    .text-center a:hover {
      text-decoration: underline;
    }

    /* RESPONSIVE DESIGN */
    @media (max-width: 1200px) {
      .register-right { padding: 60px 80px; }
    }

    @media (max-width: 992px) {
      .register-wrapper {
        flex-direction: column;
        height: auto;
      }

      .register-left {
        height: 300px;
        padding: 40px;
        text-align: center;
        align-items: center;
      }

      .register-right {
        padding: 40px 25px;
      }

      .register-left h1 {
        font-size: 2rem;
      }

      .register-left p {
        font-size: 0.9rem;
      }
    }

    @media (max-width: 576px) {
      .register-right {
        padding: 30px 20px;
      }

      .register-right h3 {
        font-size: 1.5rem;
        text-align: center;
      }

      .btn-register {
        height: 42px;
      }

      .form-control {
        height: 42px;
      }
    }
  </style>
</head>

<body>

  <div class="register-wrapper">
    <div class="register-left">
      <h1>Join CheckIn</h1>
      <p>Discover your next stay with ease. Create your account and experience hassle-free booking designed for modern travelers.</p>
    </div>

    <div class="register-right">
      <h3>Create Your Account</h3>

      <?php if ($msg): ?>
        <div class="alert <?=$alertClass?>"><?=$msg?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <?=csrf_input_field()?>
        <div class="row g-2">
          <div class="col-md-4"><input name="first_name" class="form-control" placeholder="First name" required></div>
          <div class="col-md-4"><input name="middle_name" class="form-control" placeholder="Middle name"></div>
          <div class="col-md-4"><input name="last_name" class="form-control" placeholder="Last name" required></div>
        </div>

        <input name="email" type="email" class="form-control" placeholder="Email" required>
        <input name="phone" id="phoneInput" type="text" pattern="[0-9+()\-\s]{6,20}" title="Enter your phone number" class="form-control" placeholder="Phone number" required>
        <input name="password" type="password" class="form-control" placeholder="Password" required>
        <input name="confirm_password" type="password" class="form-control" placeholder="Confirm password" required>

        <button class="btn btn-register w-100 mt-2">Register</button>
        <p class="mt-3 text-center">Already have an account? <a href="login.php">Login here</a></p>
      </form>
    </div>
  </div>

</body>
</html>
