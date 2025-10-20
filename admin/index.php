<?php
session_start();
require_once "../db_connect.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #ffffff;
    }
    .sidebar {
      min-height: 100vh;
      background-color: #b30000; /* deep red */
      color: white;
      position: fixed;
      top: 0;
      left: 0;
      width: 220px;
      padding-top: 20px;
    }
    .sidebar a {
      color: white;
      text-decoration: none;
    }
    .sidebar a:hover {
      background-color: #990000;
      border-radius: 5px;
    }
    .sidebar .nav-link.active {
      background-color: #990000;
      border-radius: 5px;
    }
    .content {
      margin-left: 240px;
      padding: 20px;
    }
  </style>
</head>

<body>
  <div class="sidebar d-flex flex-column p-3">
    
    <h4 class="text-center mb-4">CheckIn Admin</h4>
    <ul class="nav flex-column">
      <li class="nav-item mb-2">
        <a href="admin_dashboard.php" class="nav-link active text-white">🏠 Dashboard</a>
      </li>
      <li class="nav-item mb-2">
        <a href="manage_rooms.php" class="nav-link text-white">🛏 Manage Rooms</a>
      </li>
      <li class="nav-item mb-2">
        <a href="manage_bookings.php" class="nav-link text-white">📘 Manage Bookings</a>
      </li>
      <li class="nav-item mb-2">
        <a href="manage_users.php" class="nav-link text-white">👤 Manage Users</a>
      </li>
      <li class="nav-item mb-2">
        <a href="manage_reviews.php" class="nav-link text-white">💬 Moderate Reviews</a>
      </li>
      <li class="nav-item mb-2">
        <a href="manage_roomtypes.php" class="nav-link text-white">🏨 Room Types</a>
      </li>
    </ul>
    <hr>
    <a href="../logout.php" class="btn btn-light text-danger mt-auto">Logout</a>
  </div>

  <div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Admin Dashboard</h3>
    </div>
    <hr>

    <div class="row g-3">
      <div class="col-md-4">
        <div class="card border-danger shadow-sm">
          <div class="card-body text-center">
            <h5 class="card-title text-danger">Manage Rooms</h5>
            <p class="card-text">Add, update, or delete available rooms.</p>
            <a href="manage_rooms.php" class="btn btn-danger">Go</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-danger shadow-sm">
          <div class="card-body text-center">
            <h5 class="card-title text-danger">Manage Bookings</h5>
            <p class="card-text">View and update customer bookings.</p>
            <a href="manage_bookings.php" class="btn btn-danger">Go</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-danger shadow-sm">
          <div class="card-body text-center">
            <h5 class="card-title text-danger">Manage Users</h5>
            <p class="card-text">Handle user accounts and roles.</p>
            <a href="manage_users.php" class="btn btn-danger">Go</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-danger shadow-sm">
          <div class="card-body text-center">
            <h5 class="card-title text-danger">Moderate Reviews</h5>
            <p class="card-text">Approve or remove user feedback.</p>
            <a href="manage_reviews.php" class="btn btn-danger">Go</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-danger shadow-sm">
          <div class="card-body text-center">
            <h5 class="card-title text-danger">Manage Room Types</h5>
            <p class="card-text">Configure different room categories.</p>
            <a href="manage_roomtypes.php" class="btn btn-danger">Go</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
