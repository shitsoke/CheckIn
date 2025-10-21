<?php
// auth_check.php - ensure user is logged in (Check if user is logged in)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: /checkin/login.php");
  exit;
}

// Role-based guard: prevent admins from viewing customer pages and customers from viewing admin pages
$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF']); // normalize
$isAdminArea = strpos($currentPath, '/admin/') !== false;
$role = $_SESSION['role'] ?? null;
// Allowlist: pages on the front-end that admins are permitted to visit (receipt download, view_user maybe)
$frontAllow = [
  '/receipt.php',
  '/view_user.php'
];

if ($role === 'admin' && !$isAdminArea) {
  $basename = '/' . basename($currentPath);
  if (!in_array($basename, $frontAllow, true)) {
    // Admin should not access other front-end customer pages; redirect to admin dashboard
    header('Location: /checkin/admin/index.php');
    exit;
  }
}
if ($role !== 'admin' && $isAdminArea) {
  // Non-admin (customer) trying to open an admin page -> send them to login or dashboard
  header('Location: /checkin/login.php');
  exit;
}
?>
