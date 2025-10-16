<?php
// Basic header/navigation include. Also ensure logs folder exists and include error handler
if (!file_exists(__DIR__ . '/../logs')) @mkdir(__DIR__ . '/../logs', 0755, true);
require_once __DIR__ . '/error_handler.php';
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-3">
	<div class="container-fluid">
		<a class="navbar-brand" href="/checkin/dashboard.php">CheckIn</a>
		<div class="d-flex">
			<a class="btn btn-outline-secondary me-2" href="/checkin/bookings.php">My Bookings</a>
			<a class="btn btn-outline-secondary" href="/checkin/browse_rooms.php">Browse</a>
		</div>
	</div>
</nav>
