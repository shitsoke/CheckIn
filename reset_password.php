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
      $stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token=? LIMIT 1");
      $stmt->bind_param("s", $token);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res->num_rows === 0) {
        $msg = 'Invalid token';
      } else {
        $row = $res->fetch_assoc();
        if (strtotime($row['reset_expires']) < time()) {
          $msg = 'Token expired';
        } else {
          $hash = password_hash($pass, PASSWORD_BCRYPT);
          $u = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
          $u->bind_param("si", $hash, $row['id']);
          $u->execute();
          header('Location: login.php?msg=password_updated'); exit;
        }
      }
    }
  }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Reset Password</title></head>
<body>
<div class="container">
  <h3>Reset password</h3>
  <?php if($msg) echo '<div>'.$msg.'</div>'; ?>
  <form method="post">
    <?=csrf_input_field()?>
    <input name="password" type="password" placeholder="New password" required>
    <input name="confirm_password" type="password" placeholder="Confirm password" required>
    <button>Set password</button>
  </form>
</div>
</body></html>