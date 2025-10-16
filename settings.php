<?php
session_start();
require_once "includes/auth_check.php";
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Settings | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container mt-4">
  <h1>Settings</h1>
  <a href="dashboard.php" class="btn btn-secondary mb-3">← Back</a>
    <div class="mb-3"><a href="about.php" class="btn btn-outline-primary">About</a></div>
  <div class="mb-3"><a href="profile.php?return_to=settings.php" class="btn btn-outline-secondary">Edit Profile</a></div>
  <div class="mb-3"><a href="change_password.php?return_to=settings.php" class="btn btn-outline-secondary">Change Password</a></div>
  <p>This is a placeholder settings page. You can add settings controls here (notifications, preferences, password change, etc.).</p>
</div>
</body>
</html>