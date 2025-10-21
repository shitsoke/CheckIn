<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
include __DIR__ . "/user_sidebar.php";
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
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Booking Details</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #fff;
      color: #333;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .page-header {
      background-color: #c40000;
      color: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 25px;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    }
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .card-header {
      background-color: #c40000;
      color: white;
      font-weight: 600;
      border-top-left-radius: 12px;
      border-top-right-radius: 12px;
    }
    .btn-primary {
      background-color: #c40000;
      border: none;
      transition: all 0.2s ease;
    }
    .btn-primary:hover {
      background-color: #a00000;
    }
    .btn-secondary {
      background-color: #fff;
      color: #c40000;
      border: 1px solid #c40000;
    }
    .btn-secondary:hover {
      background-color: #c40000;
      color: white;
    }
    textarea.form-control {
      min-height: 100px;
    }
  </style>
</head>
<body>
<div class="container mt-4 mb-5">
  <div class="page-header d-flex justify-content-between align-items-center">
    <h3 class="m-0">Booking Details</h3>
    <a href="bookings.php" class="btn btn-light fw-bold">← Back</a>
  </div>

  <div class="card">
    <div class="card-header">
      Booking #<?= htmlspecialchars($bk['id']) ?>
    </div>
    <div class="card-body">
      <p><strong>Customer:</strong> <?= htmlspecialchars($bk['first_name'].' '.($bk['middle_name']? $bk['middle_name'].' ':'').$bk['last_name']) ?></p>
      <p><strong>Room:</strong> <?= htmlspecialchars($bk['room_number']) ?> (<?= htmlspecialchars($bk['room_type']) ?>)</p>
      <p><strong>Start:</strong> <?= htmlspecialchars($bk['start_time'] ?? '') ?></p>
      <p><strong>End:</strong> <?= htmlspecialchars($bk['end_time'] ?? '') ?></p>
      <p><strong>Hours:</strong> <?= intval($bk['hours']) ?></p>
      <p><strong>Total:</strong> <span class="text-danger fw-semibold">₱<?= number_format($bk['total_amount'],2) ?></span></p>
      <p><strong>Status:</strong> 
        <span class="badge 
          <?= $bk['status'] === 'checked_out' ? 'bg-success' : 
               ($bk['status'] === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
          <?= htmlspecialchars($bk['status']) ?>
        </span>
      </p>
    </div>
  </div>

  <?php if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] == $bk['user_id'] && $bk['status'] === 'checked_out'): ?>
    <div class="card mt-4">
      <div class="card-header">
        Leave a Review
      </div>
      <div class="card-body">
        <form method="post" action="reviews.php">
          <?php require_once __DIR__.'/includes/csrf.php'; echo csrf_input_field(); ?>
          <input type="hidden" name="room_id" value="<?= intval($bk['room_id']) ?>">
          <input type="hidden" name="return_to" value="dashboard.php">
          
          <div class="mb-3">
            <label class="form-label fw-semibold">Rating</label>
            <select name="rating" class="form-select">
              <option value="5">5 - Excellent</option>
              <option value="4">4 - Good</option>
              <option value="3">3 - Average</option>
              <option value="2">2 - Poor</option>
              <option value="1">1 - Terrible</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-semibold">Comment</label>
            <textarea name="comment" class="form-control" placeholder="Share your experience..."></textarea>
          </div>
          
          <button class="btn btn-primary w-100">Submit Review</button>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
