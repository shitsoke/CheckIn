<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";

$id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Dashboard | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<div class="container mt-4">
  <h4>Welcome, <?=htmlspecialchars($user['first_name'])?> <?=htmlspecialchars($user['last_name'])?></h4>
  <p>Role: <strong><?=htmlspecialchars($_SESSION['role'])?></strong></p>
  <a href="browse_rooms.php" class="btn btn-primary">Browse Rooms</a>
  <a href="bookings.php" class="btn btn-success">My Bookings</a>
  <a href="profile.php" class="btn btn-info">My Profile</a>
  <a href="reviews.php" class="btn btn-warning">Leave Review</a>
  <a href="logout.php" class="btn btn-danger float-end">Logout</a>
</div>
</body>
</html>
