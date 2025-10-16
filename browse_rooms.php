<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
require_once "includes/csrf.php";

// Filters
$where = ['r.is_visible = 1'];
$params = [];
$types = '';
if (!empty($_GET['q'])) { $where[] = "(r.room_number LIKE ? OR r.description LIKE ? OR t.name LIKE ?)"; $params[] = '%'.$_GET['q'].'%'; $params[] = '%'.$_GET['q'].'%'; $params[] = '%'.$_GET['q'].'%'; $types .= 'sss'; }
if (!empty($_GET['room_type'])) { $where[] = "r.room_type_id = ?"; $params[] = intval($_GET['room_type']); $types .= 'i'; }
if (!empty($_GET['status'])) { $where[] = "r.status = ?"; $params[] = $_GET['status']; $types .= 's'; }

$where_sql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT r.id, r.room_number, r.status, t.name AS type, t.hourly_rate, r.description,
  (SELECT ri.filepath FROM room_images ri WHERE ri.room_id=r.id ORDER BY ri.is_primary DESC, ri.id ASC LIMIT 1) AS thumb,
    (SELECT IFNULL(ROUND(AVG(rw.rating),2),NULL) FROM reviews rw WHERE rw.room_id=r.id AND rw.is_visible=1) AS avg_rating
  FROM rooms r
  JOIN room_types t ON r.room_type_id = t.id
  " . $where_sql . "
  ORDER BY (r.status='available') DESC, r.room_number ASC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

