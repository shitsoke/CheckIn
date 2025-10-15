<?php
session_start();
require_once "../db_connect.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Admin | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
<div class="container">
  <h3>Admin Dashboard</h3>
  <a href="../logout.php" class="btn btn-danger float-end">Logout</a>
  <hr>
  <div class="d-grid gap-2 d-md-flex">
  <a class="btn btn-primary me-2" href="manage_rooms.php">Manage Rooms</a>
  <a class="btn btn-success me-2" href="manage_bookings.php">Manage Bookings</a>
  <a class="btn btn-warning me-2" href="manage_users.php">Manage Users</a>
  <a class="btn btn-info me-2" href="manage_reviews.php">Moderate Reviews</a>
  <a class="btn btn-secondary" href="manage_roomtypes.php">Manage Room Types</a>
</div>
</div>
</body></html>
