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
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: rgba(220, 53, 69, 0.85);
      --primary-solid: #dc3545;
      --secondary-color: #c82333;
      --accent-color: #ff7b7b;
      --light-bg: #f8f9fa;
      --dark-bg: #212529;
      --sidebar-width: 250px;
      --sidebar-width-collapsed: 70px;
    }
    
    body {
      background-color: #f9f5f5;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      overflow-x: hidden;
    }
    
    /* Sidebar Styles */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: var(--sidebar-width);
      background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      padding: 20px 0;
      box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      transition: all 0.3s ease;
    }
    
    .sidebar-header {
      padding: 0 20px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      margin-bottom: 20px;
    }
    
    .sidebar-header h3 {
      font-weight: 700;
      margin-bottom: 0;
      white-space: nowrap;
      overflow: hidden;
    }
    
    .sidebar-menu {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .sidebar-menu li {
      margin-bottom: 5px;
    }
    
    .sidebar-menu a {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: rgba(255, 255, 255, 0.9);
      text-decoration: none;
      transition: all 0.2s ease;
      border-left: 3px solid transparent;
      white-space: nowrap;
      overflow: hidden;
    }
    
    .sidebar-menu a:hover, .sidebar-menu a.active {
      background-color: rgba(255, 255, 255, 0.15);
      color: white;
      border-left: 3px solid var(--accent-color);
    }
    
    .sidebar-menu i {
      margin-right: 10px;
      font-size: 18px;
      width: 24px;
      text-align: center;
      flex-shrink: 0;
    }
    
    /* Mobile Toggle Button */
    .sidebar-toggle {
      display: none;
      position: fixed;
      top: 15px;
      left: 15px;
      z-index: 1001;
      background: var(--primary-solid);
      border: none;
      color: white;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      font-size: 1.2rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    
    /* Main Content */
    .main-content {
      margin-left: var(--sidebar-width);
      padding: 30px;
      transition: all 0.3s ease;
    }
    
    .welcome-card {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      border-radius: 15px;
      box-shadow: 0 10px 20px rgba(220, 53, 69, 0.2);
      padding: 25px;
      margin-bottom: 30px;
    }
    
    .card {
      border-radius: 15px;
      border: none;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      margin-bottom: 25px;
      border-top: 3px solid var(--primary-solid);
    }
    
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(220, 53, 69, 0.1);
    }
    
    .card-header {
      background-color: white;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      border-radius: 15px 15px 0 0 !important;
      padding: 15px 20px;
      font-weight: 600;
      color: var(--primary-solid);
    }
    
    .quick-actions .btn {
      border-radius: 10px;
      padding: 12px 20px;
      margin: 5px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-primary {
      background-color: var(--primary-solid);
      border-color: var(--primary-solid);
    }
    
    .btn-primary:hover {
      background-color: var(--secondary-color);
      border-color: var(--secondary-color);
      transform: translateY(-3px);
    }
    
    .btn-success {
      background-color: #28a745;
      border-color: #28a745;
    }
    
    .btn-warning {
      background-color: #ffc107;
      border-color: #ffc107;
      color: #212529;
    }
    
    .btn-secondary {
      background-color: #6c757d;
      border-color: #6c757d;
    }
    
    .quick-actions .btn:hover {
      transform: translateY(-3px);
    }
    
    .booking-item {
      border-left: 4px solid var(--primary-solid);
      padding-left: 15px;
      margin-bottom: 15px;
    }
    
    .status-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }
    
    .status-reserved {
      background-color: rgba(220, 53, 69, 0.1);
      color: var(--primary-solid);
    }
    
    .status-confirmed {
      background-color: rgba(40, 167, 69, 0.1);
      color: #28a745;
    }
    
    .user-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background-color: var(--accent-color);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 1.2rem;
      flex-shrink: 0;
    }
    
    .badge-primary {
      background-color: var(--primary-solid);
    }
    
    .badge-success {
      background-color: #28a745;
    }
    
    .badge-warning {
      background-color: #ffc107;
      color: #212529;
    }
    
    .btn-outline-primary {
      color: var(--primary-solid);
      border-color: var(--primary-solid);
    }
    
    .btn-outline-primary:hover {
      background-color: var(--primary-solid);
      border-color: var(--primary-solid);
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
        width: var(--sidebar-width);
      }
      
      .sidebar.active {
        transform: translateX(0);
      }
      
      .sidebar-toggle {
        display: block;
      }
      
      .main-content {
        margin-left: 0;
        padding: 60px 15px 15px;
      }
      
      .welcome-card {
        padding: 20px;
      }
      
      .welcome-card h2 {
        font-size: 1.5rem;
      }
      
      .quick-actions .btn {
        width: 100%;
        margin: 5px 0;
      }
    }
    
    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        z-index: 1002;
      }
      
      .sidebar-header h3 {
        font-size: 1.5rem;
      }
      
      .main-content {
        padding: 60px 10px 10px;
      }
      
      .card-body {
        padding: 15px;
      }
      
      .welcome-card {
        text-align: center;
        padding: 15px;
      }
      
      .welcome-card .d-flex {
        flex-direction: column;
      }
      
      .user-avatar {
        margin-top: 10px;
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
      }
      
      .booking-item h6 {
        font-size: 0.9rem;
      }
      
      .booking-item small {
        font-size: 0.75rem;
      }
      
      .status-badge {
        font-size: 0.7rem;
        padding: 4px 8px;
      }
      
      .list-group-item {
        flex-direction: column;
        align-items: flex-start !important;
      }
      
      .list-group-item .d-flex {
        width: 100%;
        justify-content: space-between;
        margin-top: 10px;
      }
    }
    
    @media (max-width: 576px) {
      .welcome-card h2 {
        font-size: 1.3rem;
      }
      
      .card-header {
        padding: 12px 15px;
        font-size: 0.9rem;
      }
      
      .card-header i {
        margin-right: 5px;
      }
      
      .quick-actions .btn {
        padding: 10px 15px;
        font-size: 0.9rem;
      }
      
      .btn-sm {
        padding: 5px 8px;
        font-size: 0.8rem;
      }
      
      .badge {
        font-size: 0.7rem;
      }
    }
    
    /* Overlay for mobile sidebar */
    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 999;
    }
    
    .sidebar-overlay.active {
      display: block;
    }
  </style>
