<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  /* Base colors / spacing */
  :root {
    --admin-red: #8B0000;
    --admin-red-dark: #a40000;
  }

  /* Offcanvas will be used for small screens.
     On large screens we force it to look/behave like a fixed sidebar. */
  .sidebar {
    background-color: var(--admin-red);
    color: #fff;
  }

  .sidebar .offcanvas-header {
    border-bottom: 1px solid rgba(255,255,255,0.06);
  }

  .sidebar .nav-link {
    color: #f8f9fa !important;
    border-radius: 6px;
    margin-bottom: 0.4rem;
  }
  .sidebar .nav-link.active,
  .sidebar .nav-link:hover {
    background-color: var(--admin-red-dark);
  }

  .logout-btn {
    background-color: #fff;
    color: var(--admin-red-dark);
    font-weight: 600;
    border: none;
  }
  .logout-btn:hover { background-color: #f8d7da; }

  /* Content spacing for desktop: keep margin-left in your page CSS too */
  .content {
    padding: 1.5rem;
  }

  /* ---------- Desktop: make offcanvas behave like fixed sidebar ---------- */
  @media (min-width: 992px) {
    /* make the offcanvas appear as a regular & fixed sidebar */
    .offcanvas-lg.sidebar {
      visibility: visible !important;   /* ensure visible */
      transform: none !important;       /* prevent sliding transform */
      position: fixed !important;
      top: 0;
      left: 0;
      width: 250px;
      height: 100vh;
      border-right: 0;
      background-color: var(--admin-red);
      padding-top: 1rem;
    }

    /* offcanvas body should be full height column */
    .offcanvas-lg.sidebar .offcanvas-body {
      display: flex;
      flex-direction: column;
      height: calc(100vh - 56px); /* account for header space */
      padding-top: 0.5rem;
    }

    /* adjust content spacing on desktop */
    .content {
      margin-left: 260px;
      padding: 2rem;
    }

    /* hide the mobile navbar toggle on large screens */
    .navbar-admin-toggle { display: none; }
  }

  /* ---------- Mobile: keep a compact content spacing ---------- */
  @media (max-width: 991.98px) {
    .content { margin-left: 0; padding: 1rem; }
  }
</style>

<!-- Mobile toggle (shows only on small screens) -->
<nav class="navbar navbar-dark bg-danger d-lg-none">
  <div class="container-fluid">
    <button class="btn btn-light text-danger fw-semibold" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
      ☰ Menu
    </button>
    <span class="navbar-text text-white fw-bold">CheckIn Admin</span>
  </div>
</nav>

<!-- Offcanvas sidebar: will be fixed on lg+ due to CSS above -->
<div class="offcanvas-lg offcanvas-start sidebar text-bg-danger" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
  <div class="offcanvas-header d-lg-flex align-items-center justify-content-between">
    <h4 id="adminSidebarLabel" class="fw-bold mb-0">CheckIn Admin</h4>
    <!-- close btn only visible on small screens -->
    <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>

  <div class="offcanvas-body">
    <ul class="nav flex-column mb-3">
      <li><a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">🏠 Dashboard</a></li>
      <li><a href="manage_rooms.php" class="nav-link <?= $current_page == 'manage_rooms.php' ? 'active' : '' ?>">🛏 Manage Rooms</a></li>
      <li><a href="manage_bookings.php" class="nav-link <?= $current_page == 'manage_bookings.php' ? 'active' : '' ?>">📘 Manage Bookings</a></li>
      <li><a href="manage_users.php" class="nav-link <?= $current_page == 'manage_users.php' ? 'active' : '' ?>">👤 Manage Users</a></li>
      <li><a href="manage_reviews.php" class="nav-link <?= $current_page == 'manage_reviews.php' ? 'active' : '' ?>">💬 Moderate Reviews</a></li>
      <li><a href="manage_roomtypes.php" class="nav-link <?= $current_page == 'manage_roomtypes.php' ? 'active' : '' ?>">🏨 Room Types</a></li>
    </ul>

    <div class="mt-auto mb-5">
  <a href="../logout.php" class="text-light btn w-100 fw-semibold">Logout</a>
</div>

  </div>
</div>

<!-- Bootstrap JS (required for offcanvas) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
