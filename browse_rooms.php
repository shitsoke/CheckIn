<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
require_once "includes/csrf.php";
include __DIR__ . "/user_sidebar.php";

// Filters
$where = ['r.is_visible = 1'];
$params = [];
$types = '';

if (!empty($_GET['q'])) {
  $where[] = "(r.room_number LIKE ? OR r.description LIKE ? OR t.name LIKE ?)";
  $params = array_merge($params, ['%'.$_GET['q'].'%', '%'.$_GET['q'].'%', '%'.$_GET['q'].'%']);
  $types .= 'sss';
}
if (!empty($_GET['room_type'])) {
  $where[] = "r.room_type_id = ?";
  $params[] = intval($_GET['room_type']);
  $types .= 'i';
}
if (!empty($_GET['status'])) {
  $where[] = "r.status = ?";
  $params[] = $_GET['status'];
  $types .= 's';
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT r.id, r.room_number, r.status, t.name AS type, t.hourly_rate, r.description,
  (SELECT ri.filepath FROM room_images ri WHERE ri.room_id=r.id ORDER BY ri.is_primary DESC, ri.id ASC LIMIT 1) AS thumb,
  (SELECT IFNULL(ROUND(AVG(rw.rating),2),NULL) FROM reviews rw WHERE rw.room_id=r.id AND rw.is_visible=1) AS avg_rating
  FROM rooms r
  JOIN room_types t ON r.room_type_id = t.id
  $where_sql
  ORDER BY (r.status='available') DESC, r.room_number ASC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

$typesList = $conn->query("SELECT * FROM room_types");
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Browse Rooms | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <style>
    :root {
      --primary-color: #dc3545;
      --primary-hover: #b32030;
    }
    
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      overflow-x: hidden;
      margin: 0;
      padding: 0;
    }
    
    .main-content {
      margin-left: 90px; /* Match sidebar width from user_sidebar.php */
      padding: 20px;
      transition: all 0.3s ease;
      min-height: 100vh;
    }
    
    .page-title {
      color: var(--primary-color);
      font-size: 2rem;
      font-weight: 700;
    }
    
    .page-subtitle {
      color: #6c757d;
      font-size: 1.1rem;
      margin-bottom: 25px;
    }
    
    .filter-section {
      background: white;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      margin-bottom: 30px;
      border-left: 4px solid var(--primary-color);
    }
    
    .room-type-card {
      border: none;
      border-radius: 15px;
      overflow: hidden;
      transition: all 0.3s ease;
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
    
    .room-price {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--primary-color);
    }
    
    .btn-primary {
      background: var(--primary-color);
      border: none;
      font-weight: 600;
    }
    
    .btn-primary:hover {
      background: var(--primary-hover);
    }
    
    .no-rooms-section {
      background: white;
      border-radius: 15px;
      padding: 40px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      margin: 30px 0;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        padding: 15px;
        padding-top: 70px; /* Space for hamburger button */
      }
    }
  </style>
</head>

<body>
  <!-- Sidebar is included via user_sidebar.php which provides:
       - Hamburger button (.sidebar-toggle)
       - Overlay (.sidebar-overlay) 
       - Sidebar (#ucSidebar)
  -->

  <!-- Main Content -->
  <div class="main-content">
    <div class="container-fluid">

      <h1 class="page-title"><i class="fas fa-bed me-3"></i>Browse Our Rooms</h1>
      <p class="page-subtitle">Find and book the perfect room for your stay</p>

      <?php if (!empty($_SESSION['booking_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['booking_error']) ?></div>
        <?php unset($_SESSION['booking_error']); ?>
      <?php endif; ?>

      <div class="filter-section">
        <h4>Find Available Rooms</h4>
        <form method="get" class="row gy-2 gx-2">
          <div class="col-md-4">
            <input name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="form-control" placeholder="Search (room number, type, description)">
          </div>
          <div class="col-md-3">
            <select name="room_type" class="form-select">
              <option value="">All types</option>
              <?php while ($tt = $typesList->fetch_assoc()): ?>
                <option value="<?= $tt['id'] ?>" <?= (!empty($_GET['room_type']) && $_GET['room_type'] == $tt['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($tt['name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3">
            <select name="status" class="form-select">
              <option value="">Any status</option>
              <option value="available" <?= (!empty($_GET['status']) && $_GET['status'] == 'available') ? 'selected' : '' ?>>Available</option>
              <option value="reserved" <?= (!empty($_GET['status']) && $_GET['status'] == 'reserved') ? 'selected' : '' ?>>Reserved</option>
              <option value="occupied" <?= (!empty($_GET['status']) && $_GET['status'] == 'occupied') ? 'selected' : '' ?>>Occupied</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-primary w-100">Filter</button>
          </div>
        </form>
      </div>

      <?php if ($result->num_rows == 0): ?>
        <div class="no-rooms-section">
          <i class="fas fa-search fa-4x mb-3 text-muted"></i>
          <h4>No rooms found</h4>
          <p class="text-muted">Try adjusting your filters or search keywords.</p>
          <a href="browse_rooms.php" class="btn btn-primary"><i class="fas fa-refresh me-2"></i>Reset Filters</a>
        </div>
      <?php else: ?>
        <div class="row">
          <?php while ($room = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
              <div class="card room-type-card h-100">
                <?php if (!empty($room['thumb'])): ?>
                  <img src="<?= htmlspecialchars($room['thumb']) ?>" class="room-type-img" alt="Room image">
                <?php else: ?>
                  <div class="room-type-img bg-light d-flex align-items-center justify-content-center text-muted">
                    <i class="fas fa-image fa-3x"></i>
                  </div>
                <?php endif; ?>
                <div class="card-body">
                  <h5>Room <?= htmlspecialchars($room['room_number']) ?> (<?= htmlspecialchars($room['type']) ?>)</h5>
                  <p><?= nl2br(htmlspecialchars($room['description'])) ?></p>
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="room-price">₱<?= number_format($room['hourly_rate'], 2) ?>/hr</span>
                    <span class="badge bg-<?= $room['status'] == 'available' ? 'success' : 'secondary' ?>">
                      <?= ucfirst($room['status']) ?>
                    </span>
                  </div>
                  <a href="room_details.php?id=<?= $room['id'] ?>&from=browse" class="btn btn-outline-primary w-100">View Details</a>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- No additional JavaScript needed - user_sidebar.php handles everything -->
</body>
</html>