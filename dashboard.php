<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
require_once __DIR__ . '/includes/name_helper.php';

$id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT u.first_name, u.middle_name, u.last_name, p.display_name FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=?");
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
  <h4>Welcome, <?=htmlspecialchars(!empty($user['display_name']) ? $user['display_name'] : ($user['first_name'].' '.$user['last_name']))?></h4>
  <p>Role: <strong><?=htmlspecialchars($_SESSION['role'])?></strong></p>
  <a href="browse_rooms.php" class="btn btn-primary">Browse Rooms</a>
  <a href="bookings.php" class="btn btn-success">My Bookings</a>
  <!-- profile is now under Settings -->
  <a href="reviews.php" class="btn btn-warning">Leave Review</a>
  <a href="settings.php" class="btn btn-secondary">Settings</a>
  <a href="logout.php" class="btn btn-danger float-end">Logout</a>
  <?php
  // show upcoming bookings for this user
  // Show only upcoming bookings that are reserved or confirmed (exclude checked_out or canceled)
  $upcoming = $conn->prepare("SELECT b.id, r.room_number, b.start_time, b.end_time, b.status FROM bookings b JOIN rooms r ON b.room_id=r.id WHERE b.user_id=? AND b.start_time >= NOW() AND b.status IN ('reserved','confirmed') ORDER BY b.start_time ASC LIMIT 5");
  $upcoming->bind_param("i", $id);
  $upcoming->execute();
  $upRes = $upcoming->get_result();
  if ($upRes && $upRes->num_rows): ?>
    <div class="mt-4">
      <h5>Upcoming bookings</h5>
      <ul class="list-group">
        <?php while($u = $upRes->fetch_assoc()): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong>Room <?=htmlspecialchars($u['room_number'])?></strong>
              <div class="text-muted small"><?=htmlspecialchars($u['start_time'])?> — <?=htmlspecialchars($u['end_time'])?></div>
            </div>
            <div><span class="badge bg-info"><?=htmlspecialchars($u['status'])?></span>
            <a class="btn btn-sm btn-outline-primary ms-2" href="booking_history.php?booking_id=<?=$u['id']?>">Details</a></div>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
