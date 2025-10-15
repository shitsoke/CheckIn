<?php
session_start();
require_once "db_connect.php";
require_once "includes/csrf.php";
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $msg = "Invalid email.";
  else {
    $stmt = $conn->prepare("SELECT u.id, u.password, r.name, u.email_verified FROM users u JOIN roles r ON u.role_id=r.id WHERE u.email=? AND u.is_banned=0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
      $user = $res->fetch_assoc();
      if (!empty($user['email_verified']) && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['name'];
        if ($user['name'] === 'admin') header("Location: admin/index.php");
        else header("Location: dashboard.php");
        exit;
      } else {
        if (empty($user['email_verified'])) $msg = "Email not verified. Please check your inbox.";
        else $msg = "Wrong password.";
      }
    } else $msg = "User not found or banned.";
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container mt-5 col-md-4">
  <h3 class="text-center">Login</h3>
  <?php if ($msg): ?><div class="alert alert-danger"><?=$msg?></div><?php endif; ?>
  <?php if (!empty($_GET['msg']) && $_GET['msg']==='password_updated'): ?><div class="alert alert-success">Password updated. Please login.</div><?php endif; ?>
  <form method="post">
    <?=csrf_input_field()?>
    <input name="email" type="email" class="form-control mb-2" placeholder="Email" required>
    <input name="password" type="password" class="form-control mb-2" placeholder="Password" required>
    <button class="btn btn-primary w-100">Login</button>
    <p class="mt-2 text-center">No account? <a href="register.php">Register</a></p>
    <p class="mt-1 text-center"><a href="forgot_password.php">Forgot password?</a></p>
  </form>
</div>
</body>
</html>
