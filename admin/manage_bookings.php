<?php
session_start();
require_once "../db_connect.php";
require_once __DIR__ . '/../includes/csrf.php';
include "admin_sidebar.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}
// --- existing logic unchanged ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && !empty($_POST['id'])) {
  verify_csrf();
  $id = intval($_POST['id']); $action = $_POST['action'];
  $valid = ['confirmed','ongoing','checked_out','canceled'];
  if (in_array($action, $valid)) {
    $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
    $stmt->bind_param("si", $action, $id);
    $stmt->execute();
    if ($action === 'canceled' || $action === 'checked_out') {
      $conn->query("UPDATE rooms SET status='available' WHERE id=(SELECT room_id FROM bookings WHERE id=$id)");
      if ($action === 'checked_out') {
        $chk = $conn->prepare("SELECT b.receipt_sent, u.email FROM bookings b JOIN users u ON b.user_id=u.id WHERE b.id=? LIMIT 1");
        $chk->bind_param("i", $id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        if ($row && intval($row['receipt_sent']) === 0) {
          $bkq = $conn->prepare("SELECT b.*, r.room_number, t.name as room_type, t.hourly_rate, u.first_name, u.middle_name, u.last_name FROM bookings b JOIN rooms r ON b.room_id=r.id JOIN room_types t ON r.room_type_id=t.id JOIN users u ON b.user_id=u.id WHERE b.id=? LIMIT 1");
          $bkq->bind_param("i", $id); $bkq->execute(); $bk = $bkq->get_result()->fetch_assoc();
          if ($bk) {
            $html = '<!doctype html><html><head><meta charset="utf-8"><title>Receipt</title></head><body>';
            $html .= '<h2>CheckIn Receipt</h2>';
            $html .= '<p>Booking ID: '.htmlspecialchars($bk['id']).'</p>';
            $fullName = $bk['first_name'] . (!empty($bk['middle_name']) ? ' '.$bk['middle_name'] : '') . ' ' . $bk['last_name'];
            $html .= '<p>Customer: '.htmlspecialchars(trim($fullName)).'</p>';
            $html .= '<p>Room: '.htmlspecialchars($bk['room_number']).' ('.htmlspecialchars($bk['room_type']).')</p>';
            $html .= '<p>Start: '.htmlspecialchars($bk['start_time']).'</p>';
            $html .= '<p>End: '.htmlspecialchars($bk['end_time']).'</p>';
            $html .= '<p>Hours: '.intval($bk['hours']).'</p>';
            $html .= '<p>Total: ₱'.number_format($bk['total_amount'],2).'</p>';
            $html .= '<p>Payment method: '.htmlspecialchars($bk['payment_method']).'</p>';
            $html .= '<p>Status: checked_out</p>';
            $html .= '</body></html>';
            $receiptPath = __DIR__ . '/../uploads/receipts';
            if (!file_exists($receiptPath)) @mkdir($receiptPath, 0755, true);
            $pdfFile = $receiptPath . '/receipt_' . $id . '.pdf';
            $pdfCreated = false;
            if (file_exists(__DIR__.'/../vendor/autoload.php')) {
              require_once __DIR__.'/../vendor/autoload.php';
              try {
                $dompdf = new Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                file_put_contents($pdfFile, $dompdf->output());
                $pdfCreated = file_exists($pdfFile);
              } catch (Exception $e) {
                error_log('Dompdf error: '.$e->getMessage());
                $pdfCreated = false;
              }
            }
            require_once __DIR__ . '/../includes/mail.php';
            $to = $row['email'];
            $subject = 'Your booking receipt (Booking #'.$id.')';
            $body = $html;
            $sent = false;
            if ($pdfCreated) $sent = send_mail_with_attachment($to, $subject, $body, true, $pdfFile);
            else $sent = send_mail($to, $subject, $body, true);
            if ($sent) {
              $upd = $conn->prepare("UPDATE bookings SET receipt_sent=1 WHERE id=?");
              $upd->bind_param("i", $id); $upd->execute();
            }
          }
        }
        $chk->close();
      }
      header('Location: manage_bookings.php?msg=booking_updated');
      exit;
    } elseif ($action === 'confirmed') {
      $conn->query("UPDATE rooms SET status='occupied' WHERE id=(SELECT room_id FROM bookings WHERE id=$id)");
    }
  }
}
$where = [];
$params = [];
$types = '';
if (!empty($_GET['q'])) { $where[] = "(u.email LIKE ? OR r.room_number LIKE ?)"; $params[] = '%'.$_GET['q'].'%'; $params[] = '%'.$_GET['q'].'%'; $types .= 'ss'; }
if (!empty($_GET['status'])) { $where[] = "b.status = ?"; $params[] = $_GET['status']; $types .= 's'; }
if (!empty($_GET['room'])) { $where[] = "r.id = ?"; $params[] = intval($_GET['room']); $types .= 'i'; }
if (!empty($_GET['from_date'])) { $where[] = "DATE(b.start_time) >= ?"; $params[] = $_GET['from_date']; $types .= 's'; }
if (!empty($_GET['to_date'])) { $where[] = "DATE(b.start_time) <= ?"; $params[] = $_GET['to_date']; $types .= 's'; }
$where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';
$sql = "SELECT b.*, u.email, r.room_number FROM bookings b JOIN users u ON b.user_id=u.id JOIN rooms r ON b.room_id=r.id " . $where_sql . " ORDER BY (b.start_time >= NOW()) DESC, b.start_time ASC, b.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Manage Bookings</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
  <h3 class="text-danger fw-bold mb-3">Manage Bookings</h3>
  <?php if (!empty($_GET['msg']) && $_GET['msg']==='booking_updated'): ?>
    <div class="alert alert-success">Booking updated.</div>
  <?php endif; ?>
  <form method="get" class="row gy-2 gx-2 mb-3">
    <div class="col-md-3"><input name="q" value="<?=htmlspecialchars($_GET['q'] ?? '')?>" class="form-control" placeholder="Search by user email or room #"></div>
    <div class="col-md-2">
      <select name="status" class="form-select">
        <option value="">Any status</option>
        <?php foreach(['reserved','confirmed','ongoing','checked_out','canceled'] as $s): ?>
          <option value="<?=$s?>" <?=(!empty($_GET['status']) && $_GET['status']==$s)? 'selected':''?>><?=ucfirst($s)?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><input name="room" value="<?=htmlspecialchars($_GET['room'] ?? '')?>" class="form-control" placeholder="Room ID"></div>
    <div class="col-md-2"><input name="from_date" type="date" value="<?=htmlspecialchars($_GET['from_date'] ?? '')?>" class="form-control"></div>
    <div class="col-md-2"><input name="to_date" type="date" value="<?=htmlspecialchars($_GET['to_date'] ?? '')?>" class="form-control"></div>
    <div class="col-md-1"><button class="btn btn-danger w-100">Filter</button></div>
  </form>

  <hr class="border-danger opacity-75 mb-4">

  <table class="table table-bordered table-striped text-center align-middle shadow-sm">
    <thead class="table-danger">
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Room</th>
        <th>Start</th>
        <th>End</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while($b = $res->fetch_assoc()): ?>
      <tr class="<?= $b['status'] === 'canceled' ? 'table-light' : '' ?>">
        <td><?=$b['id']?></td>
        <td><?=htmlspecialchars($b['email'])?></td>
        <td><?=htmlspecialchars($b['room_number'])?></td>
        <td><?=htmlspecialchars($b['start_time'])?></td>
        <td><?=htmlspecialchars($b['end_time'])?></td>
        <td>₱<?=number_format($b['total_amount'],2)?></td>
        <td><span class="badge bg-danger text-light"><?=htmlspecialchars($b['status'])?></span></td>
        <td>
          <?php foreach(['confirmed','ongoing','checked_out','canceled'] as $st): ?>
            <form method="post" class="d-inline">
              <?=csrf_input_field()?>
              <input type="hidden" name="id" value="<?=$b['id']?>">
              <input type="hidden" name="action" value="<?=$st?>">
              <button class="btn btn-sm btn-outline-danger" type="submit"><?=ucfirst($st)?></button>
            </form>
          <?php endforeach; ?>
          <a class="btn btn-sm btn-danger" href="../receipt.php?booking_id=<?=$b['id']?>&download=pdf">Receipt</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>
