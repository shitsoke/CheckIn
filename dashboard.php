<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
require_once __DIR__ . '/includes/name_helper.php';
include __DIR__ . "/user_sidebar.php";

$id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT u.first_name, u.middle_name, u.last_name, p.display_name FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!doctype html>
<html lang="en">
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
      --sidebar-width: 200px; /* reduced from 250px */
    }

    body {
      background-color: #f9f5f5;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      overflow-x: hidden;
      margin: 0;
      padding: 0;
      display: flex;
    }

    /* Main content */
    .main-content {
      margin-left: var(--sidebar-width);
      padding: 20px; /* tighter spacing */
      flex: 1;
      width: calc(100% - var(--sidebar-width));
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
    }

    /* Responsive sidebar behavior */
    @media (max-width: 992px) {
      .sidebar {
        position: fixed;
        left: -200px; /* adjusted width */
        transition: left 0.3s ease;
      }

      .sidebar.active {
        left: 0;
      }

      .main-content {
        margin-left: 0;
        width: 100%;
        padding: 15px;
      }

      .toggle-btn {
        display: block;
        position: fixed;
        top: 15px;
        left: 15px;
        background-color: var(--primary-solid);
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 5px;
        z-index: 1001;
      }
    }
  </style>

</head>
<body>

  <!-- Sidebar (from include) -->
  <?php include __DIR__ . "/user_sidebar.php"; ?>

  <!-- Sidebar Toggle for mobile -->
  <button class="toggle-btn d-lg-none"><i class="fas fa-bars"></i></button>

  <!-- Main Content -->
  <div class="main-content">
    <div class="welcome-card">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
          <h2>Welcome, <?= htmlspecialchars(!empty($user['display_name']) ? $user['display_name'] : ($user['first_name'] . ' ' . $user['last_name'])) ?></h2>
          <p class="mb-0">Role: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong></p>
        </div>
        <div class="user-avatar"><?= strtoupper(substr($user['first_name'], 0, 1)) ?></div>
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
            $upcoming = $conn->prepare("SELECT b.id, r.room_number, b.start_time, b.end_time, b.status 
              FROM bookings b 
              JOIN rooms r ON b.room_id=r.id 
              WHERE b.user_id=? AND b.start_time >= NOW() AND b.status IN ('reserved','confirmed') 
              ORDER BY b.start_time ASC LIMIT 5");
            $upcoming->bind_param("i", $id);
            $upcoming->execute();
            $upRes = $upcoming->get_result();
            if ($upRes && $upRes->num_rows): ?>
              <div class="list-group list-group-flush">
                <?php while ($u = $upRes->fetch_assoc()): ?>
                  <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <div class="booking-item">
                      <h6 class="mb-1">Room <?= htmlspecialchars($u['room_number']) ?></h6>
                      <small class="text-muted"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($u['start_time']))) ?> — <?= htmlspecialchars(date('M j, Y g:i A', strtotime($u['end_time']))) ?></small>
                    </div>
                    <div class="d-flex align-items-center">
                      <span class="status-badge status-<?= htmlspecialchars($u['status']) ?> me-2"><?= htmlspecialchars(ucfirst($u['status'])) ?></span>
                      <a class="btn btn-sm btn-outline-primary" href="booking_history.php?booking_id=<?= $u['id'] ?>">
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
              <span class="badge bg-danger rounded-pill p-2"><?= $total['total'] ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <h6 class="mb-0">Upcoming</h6>
                <small class="text-muted">Future bookings</small>
              </div>
              <span class="badge bg-success rounded-pill p-2"><?= $upcoming['upcoming'] ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-0">Reviews</h6>
                <small class="text-muted">Your feedback</small>
              </div>
              <span class="badge bg-warning text-dark rounded-pill p-2"><?= $reviews['reviews'] ?></span>
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
              <a href="browse_rooms.php" class="btn btn-outline-danger text-start"><i class="fas fa-search me-2"></i> Find Available Rooms</a>
              <a href="bookings.php" class="btn btn-outline-danger text-start"><i class="fas fa-history me-2"></i> Booking History</a>
              <a href="reviews.php" class="btn btn-outline-danger text-start"><i class="fas fa-pen me-2"></i> Write a Review</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Mobile sidebar toggle
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.toggle-btn');
    toggleBtn?.addEventListener('click', () => {
      sidebar.classList.toggle('active');
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
