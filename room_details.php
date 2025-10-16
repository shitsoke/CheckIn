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
$ratingFilter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$reviewsSql = "SELECT rv.*, u.first_name, u.last_name, p.display_name FROM reviews rv JOIN users u ON rv.user_id=u.id LEFT JOIN profiles p ON p.user_id=u.id WHERE rv.room_id=? AND rv.is_visible=1";
if ($ratingFilter >= 1 && $ratingFilter <= 5) { $reviewsSql .= " AND rv.rating = ?"; $reviews = $conn->prepare($reviewsSql . " ORDER BY rv.created_at DESC"); $reviews->bind_param("ii", $room_id, $ratingFilter); }
else { $reviews = $conn->prepare($reviewsSql . " ORDER BY rv.created_at DESC"); $reviews->bind_param("i", $room_id); }
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
function parseLocalDatetimeLocal(v) {
  if (!v) return NaN;
  const parts = v.split('T'); if (parts.length !== 2) return NaN;
  const date = parts[0].split('-').map(x=>parseInt(x,10));
  const time = parts[1].split(':').map(x=>parseInt(x,10));
  if (date.length<3 || time.length<2) return NaN;
  return new Date(date[0], date[1]-1, date[2], time[0], time[1], 0, 0).getTime();
}

function calcEstimateFromHours(rateId, hoursId, totalId) {
  const rateEl = document.getElementById(rateId);
  const hoursEl = document.getElementById(hoursId);
  const totalEl = document.getElementById(totalId);
  if (!rateEl || !hoursEl || !totalEl) return;
  const rate = parseFloat(rateEl.value);
  const hours = parseInt(hoursEl.value);
  if (!isNaN(rate) && !isNaN(hours)) {
    const rateCents = Math.round(rate * 100);
    let totalCents = rateCents * hours;
    totalEl.value = (totalCents/100).toFixed(2);
  } else totalEl.value = '';
}

