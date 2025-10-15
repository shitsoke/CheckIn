<?php
session_start();
require_once "includes/auth_check.php";
require_once "includes/csrf.php";
require_once "db_connect.php";

$user_id = $_SESSION['user_id'];
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $return_to = $_POST['return_to'] ?? null; // optional URL to go back to
  // Either room_id (review for specific room) or 'hotel' for overall hotel review
  $room_id = isset($_POST['room_id']) && $_POST['room_id'] !== 'hotel' ? intval($_POST['room_id']) : null;
  $is_hotel = isset($_POST['room_id']) && $_POST['room_id'] === 'hotel';
  $rating = intval($_POST['rating'] ?? 0);
  $comment = trim($_POST['comment'] ?? '');
  if (!$is_hotel && !$room_id && ($rating < 1 || $rating > 5)) {
    $msg = "Invalid data.";
  } elseif ($rating < 1 || $rating > 5) {
    $msg = "Invalid rating.";
  } else {
  if ($is_hotel) {
      // check if user has any completed booking at all
      $chk = $conn->prepare("SELECT id FROM bookings WHERE user_id=? AND status='checked_out' LIMIT 1");
      $chk->bind_param("i", $user_id);
      $chk->execute(); $chk->store_result();
      if ($chk->num_rows === 0) $msg = "You can only leave a hotel review after completing a booking.";
      else {
        $ins = $conn->prepare("INSERT INTO reviews (user_id, room_type_id, room_id, rating, comment, is_visible) VALUES (?, NULL, NULL, ?, ?, 1)");
        $ins->bind_param("iis", $user_id, $rating, $comment);
        $ins->execute();
        $msg = $ins->affected_rows ? 'Hotel review submitted.' : 'Failed to submit review.';
      }
      } else {
      // room-specific review: verify user completed that room
      $chk = $conn->prepare("SELECT id FROM bookings WHERE user_id=? AND room_id=? AND status='checked_out' LIMIT 1");
      $chk->bind_param("ii", $user_id, $room_id);
      $chk->execute(); $chk->store_result();
      if ($chk->num_rows === 0) $msg = 'You can only review rooms you have completed (checked out).';
      else {
        // determine room_type_id for the room
        $rt = $conn->prepare("SELECT room_type_id FROM rooms WHERE id=?");
        $rt->bind_param("i", $room_id); $rt->execute(); $roomRow = $rt->get_result()->fetch_assoc();
        $room_type_id = $roomRow['room_type_id'] ?? null;
        $ins = $conn->prepare("INSERT INTO reviews (user_id, room_type_id, room_id, rating, comment, is_visible) VALUES (?, ?, ?, ?, ?, 1)");
        $ins->bind_param("iiiis", $user_id, $room_type_id, $room_id, $rating, $comment);
        $ins->execute();
        if ($ins->affected_rows) {
          if ($return_to) {
            header('Location: '. $return_to .'?msg=review_submitted'); exit;
          }
          header('Location: room_details.php?id=' . intval($room_id)); exit;
        } else {
          if ($return_to) {
            header('Location: '. $return_to .'?err=cannot_submit_review'); exit;
          }
          $msg = 'Failed to submit review.';
        }
      }
    }
  }
}
// fetch rooms that the user has completed (checked_out)
$roomsDone = $conn->prepare("SELECT DISTINCT r.id, r.room_number, t.name as room_type FROM bookings b JOIN rooms r ON b.room_id=r.id JOIN room_types t ON r.room_type_id=t.id WHERE b.user_id=? AND b.status='checked_out'");
$roomsDone->bind_param("i", $user_id);
$roomsDone->execute();
$roomsDoneRes = $roomsDone->get_result();
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$sql = $isAdmin
  ? "SELECT r.*, t.name as room_type FROM reviews r LEFT JOIN room_types t ON r.room_type_id=t.id WHERE r.user_id=? ORDER BY r.created_at DESC"
  : "SELECT r.*, t.name as room_type FROM reviews r LEFT JOIN room_types t ON r.room_type_id=t.id WHERE r.user_id=? AND r.is_visible=1 ORDER BY r.created_at DESC";
