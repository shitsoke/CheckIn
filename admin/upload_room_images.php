<?php
session_start();
require_once "../db_connect.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}
$room_id = intval($_GET['room_id'] ?? 0);
if ($room_id <= 0) die('Invalid room id');
$msg = '';
// helper: does room_images.is_primary exist?
$hasIsPrimary = false;
$cols = $conn->query("SHOW COLUMNS FROM room_images LIKE 'is_primary'");
if ($cols && $cols->num_rows) $hasIsPrimary = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // handle make primary
  if (!empty($_POST['make_primary_id'])) {
    $mid = intval($_POST['make_primary_id']);
    if ($hasIsPrimary) {
      // clear existing
      $conn->begin_transaction();
      $conn->query("UPDATE room_images SET is_primary=0 WHERE room_id=".intval($room_id));
      $conn->query("UPDATE room_images SET is_primary=1 WHERE id=".$mid);
      $conn->commit();
    }
    header('Location: upload_room_images.php?room_id='.$room_id);
    exit;
  }
  // handle delete
  if (!empty($_POST['delete_id'])) {
    $did = intval($_POST['delete_id']);
    $sel = $conn->prepare("SELECT filepath, is_primary FROM room_images WHERE id=? AND room_id=?");
    $sel->bind_param("ii", $did, $room_id); $sel->execute(); $row = $sel->get_result()->fetch_assoc();
      if ($row) {
      if (file_exists(__DIR__.'/../'. $row['filepath'])) @unlink(__DIR__.'/../'. $row['filepath']);
      $stmt = $conn->prepare("DELETE FROM room_images WHERE id=? AND room_id=?");
      $stmt->bind_param("ii", $did, $room_id); $stmt->execute();
      // if deleted was primary, set another as primary (only if column exists)
      if ($hasIsPrimary && $row['is_primary']) {
        $conn->query("UPDATE room_images SET is_primary=1 WHERE room_id=".intval($room_id).' ORDER BY id ASC LIMIT 1');
      }
    }
    header('Location: upload_room_images.php?room_id='.$room_id);
    exit;
  }
  if (empty($_FILES['images'])) {
    $msg = 'No files uploaded.';
  } else {
    $uploadsDir = __DIR__ . '/../uploads/rooms/';
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
    $allowed = ['image/jpeg','image/png','image/webp'];
    for ($i=0;$i<count($_FILES['images']['name']);$i++) {
      $name = $_FILES['images']['name'][$i];
      $tmp = $_FILES['images']['tmp_name'][$i];
      $err = $_FILES['images']['error'][$i];
      $size = $_FILES['images']['size'][$i];
      if ($err !== UPLOAD_ERR_OK) { $msg .= "Upload error for $name\n"; continue; }
      $mime = mime_content_type($tmp);
      if (!in_array($mime, $allowed)) { $msg .= "Skipped $name (invalid type)\n"; continue; }
      if ($size > 3*1024*1024) { $msg .= "Skipped $name (too large)\n"; continue; }
      $ext = pathinfo($name, PATHINFO_EXTENSION);
      $fname = 'room'.$room_id.'-'.time().'-'.bin2hex(random_bytes(6)).'.'.$ext;
      $dest = $uploadsDir . $fname;
      if (!move_uploaded_file($tmp, $dest)) { $msg .= "Failed to move $name\n"; continue; }
      $filepath = 'uploads/rooms/'.$fname;
      $stmt = $conn->prepare("INSERT INTO room_images (room_id, filepath, alt_text) VALUES (?, ?, ?)");
      $alt = '';
      $stmt->bind_param("iss", $room_id, $filepath, $alt);
      $stmt->execute();
      // if no primary exists for this room, make this the primary image (only if column exists)
      if ($hasIsPrimary) {
        $checkPrimary = $conn->prepare("SELECT id FROM room_images WHERE room_id=? AND is_primary=1 LIMIT 1");
        $checkPrimary->bind_param("i", $room_id); $checkPrimary->execute(); $checkPrimary->store_result();
        if ($checkPrimary->num_rows === 0) {
          $lastId = $conn->insert_id;
          $conn->query("UPDATE room_images SET is_primary=1 WHERE id=".intval($lastId));
        }
      }
    }
    if ($msg === '') $msg = 'Upload complete.';
  }
}

// fetch room info
$stmt = $conn->prepare("SELECT r.room_number, t.name AS type FROM rooms r JOIN room_types t ON r.room_type_id=t.id WHERE r.id=?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
if (!$room) die('Room not found');

// fetch existing images
$imgs = $conn->prepare("SELECT * FROM room_images WHERE room_id=? ORDER BY id ASC");
$imgs->bind_param("i", $room_id);
$imgs->execute();
$imgsRes = $imgs->get_result();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Upload Images | Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
<div class="container">
  <h3>Upload Images for Room <?=htmlspecialchars($room['room_number'])?> (<?=htmlspecialchars($room['type'])?>)</h3>
  <a href="manage_rooms.php" class="btn btn-secondary mb-3">← Back</a>
  <?php if($msg): ?><div class="alert alert-info white-space-pre-wrap"><?=nl2br(htmlspecialchars($msg))?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label>Select images (jpg, png, webp) max 3MB each</label>
      <input type="file" name="images[]" multiple accept="image/*" class="form-control">
    </div>
    <button class="btn btn-primary">Upload</button>
  </form>

  <hr>
  <h5>Existing Images</h5>
  <div class="row">
    <?php while($im = $imgsRes->fetch_assoc()): ?>
    <div class="col-md-3 mb-3">
  <img src="<?=htmlspecialchars('../'.$im['filepath'])?>" class="img-fluid click-enlarge" data-src="<?=htmlspecialchars('../'.$im['filepath'])?>" style="height:140px;object-fit:cover">
        <div class="mt-1 small text-muted"><?=htmlspecialchars($im['alt_text'])?></div>
        <div class="mt-1">
          <?php if($im['is_primary']): ?>
            <span class="badge bg-success">Primary</span>
          <?php else: ?>
            <form method="post" class="d-inline">
              <input type="hidden" name="make_primary_id" value="<?=intval($im['id'])?>">
              <button class="btn btn-sm btn-outline-primary" type="submit">Make Primary</button>
            </form>
          <?php endif; ?>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete image?');">
            <input type="hidden" name="delete_id" value="<?=intval($im['id'])?>">
            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
          </form>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>
</body></html>
<?php require_once __DIR__ . '/../includes/image_modal.php'; ?>