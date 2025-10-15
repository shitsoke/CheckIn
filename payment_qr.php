<?php
session_start();
require_once "includes/auth_check.php";
$amount = floatval($_GET['amount'] ?? 0);
$booking_id = intval($_GET['booking_id'] ?? 0);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Pay | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container text-center mt-5">
  <h3>Mock GCash Payment</h3>
  <p>Amount: ₱<?=number_format($amount,2)?></p>
  <img src="assets/gcash_qr.png" alt="QR" style="max-width:250px;" class="mb-3">
  <p>After paying, click confirm to mark booking <em>confirmed</em> (admin would normally receive webhook).</p>
  <a href="bookings.php?paid=1&booking_id=<?=$booking_id?>" class="btn btn-success">I have paid (mock)</a>
  <a href="bookings.php" class="btn btn-secondary">Cancel</a>
</div>
</body>
</html>
