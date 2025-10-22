<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
include __DIR__ . "/user_sidebar.php";
$booking_id = intval($_GET['booking_id'] ?? 0);
if ($booking_id <= 0) die('Invalid booking id');
$stmt = $conn->prepare("SELECT b.*, r.room_number, t.name as room_type, u.first_name, u.middle_name, u.last_name FROM bookings b JOIN rooms r ON b.room_id=r.id JOIN room_types t ON r.room_type_id=t.id JOIN users u ON b.user_id=u.id WHERE b.id=? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$bk = $stmt->get_result()->fetch_assoc();
if (!$bk) die('Booking not found');
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $bk['user_id']) die('Unauthorized');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Booking Details</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #dc3545;
      --primary-hover: #c82333;
      --primary-light: rgba(220, 53, 69, 0.1);
    }
    
    body {
      background-color: #f8f9fa;
      color: #333;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
    }
    
    .main-content {
      margin-left: 90px;
      padding: 20px;
      transition: all 0.3s ease;
      min-height: 100vh;
    }
    
    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 0 15px;
    }
    
    .page-header {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
      color: white;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 25px;
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
      position: relative;
    }
    
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      margin-bottom: 20px;
      overflow: hidden;
    }
    
    .card-header {
      background-color: var(--primary-color);
      color: white;
      font-weight: 600;
      padding: 15px 20px;
      border: none;
    }
    
    .card-body {
      padding: 25px;
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      border: none;
      transition: all 0.2s ease;
      font-weight: 600;
      padding: 10px 20px;
    }
    
    .btn-primary:hover {
      background-color: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
    
    .btn-light {
      background-color: white;
      color: var(--primary-color);
      border: 1px solid var(--primary-color);
      font-weight: 600;
      padding: 10px 20px;
      transition: all 0.2s ease;
    }
    
    .btn-light:hover {
      background-color: var(--primary-color);
      color: white;
      transform: translateY(-2px);
    }
    
    .badge {
      font-weight: 500;
      padding: 6px 12px;
      border-radius: 20px;
    }
    
    .bg-success {
      background-color: #28a745 !important;
    }
    
    .bg-warning {
      background-color: #ffc107 !important;
      color: #212529 !important;
    }
    
    .bg-secondary {
      background-color: #6c757d !important;
    }
    
    .text-danger {
      color: var(--primary-color) !important;
      font-weight: 600;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .booking-details-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
    }
    
    .detail-item {
      margin-bottom: 15px;
    }
    
    .detail-label {
      font-weight: 600;
      color: #555;
      margin-bottom: 5px;
    }
    
    .detail-value {
      color: #333;
      font-size: 1.05rem;
    }
    
    /* Mobile Back Button */
    .mobile-back-btn {
      display: none;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        padding: 15px;
        padding-top: 70px;
      }
      
      .container {
        padding: 0 10px;
      }
      
      .page-header {
        padding: 15px;
        margin-bottom: 20px;
      }
      
      .card-body {
        padding: 20px;
      }
      
      .booking-details-grid {
        grid-template-columns: 1fr;
        gap: 10px;
      }
      
      /* Hide desktop back button on mobile */
      .desktop-back-btn {
        display: none;
      }
      
      /* Show mobile back button */
      .mobile-back-btn {
        display: block;
        position: fixed;
        top: 15px;
        right: 15px;
        z-index: 1000;
        background: var(--primary-color);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
      }
      
      .mobile-back-btn:hover {
        background: var(--primary-hover);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      }
    }
    
    @media (max-width: 576px) {
      .page-header {
        padding: 12px;
      }
      
      .card-body {
        padding: 15px;
      }
      
      .mobile-back-btn {
        padding: 6px 10px;
        font-size: 0.8rem;
      }
      
      .btn-primary, .btn-light {
        width: 100%;
        margin-bottom: 10px;
      }
    }
    
    @media (max-width: 480px) {
      .main-content {
        padding: 10px;
        padding-top: 70px;
      }
      
      .page-header h3 {
        font-size: 1.4rem;
      }
      
      .detail-value {
        font-size: 1rem;
      }
      
      .mobile-back-btn {
        padding: 5px 8px;
        font-size: 0.75rem;
      }
    }
  </style>
