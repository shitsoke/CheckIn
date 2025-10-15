<?php
session_start();
require_once "db_connect.php";
$token = $_GET['token'] ?? '';
if (!$token) die('Invalid token');
$stmt = $conn->prepare("SELECT id FROM users WHERE verification_token=? AND email_verified=0 LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) die('Invalid or expired token');
$user = $res->fetch_assoc();
$u = $conn->prepare("UPDATE users SET email_verified=1, verification_token=NULL WHERE id=?");
$u->bind_param("i", $user['id']);
$u->execute();
echo "Email verified. You may now login.";
?>