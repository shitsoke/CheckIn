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
$payment = $_POST['payment'] ?? 'cash';

// Validate input
if ($user_id <= 0 || $room_id <= 0 || $hours <= 0 || !in_array($payment, ['cash', 'online'])) {
    die("Invalid input provided.");
}

//  Fetch room info using prepared statement
$stmt = $conn->prepare("
    SELECT r.status, t.hourly_rate 
    FROM rooms r 
    JOIN room_types t ON r.room_type_id = t.id 
    WHERE r.id = ?
");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) die("Room not found.");
if ($room['status'] !== 'available') die("Room is not available.");

//  Compute total with discount
$rate = (float)$room['hourly_rate'];
$total = $rate * $hours;
if ($hours >= 5) $total *= 0.95; // 5% discount
$total = round($total, 2);

//  Set booking times
$start = date("Y-m-d H:i:s");
$end   = date("Y-m-d H:i:s", strtotime("+{$hours} hours"));

//  Insert booking (prepared + checked)
$ins = $conn->prepare("
    INSERT INTO bookings 
    (user_id, room_id, start_time, end_time, hours, total_amount, payment_method, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'reserved')
");
$ins->bind_param("iissids", $user_id, $room_id, $start, $end, $hours, $total, $payment);

if (!$ins->execute()) {
    die("Booking failed: " . htmlspecialchars($ins->error));
}
$booking_id = $ins->insert_id;
$ins->close();

//  Update room status to reserved
$update = $conn->prepare("UPDATE rooms SET status='reserved' WHERE id=?");
$update->bind_param("i", $room_id);
if (!$update->execute()) {
    die("Failed to update room: " . htmlspecialchars($update->error));
}
$update->close();

//  Redirect based on payment method
if ($payment === 'online') {
    header("Location: payment_qr.php?amount={$total}&booking_id={$booking_id}");
    exit;
}

header("Location: bookings.php?msg=reserved");
exit;
?>
