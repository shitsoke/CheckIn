<?php
session_start();
require_once "../db_connect.php";
include "admin_sidebar.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

// ─────────────────────────────────────────────
//  Placeholder counts — replace with real queries
// ─────────────────────────────────────────────
$available_rooms = 12;  // e.g., SELECT COUNT(*) FROM rooms WHERE status='available'
$reserved_rooms  = 8;   // e.g., SELECT COUNT(*) FROM bookings WHERE status='reserved'
$ongoing_rooms   = 5;   // e.g., SELECT COUNT(*) FROM bookings WHERE status='confirmed' OR 'ongoing'
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .stat-card {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }
    .stat-icon {
      font-size: 2.5rem;
      margin-bottom: 0.5rem;
    }
  </style>
</head>

<body>
  <div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold text-danger">Admin Dashboard</h3>
    </div>
    <div class="text-center text-muted mb-3" style="font-size: 1.1rem;">
  <p class="mb-1">
    Welcome back, 
    <strong style="font-size: 1.2rem; color: #b71c1c;">
      <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
    </strong>!
  </p>
  <small style="font-size: 1rem;">Use the sidebar to manage rooms, users, and bookings.</small>
</div>
    <div class="row g-4 mb-4">
      <!-- Available Rooms -->
      <div class="col-md-4">
        <div class="card stat-card text-center border-success shadow-sm">
          <div class="card-body">
            <div class="stat-icon text-success">🟢</div>
            <h5 class="card-title text-success">Available Rooms</h5>
            <h2 class="fw-bold"><?= $available_rooms ?></h2>
            <p class="text-muted mb-0">Rooms ready for booking</p>
          </div>
        </div>
      </div>

      <!-- Reserved Rooms -->
      <div class="col-md-4">
        <div class="card stat-card text-center border-warning shadow-sm">
          <div class="card-body">
            <div class="stat-icon text-warning">🟡</div>
            <h5 class="card-title text-warning">Reserved Rooms</h5>
            <h2 class="fw-bold"><?= $reserved_rooms ?></h2>
            <p class="text-muted mb-0">Awaiting guest check-in</p>
          </div>
        </div>
      </div>

      <!-- Confirmed / Ongoing Rooms -->
      <div class="col-md-4">
        <div class="card stat-card text-center border-danger shadow-sm">
          <div class="card-body">
            <div class="stat-icon text-danger">🔴</div>
            <h5 class="card-title text-danger">Confirmed / Ongoing</h5>
            <h2 class="fw-bold"><?= $ongoing_rooms ?></h2>
            <p class="text-muted mb-0">Currently occupied rooms</p>
          </div>
        </div>
      </div>
    </div>

    <hr class="mb-4">
  </div>
</body>
</html>
