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
      $msg = 'Password changed.';
    }
  }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Change Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container mt-4 col-md-6">
  <h3>Change Password</h3>
  <?php if($msg): ?><div class="alert alert-info"><?=htmlspecialchars($msg)?></div><?php endif; ?>
  <form method="post">
    <?=csrf_input_field()?>
    <label>Current password</label>
    <input name="current" type="password" class="form-control mb-2" required>
    <label>New password</label>
    <input name="new" type="password" class="form-control mb-2" required>
    <label>Confirm new password</label>
    <input name="confirm" type="password" class="form-control mb-2" required>
    <button class="btn btn-primary">Change password</button>
    <?php $ret = $_GET['return_to'] ?? 'dashboard.php'; ?>
    <a href="<?=htmlspecialchars($ret)?>" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body></html>