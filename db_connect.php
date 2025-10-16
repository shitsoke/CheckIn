<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "checkin";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
// Ensure PHP uses the host local timezone when provided.
// You can set an environment variable APP_TIMEZONE (e.g., 'Asia/Manila') or set date.timezone in php.ini.
$appTz = getenv('APP_TIMEZONE') ?: ini_get('date.timezone');
if (!empty($appTz)) {
  date_default_timezone_set($appTz);
}
?>
