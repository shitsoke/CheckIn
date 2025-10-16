<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
$booking_id = intval($_GET['booking_id'] ?? 0);
if ($booking_id <= 0) die('Invalid booking id');
$stmt = $conn->prepare("SELECT b.*, r.room_number, t.name as room_type, u.first_name, u.middle_name, u.last_name FROM bookings b JOIN rooms r ON b.room_id=r.id JOIN room_types t ON r.room_type_id=t.id JOIN users u ON b.user_id=u.id WHERE b.id=? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$bk = $stmt->get_result()->fetch_assoc();
if (!$bk) die('Booking not found');
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $bk['user_id']) die('Unauthorized');
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Booking Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container mt-4">
  <a href="bookings.php" class="btn btn-secondary mb-3">← Back</a>
  <h3>Booking #<?=htmlspecialchars($bk['id'])?></h3>
  <p>Customer: <?=htmlspecialchars($bk['first_name'].' '.($bk['middle_name']? $bk['middle_name'].' ':'').$bk['last_name'])?></p>
  <p>Room: <?=htmlspecialchars($bk['room_number'])?> (<?=htmlspecialchars($bk['room_type'])?>)</p>
  <p>Start: <?=htmlspecialchars($bk['start_time'] ?? '')?></p>
  <p>End: <?=htmlspecialchars($bk['end_time'] ?? '')?></p>
  <p>Hours: <?=intval($bk['hours'])?></p>
  <p>Total: ₱<?=number_format($bk['total_amount'],2)?></p>
  <p>Status: <?=htmlspecialchars($bk['status'])?></p>
  <?php if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] == $bk['user_id'] && $bk['status'] === 'checked_out'): ?>
    <hr>
    <h5>Leave a review for this booking</h5>
    <form method="post" action="reviews.php">
      <?php require_once __DIR__.'/includes/csrf.php'; echo csrf_input_field(); ?>
      <input type="hidden" name="room_id" value="<?=intval($bk['room_id'])?>">
      <input type="hidden" name="return_to" value="dashboard.php">
      <label>Rating</label>
      <select name="rating" class="form-select mb-2">
        <option value="5">5</option>
        <option value="4">4</option>
        <option value="3">3</option>
        <option value="2">2</option>
        <option value="1">1</option>
      </select>
      <label>Comment</label>
      <textarea name="comment" class="form-control mb-2"></textarea>
      <button class="btn btn-primary">Submit Review</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>