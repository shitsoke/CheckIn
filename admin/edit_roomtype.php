<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM room_types WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
  die("Room type not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $desc = trim($_POST['description']);
  $rate = floatval($_POST['hourly_rate']);

  if ($rate < 0) $rate = 0;

  $update = $conn->prepare("UPDATE room_types SET name=?, description=?, hourly_rate=? WHERE id=?");
  $update->bind_param("ssdi", $name, $desc, $rate, $id);

  if ($update->execute()) {
    header("Location: manage_roomtypes.php");
    exit;
  } else {
    $error = "Failed to update. Please try again.";
  }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Edit Room Type | Admin - CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h3>Edit Room Type</h3>
  <a href="manage_roomtypes.php" class="btn btn-secondary mb-3">Back</a>

  <?php if (isset($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="border p-4 bg-light rounded">
    <div class="mb-3">
      <label>Room Name</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($room['name']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Description</label>
      <textarea name="description" class="form-control" required><?= htmlspecialchars($room['description']) ?></textarea>
    </div>
    <div class="mb-3">
      <label>Hourly Rate (₱)</label>
      <input type="number" name="hourly_rate" step="0.01" class="form-control" value="<?= htmlspecialchars($room['hourly_rate']) ?>" required>
    </div>
    <button type="submit" class="btn btn-success">Save Changes</button>
  </form>
</div>
</body>
</html>
