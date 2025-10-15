<?php
session_start();
require_once "../db_connect.php";
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}
// handle POST actions (toggle visibility, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  if (!empty($_POST['toggle_id'])) {
    $id = intval($_POST['toggle_id']);
    $stmt = $conn->prepare("UPDATE reviews SET is_visible = 1 - is_visible WHERE id=?");
    $stmt->bind_param("i", $id); $stmt->execute();
    header('Location: manage_reviews.php'); exit;
  }
  if (!empty($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $d = $conn->prepare("DELETE FROM reviews WHERE id=?"); $d->bind_param("i", $id); $d->execute();
    header('Location: manage_reviews.php'); exit;
  }
}

$filterRoom = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
if ($filterRoom) {
  $res = $conn->prepare("SELECT rv.*, u.email, t.name as roomtype FROM reviews rv JOIN users u ON rv.user_id=u.id LEFT JOIN room_types t ON rv.room_type_id=t.id WHERE rv.room_id=? ORDER BY rv.created_at DESC");
  $res->bind_param("i", $filterRoom);
  $res->execute();
  $res = $res->get_result();
} else {
  $res = $conn->query("SELECT rv.*, u.email, t.name as roomtype FROM reviews rv JOIN users u ON rv.user_id=u.id LEFT JOIN room_types t ON rv.room_type_id=t.id ORDER BY rv.created_at DESC");
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Manage Reviews</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .break-word { overflow-wrap: anywhere; word-break: break-word; white-space: pre-wrap; }
</style></head>
<body class="p-4">
<div class="container">
  <h3>Reviews Moderation</h3>
  <a href="index.php" class="btn btn-secondary mb-3">← Back</a>
  <div class="alert alert-info">Toggle: hide/unhide a review from public view. Delete: permanently remove a review.</div>
  <table class="table table-striped">
  <thead><tr><th>ID</th><th>User</th><th>Room Type</th><th>Room</th><th>Rating</th><th>Comment</th><th>Visible</th><th>Action</th></tr></thead>
    <tbody>
      <?php while($r = $res->fetch_assoc()): ?>
      <tr>
        <td><?=$r['id']?></td>
        <td><?=htmlspecialchars($r['email'])?></td>
  <td><?=htmlspecialchars($r['roomtype'] ?? 'Hotel')?></td>
  <td><?php if(!empty($r['room_id'])): ?><a href="room_details.php?id=<?=intval($r['room_id'])?>&from=manage_reviews">View room</a><?php else: ?>Overall Hotel<?php endif; ?></td>
        <td><?=intval($r['rating'])?></td>
  <td class="break-word"><?=htmlspecialchars($r['comment'])?></td>
        <td><?=$r['is_visible'] ? 'Yes' : 'No'?></td>
        <td>
          <form method="post" class="d-inline">
            <?=csrf_input_field()?>
            <input type="hidden" name="toggle_id" value="<?=$r['id']?>">
            <button class="btn btn-sm btn-outline-primary" type="submit">Toggle</button>
          </form>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this review?');">
            <?=csrf_input_field()?>
            <input type="hidden" name="delete_id" value="<?=$r['id']?>">
            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body></html>
