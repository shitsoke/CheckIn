<?php
session_start();
require_once "../db_connect.php";
require_once __DIR__ . '/../includes/csrf.php';

// Secure admin auth before sidebar
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

// Handle POST actions (toggle visibility, delete) before output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  if (!empty($_POST['toggle_id'])) {
    $id = intval($_POST['toggle_id']);
    $stmt = $conn->prepare("UPDATE reviews SET is_visible = 1 - is_visible WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_reviews.php");
    exit;
  }
  if (!empty($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_reviews.php");
    exit;
  }
}

include "admin_sidebar.php";

// Filtering
$where = [];
$params = [];
$types = '';
if (!empty($_GET['room_id'])) { $where[] = 'rv.room_id = ?'; $params[] = intval($_GET['room_id']); $types .= 'i'; }
if (!empty($_GET['room_type'])) { $where[] = 'rv.room_type_id = ?'; $params[] = intval($_GET['room_type']); $types .= 'i'; }
if (!empty($_GET['rating'])) { $where[] = 'rv.rating = ?'; $params[] = intval($_GET['rating']); $types .= 'i'; }
if (isset($_GET['visible']) && $_GET['visible'] !== '') { $where[] = 'rv.is_visible = ?'; $params[] = intval($_GET['visible']); $types .= 'i'; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT rv.*, u.email, t.name as roomtype 
        FROM reviews rv 
        JOIN users u ON rv.user_id = u.id 
        LEFT JOIN room_types t ON rv.room_type_id = t.id 
        $where_sql ORDER BY rv.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
?>

<!doctype html>

<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Reviews | Admin - CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background-color: #f8f9fa; color: #333; }
  .main-content { margin-left: 250px; padding: 20px; }
  @media (max-width: 991.98px) { .main-content { margin-left: 0; } }
  .table-responsive { border-radius: 10px; overflow-x: auto; background-color: #fff; }
  .table thead th { background-color: #dc3545 !important; color: #fff !important; }
  .filter-box .form-control, .filter-box .form-select { border-width: 2px; border-color: #dc3545; }
</style>
</head>
<body>
  <div class="main-content">
    <div class="container-fluid py-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <h3 class="fw-bold text-danger mb-3 mb-md-0">⭐ Reviews Moderation</h3>
        <a href="dashboard.php" class="btn btn-outline-danger fw-semibold">← Back</a>
      </div>

```
  <div class="alert alert-danger shadow-sm">
    <strong>Note:</strong> "Toggle" hides/unhides a review. "Delete" permanently removes it.
  </div>

  <form method="get" class="row gy-2 gx-2 mb-3 filter-box">
    <div class="col-12 col-md-3">
      <input name="room_id" value="<?= htmlspecialchars($_GET['room_id'] ?? '') ?>" class="form-control" placeholder="Room ID">
    </div>
    <div class="col-12 col-md-3">
      <select name="room_type" class="form-select">
        <option value="">Any room type</option>
        <?php $rtypes = $conn->query("SELECT * FROM room_types");
        while ($rt = $rtypes->fetch_assoc()): ?>
          <option value="<?= $rt['id'] ?>" <?= (!empty($_GET['room_type']) && $_GET['room_type']==$rt['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($rt['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-12 col-md-2">
      <select name="rating" class="form-select">
        <option value="">Any rating</option>
        <?php for ($i=5; $i>=1; $i--): ?>
          <option value="<?= $i ?>" <?= (!empty($_GET['rating']) && $_GET['rating']==$i) ? 'selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-12 col-md-2">
      <select name="visible" class="form-select">
        <option value="">Any</option>
        <option value="1" <?= (isset($_GET['visible']) && $_GET['visible']==='1') ? 'selected' : '' ?>>Visible</option>
        <option value="0" <?= (isset($_GET['visible']) && $_GET['visible']==='0') ? 'selected' : '' ?>>Hidden</option>
      </select>
    </div>
    <div class="col-12 col-md-2">
      <button class="btn btn-danger w-100 fw-semibold">Filter</button>
    </div>
  </form>

  <div class="table-responsive shadow-sm">
    <table class="table table-bordered table-hover text-center align-middle mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Room Type</th>
          <th>Room</th>
          <th>Rating</th>
          <th>Comment</th>
          <th>Visible</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($res->num_rows === 0): ?>
          <tr><td colspan="8" class="text-muted">No reviews found.</td></tr>
        <?php else: while($r = $res->fetch_assoc()): ?>
          <tr class="<?= $r['id'] % 2 == 0 ? 'table-light' : '' ?>">
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= htmlspecialchars($r['roomtype'] ?? 'Hotel') ?></td>
            <td>
              <?php if (!empty($r['room_id'])): ?>
                <a href="room_details.php?id=<?= intval($r['room_id']) ?>&from=manage_reviews" class="text-danger text-decoration-none">View room</a>
              <?php else: ?>
                Overall Hotel
              <?php endif; ?>
            </td>
            <td><?= intval($r['rating']) ?></td>
            <td class="text-break"><?= htmlspecialchars($r['comment']) ?></td>
            <td>
              <?= $r['is_visible']
                ? '<span class="badge bg-danger">Yes</span>'
                : '<span class="badge bg-secondary">No</span>' ?>
            </td>
            <td>
              <form method="post" class="d-inline">
                <?= csrf_input_field() ?>
                <input type="hidden" name="toggle_id" value="<?= $r['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Toggle</button>
              </form>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this review?');">
                <?= csrf_input_field() ?>
                <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>
```

  </div>
</body>
</html>
