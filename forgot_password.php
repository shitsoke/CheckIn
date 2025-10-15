<?php
session_start();
require_once "db_connect.php";
require_once "includes/csrf.php";
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $email = trim($_POST['email'] ?? '');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $msg = 'Invalid email';
  else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) { $msg = 'No account found.'; }
    else {
      $user = $res->fetch_assoc();
      $token = bin2hex(random_bytes(32));
      $expires = date('Y-m-d H:i:s', time()+3600);
      $u = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
      $u->bind_param("ssi", $token, $expires, $user['id']);
      $u->execute();
      $link = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=$token";
  $subject = 'Password reset';
  $body = "Click to reset your password (valid 1 hour): <a href=\"$link\">$link</a>";
  require_once __DIR__.'/includes/mail.php';
  send_mail($email, $subject, $body, true);
      $msg = 'If the email is configured, a reset link was sent.';
    }
  }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Forgot Password</title></head>
<body>
<div class="container">
  <h3>Forgot password</h3>
  <?php if($msg) echo '<div>'.$msg.'</div>'; ?>
  <a href="login.php" class="btn btn-secondary mb-2">← Back to Login</a>
  <form method="post">
    <?=csrf_input_field()?>
    <input name="email" type="email" required>
    <button>Send reset link</button>
  </form>
</div>
</body></html>