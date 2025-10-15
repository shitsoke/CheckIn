<?php
session_start();
require_once "../db_connect.php";
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}
// change booking status action (POST only, CSRF protected)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && !empty($_POST['id'])) {
  verify_csrf();
  $id = intval($_POST['id']); $action = $_POST['action'];
  $valid = ['confirmed','ongoing','checked_out','canceled'];
  if (in_array($action, $valid)) {
    $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
    $stmt->bind_param("si", $action, $id);
    $stmt->execute();
    // if canceled or checked_out -> free room
    if ($action === 'canceled' || $action === 'checked_out') {
      // set room available
      $conn->query("UPDATE rooms SET status='available' WHERE id=(SELECT room_id FROM bookings WHERE id=$id)");
      // don't auto-redirect to PDF; show message on list
      header('Location: manage_bookings.php?msg=booking_updated');
      exit;
    } elseif ($action === 'confirmed') {
      // set room occupied
      $conn->query("UPDATE rooms SET status='occupied' WHERE id=(SELECT room_id FROM bookings WHERE id=$id)");
    }
  }
}
$res = $conn->query("SELECT b.*, u.email, r.room_number FROM bookings b
                     JOIN users u ON b.user_id=u.id
                     JOIN rooms r ON b.room_id=r.id
                     ORDER BY b.created_at DESC");
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Manage Bookings</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
<div class="container">
  <h3>Manage Bookings</h3>
  <?php if (!empty($_GET['msg']) && $_GET['msg']==='booking_updated'): ?><div class="alert alert-success">Booking updated.</div><?php endif; ?>
  <a href="index.php" class="btn btn-secondary mb-3">← Back</a>
  <table class="table table-striped">
    <thead><tr><th>ID</th><th>User</th><th>Room</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
      <?php while($b = $res->fetch_assoc()): ?>
      <tr>
        <td><?=$b['id']?></td>
        <td><?=htmlspecialchars($b['email'])?></td>
        <td><?=htmlspecialchars($b['room_number'])?></td>
        <td>₱<?=number_format($b['total_amount'],2)?></td>
        <td><?=htmlspecialchars($b['status'])?></td>
        <td>
          <?php foreach(['confirmed','ongoing','checked_out','canceled'] as $st): ?>
            <form method="post" class="d-inline">
              <?=csrf_input_field()?>
              <input type="hidden" name="id" value="<?=$b['id']?>">
              <input type="hidden" name="action" value="<?=$st?>">
              <button class="btn btn-sm btn-outline-primary" type="submit"><?=ucfirst($st)?></button>
            </form>
          <?php endforeach; ?>
          <a class="btn btn-sm btn-success" href="../receipt.php?booking_id=<?=$b['id']?>&download=pdf">Generate Receipt</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body></html>
