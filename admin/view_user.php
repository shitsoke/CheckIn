<?php
session_start();
require_once "../db_connect.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die('Invalid user id');

$stmt = $conn->prepare("SELECT u.first_name,u.middle_name,u.last_name,u.email,u.created_at,u.is_banned,p.phone,p.address,p.avatar FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
if (!$user) die('User not found');
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>View User | Admin - CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
<div class="container">
  <h3>User Profile</h3>
  <a href="manage_users.php" class="btn btn-secondary mb-3">← Back</a>
  <div class="row">
    <div class="col-md-4">
      <?php if (!empty($user['avatar'])): ?>
        <img src="<?=htmlspecialchars('../'.$user['avatar'])?>" class="img-fluid rounded" alt="Avatar">
      <?php else: ?>
        <div style="width:100%;padding:40% 0;background:#f0f0f0;text-align:center;color:#777">No avatar</div>
      <?php endif; ?>
    </div>
    <div class="col-md-8">
      <table class="table">
        <tr><th>Name</th><td><?=htmlspecialchars($user['first_name'].' '.($user['middle_name']?:'').' '.$user['last_name'])?></td></tr>
        <tr><th>Email</th><td><?=htmlspecialchars($user['email'])?></td></tr>
        <tr><th>Phone</th><td><?=htmlspecialchars($user['phone'] ?? '')?></td></tr>
        <tr><th>Address</th><td><?=nl2br(htmlspecialchars($user['address'] ?? ''))?></td></tr>
        <tr><th>Registered</th><td><?=htmlspecialchars($user['created_at'])?></td></tr>
        <tr><th>Status</th><td><?= $user['is_banned'] ? '<span class="badge bg-danger">Banned</span>' : '<span class="badge bg-success">Active</span>' ?></td></tr>
      </table>
    </div>
  </div>
</div>
</body></html>