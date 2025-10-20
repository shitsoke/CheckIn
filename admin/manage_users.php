<?php
session_start();
require_once "../db_connect.php";
require_once __DIR__ . '/../includes/name_helper.php';
include "admin_sidebar.php";

// Check if logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

// --- Ban user ---
if (isset($_GET['ban'])) {
  $id = intval($_GET['ban']);
  $check = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
  $check->bind_param("i", $id);
  $check->execute();
  $check->bind_result($role_id);
  $check->fetch();
  $check->close();

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
$where = [];
$params = [];
$types = '';
if (!empty($_GET['q'])) { 
  $where[] = "(u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
  $params[] = '%'.$_GET['q'].'%'; $params[] = '%'.$_GET['q'].'%'; $params[] = '%'.$_GET['q'].'%';
  $types .= 'sss';
}
if (!empty($_GET['role'])) { 
  $where[] = "u.role_id = ?"; 
  $params[] = intval($_GET['role']); 
  $types .= 'i'; 
}
if (isset($_GET['status']) && $_GET['status'] !== '') { 
  if ($_GET['status'] === 'banned') { $where[] = "u.is_banned = 1"; } 
  else { $where[] = "u.is_banned = 0"; } 
}
$where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';
$sql = "SELECT u.*, r.name AS role, p.display_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        LEFT JOIN profiles p ON p.user_id=u.id 
        $where_sql ORDER BY u.id DESC";
$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Users | Admin - CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
  <h3 class="text-danger fw-bold mb-3">Manage Users</h3>
  <form method="get" class="row gy-2 gx-2 mb-3">
    <div class="col-md-4">
      <input name="q" value="<?=htmlspecialchars($_GET['q'] ?? '')?>" class="form-control border-danger" placeholder="Search by name or email">
    </div>
    <div class="col-md-3">
      <select name="role" class="form-select border-danger">
        <option value="">All roles</option>
        <?php $roles = $conn->query("SELECT * FROM roles"); while($rrole=$roles->fetch_assoc()): ?>
          <option value="<?=$rrole['id']?>" <?=(!empty($_GET['role']) && $_GET['role']==$rrole['id'])? 'selected':''?>>
            <?=htmlspecialchars($rrole['name'])?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select border-danger">
        <option value="">Any status</option>
        <option value="active" <?=(!empty($_GET['status']) && $_GET['status']=='active')? 'selected':''?>>Active</option>
        <option value="banned" <?=(!empty($_GET['status']) && $_GET['status']=='banned')? 'selected':''?>>Banned</option>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-danger w-100">Filter</button>
    </div>
  </form>

  <?php if(isset($_SESSION['msg'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?></div>
  <?php endif; ?>

  <table class="table table-bordered text-center align-middle">
    <thead class="table-danger">
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
      <?php require_once __DIR__ . '/../includes/name_helper.php'; while($u = $res->fetch_assoc()): ?>
      <tr class="<?= $u['id'] % 2 == 0 ? 'table-light' : '' ?>">
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars(display_name_from_row($u)) ?></td>
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
          <a class="btn btn-sm btn-outline-danger" href="view_user.php?id=<?= $u['id'] ?>">View</a>
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
