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
        $msg = "Profile updated.";
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
      $msg = "Profile updated.";
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
<html><head><meta charset="utf-8"><title>Profile | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container mt-4 col-md-6">
  <h3>My Profile</h3>
  <?php if($msg): ?><div class="alert alert-info"><?=$msg?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?=csrf_input_field()?>
    <div class="row mb-3">
      <div class="col-md-3 text-center">
        <?php if(!empty($user['avatar'])): ?>
          <img src="<?=htmlspecialchars($user['avatar'])?>" class="rounded-circle" style="width:120px;height:120px;object-fit:cover">
        <?php else: ?>
          <div style="width:120px;height:120px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#777;font-size:48px">👤</div>
        <?php endif; ?>
      </div>
      <div class="col-md-9">
        <div class="mb-2"><label>Email</label><input disabled class="form-control" value="<?=htmlspecialchars($user['email'])?>"></div>
        <div class="mb-2"><label>Display name (optional)</label><input name="display_name" class="form-control mb-2" value="<?=htmlspecialchars($user['display_name'] ?? '')?>" placeholder="How your name will appear"></div>
        <div class="row">
          <div class="col"><input name="first_name" class="form-control mb-2" value="<?=htmlspecialchars($user['first_name'])?>"></div>
          <div class="col"><input name="middle_name" class="form-control mb-2" value="<?=htmlspecialchars($user['middle_name'])?>"></div>
          <div class="col"><input name="last_name" class="form-control mb-2" value="<?=htmlspecialchars($user['last_name'])?>"></div>
        </div>
      </div>
    </div>
    <label>Phone</label>
    <input name="phone" class="form-control mb-2" value="<?=htmlspecialchars($user['phone'])?>">
    <label>Address</label>
    <input name="address" class="form-control mb-2" value="<?=htmlspecialchars($user['address'])?>">
    <label>Avatar (JPG/PNG/WEBP, max 2MB)</label>
    <?php if(!empty($user['avatar'])): ?>
      <div><img src="<?=htmlspecialchars($user['avatar'])?>" style="max-width:120px"></div>
    <?php endif; ?>
    <input type="file" name="avatar" class="form-control mb-2" accept="image/*">
    <button class="btn btn-primary">Save Profile</button>
    <?php $ret = $_GET['return_to'] ?? 'dashboard.php'; ?>
    <a href="<?=htmlspecialchars($ret)?>" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body></html>
