<?php
session_start();
require_once "includes/auth_check.php";
require_once "includes/csrf.php";
require_once "db_connect.php";

$user_id = $_SESSION['user_id'];
$msg = "";

// fetch user & profile
$stmt = $conn->prepare("SELECT u.first_name, u.middle_name, u.last_name, u.email, p.phone, p.address, p.avatar, p.display_name
  FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $phone = trim($_POST['phone'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $display_name = trim($_POST['display_name'] ?? '');
  $first = trim($_POST['first_name'] ?? '');
  $middle = trim($_POST['middle_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');

  // file upload handling
  $updated = false;
  if (!empty($_FILES['avatar']['name'])) {
    $allowed = ['image/jpeg','image/png','image/webp'];
    if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
      $msg = "File upload error.";
    } elseif (!in_array(mime_content_type($_FILES['avatar']['tmp_name']), $allowed)) {
      $msg = "Only JPG/PNG/WEBP allowed.";
    } elseif ($_FILES['avatar']['size'] > 2*1024*1024) {
      $msg = "Max file size 2MB.";
    } else {
      $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
      $targetDir = __DIR__ . "/uploads/avatars";
      if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
      $filename = 'avatar_'.$user_id.'_'.time().'.'.$ext;
      $dest = $targetDir.'/'.$filename;
      if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
          $avatarPath = 'uploads/avatars/'.$filename;
          $u = $conn->prepare("UPDATE profiles SET phone=?, address=?, avatar=?, display_name=? WHERE user_id=?");
          $u->bind_param("ssssi", $phone, $address, $avatarPath, $display_name, $user_id);
          $u->execute();
          // update names on users table
          $uu = $conn->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=? WHERE id=?");
          $uu->bind_param("sssi", $first, $middle, $last, $user_id); $uu->execute();
        $updated = true;
        $msg = "Profile updated successfully!";
      } else $msg = "Failed to save uploaded file.";
    }
  } else {
    // update without avatar, but only if changed
    if ($phone !== ($user['phone'] ?? '') || $address !== ($user['address'] ?? '') || $display_name !== ($user['display_name'] ?? '') || $first !== ($user['first_name'] ?? '') || $middle !== ($user['middle_name'] ?? '') || $last !== ($user['last_name'] ?? '')) {
      $u = $conn->prepare("UPDATE profiles SET phone=?, address=?, display_name=? WHERE user_id=?");
      $u->bind_param("sssi", $phone, $address, $display_name, $user_id);
      $u->execute();
      // update names in users table
      $uu = $conn->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=? WHERE id=?");
      $uu->bind_param("sssi", $first, $middle, $last, $user_id); $uu->execute();
      $updated = true;
      $msg = "Profile updated successfully!";
    } else {
      $msg = "No changes detected.";
    }
  }
  // refresh user data
  if ($updated) {
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Profile | CheckIn</title>
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
    
    .profile-card {
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
    .btn-primary {
      background: var(--primary-color);
      border: none;
      height: 45px;
      font-weight: 600;
      color: white;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 25px;
    }

    .btn-primary:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }

    .btn-secondary {
      background: #6c757d;
      border: none;
      height: 45px;
      font-weight: 600;
      color: white;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 25px;
    }

    .btn-secondary:hover {
      background: #545b62;
      transform: translateY(-2px);
    }

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

    .form-control:focus, .form-select:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .alert-info {
      border-left: 4px solid var(--primary-color);
      background: var(--primary-light);
    }
    
    .avatar-container {
      position: relative;
      display: inline-block;
    }
    
    .avatar-upload {
      position: absolute;
      bottom: 10px;
      right: 10px;
      background: var(--primary-color);
      color: white;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .avatar-upload:hover {
      background: var(--primary-hover);
      transform: scale(1.1);
    }
    
    .avatar-preview {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--primary-color);
    }
    
    .avatar-placeholder {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background: var(--primary-light);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-color);
      font-size: 48px;
      border: 3px solid var(--primary-color);
    }
    
    .form-label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
    }
    
    .section-divider {
      border-top: 2px solid var(--primary-light);
      margin: 25px 0;
    }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4">
  <!-- Header Section -->
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <h1 class="page-title"><i class="fas fa-user-edit me-2"></i>Edit Profile</h1>
      <p class="page-subtitle">Update your personal information and profile settings</p>
    </div>
    <?php $ret = $_GET['return_to'] ?? 'dashboard.php'; ?>
    <a href="<?=htmlspecialchars($ret)?>" class="btn-back">
      <i class="fas fa-arrow-left"></i>
      Back
    </a>
  </div>

  <!-- Profile Card -->
  <div class="profile-card">
    <?php if($msg): ?>
      <div class="alert alert-info d-flex align-items-center">
        <i class="fas fa-info-circle me-2"></i>
        <?=$msg?>
      </div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data">
      <?=csrf_input_field()?>
      
      <!-- Avatar Section -->
      <div class="row mb-4">
        <div class="col-md-3 text-center">
          <div class="avatar-container">
            <?php if(!empty($user['avatar'])): ?>
              <img src="<?=htmlspecialchars($user['avatar'])?>" class="avatar-preview">
            <?php else: ?>
              <div class="avatar-placeholder">👤</div>
            <?php endif; ?>
            <label for="avatar-upload" class="avatar-upload">
              <i class="fas fa-camera"></i>
            </label>
            <input type="file" name="avatar" id="avatar-upload" class="d-none" accept="image/*">
          </div>
          <small class="text-muted mt-2 d-block">Click camera icon to upload</small>
        </div>
        
        <div class="col-md-9">
          <!-- Email (Disabled) -->
          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input disabled class="form-control" value="<?=htmlspecialchars($user['email'])?>">
            <small class="text-muted">Email cannot be changed</small>
          </div>
          
          <!-- Display Name -->
          <div class="mb-3">
            <label class="form-label">Display Name</label>
            <input name="display_name" class="form-control" value="<?=htmlspecialchars($user['display_name'] ?? '')?>" placeholder="How your name will appear to others">
            <small class="text-muted">Optional - leave blank to use your real name</small>
          </div>
        </div>
      </div>

      <div class="section-divider"></div>

      <!-- Personal Information -->
      <h5 class="mb-3" style="color: var(--primary-color);"><i class="fas fa-user me-2"></i>Personal Information</h5>
      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label">First Name</label>
          <input name="first_name" class="form-control" value="<?=htmlspecialchars($user['first_name'])?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Middle Name</label>
          <input name="middle_name" class="form-control" value="<?=htmlspecialchars($user['middle_name'])?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Last Name</label>
          <input name="last_name" class="form-control" value="<?=htmlspecialchars($user['last_name'])?>" required>
        </div>
      </div>

      <!-- Contact Information -->
      <h5 class="mb-3 mt-4" style="color: var(--primary-color);"><i class="fas fa-phone me-2"></i>Contact Information</h5>
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Phone Number</label>
          <input name="phone" class="form-control" value="<?=htmlspecialchars($user['phone'])?>" placeholder="Your contact number">
        </div>
        <div class="col-md-6">
          <label class="form-label">Address</label>
          <input name="address" class="form-control" value="<?=htmlspecialchars($user['address'])?>" placeholder="Your current address">
        </div>
      </div>

      <!-- Avatar Upload -->
      <h5 class="mb-3 mt-4" style="color: var(--primary-color);"><i class="fas fa-image me-2"></i>Profile Picture</h5>
      <div class="mb-4">
        <label class="form-label">Upload New Avatar</label>
        <input type="file" name="avatar" class="form-control" accept="image/*">
        <small class="text-muted">JPG, PNG, or WEBP format. Maximum file size: 2MB</small>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary">
          <i class="fas fa-save me-2"></i>Save Changes
        </button>
        <?php $ret = $_GET['return_to'] ?? 'dashboard.php'; ?>
        <a href="<?=htmlspecialchars($ret)?>" class="btn btn-secondary">
          <i class="fas fa-times me-2"></i>Cancel
        </a>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Preview avatar before upload
  document.getElementById('avatar-upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const avatarPreview = document.querySelector('.avatar-preview') || document.querySelector('.avatar-placeholder');
        if (avatarPreview) {
          if (avatarPreview.classList.contains('avatar-placeholder')) {
            avatarPreview.outerHTML = `<img src="${e.target.result}" class="avatar-preview">`;
          } else {
            avatarPreview.src = e.target.result;
          }
        }
      }
      reader.readAsDataURL(file);
    }
  });
</script>
</body>
</html>