</head>
<body>
  <!-- Mobile Back Button (only visible on mobile) -->
  <a href="bookings.php" class="mobile-back-btn">
    <i class="fas fa-arrow-left"></i>
    Back
  </a>

  <div class="main-content">
    <div class="container mt-4 mb-5">
      <div class="page-header d-flex justify-content-between align-items-center">
        <h3 class="m-0"><i class="fas fa-calendar-check me-2"></i>Booking Details</h3>
        <a href="bookings.php" class="btn btn-light desktop-back-btn">
          <i class="fas fa-arrow-left me-2"></i>Back to Bookings
        </a>
      </div>

      <div class="card">
        <div class="card-header">
          <i class="fas fa-receipt me-2"></i>Booking #<?= htmlspecialchars($bk['id']) ?>
        </div>
        <div class="card-body">
          <div class="booking-details-grid">
            <div class="detail-item">
              <div class="detail-label">Customer</div>
              <div class="detail-value"><?= htmlspecialchars($bk['first_name'].' '.($bk['middle_name']? $bk['middle_name'].' ':'').$bk['last_name']) ?></div>
            </div>
            
            <div class="detail-item">
              <div class="detail-label">Room</div>
              <div class="detail-value"><?= htmlspecialchars($bk['room_number']) ?> (<?= htmlspecialchars($bk['room_type']) ?>)</div>
            </div>
            
            <div class="detail-item">
              <div class="detail-label">Start Time</div>
              <div class="detail-value"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($bk['start_time']))) ?></div>
            </div>
            
            <div class="detail-item">
              <div class="detail-label">End Time</div>
              <div class="detail-value"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($bk['end_time']))) ?></div>
            </div>
            
            <div class="detail-item">
              <div class="detail-label">Duration</div>
              <div class="detail-value"><?= intval($bk['hours']) ?> hour<?= $bk['hours'] > 1 ? 's' : '' ?></div>
            </div>
            
            <div class="detail-item">
              <div class="detail-label">Total Amount</div>
              <div class="detail-value text-danger">₱<?= number_format($bk['total_amount'],2) ?></div>
            </div>
            
            <div class="detail-item">
              <div class="detail-label">Status</div>
              <div class="detail-value">
                <span class="badge 
                  <?= $bk['status'] === 'checked_out' ? 'bg-success' : 
                       ($bk['status'] === 'pending' ? 'bg-warning' : 'bg-secondary') ?>">
                  <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $bk['status']))) ?>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] == $bk['user_id'] && $bk['status'] === 'checked_out'): ?>
        <div class="card">
          <div class="card-header">
            <i class="fas fa-star me-2"></i>Leave a Review
          </div>
          <div class="card-body">
            <form method="post" action="reviews.php">
              <?php require_once __DIR__.'/includes/csrf.php'; echo csrf_input_field(); ?>
              <input type="hidden" name="room_id" value="<?= intval($bk['room_id']) ?>">
              <input type="hidden" name="return_to" value="dashboard.php">
              
              <div class="mb-3">
                <label class="form-label fw-semibold">Rating</label>
                <select name="rating" class="form-select">
                  <option value="5">★★★★★ - Excellent</option>
                  <option value="4">★★★★☆ - Good</option>
                  <option value="3">★★★☆☆ - Average</option>
                  <option value="2">★★☆☆☆ - Poor</option>
                  <option value="1">★☆☆☆☆ - Terrible</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label fw-semibold">Comment</label>
                <textarea name="comment" class="form-control" placeholder="Share your experience with this room..." rows="4"></textarea>
              </div>
              
              <button class="btn btn-primary w-100">
                <i class="fas fa-paper-plane me-2"></i>Submit Review
              </button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>