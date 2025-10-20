<?php
session_start();
require_once "../db_connect.php";
require_once __DIR__ . '/../includes/csrf.php';
include "admin_sidebar.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}
$room_id = intval($_GET['id'] ?? 0);
// check is_primary column exists
$hasIsPrimary = false;
$cols = $conn->query("SHOW COLUMNS FROM room_images LIKE 'is_primary'");
if ($cols && $cols->num_rows) $hasIsPrimary = true;
// handle make primary / delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['make_primary_id'])) {
    $mid = intval($_POST['make_primary_id']);
    if ($hasIsPrimary) {
      $conn->begin_transaction();
      $conn->query("UPDATE room_images SET is_primary=0 WHERE room_id=".intval($room_id));
      $conn->query("UPDATE room_images SET is_primary=1 WHERE id=".$mid);
      $conn->commit();
    }
    header('Location: room_details.php?id='.$room_id);
    exit;
  }
  if (!empty($_POST['delete_id'])) {
    $did = intval($_POST['delete_id']);
    $sel = $conn->prepare("SELECT filepath, is_primary FROM room_images WHERE id=? AND room_id=?");
    $sel->bind_param("ii", $did, $room_id); $sel->execute(); $row = $sel->get_result()->fetch_assoc();
    if ($row) {
      if (file_exists(__DIR__.'/../'. $row['filepath'])) @unlink(__DIR__.'/../'. $row['filepath']);
      $stmt = $conn->prepare("DELETE FROM room_images WHERE id=? AND room_id=?");
      $stmt->bind_param("ii", $did, $room_id); $stmt->execute();
      if ($row['is_primary']) {
        $conn->query("UPDATE room_images SET is_primary=1 WHERE room_id=".intval($room_id).' ORDER BY id ASC LIMIT 1');
      }
    }
    header('Location: room_details.php?id='.$room_id);
    exit;
  }
}
$room_id = intval($_GET['id'] ?? 0);
if ($room_id <= 0) die('Invalid room id');
// handle edit/save of room description and visibility
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room'])) {
  // CSRF not previously included on this page
  require_once __DIR__ . '/../includes/csrf.php'; verify_csrf();
  $desc = trim($_POST['description'] ?? '');
  $vis = isset($_POST['is_visible']) ? 1 : 0;
  $upd = $conn->prepare("UPDATE rooms SET description=?, is_visible=? WHERE id=?");
  $upd->bind_param("sii", $desc, $vis, $room_id); $upd->execute();
  header('Location: room_details.php?id='.$room_id.'&msg=saved'); exit;
}
$stmt = $conn->prepare("SELECT r.*, t.name as type, t.hourly_rate FROM rooms r JOIN room_types t ON r.room_type_id=t.id WHERE r.id=?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
if (!$room) die('Room not found');
// images
$imgs = $conn->prepare("SELECT * FROM room_images WHERE room_id=? ORDER BY id ASC");
$imgs->bind_param("i", $room_id);
$imgs->execute();
$imgsRes = $imgs->get_result();
// reviews for this room (admin view - no form)
$reviews = $conn->prepare("SELECT rv.*, u.first_name, u.last_name FROM reviews rv JOIN users u ON rv.user_id=u.id WHERE rv.room_id=? ORDER BY rv.created_at DESC");
$reviews->bind_param("i", $room_id);
$reviews->execute();
$reviewsRes = $reviews->get_result();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Room Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    background-color: #ffffff;
    color: #333;
    font-family: 'Inter', sans-serif;
  }
  h3, h5 {
    color: #b30000;
    font-weight: 700;
  }
  .btn-primary {
    background-color: #b30000;
    border-color: #b30000;
  }
  .btn-primary:hover {
    background-color: #990000;
    border-color: #990000;
  }
  .btn-outline-primary {
    color: #b30000;
    border-color: #b30000;
  }
  .btn-outline-primary:hover {
    background-color: #b30000;
    color: #fff;
  }
  .btn-outline-danger {
    color: #b30000;
    border-color: #b30000;
  }
  .btn-outline-danger:hover {
    background-color: #b30000;
    color: #fff;
  }
  .btn-secondary {
    background-color: #f8f9fa;
    color: #b30000;
    border: 1px solid #b30000;
  }
  .btn-secondary:hover {
    background-color: #b30000;
    color: #fff;
  }
  .card {
    border: 1px solid #f3f3f3;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  }
  .img-thumb {
    border: 2px solid #b30000;
    border-radius: 8px;
    height: 140px;
    object-fit: cover;
    transition: transform .2s ease;
  }
  .img-thumb:hover {
    transform: scale(1.05);
  }
  .alert-success {
    background-color: #ffeaea;
    color: #b30000;
    border: none;
  }
  .form-check-input:checked {
    background-color: #b30000;
    border-color: #b30000;
  }
  hr {
    border-top: 2px solid #b30000;
    opacity: 0.6;
  }
  .review-card {
    background-color: #fff;
    border-left: 5px solid #b30000;
    border-radius: 6px;
    padding: 1rem;
  }
  .badge.bg-success {
    background-color: #b30000 !important;
  }
  .break-word {
    overflow-wrap: anywhere;
    word-break: break-word;
    white-space: pre-wrap;
  }
</style>
</head>

<body class="p-4">
<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Room <?=htmlspecialchars($room['room_number'])?> — <?=htmlspecialchars($room['type'])?></h3>
    <?php
      $adminBack = 'manage_rooms.php';
      if (isset($_GET['from']) && $_GET['from']==='manage_reviews') $adminBack = 'manage_reviews.php';
    ?>
    <a href="<?=htmlspecialchars($adminBack)?>" class="btn btn-secondary">← Back to Admin</a>
  </div>

  <div class="row">
    <div class="col-md-8">

      <!-- ROOM IMAGES -->
      <div class="card mb-4 p-3">
        <h5>Room Images</h5>
        <?php if ($imgsRes->num_rows): ?>
          <div class="row mt-2">
            <?php while($im=$imgsRes->fetch_assoc()): ?>
              <div class="col-md-4 mb-3 text-center">
                <img src="<?=htmlspecialchars('../'.$im['filepath'])?>" class="img-fluid img-thumb click-enlarge" data-src="<?=htmlspecialchars('../'.$im['filepath'])?>">
                <div class="mt-2">
                  <?php if($im['is_primary']): ?>
                    <span class="badge bg-success">Primary</span>
                  <?php else: ?>
                    <form method="post" class="d-inline">
                      <?=csrf_input_field()?>
                      <input type="hidden" name="make_primary_id" value="<?=intval($im['id'])?>">
                      <button class="btn btn-sm btn-outline-primary" type="submit">Make Primary</button>
                    </form>
                  <?php endif; ?>
                  <form method="post" class="d-inline" onsubmit="return confirm('Delete image?');">
                    <?=csrf_input_field()?>
                    <input type="hidden" name="delete_id" value="<?=intval($im['id'])?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                  </form>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-4 text-muted bg-light rounded">No images available</div>
        <?php endif; ?>
      </div>

      <!-- ROOM DESCRIPTION -->
      <div class="card mb-4 p-3">
        <h5>Room Description</h5>
        <p class="break-word"><?=nl2br(htmlspecialchars($room['description'] ?? ''))?></p>
        <p class="fw-bold text-danger">Rate: ₱<?=number_format($room['hourly_rate'],2)?>/hr</p>
      </div>

      <!-- EDIT FORM -->
      <div class="card mb-4 p-3">
        <h5>Edit Room Details</h5>
        <?php if (!empty($_GET['msg']) && $_GET['msg']==='saved'): ?>
          <div class="alert alert-success mt-2">Saved successfully!</div>
        <?php endif; ?>
        <form method="post" class="mt-3">
          <?=csrf_input_field()?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="4"><?=htmlspecialchars($room['description'] ?? '')?></textarea>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" name="is_visible" class="form-check-input" id="visCheck" <?=($room['is_visible']? 'checked':'')?>>
            <label for="visCheck" class="form-check-label">Visible to customers</label>
          </div>
          <input type="hidden" name="save_room" value="1">
          <button class="btn btn-primary px-4">Save Changes</button>
        </form>
      </div>

      <!-- REVIEWS -->
      <div class="card mb-4 p-3">
        <h5>Reviews for this room</h5>
        <?php if ($reviewsRes->num_rows): ?>
          <?php while($rv=$reviewsRes->fetch_assoc()): ?>
            <div class="review-card mb-3">
              <div class="d-flex justify-content-between">
                <strong><?=htmlspecialchars($rv['first_name'].' '.$rv['last_name'])?></strong>
                <span class="text-muted small"><?=htmlspecialchars($rv['created_at'])?></span>
              </div>
              <div>Rating: <span class="text-danger fw-bold"><?=intval($rv['rating'])?>/5</span></div>
              <div class="break-word mt-2"><?=nl2br(htmlspecialchars($rv['comment']))?></div>
              <div class="mt-3">
                <a class="btn btn-sm btn-outline-primary" href="?toggle=1&id=<?=$rv['id']?>">Toggle Visible</a>
                <a class="btn btn-sm btn-outline-danger" href="manage_reviews.php?delete=<?=$rv['id']?>" onclick="return confirm('Delete?')">Delete</a>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="text-muted">No reviews yet for this room.</div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>
</body>
</html>
<?php require_once __DIR__ . '/../includes/image_modal.php'; ?>
