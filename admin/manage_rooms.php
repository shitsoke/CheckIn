<?php
session_start();
require_once "../db_connect.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $room_number = trim($_POST['room_number'] ?? '');
  $type_id = intval($_POST['room_type_id'] ?? 0);
  if ($room_number === '' || $type_id <= 0) $msg = "Invalid input.";
  else {
    $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type_id) VALUES (?, ?)");
    $stmt->bind_param("si", $room_number, $type_id);
    $stmt->execute();
    $msg = "Room added.";
  }
}
$rooms = $conn->query("SELECT r.*, t.name AS type FROM rooms r LEFT JOIN room_types t ON r.room_type_id=t.id ORDER BY r.id DESC");
$types = $conn->query("SELECT * FROM room_types");
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Manage Rooms</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
<div class="container">
  <h3>Manage Rooms</h3>
  <a href="index.php" class="btn btn-secondary mb-3">← Back</a>
  <?php if($msg): ?><div class="alert alert-info"><?=$msg?></div><?php endif; ?>
  <form method="post" class="row g-2 mb-3">
    <div class="col-md-4"><input name="room_number" class="form-control" placeholder="Room Number" required></div>
    <div class="col-md-4">
      <select name="room_type_id" class="form-select" required>
        <option value="">Select type</option>
        <?php while($t=$types->fetch_assoc()): ?><option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option><?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-4"><button class="btn btn-primary w-100">Add Room</button></div>
  </form>

  <table class="table table-bordered">
    <thead><tr><th>ID</th><th>Room #</th><th>Type</th><th>Status</th><th>Action</th></tr></thead>
    <tbody><?php while($r=$rooms->fetch_assoc()): ?>
      <tr>
        <td><?=$r['id']?></td>
        <td><?=htmlspecialchars($r['room_number'])?></td>
        <td><?=htmlspecialchars($r['type'])?></td>
        <td><?=htmlspecialchars($r['status'])?></td>
        <td>
          <a class="btn btn-sm btn-secondary" href="upload_room_images.php?room_id=<?=$r['id']?>">Upload Images</a>
          <a class="btn btn-sm btn-info" href="room_details.php?id=<?=$r['id']?>&from=manage_rooms">View</a>
        </td>
      </tr>
    <?php endwhile; ?></tbody>
  </table>
</div>
</body></html>
