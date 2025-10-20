<?php
session_start();
require_once "../db_connect.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}
$msg = "";
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  if (!empty($_POST['toggle_visible_id'])) {
    $tid = intval($_POST['toggle_visible_id']);
    $u = $conn->prepare("UPDATE rooms SET is_visible = 1 - is_visible WHERE id=?");
    $u->bind_param("i", $tid); $u->execute();
    header('Location: manage_rooms.php'); exit;
  }
  $room_number = trim($_POST['room_number'] ?? '');
  $type_id = intval($_POST['room_type_id'] ?? 0);
  $description = trim($_POST['description'] ?? '');
  $is_visible = isset($_POST['is_visible']) ? 1 : 0;
  if ($room_number === '' || $type_id <= 0) {
    $msg = "Invalid input.";
  } else {
    $chk = $conn->prepare("SELECT id FROM rooms WHERE room_number=? LIMIT 1");
    $chk->bind_param("s", $room_number);
    $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) {
      $msg = "Room number already exists.";
    } else {
      $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type_id, description, is_visible) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("sisi", $room_number, $type_id, $description, $is_visible);
      $stmt->execute();
      $msg = "Room added.";
    }
  }
}

$where = [];
$params = [];
$typestr = '';
if (!empty($_GET['q'])) { $where[] = "(r.room_number LIKE ? OR r.description LIKE ? )"; $params[] = '%'.$_GET['q'].'%'; $params[] = '%'.$_GET['q'].'%'; $typestr .= 'ss'; }
if (!empty($_GET['room_type'])) { $where[] = "r.room_type_id = ?"; $params[] = intval($_GET['room_type']); $typestr .= 'i'; }
if (!empty($_GET['status'])) { $where[] = "r.status = ?"; $params[] = $_GET['status']; $typestr .= 's'; }
if (isset($_GET['visible']) && $_GET['visible'] !== '') { $where[] = "r.is_visible = ?"; $params[] = intval($_GET['visible']); $typestr .= 'i'; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT r.*, t.name AS type FROM rooms r LEFT JOIN room_types t ON r.room_type_id=t.id " . $where_sql . " ORDER BY (r.status='available') DESC, r.room_number ASC";
$rooms = $conn->prepare($sql);
if ($params) { $rooms->bind_param($typestr, ...$params); }
$rooms->execute();
$roomsRes = $rooms->get_result();
$types = $conn->query("SELECT * FROM room_types");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Rooms</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #fff; }
    .btn-primary, .table thead 
    { 
      background-color: #c62828 !important; 
      border-color: #b71c1c !important;
     }
    .btn-primary:hover {
       background-color: #b71c1c !important; 
      }
    .btn-secondary {
       background-color: #e53935 !important; 
       border-color: #c62828 !important; 
      }
    .btn-secondary:hover { 
      background-color: #c62828 !important;
     }
    .btn-info {
       background-color: #f44336 !important;
        border-color: #d32f2f !important;
         color: white; 
        }
    .table th, .table td {
       vertical-align: middle; 
      }
    .card { 
      border: 1px solid #ffcdd2; 
    }
    h3, h5 { 
      color: #c62828; }
  </style>
</head>

<body class="p-4">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Manage Rooms</h3>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>

  <?php if($msg): ?><div class="alert alert-info"><?=$msg?></div><?php endif; ?>

  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <h5 class="card-title mb-3">Add New Room</h5>
      <form method="post" class="row g-2">
        <?=csrf_input_field()?>
        <div class="col-md-3"><input name="room_number" class="form-control" placeholder="Room Number"></div>
        <div class="col-md-3">
          <select name="room_type_id" class="form-select">
            <option value="">Select type</option>
            <?php $types->data_seek(0); while($t=$types->fetch_assoc()): ?><option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option><?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-3"><input name="description" class="form-control" placeholder="Description (optional)"></div>
        <div class="col-md-1">
          <div class="form-check mt-2">
            <input type="checkbox" name="is_visible" class="form-check-input" id="isVisible" checked>
            <label class="form-check-label" for="isVisible">Visible</label>
          </div>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100">Add Room</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <h5 class="card-title mb-3">Filter Rooms</h5>
      <form method="get" class="row g-2">
        <div class="col-md-3"><input name="q" value="<?=htmlspecialchars($_GET['q'] ?? '')?>" class="form-control" placeholder="Search..."></div>
        <div class="col-md-2">
          <select name="status" class="form-select">
            <option value="">Any status</option>
            <option value="Available" <?=(!empty($_GET['status']) && $_GET['status']=='Available')? 'selected':''?>>Available</option>
            <option value="Reserved" <?=(!empty($_GET['status']) && $_GET['status']=='Reserved')? 'selected':''?>>Reserved</option>
            <option value="Occupied" <?=(!empty($_GET['status']) && $_GET['status']=='Occupied')? 'selected':''?>>Occupied</option>
            <option value="Maintenance" <?=(!empty($_GET['status']) && $_GET['status']=='Maintenance')? 'selected':''?>>Maintenance</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="room_type" class="form-select">
            <option value="">All types</option>
            <?php $types->data_seek(0); while($t2=$types->fetch_assoc()): ?><option value="<?=$t2['id']?>" <?=(!empty($_GET['room_type']) && $_GET['room_type']==$t2['id'])? 'selected':''?>><?=htmlspecialchars($t2['name'])?></option><?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select name="visible" class="form-select">
            <option value="">Visibility</option>
            <option value="1" <?=isset($_GET['visible']) && $_GET['visible']==='1'? 'selected':''?>>Visible</option>
            <option value="0" <?=isset($_GET['visible']) && $_GET['visible']==='0'? 'selected':''?>>Hidden</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-danger w-100">Filter</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="card-title mb-3">Room List</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-danger text-white">
            <tr>
              <th>ID</th>
              <th>Room #</th>
              <th>Type</th>
              <th>Status</th>
              <th>Visible</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while($r=$roomsRes->fetch_assoc()): ?>
              <tr>
                <td><?=$r['id']?></td>
                <td><?=htmlspecialchars($r['room_number'])?></td>
                <td><?=htmlspecialchars($r['type'])?></td>
                <td><?=htmlspecialchars($r['status'])?></td>
                <td><?=($r['is_visible']? 'Yes':'No')?></td>
                <td>
                  <form method="post" class="d-inline">
                    <?=csrf_input_field()?>
                    <input type="hidden" name="toggle_visible_id" value="<?=$r['id']?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit"><?= $r['is_visible'] ? 'Hide' : 'Show'?></button>
                  </form>
                  <a class="btn btn-sm btn-secondary" href="upload_room_images.php?room_id=<?=$r['id']?>">Upload Images</a>
                  <a class="btn btn-sm btn-info" href="room_details.php?id=<?=$r['id']?>&from=manage_rooms">View / Edit</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
