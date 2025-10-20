<?php
session_start();
require_once "../db_connect.php";
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}
// handle POST actions (toggle visibility, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  if (!empty($_POST['toggle_id'])) {
    $id = intval($_POST['toggle_id']);
    $stmt = $conn->prepare("UPDATE reviews SET is_visible = 1 - is_visible WHERE id=?");
    $stmt->bind_param("i", $id); $stmt->execute();
    header('Location: manage_reviews.php'); exit;
  }
  if (!empty($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $d = $conn->prepare("DELETE FROM reviews WHERE id=?"); $d->bind_param("i", $id); $d->execute();
    header('Location: manage_reviews.php'); exit;
  }
}

$filterRoom = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$where = [];
$params = [];
$types = '';
if (!empty($_GET['room_id'])) { $where[] = 'rv.room_id = ?'; $params[] = intval($_GET['room_id']); $types .= 'i'; }
if (!empty($_GET['room_type'])) { $where[] = 'rv.room_type_id = ?'; $params[] = intval($_GET['room_type']); $types .= 'i'; }
if (!empty($_GET['rating'])) { $where[] = 'rv.rating = ?'; $params[] = intval($_GET['rating']); $types .= 'i'; }
if (isset($_GET['visible']) && $_GET['visible'] !== '') { $where[] = 'rv.is_visible = ?'; $params[] = intval($_GET['visible']); $types .= 'i'; }
$where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';
$sql = "SELECT rv.*, u.email, t.name as roomtype FROM reviews rv 
        JOIN users u ON rv.user_id=u.id 
        LEFT JOIN room_types t ON rv.room_type_id=t.id 
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
<title>Manage Reviews | Admin - CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
  <h3 class="text-danger fw-bold mb-3">Reviews Moderation</h3>
  <a href="index.php" class="btn btn-outline-danger mb-3">← Back</a>
  <div class="alert alert-danger">
    <strong>Note:</strong> Toggle hides/unhides a review from public view. Delete permanently removes a review.
  </div>

  <form method="get" class="row gy-2 gx-2 mb-3">
    <div class="col-md-3">
      <input name="room_id" value="<?=htmlspecialchars($_GET['room_id'] ?? '')?>" class="form-control border-danger" placeholder="Room ID">
    </div>
    <div class="col-md-3">
      <select name="room_type" class="form-select border-danger">
        <option value="">Any room type</option>
        <?php $rtypes = $conn->query("SELECT * FROM room_types"); while($rt=$rtypes->fetch_assoc()): ?>
          <option value="<?=$rt['id']?>" <?=(!empty($_GET['room_type']) && $_GET['room_type']==$rt['id'])? 'selected':''?>>
            <?=htmlspecialchars($rt['name'])?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="rating" class="form-select border-danger">
        <option value="">Any rating</option>
        <?php for($i=5;$i>=1;$i--): ?>
          <option value="<?=$i?>" <?=(!empty($_GET['rating']) && $_GET['rating']==$i)? 'selected':''?>><?=$i?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="visible" class="form-select border-danger">
        <option value="">Any</option>
        <option value="1" <?=isset($_GET['visible']) && $_GET['visible']==='1' ? 'selected':''?>>Visible</option>
        <option value="0" <?=isset($_GET['visible']) && $_GET['visible']==='0' ? 'selected':''?>>Hidden</option>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-danger w-100">Filter</button>
    </div>
  </form>

  <table class="table table-bordered text-center align-middle">
    <thead class="table-danger">
      <tr>
        <th>ID</t
