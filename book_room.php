<?php
session_start();
require_once "includes/auth_check.php";
require_once "includes/csrf.php";
require_once "db_connect.php";

// enable error display for debugging (turn off in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

//  Verify CSRF token correctly
verify_csrf();


$user_id = $_SESSION['user_id'] ?? 0;
$room_id = intval($_POST['room_id'] ?? 0);
$hours   = intval($_POST['hours'] ?? 0);
$start_input = trim($_POST['start_time'] ?? '');
$end_input = trim($_POST['end_time'] ?? '');
$payment = $_POST['payment'] ?? 'cash';

// Basic validation
if ($user_id <= 0 || $room_id <= 0 || !in_array($payment, ['cash', 'online'])) {
    // invalid request
    $_SESSION['booking_error'] = 'Invalid input provided.';
    $ret = $_POST['return_to'] ?? 'browse_rooms.php';
    header('Location: ' . $ret);
    exit;
}

// If start/end provided, compute hours from them (ceil to hours)
if ($start_input !== '' && $end_input !== '') {
    // normalize datetime-local format (replace T with space to help strtotime interpret local time)
    $start_input_fixed = str_replace('T',' ',$start_input);
    $end_input_fixed = str_replace('T',' ',$end_input);
    $startTs = strtotime($start_input_fixed);
    $endTs = strtotime($end_input_fixed);
    if ($startTs === false || $endTs === false) {
        $_SESSION['booking_error'] = 'Invalid start or end time.';
        $ret = $_POST['return_to'] ?? 'browse_rooms.php'; header('Location: ' . $ret); exit;
    }
    if ($endTs <= $startTs) {
        $_SESSION['booking_error'] = 'End time must be after start time.';
        $ret = $_POST['return_to'] ?? 'browse_rooms.php'; header('Location: ' . $ret); exit;
    }
    // Do not allow booking start in the past
    if ($startTs < time()) {
        $_SESSION['booking_error'] = 'Start time cannot be in the past.';
        $ret = $_POST['return_to'] ?? 'browse_rooms.php'; header('Location: ' . $ret); exit;
    }
    // Enforce hour-only selection: minutes and seconds must be zero
    $startMin = intval(date('i', $startTs));
    $endMin = intval(date('i', $endTs));
    $startSec = intval(date('s', $startTs));
    $endSec = intval(date('s', $endTs));
    if ($startMin !== 0 || $endMin !== 0 || $startSec !== 0 || $endSec !== 0) {
        $_SESSION['booking_error'] = 'Please select times on exact hours (minutes and seconds must be 00).';
        $ret = $_POST['return_to'] ?? 'browse_rooms.php'; header('Location: ' . $ret); exit;
    }
    $hours = (int)ceil(($endTs - $startTs) / 3600);
    $start = date("Y-m-d H:i:s", $startTs);
    $end = date("Y-m-d H:i:s", $endTs);
} else {
    // fallback: hours-only booking
    if ($hours <= 0) {
        $_SESSION['booking_error'] = 'Invalid input provided.';
        $ret = $_POST['return_to'] ?? 'browse_rooms.php'; header('Location: ' . $ret); exit;
    }
    $startTs = time();
    $start = date("Y-m-d H:i:s", $startTs);
    $end   = date("Y-m-d H:i:s", strtotime("+{$hours} hours", $startTs));
}

// Begin transaction to ensure consistent locking and avoid double-booking
$conn->begin_transaction();
// Re-fetch room info (SELECT ... FOR UPDATE) while in transaction
$stmt = $conn->prepare("SELECT r.status, r.room_number, t.hourly_rate FROM rooms r JOIN room_types t ON r.room_type_id = t.id WHERE r.id = ? FOR UPDATE");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    $conn->rollback();
    $_SESSION['booking_error'] = 'Room not found.';
    $ret = $_POST['return_to'] ?? 'browse_rooms.php'; header('Location: ' . $ret); exit;
}
if ($room['status'] !== 'available') {
    $conn->rollback();
    $_SESSION['booking_error'] = 'Room is not available.';
    $ret = $_POST['return_to'] ?? 'browse_rooms.php'; header('Location: ' . $ret); exit;
}

// Prevent overlapping bookings for the same room (concurrent safety)
$ov = $conn->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE room_id=? AND status IN ('reserved','confirmed','ongoing') AND NOT (end_time <= ? OR start_time >= ?) LIMIT 1");
$ov->bind_param("iss", $room_id, $start, $end);
$ov->execute(); $ovc = $ov->get_result()->fetch_assoc(); $ov->close();
if ($ovc && intval($ovc['cnt']) > 0) {
    $conn->rollback();
    $_SESSION['booking_error'] = 'Room already has a booking overlapping your requested time.';
    $ret = $_POST['return_to'] ?? 'browse_rooms.php'; header('Location: ' . $ret); exit;
}

//  Compute total with discount using integer cents to avoid float errors
$rate = (float)$room['hourly_rate'];
$rateCents = (int)round($rate * 100);
$totalCents = $rateCents * $hours;
$total = number_format($totalCents / 100, 2, '.', '');

//  Insert booking (prepared + checked)
// Begin transaction to ensure atomic update when reserving a room
$ins = $conn->prepare("INSERT INTO bookings (user_id, room_id, start_time, end_time, hours, total_amount, payment_method, status, receipt_sent) VALUES (?, ?, ?, ?, ?, ?, ?, 'reserved', 0)");
$ins->bind_param("iissids", $user_id, $room_id, $start, $end, $hours, $total, $payment);

 $ok = $ins->execute();
if (!$ok) {
    $conn->rollback();
    $_SESSION['booking_error'] = 'Booking failed: ' . htmlspecialchars($ins->error);
    $ret = $_POST['return_to'] ?? 'browse_rooms.php'; header('Location: ' . $ret); exit;
}
$booking_id = $ins->insert_id;
$ins->close();

//  Update room status to reserved
$update = $conn->prepare("UPDATE rooms SET status='reserved' WHERE id=?");
$update->bind_param("i", $room_id);
if (!$update->execute()) {
    $conn->rollback();
    $_SESSION['booking_error'] = 'Failed to update room: ' . htmlspecialchars($update->error);
    $ret = $_POST['return_to'] ?? 'browse_rooms.php'; header('Location: ' . $ret); exit;
}
$update->close();

// Commit transaction
$conn->commit();

//  Redirect based on payment method
if ($payment === 'online') {
    header("Location: payment_qr.php?amount={$total}&booking_id={$booking_id}");
    exit;
}
// send booking confirmation email (one-line html) if mail helper available
if (file_exists(__DIR__.'/includes/mail.php')) {
    require_once __DIR__.'/includes/mail.php';
    $toStmt = $conn->prepare("SELECT email FROM users WHERE id=?"); $toStmt->bind_param("i", $user_id); $toStmt->execute(); $toRow = $toStmt->get_result()->fetch_assoc();
    if ($toRow && !empty($toRow['email'])) {
        $subject = 'Booking confirmation #' . $booking_id;
        $body = '<p>Thank you — your booking has been received.</p>';
        $body .= '<p>Booking ID: '.htmlspecialchars($booking_id).'</p>';
        $body .= '<p>Room: '.htmlspecialchars($room['room_number'] ?? '').'</p>';
        $body .= '<p>Start: '.htmlspecialchars($start).' End: '.htmlspecialchars($end).'</p>';
        $body .= '<p>Total: ₱'.htmlspecialchars($total).'</p>';
        send_mail($toRow['email'], $subject, $body, true);
    }
}

header("Location: bookings.php?msg=reserved");
exit;
?>
