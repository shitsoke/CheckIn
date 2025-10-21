<?php
// Reusable customer sidebar for front-end pages
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<div class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h3><i class="fas fa-hotel me-2"></i> CheckIn</h3>
  </div>
  <ul class="sidebar-menu">
    <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
    <li><a href="browse_rooms.php"><i class="fas fa-door-open"></i> <span>Browse Rooms</span></a></li>
    <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> <span>My Bookings</span></a></li>
    <li><a href="reviews.php"><i class="fas fa-star"></i> <span>Leave Review</span></a></li>
    <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
    <li><a href="logout.php" class="mt-4"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
  </ul>
</div>
