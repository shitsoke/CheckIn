<?php
session_start();
require_once "includes/auth_check.php";
include __DIR__ . "/user_sidebar.php";
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Settings | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #dc3545;
      --primary-hover: #c82333;
      --primary-light: rgba(220, 53, 69, 0.1);
    }
    
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .container {
      max-width: 800px;
    }
    
    .settings-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      padding: 30px;
      margin-top: 20px;
    }
    
    .page-title {
      color: var(--primary-color);
      font-weight: 700;
      margin-bottom: 5px;
    }
    
    .page-subtitle {
      color: #666;
      font-size: 1.1rem;
      margin-bottom: 30px;
    }
    
    /* Button Styles */
    .btn-back {
      background: var(--primary-color);
      border: none;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      margin-bottom: 30px;
    }
    
    .btn-back:hover {
      background: var(--primary-hover);
      color: white;
      transform: translateY(-2px);
    }
    
    .btn-settings {
      background: white;
      border: 2px solid var(--primary-color);
      color: var(--primary-color);
      padding: 15px 20px;
      border-radius: 10px;
      font-weight: 600;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.3s ease;
      margin-bottom: 15px;
      width: 100%;
      text-align: left;
    }
    
    .btn-settings:hover {
      background: var(--primary-color);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
    
    .btn-settings i {
      font-size: 1.2rem;
      width: 24px;
      text-align: center;
    }
    
    .settings-section {
      margin-bottom: 30px;
    }
    
    .section-title {
      color: var(--primary-color);
      font-weight: 600;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 2px solid var(--primary-light);
    }
    
    .info-text {
      background: var(--primary-light);
      border-left: 4px solid var(--primary-color);
      padding: 15px;
      border-radius: 8px;
      margin-top: 30px;
    }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4">
  <!-- Header Section -->
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <h1 class="page-title"><i class="fas fa-cog me-2"></i>Settings</h1>
      <p class="page-subtitle">Manage your account preferences and profile settings</p>
    </div>
  </div>

  <!-- Settings Card -->
  <div class="settings-card">
    <!-- Profile Settings Section -->
    <div class="settings-section">
      <h3 class="section-title"><i class="fas fa-user me-2"></i>Profile Settings</h3>
      <a href="profile.php?return_to=settings.php" class="btn-settings">
        <i class="fas fa-user-edit"></i>
        <div>
          <div class="fw-bold">Edit Profile</div>
          <small class="opacity-75">Update your personal information and display name</small>
        </div>
      </a>
      
      <a href="change_password.php?return_to=settings.php" class="btn-settings">
        <i class="fas fa-key"></i>
        <div>
          <div class="fw-bold">Change Password</div>
          <small class="opacity-75">Update your account password for security</small>
        </div>
      </a>
    </div>

    <!-- Application Settings Section -->
    <div class="settings-section">
      <h3 class="section-title"><i class="fas fa-info-circle me-2"></i>About & Information</h3>
      <a href="about.php" class="btn-settings">
        <i class="fas fa-info-circle"></i>
        <div>
          <div class="fw-bold">About CheckIn</div>
          <small class="opacity-75">Learn more about our hotel booking system</small>
        </div>
      </a>
    </div>

    <!-- Additional Settings (Placeholder for future features) -->
    <div class="settings-section">
      <h3 class="section-title"><i class="fas fa-bell me-2"></i>Preferences</h3>
      <div class="btn-settings" style="cursor: not-allowed; opacity: 0.6;">
        <i class="fas fa-bell"></i>
        <div>
          <div class="fw-bold">Notification Settings</div>
          <small class="opacity-75">Manage your email and push notifications (Coming Soon)</small>
        </div>
      </div>
      
      <div class="btn-settings" style="cursor: not-allowed; opacity: 0.6;">
        <i class="fas fa-moon"></i>
        <div>
          <div class="fw-bold">Appearance</div>
          <small class="opacity-75">Customize theme and display settings (Coming Soon)</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Information Text -->
  <div class="info-text">
    <p class="mb-0"><strong>Need help?</strong> Contact our support team if you need assistance with any settings or have questions about your account.</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>