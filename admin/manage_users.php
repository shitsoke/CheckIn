<?php
session_start();
require_once "../db_connect.php";

// Check if logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

// --- Ban user ---
if (isset($_GET['ban'])) {
  $id = intval($_GET['ban']);
  
  // Prevent banning admin users
  $check = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
  $check->bind_param("i", $id);
  $check->execute();
  $check->bind_result($role_id);
  $check->fetch();
  $check->close();

  // If target is admin (role_id = 1), skip banning
  if ($role_id == 1) {
    $_SESSION['msg'] = "❌ You cannot ban an admin account.";
  } elseif ($id == $_SESSION['user_id']) {
    $_SESSION['msg'] = "⚠️ You cannot ban your own account.";
  } else {
    $stmt = $conn->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['msg'] = "✅ User has been banned successfully.";
  }
  header("Location: manage_users.php");
  exit;
}

// --- Unban user ---
if (isset($_GET['unban'])) {
  $id = intval($_GET['unban']);
  $stmt = $conn->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $_SESSION['msg'] = "✅ User has been unbanned successfully.";
  header("Location: manage_users.php");
  exit;
}

// --- Load all users ---
$res = $conn->query("
  SELECT u.*, r.name AS role
  FROM users u
  JOIN roles r ON u.role_id = r.id
  ORDER BY u.id DESC
");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Users | Admin - CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h3>Manage Users</h3>
  <a href="index.php" class="btn btn-secondary mb-3">← Back</a>

  <?php if(isset($_SESSION['msg'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?></div>
  <?php endif; ?>

  <table class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while($u = $res->fetch_assoc()): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= htmlspecialchars(ucfirst($u['role'])) ?></td>
        <td>
          <?php if($u['is_banned']): ?>
            <span class="badge bg-danger">Banned</span>
          <?php else: ?>
            <span class="badge bg-success">Active</span>
          <?php endif; ?>
        </td>
        <td>
          <a class="btn btn-sm btn-info" href="view_user.php?id=<?= $u['id'] ?>">View Profile</a>
          <?php if($u['role'] === 'admin'): ?>
            <button class="btn btn-sm btn-secondary" disabled>Protected</button>
          <?php elseif($u['is_banned']): ?>
            <a class="btn btn-sm btn-success" href="?unban=<?= $u['id'] ?>" onclick="return confirm('Unban this user?');">Unban</a>
          <?php else: ?>
            <a class="btn btn-sm btn-danger" href="?ban=<?= $u['id'] ?>" onclick="return confirm('Ban this user?');">Ban</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>