</head>
<body>
  <!-- Mobile Sidebar Toggle -->
  <button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
  </button>
  
  <!-- Sidebar Overlay for Mobile -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Sidebar -->
  <?php require_once __DIR__ . '/includes/customer_sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content" id="mainContent">
    <!-- Welcome Card -->
    <div class="welcome-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h2>Welcome, <?=htmlspecialchars(!empty($user['display_name']) ? $user['display_name'] : ($user['first_name'].' '.$user['last_name']))?></h2>
          <p class="mb-0">Role: <strong><?=htmlspecialchars($_SESSION['role'])?></strong></p>
        </div>
        <div class="user-avatar">
          <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-bolt me-2"></i> Quick Actions
      </div>
      <div class="card-body">
        <div class="quick-actions d-flex flex-wrap">
          <a href="browse_rooms.php" class="btn btn-primary"><i class="fas fa-door-open me-2"></i> Browse Rooms</a>
          <a href="bookings.php" class="btn btn-success"><i class="fas fa-calendar-check me-2"></i> My Bookings</a>
          <a href="reviews.php" class="btn btn-warning"><i class="fas fa-star me-2"></i> Leave Review</a>
          <a href="settings.php" class="btn btn-secondary"><i class="fas fa-cog me-2"></i> Settings</a>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Upcoming Bookings -->
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <i class="fas fa-calendar-alt me-2"></i> Upcoming Bookings
          </div>
          <div class="card-body">
            <?php
            // show upcoming bookings for this user
            // Show only upcoming bookings that are reserved or confirmed (exclude checked_out or canceled)
            $upcoming = $conn->prepare("SELECT b.id, r.room_number, b.start_time, b.end_time, b.status FROM bookings b JOIN rooms r ON b.room_id=r.id WHERE b.user_id=? AND b.start_time >= NOW() AND b.status IN ('reserved','confirmed') ORDER BY b.start_time ASC LIMIT 5");
            $upcoming->bind_param("i", $id);
            $upcoming->execute();
            $upRes = $upcoming->get_result();
            if ($upRes && $upRes->num_rows): ?>
              <div class="list-group list-group-flush">
                <?php while($u = $upRes->fetch_assoc()): ?>
                  <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <div class="booking-item">
                      <h6 class="mb-1">Room <?=htmlspecialchars($u['room_number'])?></h6>
                      <small class="text-muted"><?=htmlspecialchars(date('M j, Y g:i A', strtotime($u['start_time'])))?> — <?=htmlspecialchars(date('M j, Y g:i A', strtotime($u['end_time'])))?></small>
                    </div>
                    <div class="d-flex align-items-center">
                      <span class="status-badge status-<?=htmlspecialchars($u['status'])?> me-2"><?=htmlspecialchars(ucfirst($u['status']))?></span>
                      <a class="btn btn-sm btn-outline-primary" href="booking_history.php?booking_id=<?=$u['id']?>">
                        <i class="fas fa-info-circle"></i>
                      </a>
                    </div>
                  </div>
                <?php endwhile; ?>
              </div>
            <?php else: ?>
              <p class="text-muted text-center py-3">No upcoming bookings found.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Stats Card -->
      <div class="col-lg-4">
        <div class="card">
          <div class="card-header">
            <i class="fas fa-chart-bar me-2"></i> Your Stats
          </div>
          <div class="card-body">
            <?php
            // Get stats for the user
            $totalBookings = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id=?");
            $totalBookings->bind_param("i", $id);
            $totalBookings->execute();
            $total = $totalBookings->get_result()->fetch_assoc();
            
            $upcomingCount = $conn->prepare("SELECT COUNT(*) as upcoming FROM bookings WHERE user_id=? AND start_time >= NOW() AND status IN ('reserved','confirmed')");
            $upcomingCount->bind_param("i", $id);
            $upcomingCount->execute();
            $upcoming = $upcomingCount->get_result()->fetch_assoc();
            
            $reviewsCount = $conn->prepare("SELECT COUNT(*) as reviews FROM reviews WHERE user_id=?");
            $reviewsCount->bind_param("i", $id);
            $reviewsCount->execute();
            $reviews = $reviewsCount->get_result()->fetch_assoc();
            ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <h6 class="mb-0">Total Bookings</h6>
                <small class="text-muted">All time</small>
              </div>
              <span class="badge badge-primary rounded-pill p-2"><?= $total['total'] ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <h6 class="mb-0">Upcoming</h6>
                <small class="text-muted">Future bookings</small>
              </div>
              <span class="badge badge-success rounded-pill p-2"><?= $upcoming['upcoming'] ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-0">Reviews</h6>
                <small class="text-muted">Your feedback</small>
              </div>
              <span class="badge badge-warning rounded-pill p-2"><?= $reviews['reviews'] ?></span>
            </div>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="card mt-4">
          <div class="card-header">
            <i class="fas fa-link me-2"></i> Quick Links
          </div>
          <div class="card-body">
            <div class="d-grid gap-2">
              <a href="browse_rooms.php" class="btn btn-outline-primary text-start"><i class="fas fa-search me-2"></i> Find Available Rooms</a>
              <a href="bookings.php" class="btn btn-outline-primary text-start"><i class="fas fa-history me-2"></i> Booking History</a>
              <a href="reviews.php" class="btn btn-outline-primary text-start"><i class="fas fa-pen me-2"></i> Write a Review</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Mobile sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
      const sidebar = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebarOverlay = document.getElementById('sidebarOverlay');
      const mainContent = document.getElementById('mainContent');
      
      function toggleSidebar() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
      }
      
      sidebarToggle.addEventListener('click', toggleSidebar);
      sidebarOverlay.addEventListener('click', toggleSidebar);
      
      // Close sidebar when clicking on a menu item (on mobile)
      if (window.innerWidth <= 992) {
        const menuItems = document.querySelectorAll('.sidebar-menu a');
        menuItems.forEach(item => {
          item.addEventListener('click', function() {
            if (sidebar.classList.contains('active')) {
              toggleSidebar();
            }
          });
        });
      }
      
      // Adjust layout on window resize
      window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
          sidebar.classList.remove('active');
          sidebarOverlay.classList.remove('active');
        }
      });
    });
  </script>
</body>
</html>