$typesList = $conn->query("SELECT * FROM room_types");
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Browse Rooms | CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script>
function calcEstimateFromHours(rateId, hoursId, totalId) {
  const rate = parseFloat(document.getElementById(rateId).value);
  const hours = parseInt(document.getElementById(hoursId).value);
  if (!isNaN(rate) && !isNaN(hours)) {
    // integer cents arithmetic to avoid floating errors
    const rateCents = Math.round(rate * 100);
    let totalCents = rateCents * hours;
    // server no longer applies automatic discount — keep simple multiplication
    document.getElementById(totalId).value = (totalCents/100).toFixed(2);
  } else document.getElementById(totalId).value = '';
}
</script>
<script>
function parseLocalDatetimeLocal(v) {
  // v is like "YYYY-MM-DDTHH:MM"; parse as local time to avoid Date inconsistencies
  if (!v) return NaN;
  const parts = v.split('T'); if (parts.length !== 2) return NaN;
  const date = parts[0].split('-').map(x=>parseInt(x,10));
  const time = parts[1].split(':').map(x=>parseInt(x,10));
  if (date.length<3 || time.length<2) return NaN;
  // month is 0-based for Date
  return new Date(date[0], date[1]-1, date[2], time[0], time[1], 0, 0).getTime();
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
  // server no longer applies automatic discount; keep total as simple rate*hours
  document.getElementById(totalId).value = (totalCents/100).toFixed(2);
}
</script>
<script>
function normalizeDatetimeToHour(id) {
  const el = document.getElementById(id);
  if (!el) return;
  const v = el.value; if (!v) return;
  // parse local components
  const parts = v.split('T'); if (parts.length !== 2) return;
  const d = parts[0].split('-').map(x=>parseInt(x,10));
  const t = parts[1].split(':').map(x=>parseInt(x,10));
  if (d.length<3 || t.length<2) return;
  const year=d[0], month=d[1], day=d[2], hour=t[0], min=t[1];
  if (min === 0) return; // already aligned
  // round up to next hour
  let newHour = hour + 1;
  let newDay = day; let newMonth = month; let newYear = year;
  // handle day/month overflow simply by using Date()
  const dt = new Date(year, month-1, day, newHour, 0, 0, 0);
  function pad(n){return n<10? '0'+n: n}
  el.value = dt.getFullYear()+'-'+pad(dt.getMonth()+1)+'-'+pad(dt.getDate())+'T'+pad(dt.getHours())+':00';
}
</script>
</script>
</head>
<body class="bg-light">
<div class="container mt-4">
  <h3>Rooms</h3>
  <a href="dashboard.php" class="btn btn-secondary mb-3">← Back</a>
  <?php if (!empty($_SESSION['booking_error'])): ?><div class="alert alert-danger"><?=htmlspecialchars($_SESSION['booking_error'])?></div><?php unset($_SESSION['booking_error']); endif; ?>
  <form method="get" class="row gy-2 gx-2 mb-3">
    <div class="col-md-4"><input name="q" value="<?=htmlspecialchars($_GET['q'] ?? '')?>" class="form-control" placeholder="Search (room number, type, description)"></div>
    <div class="col-md-3">
      <select name="room_type" class="form-select">
        <option value="">All types</option>
        <?php while($tt = $typesList->fetch_assoc()): ?>
          <option value="<?=$tt['id']?>" <?=(!empty($_GET['room_type']) && $_GET['room_type']==$tt['id'])? 'selected':''?>><?=htmlspecialchars($tt['name'])?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="">Any status</option>
        <option value="available" <?=(!empty($_GET['status']) && $_GET['status']=='available')? 'selected':''?>>Available</option>
        <option value="reserved" <?=(!empty($_GET['status']) && $_GET['status']=='reserved')? 'selected':''?>>Reserved</option>
        <option value="occupied" <?=(!empty($_GET['status']) && $_GET['status']=='occupied')? 'selected':''?>>Occupied</option>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
  </form>
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
    <div class="alert alert-warning">Prices may vary; final price is confirmed at booking.</div>
          <p><?=nl2br(htmlspecialchars($room['description']))?></p>
          <p>Rate: ₱<?=number_format($room['hourly_rate'],2)?>/hr</p>
          <p>Rating: <?= $room['avg_rating'] ? number_format($room['avg_rating'],2).' / 5' : 'N/A' ?></p>
          <p>Status: <span class="badge bg-<?= $room['status']=='available' ? 'success':'danger' ?>">
            <?=ucfirst($room['status'])?></span></p>
          <div class="d-flex gap-2 mb-2 align-items-center">
            <?php if (!empty($room['thumb'])): ?>
              <img src="<?=htmlspecialchars($room['thumb'])?>" class="img-thumbnail click-enlarge" data-src="<?=htmlspecialchars($room['thumb'])?>" style="height:80px;object-fit:cover">
            <?php endif; ?>
            <a href="room_details.php?id=<?=$room['id']?>&from=browse" class="btn btn-outline-primary btn-sm">View Details</a>
          </div>
    <?php if ($room['status']=='available'): ?>
    <form action="book_room.php" method="post" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
  <?= csrf_input_field() ?>
  <input type="hidden" name="room_id" value="<?=$room['id']?>">
          <input type="hidden" name="return_to" value="browse_rooms.php">
  <input type="hidden" id="rate<?=$room['id']?>" value="<?=$room['hourly_rate']?>">
  <label>Start (date & time)</label>
  <input id="start<?=$room['id']?>" name="start_time" type="datetime-local" step="3600" required class="form-control mb-2" onchange="normalizeDatetimeToHour('start<?=$room['id']?>'); calcEstimateFromTimes('rate<?=$room['id']?>','start<?=$room['id']?>','end<?=$room['id']?>','total<?=$room['id']?>')">
  <label>End (date & time)</label>
  <input id="end<?=$room['id']?>" name="end_time" type="datetime-local" step="3600" required class="form-control mb-2" onchange="normalizeDatetimeToHour('end<?=$room['id']?>'); calcEstimateFromTimes('rate<?=$room['id']?>','start<?=$room['id']?>','end<?=$room['id']?>','total<?=$room['id']?>')">
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
<?php require_once __DIR__ . '/includes/image_modal.php'; ?>
</html>
