<?php
// auth_check.php - ensure user is logged in (Check if user is logged in)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: /checkin/login.php");
  exit;
}
?>
