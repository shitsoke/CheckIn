<?php
session_start();
require_once "includes/auth_check.php";
require_once "includes/csrf.php";
require_once "db_connect.php";
require_once __DIR__ . '/includes/name_helper.php';

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
<html>
<head>
  <meta charset="utf-8">
  <title>Reviews | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-solid: #dc3545;
      --primary-hover: #b32030;
    }
    
    body {
      background-color: #f9f5f5;
    }
    
    .container {
      margin-top: 20px;
      margin-bottom: 20px;
    }
    
    /* Button Styles */
    .btn-primary {
      background: var(--primary-solid);
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

    .btn-primary:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
    }

    .btn-secondary {
      background: var(--primary-solid);
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
      background: var(--primary-hover);
      transform: translateY(-2px);
    }

    .form-select:focus, .form-control:focus {
      border-color: var(--primary-solid);
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .alert-success {
      border-left: 4px solid #28a745;
    }
    
    .alert-danger {
      border-left: 4px solid var(--primary-solid);
    }
    
    .alert-info {
      border-left: 4px solid #17a2b8;
    }
    
    .review-item {
      border-bottom: 1px solid #e9ecef;
      padding: 15px 0;
      margin-bottom: 0;
    }
    
    .review-item:last-child {
      border-bottom: none;
    }
    
    .review-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }
    
    .reviewer-name {
      font-weight: 600;
      color: var(--primary-solid);
    }
    
    .review-rating {
      color: #ffc107;
      font-weight: bold;
    }
    
    .table th {
      background-color: var(--primary-solid);
      color: white;
      border-color: var(--primary-solid);
    }
    
    .table-hover tbody tr:hover {
      background-color: rgba(220, 53, 69, 0.05);
    }
    
    hr {
      border-color: rgba(220, 53, 69, 0.2);
      margin: 2rem 0;
    }
    
    h3, h4 {
      color: var(--primary-solid);
      margin-bottom: 1.5rem;
    }
    
    .review-text { 
      max-width: 100%; 
      overflow: hidden; 
    }
    
    .break-word { 
      overflow-wrap: anywhere; 
      word-break: break-word; 
      white-space: pre-wrap; 
    }
    
    .review-text.clamped { 
      display: -webkit-box; 
      -webkit-line-clamp: 3; 
      -webkit-box-orient: vertical; 
      overflow: hidden; 
    }
    
    .show-more { 
      cursor: pointer; 
      color: var(--primary-solid); 
      text-decoration: underline; 
      font-size: 0.9rem; 
      font-weight: 600;
      margin-top: 8px;
    }
    
    .show-more:hover {
      color: var(--primary-hover);
    }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4 col-md-10">
  <h3>Leave a Review</h3>
  <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
  
  <?php if(!empty($_GET['msg']) && $_GET['msg']==='review_submitted'): ?>
    <div class="alert alert-success">Review submitted successfully!</div>
  <?php endif; ?>
  
  <?php if(!empty($_GET['err']) && $_GET['err']==='cannot_submit_review'): ?>
    <div class="alert alert-danger">You cannot submit review at this time.</div>
  <?php endif; ?>
  
  <?php if($msg): ?>
    <div class="alert alert-info"><?=$msg?></div>
  <?php endif; ?>
  
  <form method="post" class="mb-4">
    <?=csrf_input_field()?>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">What are you reviewing?</label>
        <select name="room_id" class="form-select" required>
          <option value="">Choose...</option>
          <option value="hotel">Overall Hotel Experience</option>
          <?php while($rd = $roomsDoneRes->fetch_assoc()): ?>
            <option value="<?=intval($rd['id'])?>">Room <?=htmlspecialchars($rd['room_number'])?> — <?=htmlspecialchars($rd['room_type'])?></option>
          <?php endwhile; ?>
        </select>
      </div>
      
      <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Rating</label>
        <select name="rating" class="form-select" required>
          <option value="">Select rating...</option>
          <?php for($i=5;$i>=1;$i--): ?>
            <option value="<?=$i?>"><?=$i?> Star<?=$i>1?'s':''?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>
    
    <div class="mb-3">
      <label class="form-label fw-bold">Your Review</label>
      <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience..."></textarea>
    </div>
    
    <button class="btn btn-primary px-4">Submit Review</button>
  </form>

  <hr>
  
  <h4>Recent Overall Hotel Reviews</h4>
  <?php
    $hotelList = $conn->query("SELECT rv.*, u.first_name, u.last_name, p.display_name FROM reviews rv JOIN users u ON rv.user_id=u.id LEFT JOIN profiles p ON p.user_id=u.id WHERE rv.room_id IS NULL AND rv.is_visible=1 ORDER BY rv.created_at DESC LIMIT 10");
  ?>
  
  <?php if ($hotelList && $hotelList->num_rows): ?>
    <div>
      <?php while($hr=$hotelList->fetch_assoc()): ?>
        <div class="review-item">
          <div class="review-header">
            <span class="reviewer-name">
              <?=htmlspecialchars(!empty($hr['display_name']) ? $hr['display_name'] : ($hr['first_name'].' '.$hr['last_name']))?>
            </span>
            <span class="review-rating"><?=intval($hr['rating'])?>/5 ★</span>
          </div>
          <?php $hsafe = htmlspecialchars($hr['comment']); ?>
          <div class="review-text break-word"><?=nl2br($hsafe)?></div>
          <small class="text-muted"><?=date('M j, Y', strtotime($hr['created_at']))?></small>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="text-muted text-center py-4">
      <i class="fas fa-comments fa-2x mb-2"></i>
      <p>No overall hotel reviews yet.</p>
    </div>
  <?php endif; ?>

  <hr>
  
  <h4>My Reviews</h4>
  <div class="table-responsive">
    <table class="table table-hover table-bordered">
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
          <td>
            <span class="badge rounded-pill" style="background-color: var(--primary-solid);">
              <?=htmlspecialchars($r['room_type'] ?? 'Hotel')?>
            </span>
          </td>
          <td>
            <span class="review-rating"><?=intval($r['rating'])?>/5 ★</span>
          </td>
          <td>
            <?php $safe = htmlspecialchars($r['comment']); ?>
            <div class="review-text break-word" data-full="<?=htmlentities($safe)?>"><?=nl2br($safe)?></div>
          </td>
          <?php if ($isAdmin): ?>
            <td>
              <span class="badge <?=$r['is_visible'] ? 'bg-success' : 'bg-secondary'?>">
                <?=$r['is_visible']? 'Yes':'No'?>
              </span>
            </td>
          <?php endif; ?>
          <td>
            <small class="text-muted"><?=date('M j, Y', strtotime($r['created_at']))?></small>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
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
<?php require_once __DIR__ . '/includes/image_modal.php'; ?>
</body>
</html>