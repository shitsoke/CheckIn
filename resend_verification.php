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
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND email_verified=0 LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) { $msg = 'No unverified account found for that email.'; }
    else {
      $user = $res->fetch_assoc();
      $token = bin2hex(random_bytes(32));
      $u = $conn->prepare("UPDATE users SET verification_token=? WHERE id=?");
      $u->bind_param("si", $token, $user['id']);
      $u->execute();
      $link = 'http://' . 
        ($_SERVER['HTTP_HOST']) . dirname($_SERVER['REQUEST_URI']) . "/verify_email.php?token=$token";
  $subject = 'Verify your email';
  $body = "Please verify your email by clicking the link: <a href=\"$link\">$link</a>";
  require_once __DIR__.'/includes/mail.php';
  send_mail($email, $subject, $body, true);
  $msg = 'Verification email sent (if mail is configured).';
    }
  }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Resend Verification</title></head>
<body>
<div class="container">
  <h3>Resend verification</h3>
  <?php if($msg) echo '<div>'.$msg.'</div>'; ?>
  <form method="post">
    <?=csrf_input_field()?>
    <input name="email" type="email" required>
    <button>Send</button>
  </form>
</div>
</body></html>