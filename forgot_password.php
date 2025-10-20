<?php
session_start();
require_once "db_connect.php";
require_once "includes/csrf.php";

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $email = trim($_POST['email'] ?? '');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $msg = 'Invalid email.';
  else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
      $msg = 'No account found with that email.';
    } else {
      $user = $res->fetch_assoc();
      $token = bin2hex(random_bytes(32));
      $expires = date('Y-m-d H:i:s', time() + 3600);
      $u = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
      $u->bind_param("ssi", $token, $expires, $user['id']);
      $u->execute();

      $link = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=$token";
      $subject = 'Password Reset';
      $body = "Click to reset your password (valid for 1 hour): <a href=\"$link\">$link</a>";

      require_once __DIR__ . '/includes/mail.php';
      send_mail($email, $subject, $body, true);

      $msg = 'If this email is registered, a reset link was sent.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Forgot Password | CheckIn</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Poppins', sans-serif;
      background: #fff;
      overflow-x: hidden;
    }

    .forgot-wrapper {
      display: flex;
      flex-wrap: wrap;
      min-height: 100vh;
    }

    .forgot-left {
      flex: 1;
      min-width: 45%;
      background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                  url('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
      color: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      padding: 80px;
    }

    .forgot-left h1 {
      font-weight: 700;
      font-size: 3rem;
      margin-bottom: 15px;
    }

    .forgot-left p {
      font-size: 16px;
      line-height: 1.7;
      max-width: 400px;
      opacity: 0.9;
    }

    .forgot-right {
      flex: 1;
      min-width: 55%;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px 80px;
    }

    .forgot-right h3 {
      font-weight: 700;
      margin-bottom: 25px;
      color: #b21f2d;
    }

    .form-control {
      border-radius: 8px;
      margin-bottom: 15px;
      height: 45px;
      font-size: 15px;
    }

    .btn-forgot {
      background: #dc3545;
      border: none;
      height: 45px;
      font-weight: 600;
      color: white;
      transition: 0.3s;
      border-radius: 8px;
    }

    .btn-forgot:hover {
      background: #b32030;
    }

    .text-muted a {
      color: #b21f2d;
      text-decoration: none;
      font-weight: 500;
    }

    .text-muted a:hover {
      text-decoration: underline;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
      .forgot-wrapper {
        flex-direction: column;
        height: auto;
      }

      .forgot-left {
        height: 260px;
        padding: 50px 30px;
        text-align: center;
        align-items: center;
      }

      .forgot-left h1 {
        font-size: 2rem;
      }

      .forgot-left p {
        font-size: 14px;
        max-width: 90%;
      }

      .forgot-right {
        padding: 40px 30px;
      }
    }

    @media (max-width: 576px) {
      .forgot-left {
        height: 200px;
        padding: 40px 20px;
      }

      .forgot-right {
        padding: 30px 20px;
      }

      .form-control {
        height: 42px;
        font-size: 14px;
      }

      .btn-forgot {
        height: 42px;
        font-size: 14px;
      }
    }
  </style>
</head>
<body>

  <div class="forgot-wrapper">
    <div class="forgot-left">
      <h1>Forgot your password?</h1>
      <p>Don’t worry — we’ll help you recover access to your account in just a few clicks.</p>
    </div>

    <div class="forgot-right">
      <h3>Forgot Password</h3>

      <?php if ($msg): ?>
        <div class="alert alert-info"><?=$msg?></div>
      <?php endif; ?>

      <form method="post">
        <?=csrf_input_field()?>
        <input name="email" type="email" class="form-control" placeholder="Enter your email address" required>
        <button class="btn btn-forgot w-100 mt-2">Reset Password</button>
      </form>

      <p class="text-center mt-3 text-muted">
        Remembered your password? <a href="login.php">Login here</a>
      </p>
    </div>
  </div>

</body>
</html>
