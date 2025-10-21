<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
include __DIR__ . "/user_sidebar.php";
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

// Get room types with their actual prices for the room type cards
$roomTypes = $conn->query("SELECT * FROM room_types ORDER BY hourly_rate ASC");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Browse Rooms | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #dc3545;
      --primary-hover: #c82333;
      --primary-light: rgba(220, 53, 69, 0.1);
    }
    
    .room-type-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: none;
      border-radius: 15px;
      overflow: hidden;
    }
    .room-type-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .room-type-img {
      height: 200px;
      object-fit: cover;
      width: 100%;
    }
    .room-type-badge {
      position: absolute;
      top: 15px;
      right: 15px;
      background: rgba(220, 53, 69, 0.9);
      color: white;
      padding: 5px 12px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.8rem;
    }
    .room-price {
      font-size: 1.5rem;
      font-weight: 700;
      color: #dc3545;
    }
    .room-features {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .room-features li {
      padding: 5px 0;
      color: #666;
    }
    .room-features li i {
      color: #28a745;
      margin-right: 8px;
    }
    .filter-section {
      background: white;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      margin-bottom: 30px;
      border-left: 4px solid #dc3545;
    }
    .room-grid {
      margin-top: 30px;
    }
    .no-rooms-section {
      background: white;
      border-radius: 15px;
      padding: 40px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      margin: 30px 0;
    }
    .no-rooms-icon {
      font-size: 4rem;
      color: #6c757d;
      margin-bottom: 20px;
    }
    .search-highlight {
      color: #dc3545;
      font-weight: 600;
    }

    /* Updated Button Styles */
    .btn-primary, .btn-register {
      background: #dc3545;
      border: none;
      height: 45px;
      font-weight: 600;
      color: white;
      transition: 0.3s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 25px;
    }

    .btn-primary:hover, .btn-register:hover {
      background: #b32030;
      transform: translateY(-2px);
    }

    .btn-secondary {
      background: #6c757d;
      border: none;
      height: 45px;
      font-weight: 600;
      color: white;
      transition: 0.3s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 25px;
    }

    .btn-secondary:hover {
      background: #545b62;
      transform: translateY(-2px);
    }

    .btn-outline-primary {
      background: transparent;
      border: 2px solid #dc3545;
      height: 45px;
      font-weight: 600;
      color: #dc3545;
      transition: 0.3s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 20px;
    }

    .btn-outline-primary:hover {
      background: #dc3545;
      color: white;
      transform: translateY(-2px);
    }

    .btn-sm {
      height: 38px;
      padding: 0 18px;
      font-size: 0.875rem;
    }

    .btn:disabled {
      background: #6c757d;
      opacity: 0.6;
      transform: none !important;
    }

    /* Form button specific styles */
    .filter-section .btn-primary,
    .room-type-card .btn-primary,
    .no-rooms-section .btn-primary {
      width: 100%;
    }

    .no-rooms-section .btn-outline-primary {
      width: auto;
      min-width: 160px;
    }

    /* Back Button Styles */
    .btn-back {
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      border: none;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }

    .btn-back:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
      color: white;
      background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
    }

    .btn-back i {
      transition: transform 0.3s ease;
    }

    .btn-back:hover i {
      transform: translateX(-3px);
    }

    /* Header Section without card styling */
    .header-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding: 0;
      background: transparent;
      border-radius: 0;
      box-shadow: none;
    }

    .header-content {
      flex: 1;
    }

    .header-section h3 {
      margin: 0;
      color: #dc3545;
      font-size: 2rem;
      font-weight: 700;
    }

    .header-section p {
      color: #666;
      margin: 8px 0 0 0;
      font-size: 1.1rem;
    }

    .page-title {
      color: #dc3545;
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .page-subtitle {
      color: #6c757d;
      font-size: 1.1rem;
      margin-bottom: 25px;
    }
  </style>
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
</head>
<body class="bg-light">
<div class="container mt-4">
  <!-- Header Section without card styling -->
  <div class="header-section">
    <div class="header-content">
      <h1 class="page-title"><i class="fas fa-bed me-3"></i>Browse Our Rooms</h1>
      <p class="page-subtitle">Find and book the perfect room for your stay</p>
    </div>
  </div>
  
  <?php if (!empty($_SESSION['booking_error'])): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($_SESSION['booking_error'])?></div>
    <?php unset($_SESSION['booking_error']); ?>
  <?php endif; ?>
  <?php if (!empty($_SESSION['booking_form_values'])): ?>
    <script>
      try {
        const vals = <?= json_encode($_SESSION['booking_form_values']) ?>;
        if (vals.start_time) localStorage.setItem('checkin_start_auto', vals.start_time);
        if (vals.end_time) localStorage.setItem('checkin_end_auto', vals.end_time);
      } catch(e) {}
    </script>
    <?php unset($_SESSION['booking_form_values']); ?>
  <?php endif; ?>

  <!-- Filter Section - Moved to Top -->
  <div class="filter-section">
    <h4>Find Available Rooms</h4>
    <p class="text-muted">Use the filters below to find the perfect room for your needs</p>
    <form method="get" class="row gy-2 gx-2">
      <div class="col-md-4">
        <input name="q" value="<?=htmlspecialchars($_GET['q'] ?? '')?>" class="form-control" placeholder="Search (room number, type, description)">
      </div>
      <div class="col-md-3">
        <select name="room_type" class="form-select">
          <option value="">All types</option>
          <?php 
          $typesList->data_seek(0); // Reset pointer to beginning
          while($tt = $typesList->fetch_assoc()): ?>
            <option value="<?=$tt['id']?>" <?=(!empty($_GET['room_type']) && $_GET['room_type']==$tt['id'])? 'selected':''?>>
              <?=htmlspecialchars($tt['name'])?>
            </option>
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
      <div class="col-md-2">
        <button class="btn btn-primary w-100">Filter</button>
      </div>
    </form>
  </div>

  <!-- No Rooms Found Section -->
  <?php if ($result->num_rows == 0): ?>
    <div class="no-rooms-section">
      <div class="no-rooms-icon">
        <i class="fas fa-search"></i>
      </div>
      <h4>No rooms found matching your criteria</h4>
      <p class="text-muted mb-4">
        <?php if (!empty($_GET['q'])): ?>
          No results found for "<span class="search-highlight"><?=htmlspecialchars($_GET['q'])?></span>"
        <?php else: ?>
          We couldn't find any rooms matching your current filters
        <?php endif; ?>
      </p>
      <div class="d-flex justify-content-center gap-2 flex-wrap">
        <a href="browse_rooms.php" class="btn btn-primary">
          <i class="fas fa-refresh me-2"></i>Reset Filters
        </a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Available Rooms Grid -->
  <?php if ($result->num_rows > 0): ?>
    <div class="room-grid">
      <h4 class="mb-4">Available Rooms (<?= $result->num_rows ?> found)</h4>
      <div class="row">
        <?php while ($room = $result->fetch_assoc()): ?>
          <div class="col-md-4 mb-4">
            <div class="card room-type-card h-100">
              <?php if (!empty($room['thumb'])): ?>
                <img src="<?=htmlspecialchars($room['thumb'])?>" class="card-img-top room-type-img" alt="Room image">
              <?php else: ?>
                <div class="room-type-img bg-light d-flex align-items-center justify-content-center text-muted">
                  <i class="fas fa-image fa-3x"></i>
                </div>
              <?php endif; ?>
              <div class="card-body">
                <h5>Room <?=htmlspecialchars($room['room_number'])?> (<?=htmlspecialchars($room['type'])?>)</h5>
                <div class="alert alert-warning py-2">Prices may vary; final price is confirmed at booking.</div>
                <p class="card-text"><?=nl2br(htmlspecialchars($room['description'] ?? 'No description available for this room.'))?></p>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="room-price">₱<?=number_format($room['hourly_rate'],2)?><span class="text-muted" style="font-size: 1rem;">/hour</span></span>
                  <span class="badge bg-<?= $room['status']=='available' ? 'success':'danger' ?>">
                    <?=ucfirst($room['status'])?>
                  </span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                  <span>Rating: <?= $room['avg_rating'] ? number_format($room['avg_rating'],2).' / 5' : 'N/A' ?></span>
                  <a href="room_details.php?id=<?=$room['id']?>&from=browse" class="btn btn-outline-primary btn-sm">View Details</a>
                </div>
                
                <?php if ($room['status']=='available'): ?>
                  <form action="book_room.php" method="post" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                    <?= csrf_input_field() ?>
                    <input type="hidden" name="room_id" value="<?=$room['id']?>">
                    <input type="hidden" name="return_to" value="browse_rooms.php">
                    <input type="hidden" id="rate<?=$room['id']?>" value="<?=$room['hourly_rate']?>">
                    
                    <div class="mb-2">
                      <label class="form-label small">Start (date & time)</label>
                      <input id="start<?=$room['id']?>" name="start_time" type="datetime-local" step="3600" required class="form-control" onchange="(function(){ normalizeDatetimeToHour('start<?=$room['id']?>'); calcEstimateFromTimes('rate<?=$room['id']?>','start<?=$room['id']?>','end<?=$room['id']?>','total<?=$room['id']?>'); try{ localStorage.setItem('checkin_start_<?=$room['id']?>', this.value);}catch(e){} }).call(this)">
                    </div>
                    
                    <div class="mb-2">
                      <label class="form-label small">End (date & time)</label>
                      <input id="end<?=$room['id']?>" name="end_time" type="datetime-local" step="3600" required class="form-control" onchange="(function(){ normalizeDatetimeToHour('end<?=$room['id']?>'); calcEstimateFromTimes('rate<?=$room['id']?>','start<?=$room['id']?>','end<?=$room['id']?>','total<?=$room['id']?>'); try{ localStorage.setItem('checkin_end_<?=$room['id']?>', this.value);}catch(e){} }).call(this)">
                    </div>
                    
                    <div class="mb-2">
                      <label class="form-label small">Estimate (₱)</label>
                      <input id="total<?=$room['id']?>" name="total_est" readonly class="form-control">
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label small">Payment:</label>
                      <select name="payment" class="form-select" required>
                        <option value="cash">Cash (pay at check-in)</option>
                        <option value="online">Online (GCash / QR)</option>
                      </select>
                    </div>
                    
                    <button class="btn btn-primary w-100">Book Now</button>
                    <script>
                      // restore saved start/end values for this room (if user previously filled them)
                      try {
                        const s = localStorage.getItem('checkin_start_<?=$room['id']?>');
                        const e = localStorage.getItem('checkin_end_<?=$room['id']?>');
                        if (s) document.getElementById('start<?=$room['id']?>').value = s;
                        if (e) document.getElementById('end<?=$room['id']?>').value = e;
                          // if redirected back with server-side values, use them
                          try { const autoS = localStorage.getItem('checkin_start_auto'); if (autoS && !s) { document.getElementById('start<?=$room['id']?>').value = autoS; localStorage.removeItem('checkin_start_auto'); } } catch(e) {}
                          try { const autoE = localStorage.getItem('checkin_end_auto'); if (autoE && !e) { document.getElementById('end<?=$room['id']?>').value = autoE; localStorage.removeItem('checkin_end_auto'); } } catch(e) {}
                        if (s && e) calcEstimateFromTimes('rate<?=$room['id']?>','start<?=$room['id']?>','end<?=$room['id']?>','total<?=$room['id']?>');
                      } catch (err) {}
                    </script>
                  </form>
                <?php else: ?>
                  <button class="btn btn-secondary w-100" disabled>Not Available</button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  <?php endif; ?>

  

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
<?php require_once __DIR__ . '/includes/image_modal.php'; ?>
</html>