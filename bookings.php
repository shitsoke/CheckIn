<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
require_once __DIR__ . '/includes/header.php';

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

$where = ['b.user_id = ?'];
$params = [$user_id];
$types = 'i';
if (!empty($_GET['q'])) { $where[] = "(r.room_number LIKE ? OR t.name LIKE ? OR b.payment_method LIKE ?)"; $params[] = '%'.$_GET['q'].'%'; $params[] = '%'.$_GET['q'].'%'; $params[] = '%'.$_GET['q'].'%'; $types .= 'sss'; }
if (!empty($_GET['status'])) { $where[] = 'b.status = ?'; $params[] = $_GET['status']; $types .= 's'; }
if (!empty($_GET['room'])) { $where[] = 'r.room_number LIKE ?'; $params[] = '%'.$_GET['room'].'%'; $types .= 's'; }
if (!empty($_GET['from_date'])) { $where[] = 'DATE(b.start_time) >= ?'; $params[] = $_GET['from_date']; $types .= 's'; }
if (!empty($_GET['to_date'])) { $where[] = 'DATE(b.start_time) <= ?'; $params[] = $_GET['to_date']; $types .= 's'; }
$where_sql = 'WHERE ' . implode(' AND ', $where);
$sql = "SELECT b.*, r.room_number, t.name AS room_type FROM bookings b JOIN rooms r ON b.room_id=r.id JOIN room_types t ON r.room_type_id=t.id " . $where_sql . " ORDER BY b.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
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
  <form method="get" class="row gy-2 gx-2 mb-3">
    <div class="col-md-3"><input name="q" value="<?=htmlspecialchars($_GET['q'] ?? '')?>" class="form-control" placeholder="Search room, type, payment"></div>
    <div class="col-md-2">
      <select name="status" class="form-select">
        <option value="">Any status</option>
        <?php foreach(['reserved','confirmed','ongoing','checked_out','canceled'] as $s): ?>
          <option value="<?=$s?>" <?=(!empty($_GET['status']) && $_GET['status']==$s)? 'selected':''?>><?=ucfirst($s)?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><input name="room" value="<?=htmlspecialchars($_GET['room'] ?? '')?>" class="form-control" placeholder="Room #"></div>
    <div class="col-md-2"><input name="from_date" type="date" value="<?=htmlspecialchars($_GET['from_date'] ?? '')?>" class="form-control"></div>
    <div class="col-md-2"><input name="to_date" type="date" value="<?=htmlspecialchars($_GET['to_date'] ?? '')?>" class="form-control"></div>
    <div class="col-md-1"><button class="btn btn-outline-primary w-100">Filter</button></div>
  </form>
  <table class="table table-bordered">
    <thead class="table-dark"><tr><th>Room</th><th>Type</th><th>Start</th><th>End</th><th>Hours</th><th>Total</th><th>Status</th><th>Payment</th><th>Action</th></tr></thead>
    <tbody>
      <?php while($b = $res->fetch_assoc()): ?>
      <tr>
        <td><?=htmlspecialchars($b['room_number'])?></td>
        <td><?=htmlspecialchars($b['room_type'])?></td>
        <td><?=htmlspecialchars($b['start_time'])?></td>
        <td><?=htmlspecialchars($b['end_time'])?></td>
        <td><?=intval($b['hours'])?></td>
    <td>₱<?=number_format($b['total_amount'],2)?></td>
        <td><?=htmlspecialchars($b['status'])?></td>
        <td><?=htmlspecialchars($b['payment_method'])?></td>
        <td>
          <a class="btn btn-sm btn-outline-secondary" href="booking_history.php?booking_id=<?=$b['id']?>">View Details</a>
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
