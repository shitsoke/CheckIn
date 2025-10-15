<?php
// csrf.php - functions to generate and verify CSRF tokens (Anti CSRF)
function csrf_token() {
  if (session_status() === PHP_SESSION_NONE) session_start();
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function csrf_input_field() {
  $token = csrf_token();
  return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($token).'">';
}

function verify_csrf() {
  if (session_status() === PHP_SESSION_NONE) session_start();
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
      die("Invalid CSRF token.");
    }
  }
}
?>
