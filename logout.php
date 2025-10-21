<?php
session_start();
// If a remember cookie exists, try to remove it from DB as well
if (session_status() === PHP_SESSION_NONE) session_start();
$uid = $_SESSION['user_id'] ?? null;
if (!empty($_COOKIE['remember']) && $uid) {
	$parts = explode(':', $_COOKIE['remember'], 2);
	if (count($parts) === 2) {
		list($r_uid, $r_token) = $parts;
		if (ctype_digit((string)$r_uid) && $r_uid == $uid) {
			require_once __DIR__ . '/db_connect.php';
			$del = $conn->prepare("DELETE FROM remember_tokens WHERE user_id=? AND token=?");
			if ($del) { $del->bind_param("is", $r_uid, $r_token); $del->execute(); }
		}
	}
	// clear cookie for all clients; include httponly to match how it's set
	setcookie('remember', '', time()-3600, '/', '', false, true);
	// also unset cookie on this request so subsequent code doesn't see it
	unset($_COOKIE['remember']);
}
session_unset();
session_destroy();
header("Location: login.php");
exit;
?>
