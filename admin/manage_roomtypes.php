<?php
session_start();
require_once "../db_connect.php";
include "admin_sidebar.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}
$result = $conn->query("SELECT * FROM room_types ORDER BY id ASC");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Manage Room Types | Admin - CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
  <h3 class="text-danger fw-bold mb-3">Manage Room Types</h3>
  <table class="table table-bordered text-center align-middle">
    <thead class="table-danger">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Hourly Rate (₱)</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
      <tr class="<?= $row['id'] % 2 == 0 ? 'table-light' : '' ?>">
        <td><?= htmlspecialchars($row['id']) ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= number_format($row['hourly_rate'], 2) ?></td>
        <td><a href="edit_roomtype.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger">Edit</a></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>
