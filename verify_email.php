<?php
session_start();
require_once "db_connect.php";

$token = $_GET['token'] ?? '';
$status = '';
$message = '';

if (!$token) {
  $status = 'error';
  $message = 'Invalid token.';
} else {
  $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token=? AND email_verified=0 LIMIT 1");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res->num_rows === 0) {
    $status = 'error';
    $message = 'Invalid or expired verification link.';
  } else {
    $user = $res->fetch_assoc();
    $u = $conn->prepare("UPDATE users SET email_verified=1, verification_token=NULL WHERE id=?");
    $u->bind_param("i", $user['id']);
    $u->execute();
    $status = 'success';
    $message = 'Your email has been successfully verified. You may now log in.';
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Email Verification | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff5f6;
    font-family: 'Poppins', sans-serif;
    color: #333;
  }

  .verify-box {
    background: #fff;
    border: 1px solid #f3d6d6;
    border-radius: 12px;
    padding: 40px 50px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    width: 90%;
    max-width: 420px;
  }

  .verify-box .icon {
    font-size: 3.5rem;
    margin-bottom: 15px;
  }

  .verify-box.success .icon {
    color: #28a745;
  }
  .verify-box.error .icon {
    color: #dc3545;
  }

  h3 {
    font-weight: 600;
    margin-bottom: 10px;
  }

  p {
    font-size: 15px;
    color: #555;
  }

  .btn-custom {
    background: #dc3545;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 10px 20px;
    transition: 0.2s;
    font-weight: 500;
  }

  .btn-custom:hover {
    background: #b92b3b;
  }
</style>
</head>
<body>

<div class="verify-box <?=htmlspecialchars($status)?>">
  <?php if ($status === 'success'): ?>
    <div class="icon"><i class="bi bi-check-circle"></i></div>
    <h3>Email Verified</h3>
    <p><?=htmlspecialchars($message)?></p>
    <a href="login.php" class="btn btn-custom mt-3"><i class="bi bi-box-arrow-in-right"></i> Go to Login</a>
  <?php else: ?>
    <div class="icon"><i class="bi bi-x-circle"></i></div>
    <h3>Verification Failed</h3>
    <p><?=htmlspecialchars($message)?></p>
    <a href="register.php" class="btn btn-custom mt-3"><i class="bi bi-person-plus"></i> Register Again</a>
  <?php endif; ?>
</div>

</body>
</html>
