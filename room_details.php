<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
require_once "includes/csrf.php";
$room_id = intval($_GET['id'] ?? 0);
if ($room_id <= 0) die('Invalid room id');
$stmt = $conn->prepare("SELECT r.*, t.name as type, t.hourly_rate, t.description AS type_description FROM rooms r JOIN room_types t ON r.room_type_id=t.id WHERE r.id=?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
if (!$room) die('Room not found');
// images
$imgs = $conn->prepare("SELECT * FROM room_images WHERE room_id=? ORDER BY id ASC");
$imgs->bind_param("i", $room_id);
$imgs->execute();
$imgsRes = $imgs->get_result();
// reviews for this room
$reviews = $conn->prepare("SELECT rv.*, u.first_name, u.last_name FROM reviews rv JOIN users u ON rv.user_id=u.id WHERE rv.room_id=? AND rv.is_visible=1 ORDER BY rv.created_at DESC");
$reviews->bind_param("i", $room_id);
$reviews->execute();
$reviewsRes = $reviews->get_result();
// overall hotel reviews
$overall = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as count_reviews FROM reviews WHERE is_visible=1")->fetch_assoc();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Room Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .break-word { overflow-wrap: anywhere; word-break: break-word; white-space: pre-wrap; }
</style>
<script>
function calcEstimate(rateId, hoursId, totalId) {
  const rateEl = document.getElementById(rateId);
  const hoursEl = document.getElementById(hoursId);
  const totalEl = document.getElementById(totalId);
  if (!rateEl || !hoursEl || !totalEl) return;
  const rate = parseFloat(rateEl.value);
  const hours = parseInt(hoursEl.value);
  if (!isNaN(rate) && !isNaN(hours)) {
    let total = rate * hours;
    if (hours >= 5) total *= 0.95;
    totalEl.value = total.toFixed(2);
  } else totalEl.value = '';
}
</script>
</head>
<body class="bg-light">
<div class="container mt-4">
  <?php
  // Back button: respect `from` parameter (browse or bookings) so user returns to origin
  $back = 'browse_rooms.php';
  if (isset($_GET['from']) && $_GET['from']==='bookings') $back = 'bookings.php';
  ?>
  <a href="<?=htmlspecialchars($back)?>" class="btn btn-secondary mb-3">← Back</a>
  <h3>Room <?=htmlspecialchars($room['room_number'])?> — <?=htmlspecialchars($room['type'])?></h3>
  <div class="row">
    <div class="col-md-8">
      <div class="mb-3">
        <?php if ($imgsRes->num_rows): ?>
          <div class="row">
            <?php while($im=$imgsRes->fetch_assoc()): ?>
              <div class="col-md-4 mb-2"><img src="<?=htmlspecialchars($im['filepath'])?>" class="img-fluid" style="height:160px;object-fit:cover"></div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div style="height:200px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#777">No images</div>
        <?php endif; ?>
      </div>
  <p><?=nl2br(htmlspecialchars($room['type_description'] ?? $room['description'] ?? ''))?></p>
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
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="text-muted">No reviews yet for this room.</div>
      <?php endif; ?>
    </div>
    <div class="col-md-4">
          <h5>Hotel Overall</h5>
          <div>Average rating: <?= $overall['avg_rating'] ? number_format($overall['avg_rating'],2) : 'N/A' ?></div>
          <div>Total reviews: <?= $overall['count_reviews'] ?></div>
          <div class="mt-2">
            <a href="reviews.php" class="btn btn-sm btn-outline-secondary">See all overall reviews</a>
          </div>

          <hr>
          <h6>Recent hotel reviews</h6>
          <?php
            $hotelRev = $conn->query("SELECT rv.*, u.first_name, u.last_name FROM reviews rv JOIN users u ON rv.user_id=u.id WHERE rv.room_id IS NULL AND rv.is_visible=1 ORDER BY rv.created_at DESC LIMIT 5");
          ?>
          <?php if ($hotelRev && $hotelRev->num_rows): ?>
            <?php while($hr=$hotelRev->fetch_assoc()): ?>
              <div class="border p-2 mb-2">
                <strong><?=htmlspecialchars($hr['first_name'].' '.$hr['last_name'])?></strong>
                <div><?=intval($hr['rating'])?>/5</div>
                <div class="break-word"><?=nl2br(htmlspecialchars($hr['comment']))?></div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-muted">No hotel reviews yet.</div>
          <?php endif; ?>

      <hr>
      <h5>Book / Estimate</h5>
      <?php if ($room['status'] === 'available'): ?>
      <form method="post" action="book_room.php">
        <?=csrf_input_field()?>
        <input type="hidden" name="room_id" value="<?=$room_id?>">
        <label>Hours</label>
        <input name="hours" type="number" min="1" required class="form-control mb-2" id="hours_detail" oninput="calcEstimate('rate_detail','hours_detail','total_detail')">
        <input type="hidden" id="rate_detail" value="<?=$room['hourly_rate']?>">
        <label>Estimate (₱)</label>
        <input id="total_detail" name="total_est" readonly class="form-control mb-2">
        <label>Payment</label>
        <select name="payment" class="form-select mb-2" required>
          <option value="cash">Cash</option>
          <option value="online">Online</option>
        </select>
        <button class="btn btn-success w-100 mb-3">Book Now</button>
      </form>
      <?php else: ?>
        <div class="alert alert-warning">This room is not available for booking.</div>
      <?php endif; ?>

      <hr>
      <h5>Leave a review</h5>
          <?php if (!empty($_GET['err']) && $_GET['err']==='cannot_submit_review'): ?>
            <div class="alert alert-danger">You can only review this room after completing (checked out) a booking for it.</div>
          <?php endif; ?>
      <form method="post" action="reviews.php">
        <?php require_once __DIR__.'/includes/csrf.php'; echo csrf_input_field(); ?>
        <input type="hidden" name="room_type_id" value="<?=intval($room['room_type_id'])?>">
        <input type="hidden" name="room_id" value="<?=$room_id?>">
  <?php $ret = 'room_details.php?id='.$room_id; if(isset($_GET['from']) && $_GET['from']==='bookings') $ret .= '&from=bookings'; ?>
  <input type="hidden" name="return_to" value="<?=htmlspecialchars($ret)?>">
        <label>Rating</label>
        <select name="rating" class="form-select mb-2">
          <option value="5">5</option>
          <option value="4">4</option>
          <option value="3">3</option>
          <option value="2">2</option>
          <option value="1">1</option>
        </select>
        <label>Comment</label>
        <textarea name="comment" class="form-control mb-2"></textarea>
        <button class="btn btn-primary">Submit Review</button>
      </form>

    </div>
  </div>
</div>
</body></html>