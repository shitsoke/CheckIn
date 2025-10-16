<?php
// Simple error landing page
require_once __DIR__ . '/includes/error_handler.php';
$code = intval($_GET['code'] ?? 0);
switch($code) {
  case 404: $title = 'Not Found'; $msg = 'The requested resource was not found.'; break;
  case 403: $title = 'Forbidden'; $msg = 'You do not have permission to access this resource.'; break;
  default: $title = 'Server Error'; $msg = 'An unexpected error occurred. Please contact support.'; break;
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Error - <?=htmlspecialchars($title)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container mt-5 text-center">
  <h1>Error <?= $code ? $code : '' ?></h1>
  <h3><?=htmlspecialchars($title)?></h3>
  <p class="lead"><?=htmlspecialchars($msg)?></p>
  <p>If you are the site administrator, check <code>logs/errors.log</code> for details.</p>
  <a href="index.php" class="btn btn-primary">Go to Home</a>
</div>
</body></html>
