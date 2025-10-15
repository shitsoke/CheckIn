<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
require_once "includes/csrf.php";

$sql = "SELECT r.id, r.room_number, r.status, t.name AS type, t.hourly_rate, t.description,
  (SELECT ri.filepath FROM room_images ri WHERE ri.room_id=r.id ORDER BY ri.is_primary DESC, ri.id ASC LIMIT 1) AS thumb,
    (SELECT IFNULL(ROUND(AVG(rw.rating),2),NULL) FROM reviews rw WHERE rw.room_id=r.id AND rw.is_visible=1) AS avg_rating
  FROM rooms r
  JOIN room_types t ON r.room_type_id = t.id
  ORDER BY r.room_number ASC";
$result = $conn->query($sql);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Browse Rooms | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script>
function calcEstimate(rateId, hoursId, totalId) {
  const rate = parseFloat(document.getElementById(rateId).value);
  const hours = parseInt(document.getElementById(hoursId).value);
  if (!isNaN(rate) && !isNaN(hours)) {
    let total = rate * hours;
    if (hours >= 5) total *= 0.95; // 5% discount
    document.getElementById(totalId).value = total.toFixed(2);
  } else document.getElementById(totalId).value = '';
}
</script>
</head>
<body class="bg-light">
<div class="container mt-4">
  <h3>Rooms</h3>
  <a href="dashboard.php" class="btn btn-secondary mb-3">← Back</a>
  <div class="row">
    <?php while ($room = $result->fetch_assoc()): ?>
  <div class="col-md-4 mb-3">
      <div class="card">
        <?php if (!empty($room['thumb'])): ?>
          <img src="<?=htmlspecialchars($room['thumb'])?>" class="card-img-top" style="height:180px;object-fit:cover" alt="Room image">
        <?php else: ?>
          <div style="height:180px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#777">No image</div>
        <?php endif; ?>
        <div class="card-body">
          <h5>Room <?=htmlspecialchars($room['room_number'])?> (<?=htmlspecialchars($room['type'])?>)</h5>
          <p><?=nl2br(htmlspecialchars($room['description']))?></p>
          <p>Rate: ₱<?=number_format($room['hourly_rate'],2)?>/hr</p>
          <p>Rating: <?= $room['avg_rating'] ? number_format($room['avg_rating'],2).' / 5' : 'N/A' ?></p>
          <p>Status: <span class="badge bg-<?= $room['status']=='available' ? 'success':'danger' ?>">
            <?=ucfirst($room['status'])?></span></p>
          <div class="d-flex gap-2 mb-2">
            <a href="room_details.php?id=<?=$room['id']?>&from=browse" class="btn btn-outline-primary btn-sm">View Details</a>
          </div>
          <?php if ($room['status']=='available'): ?>
          <form action="book_room.php" method="post">
  <?= csrf_input_field() ?>
  <input type="hidden" name="room_id" value="<?=$room['id']?>">
  <input type="hidden" id="rate<?=$room['id']?>" value="<?=$room['hourly_rate']?>">
  <label>Hours:</label>
  <input id="hours<?=$room['id']?>" name="hours" type="number" min="1" required class="form-control mb-2"
    oninput="calcEstimate('rate<?=$room['id']?>','hours<?=$room['id']?>','total<?=$room['id']?>')">
  <label>Estimate (₱)</label>
  <input id="total<?=$room['id']?>" name="total_est" readonly class="form-control mb-2">
  <label>Payment:</label>
  <select name="payment" class="form-select mb-2" required>
    <option value="cash">Cash (pay at check-in)</option>
    <option value="online">Online (GCash / QR)</option>
  </select>
  <button class="btn btn-primary w-100">Book Now</button>
</form>

          <?php else: ?>
            <small>Not available</small>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>
</body>
</html>
