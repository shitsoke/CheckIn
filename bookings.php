<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";

$user_id = $_SESSION['user_id'];

// if mock payment confirm (clicked from payment_qr.php)
if (isset($_GET['paid']) && isset($_GET['booking_id'])) {
  $bid = intval($_GET['booking_id']);
  // mark booking confirmed
  $stmt = $conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=? AND user_id=?");
  $stmt->bind_param("ii", $bid, $user_id);
  $stmt->execute();
  // set room to occupied? We'll keep 'reserved' until admin confirms; set to 'reserved'->'reserved'
  header("Location: bookings.php");
  exit;
}

$stmt = $conn->prepare("SELECT b.*, r.room_number, t.name AS room_type
  FROM bookings b
  JOIN rooms r ON b.room_id=r.id
  JOIN room_types t ON r.room_type_id=t.id
  WHERE b.user_id = ?
  ORDER BY b.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>My Bookings | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container mt-4">
  <h3>My Bookings</h3>
  <a href="dashboard.php" class="btn btn-secondary mb-3">← Back</a>
  <table class="table table-bordered">
    <thead class="table-dark"><tr><th>Room</th><th>Type</th><th>Hours</th><th>Total</th><th>Status</th><th>Payment</th><th>Created</th></tr></thead>
    <tbody>
      <?php while($b = $res->fetch_assoc()): ?>
      <tr>
        <td><?=htmlspecialchars($b['room_number'])?></td>
        <td><?=htmlspecialchars($b['room_type'])?></td>
        <td><?=intval($b['hours'])?></td>
        <td>₱<?=number_format($b['total_amount'],2)?></td>
        <td><?=htmlspecialchars($b['status'])?></td>
        <td><?=htmlspecialchars($b['payment_method'])?></td>
        <td><?=htmlspecialchars($b['created_at'])?></td>
        <td>
          <a class="btn btn-sm btn-outline-secondary" href="room_details.php?id=<?=$b['room_id']?>&from=bookings">View Details</a>
          <?php if(in_array($b['status'], ['confirmed','checked_out','ongoing'])): ?>
            <a class="btn btn-sm btn-outline-primary" href="receipt.php?booking_id=<?=$b['id']?>&download=pdf">Download Receipt</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>
