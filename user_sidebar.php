<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
?>

<!-- ✅ CheckIn User Sidebar -->
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
/>

<!-- Toggle Button (Mobile) -->
<button class="checkin-user-toggle" id="checkinUserToggle">
  <i class="fas fa-bars"></i>
</button>

<!-- Overlay -->
<div class="checkin-user-overlay" id="checkinUserOverlay"></div>

<!-- Sidebar -->
<aside class="checkin-user-sidebar" id="checkinUserSidebar">
  <div class="checkin-user-header">
    <h2><i class="fas fa-hotel me-2"></i> CheckIn</h2>
  </div>
  <ul class="checkin-user-menu">
    <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
    <li><a href="browse_rooms.php" class="<?= basename($_SERVER['PHP_SELF']) == 'browse_rooms.php' ? 'active' : '' ?>"><i class="fas fa-door-open"></i> Browse Rooms</a></li>
    <li><a href="bookings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
    <li><a href="reviews.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : '' ?>"><i class="fas fa-star"></i> Leave Review</a></li>
    <li><a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
    <li><a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
  </ul>
</aside>

<style>
  /* ==============================
     CHECKIN USER SIDEBAR (FINAL VERSION)
     ============================== */

  .checkin-user-sidebar {
    --checkin-primary: #dc3545;
    --checkin-secondary: #b71c1c;
    --checkin-hover: rgba(255, 255, 255, 0.2);
    --checkin-width: 220px; /* ✅ Slimmer sidebar */

    position: fixed;
    top: 0;
    left: 0;
    width: var(--checkin-width);
    height: 100vh;
    background: linear-gradient(180deg, var(--checkin-primary) 0%, var(--checkin-secondary) 100%);
    color: #fff;
    display: flex;
    flex-direction: column;
    box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
    z-index: 4000;
    transition: transform 0.3s ease-in-out;
  }

  .checkin-user-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    text-align: center;
  }

  .checkin-user-header h2 {
    font-size: 1.4rem;
    margin: 0;
    font-weight: 700;
    letter-spacing: 1px;
    color: #fff;
  }

  .checkin-user-menu {
    list-style: none;
    padding: 0;
    margin: 20px 0;
    flex-grow: 1;
  }

  .checkin-user-menu li {
    margin: 3px 0;
  }

  .checkin-user-menu a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: rgba(255, 255, 255, 0.85);
    text-decoration: none;
    font-size: 0.95rem;
    border-left: 4px solid transparent;
    transition: all 0.25s ease;
  }

  .checkin-user-menu a i {
    font-size: 1.1rem;
    margin-right: 10px;
    width: 22px;
    text-align: center;
  }

  .checkin-user-menu a:hover {
    background-color: var(--checkin-hover);
    border-left: 4px solid #fff;
    color: #fff;
    transform: translateX(3px);
  }

  .checkin-user-menu a.active {
    background-color: var(--checkin-hover);
    border-left: 4px solid #fff;
    color: #fff;
    font-weight: 600;
  }

  .checkin-user-menu a.logout {
    margin-top: auto;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 15px;
  }

  /* ====== TOGGLE BUTTON (MOBILE) ====== */
  .checkin-user-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 4100;
    background: #dc3545;
    border: none;
    color: #fff;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    font-size: 1.2rem;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.25);
    cursor: pointer;
  }

  /* ====== OVERLAY ====== */
  .checkin-user-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    width: 100%;
    background: rgba(0, 0, 0, 0.55);
    z-index: 3900;
  }

  .checkin-user-overlay.active {
    display: block;
  }

  /* ====== FIX MAIN CONTENT SPACING ====== */
  main, .container, .content-wrapper {
    margin-left: calc(var(--checkin-width) + 40px); /* ✅ Add gap to prevent overlap */
    transition: margin-left 0.3s ease;
  }

  /* ====== RESPONSIVE BEHAVIOR ====== */
  @media (max-width: 992px) {
    .checkin-user-sidebar {
      transform: translateX(-100%);
    }

    .checkin-user-sidebar.active {
      transform: translateX(0);
    }

    .checkin-user-toggle {
      display: block;
    }

    main, .container, .content-wrapper {
      margin-left: 0 !important;
    }
  }

  /* Small improvements for touch devices */
  @media (hover: none) {
    .checkin-user-menu a:hover {
      background: none;
      transform: none;
    }
  }
</style>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("checkinUserSidebar");
    const toggleBtn = document.getElementById("checkinUserToggle");
    const overlay = document.getElementById("checkinUserOverlay");

    function toggleSidebar() {
      sidebar.classList.toggle("active");
      overlay.classList.toggle("active");
    }

    toggleBtn.addEventListener("click", toggleSidebar);
    overlay.addEventListener("click", toggleSidebar);
  });
</script>
