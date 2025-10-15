<?php
session_start();
require_once "../db_connect.php";
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
<html><head><meta charset="utf-8"><title>Admin Room Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .break-word { overflow-wrap: anywhere; word-break: break-word; white-space: pre-wrap; }
</style></head>
<body class="p-4">
<div class="container">
  <h3>Room <?=htmlspecialchars($room['room_number'])?> — <?=htmlspecialchars($room['type'])?></h3>
  <?php
    $adminBack = 'manage_rooms.php';
    if (isset($_GET['from']) && $_GET['from']==='manage_reviews') $adminBack = 'manage_reviews.php';
  ?>
  <a href="<?=htmlspecialchars($adminBack)?>" class="btn btn-secondary mb-3">← Back to Admin</a>
  <div class="row">
    <div class="col-md-8">
      <?php if ($imgsRes->num_rows): ?>
        <div class="row">
          <?php while($im=$imgsRes->fetch_assoc()): ?>
            <div class="col-md-4 mb-2">
              <img src="<?=htmlspecialchars('../'.$im['filepath'])?>" class="img-fluid" style="height:140px;object-fit:cover">
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
      <?php else: ?>
        <div style="height:160px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#777">No images</div>
      <?php endif; ?>

      <p><?=nl2br(htmlspecialchars($room['description'] ?? ''))?></p>
      <p>Rate: ₱<?=number_format($room['hourly_rate'],2)?>/hr</p>

      <hr>
      <h5>Reviews for this room</h5>
      <?php if ($reviewsRes->num_rows): ?>
        <?php while($rv=$reviewsRes->fetch_assoc()): ?>
          <div class="border p-2 mb-2">
            <strong><?=htmlspecialchars($rv['first_name'].' '.$rv['last_name'])?></strong>
            <span class="text-muted"> — <?=htmlspecialchars($rv['created_at'])?></span>
            <div>Rating: <?=intval($rv['rating'])?>/5</div>
            <div class="break-word"><?=nl2br(htmlspecialchars($rv['comment']))?></div>
            <div class="mt-2">
              <a class="btn btn-sm btn-outline-primary" href="?toggle=1&id=<?=$rv['id']?>">Toggle Visible</a>
              <a class="btn btn-sm btn-outline-danger" href="manage_reviews.php?delete=<?=$rv['id']?>" onclick="return confirm('Delete?')">Delete</a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="text-muted">No reviews yet for this room.</div>
      <?php endif; ?>
    </div>
    <div class="col-md-4">
      <h5>Admin tools</h5>
      <p>Use this page to inspect room images and reviews. Admin cannot leave reviews here.</p>
    </div>
  </div>
</div>
</body></html>