function calcEstimateFromTimes(rateId, startId, endId, totalId) {
  const rate = parseFloat(document.getElementById(rateId).value);
  const start = document.getElementById(startId).value;
  const end = document.getElementById(endId).value;
  if (!start || !end || isNaN(rate)) { document.getElementById(totalId).value = ''; return; }
  const sMs = parseLocalDatetimeLocal(start);
  const eMs = parseLocalDatetimeLocal(end);
  if (isNaN(sMs) || isNaN(eMs) || eMs <= sMs) { document.getElementById(totalId).value = ''; return; }
  const hours = Math.ceil((eMs - sMs) / (1000*60*60));
  const rateCents = Math.round(rate * 100);
  let totalCents = rateCents * hours;
  document.getElementById(totalId).value = (totalCents/100).toFixed(2);
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
  <?php if (!empty($_SESSION['booking_error'])): ?><div class="alert alert-danger"><?=htmlspecialchars($_SESSION['booking_error'])?></div><?php unset($_SESSION['booking_error']); endif; ?>
  <h3>Room <?=htmlspecialchars($room['room_number'])?> — <?=htmlspecialchars($room['type'])?></h3>
  <div class="alert alert-warning">Note: Rates may vary and are subject to change. Final price confirmed at booking.</div>
  <div class="row">
    <div class="col-md-8">
      <div class="mb-3">
        <?php if ($imgsRes->num_rows): ?>
          <div class="row">
            <?php while($im=$imgsRes->fetch_assoc()): ?>
              <div class="col-md-4 mb-2"><img src="<?=htmlspecialchars($im['filepath'])?>" class="img-fluid click-enlarge" data-src="<?=htmlspecialchars($im['filepath'])?>" style="height:160px;object-fit:cover"></div>
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
      <form method="get" class="mb-2">
        <input type="hidden" name="id" value="<?=intval($room_id)?>">
        <label>Filter by rating</label>
        <select name="rating" class="form-select w-auto d-inline-block ms-2" onchange="this.form.submit();">
          <option value="">All</option>
          <?php for($i=5;$i>=1;$i--): ?><option value="<?=$i?>" <?=($ratingFilter==$i)?'selected':''?>><?=$i?>/5</option><?php endfor; ?>
        </select>
      </form>
      <?php if ($reviewsRes->num_rows): ?>
        <?php while($rv=$reviewsRes->fetch_assoc()): ?>
            <div class="border p-2 mb-2">
            <?php require_once __DIR__ . '/includes/name_helper.php'; ?>
            <strong><?=htmlspecialchars(!empty($rv['display_name']) ? $rv['display_name'] : ($rv['first_name'].' '.$rv['last_name']))?></strong>
            <span class="text-muted"> — <?=htmlspecialchars($rv['created_at'])?></span>
            <div>Rating: <?=intval($rv['rating'])?>/5</div>
            <div class="break-word review-text"><?=nl2br(htmlspecialchars($rv['comment']))?></div>
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
            $hotelRev = $conn->query("SELECT rv.*, u.first_name, u.last_name, p.display_name FROM reviews rv JOIN users u ON rv.user_id=u.id LEFT JOIN profiles p ON p.user_id=u.id WHERE rv.room_id IS NULL AND rv.is_visible=1 ORDER BY rv.created_at DESC LIMIT 5");
          ?>
          <?php if ($hotelRev && $hotelRev->num_rows): ?>
            <?php while($hr=$hotelRev->fetch_assoc()): ?>
                <div class="border p-2 mb-2">
                  <strong><?=htmlspecialchars(!empty($hr['display_name']) ? $hr['display_name'] : ($hr['first_name'].' '.$hr['last_name']))?></strong>
                  <div><?=intval($hr['rating'])?>/5</div>
                  <div class="break-word review-text"><?=nl2br(htmlspecialchars($hr['comment']))?></div>
                </div>
              <?php endwhile; ?>
          <?php else: ?>
            <div class="text-muted">No hotel reviews yet.</div>
          <?php endif; ?>

      <hr>
      <h5>Book / Estimate</h5>
      <?php if ($room['status'] === 'available'): ?>
      <form method="post" action="book_room.php" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
        <?=csrf_input_field()?>
        <input type="hidden" name="room_id" value="<?=$room_id?>">
        <input type="hidden" id="rate_detail" value="<?=$room['hourly_rate']?>">
        <label>Start (date & time)</label>
  <input name="start_time" id="start_detail" type="datetime-local" step="3600" required class="form-control mb-2" onchange="normalizeDatetimeToHour('start_detail'); calcEstimateFromTimes('rate_detail','start_detail','end_detail','total_detail')">
        <label>End (date & time)</label>
  <input name="end_time" id="end_detail" type="datetime-local" step="3600" required class="form-control mb-2" onchange="normalizeDatetimeToHour('end_detail'); calcEstimateFromTimes('rate_detail','start_detail','end_detail','total_detail')">
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
<?php require_once __DIR__ . '/includes/image_modal.php'; ?>
<script>
function normalizeDatetimeToHour(id) {
  const el = document.getElementById(id);
  if (!el) return;
  const v = el.value; if (!v) return;
  // parse as local YYYY-MM-DDTHH:MM and round up to next hour
  const parts = v.split('T'); if (parts.length !== 2) return;
  const date = parts[0].split('-').map(x=>parseInt(x,10));
  const time = parts[1].split(':').map(x=>parseInt(x,10));
  if (date.length<3 || time.length<2) return;
  const dt = new Date(date[0], date[1]-1, date[2], time[0], time[1], 0, 0);
  if (isNaN(dt.getTime())) return;
  if (dt.getMinutes() === 0 && dt.getSeconds() === 0) return;
  dt.setHours(dt.getHours() + 1);
  dt.setMinutes(0);
  dt.setSeconds(0);
  function pad(n){return n<10? '0'+n: n}
  el.value = dt.getFullYear()+'-'+pad(dt.getMonth()+1)+'-'+pad(dt.getDate())+'T'+pad(dt.getHours())+':00';
}
</script>