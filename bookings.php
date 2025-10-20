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
<head>
  <meta charset="utf-8">
  <title>My Bookings | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #dc3545;
      --primary-hover: #c82333;
      --primary-light: rgba(220, 53, 69, 0.1);
    }
    
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .container {
      max-width: 1400px;
    }
    .table {
      background-color: white;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border-radius: 8px;
      overflow: hidden;
    }
    .table thead {
      background: var(--primary-color);
    }
    .table thead th {
      color: white;
      border: none;
      padding: 15px 12px;
      font-weight: 600;
    }
    .table tbody tr:hover {
      background-color: var(--primary-light);
    }
    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }
    .btn-primary:hover {
      background-color: var(--primary-hover);
      border-color: var(--primary-hover);
    }
    .btn-outline-primary {
      border-color: var(--primary-color);
      color: var(--primary-color);
    }
    .btn-outline-primary:hover {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      color: white;
    }
    .btn-back {
      background: var(--primary-color);
      border: none;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
    }
    .btn-back:hover {
      background: var(--primary-hover);
      color: white;
      transform: translateY(-2px);
    }
    h3 {
      color: var(--primary-color);
      font-weight: 700;
      margin-bottom: 5px;
    }
    .page-subtitle {
      color: #666;
      font-size: 1rem;
      margin-bottom: 20px;
    }
    .form-control, .form-select {
      border-radius: 6px;
      border: 1px solid #ddd;
      padding: 8px 12px;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    .badge.bg-primary {
      background-color: var(--primary-color) !important;
    }
    .filter-section {
      background: linear-gradient(135deg, var(--primary-color) 0%, #c82333 100%);
      color: white;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .filter-section h5 {
      color: white;
      margin-bottom: 15px;
    }
    .header-section {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 25px;
      padding: 0;
    }
    .header-content {
      flex: 1;
    }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4">
  <!-- Header Section with Back Button -->
  <div class="header-section">
    <div class="header-content">
      <h3><i class="fas fa-calendar-check"></i> My Bookings</h3>
      <p class="page-subtitle">Manage and view all your booking history</p>
    </div>
    <a href="dashboard.php" class="btn-back">
      <i class="fas fa-arrow-left"></i>
      Back to Dashboard
    </a>
  </div>
  
  <!-- Search and Filter Form -->
  <div class="filter-section">
    <h5><i class="fas fa-search me-2"></i>Search & Filter Bookings</h5>
    <form method="get" class="row gy-2 gx-2">
      <div class="col-md-3">
        <input name="q" value="<?=htmlspecialchars($_GET['q'] ?? '')?>" class="form-control" placeholder="Search room, type, payment">
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">Any status</option>
          <?php foreach(['reserved','confirmed','ongoing','checked_out','canceled'] as $s): ?>
            <option value="<?=$s?>" <?=(!empty($_GET['status']) && $_GET['status']==$s)? 'selected':''?>><?=ucfirst($s)?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input name="room" value="<?=htmlspecialchars($_GET['room'] ?? '')?>" class="form-control" placeholder="Room #">
      </div>
      <div class="col-md-2">
        <input name="from_date" type="date" value="<?=htmlspecialchars($_GET['from_date'] ?? '')?>" class="form-control" placeholder="From date">
      </div>
      <div class="col-md-2">
        <input name="to_date" type="date" value="<?=htmlspecialchars($_GET['to_date'] ?? '')?>" class="form-control" placeholder="To date">
      </div>
      <div class="col-md-1">
        <button class="btn btn-light w-100" style="color: var(--primary-color); font-weight: 600;">
          <i class="fas fa-filter"></i>
        </button>
      </div>
    </form>
  </div>

  <!-- Bookings Table -->
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead class="table-dark">
        <tr>
          <th>Room</th>
          <th>Type</th>
          <th>Start</th>
          <th>End</th>
          <th>Hours</th>
          <th>Total</th>
          <th>Status</th>
          <th>Payment</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while($b = $res->fetch_assoc()): ?>
        <tr>
          <td><?=htmlspecialchars($b['room_number'])?></td>
          <td><?=htmlspecialchars($b['room_type'])?></td>
          <td><?=htmlspecialchars($b['start_time'])?></td>
          <td><?=htmlspecialchars($b['end_time'])?></td>
          <td><?=intval($b['hours'])?></td>
          <td>₱<?=number_format($b['total_amount'],2)?></td>
          <td>
            <span class="badge 
              <?= $b['status'] == 'confirmed' ? 'bg-success' : '' ?>
              <?= $b['status'] == 'reserved' ? 'bg-warning text-dark' : '' ?>
              <?= $b['status'] == 'ongoing' ? 'bg-primary' : '' ?>
              <?= $b['status'] == 'checked_out' ? 'bg-info' : '' ?>
              <?= $b['status'] == 'canceled' ? 'bg-danger' : '' ?>
            ">
              <?=htmlspecialchars($b['status'])?>
            </span>
          </td>
          <td><?=htmlspecialchars($b['payment_method'])?></td>
          <td>
            <div class="d-flex flex-column gap-1">
              <a class="btn btn-sm btn-outline-primary" href="booking_history.php?booking_id=<?=$b['id']?>">
                <i class="fas fa-eye me-1"></i>View Details
              </a>
              <?php if(in_array($b['status'], ['confirmed','checked_out','ongoing'])): ?>
                <a class="btn btn-sm btn-primary" href="receipt.php?booking_id=<?=$b['id']?>&download=pdf">
                  <i class="fas fa-download me-1"></i>Download Receipt
                </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <?php if($res->num_rows === 0): ?>
    <div class="text-center py-5">
      <div class="mb-4">
        <i class="fas fa-calendar-times" style="font-size: 4rem; color: #dc3545;"></i>
      </div>
      <h4 class="text-muted">No bookings found</h4>
      <p class="text-muted">Try adjusting your search filters or make a new booking</p>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>