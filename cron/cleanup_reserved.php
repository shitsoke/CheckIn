<?php
// cleanup_reserved.php
// Run this script periodically (cron / Task Scheduler)
require_once __DIR__ . "/../db_connect.php";

// number of hours after which reserved booking is considered stale
$staleHours = 48;

$sql = "SELECT b.id, b.room_id, b.created_at FROM bookings b WHERE b.status='reserved' AND b.created_at < (NOW() - INTERVAL ? HOUR)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staleHours);
$stmt->execute();
$res = $stmt->get_result();

$freed = 0;
while ($row = $res->fetch_assoc()) {
  $bid = $row['id'];
  $room_id = $row['room_id'];
  // cancel booking
  $u = $conn->prepare("UPDATE bookings SET status='canceled' WHERE id=?");
  $u->bind_param("i", $bid);
  $u->execute();
  // free room
  $v = $conn->prepare("UPDATE rooms SET status='available' WHERE id=?");
  $v->bind_param("i", $room_id);
  $v->execute();
  $freed++;
}
echo "Freed $freed bookings and rooms.\n";
?>