$myReviews = $conn->prepare($sql);
$myReviews->bind_param("i", $user_id);
$myReviews->execute();
$myReviewsRes = $myReviews->get_result();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Reviews | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container mt-4 col-md-8">
  <h3>Leave a Review</h3>
  <a href="dashboard.php" class="btn btn-secondary mb-3">← Back</a>
  <?php if(!empty($_GET['msg']) && $_GET['msg']==='review_submitted'): ?><div class="alert alert-success">Review submitted.</div><?php endif; ?>
  <?php if(!empty($_GET['err']) && $_GET['err']==='cannot_submit_review'): ?><div class="alert alert-danger">You cannot submit review at this time.</div><?php endif; ?>
  <?php if($msg): ?><div class="alert alert-info"><?=$msg?></div><?php endif; ?>
  <form method="post">
    <?=csrf_input_field()?>
    <label>Target</label>
    <select name="room_id" class="form-select mb-2" required>
      <option value="">Choose...</option>
      <option value="hotel">Overall hotel review</option>
      <?php while($rd = $roomsDoneRes->fetch_assoc()): ?>
        <option value="<?=intval($rd['id'])?>">Room <?=htmlspecialchars($rd['room_number'])?> — <?=htmlspecialchars($rd['room_type'])?></option>
      <?php endwhile; ?>
    </select>
    <label>Rating (1-5)</label>
    <select name="rating" class="form-select mb-2" required>
      <?php for($i=5;$i>=1;$i--): ?><option value="<?=$i?>"><?=$i?></option><?php endfor; ?>
    </select>
    <label>Comment</label>
    <textarea name="comment" class="form-control mb-2"></textarea>
    <button class="btn btn-primary">Submit Review</button>
  </form>

  <hr>
  <h4>Recent Overall Hotel Reviews</h4>
  <?php
    $hotelList = $conn->query("SELECT rv.*, u.first_name, u.last_name FROM reviews rv JOIN users u ON rv.user_id=u.id WHERE rv.room_id IS NULL AND rv.is_visible=1 ORDER BY rv.created_at DESC LIMIT 10");
  ?>
  <?php if ($hotelList && $hotelList->num_rows): ?>
      <?php while($hr=$hotelList->fetch_assoc()): ?>
        <div class="border p-2 mb-2">
          <strong><?=htmlspecialchars($hr['first_name'].' '.$hr['last_name'])?></strong>
          <div>Rating: <?=intval($hr['rating'])?>/5</div>
          <?php $hsafe = htmlspecialchars($hr['comment']); ?>
          <div class="review-text break-word"><?=nl2br($hsafe)?></div>
        </div>
      <?php endwhile; ?>
  <?php else: ?>
    <div class="text-muted">No overall hotel reviews yet.</div>
  <?php endif; ?>

  <hr>
  <h4>My Reviews</h4>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Type</th>
        <th>Rating</th>
        <th>Comment</th>
        <?php if ($isAdmin): ?><th>Visible</th><?php endif; ?>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php while($r=$myReviewsRes->fetch_assoc()): ?>
      <tr>
        <td><?=htmlspecialchars($r['room_type'] ?? 'Hotel')?></td>
        <td><?=intval($r['rating'])?></td>
        <td>
          <?php $safe = htmlspecialchars($r['comment']); ?>
          <div class="review-text break-word" data-full="<?=htmlentities($safe)?>"><?=nl2br($safe)?></div>
        </td>
        <?php if ($isAdmin): ?><td><?=$r['is_visible']? 'Yes':'No'?></td><?php endif; ?>
        <td><?=htmlspecialchars($r['created_at'])?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<style>
  .review-text { max-width: 100%; overflow: hidden; }
  .break-word { overflow-wrap: anywhere; word-break: break-word; white-space: pre-wrap; }
  .review-text.clamped { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
  .show-more { cursor: pointer; color: #0d6efd; text-decoration: underline; font-size: 0.9rem; }
</style>
<script>
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.review-text').forEach(function(el){
      var text = el.textContent || el.innerText || '';
      if (text.length > 300) {
        el.classList.add('clamped');
        var btn = document.createElement('div');
        btn.className = 'show-more';
        btn.textContent = 'Show more';
        btn.addEventListener('click', function(){
          if (el.classList.contains('clamped')) {
            el.classList.remove('clamped');
            btn.textContent = 'Show less';
          } else {
            el.classList.add('clamped');
            btn.textContent = 'Show more';
          }
        });
        el.parentNode.appendChild(btn);
      }
    });
  });
</script>
</body></html>
