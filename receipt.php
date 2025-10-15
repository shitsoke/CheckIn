<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
$booking_id = intval($_GET['booking_id'] ?? 0);
if ($booking_id <= 0) die('Invalid booking id');
// fetch booking
$stmt = $conn->prepare("SELECT b.*, r.room_number, t.name as room_type, t.hourly_rate, u.first_name, u.last_name FROM bookings b JOIN rooms r ON b.room_id=r.id JOIN room_types t ON r.room_type_id=t.id JOIN users u ON b.user_id=u.id WHERE b.id=? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$bk = $stmt->get_result()->fetch_assoc();
if (!$bk) die('Booking not found');
// verify current user owns booking or is admin
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $bk['user_id']) die('Unauthorized');

// Simple HTML receipt
$html = '<!doctype html><html><head><meta charset="utf-8"><title>Receipt</title></head><body>';
$html .= '<h2>CheckIn Receipt</h2>';
$html .= '<p>Booking ID: '.htmlspecialchars($bk['id']).'</p>';
$html .= '<p>Customer: '.htmlspecialchars($bk['first_name'].' '.$bk['last_name']).'</p>';
$html .= '<p>Room: '.htmlspecialchars($bk['room_number']).' ('.htmlspecialchars($bk['room_type']).')</p>';
$html .= '<p>Hours: '.intval($bk['hours']).'</p>';
$html .= '<p>Total: ₱'.number_format($bk['total_amount'],2).'</p>';
$html .= '<p>Payment method: '.htmlspecialchars($bk['payment_method']).'</p>';
$html .= '<p>Status: '.htmlspecialchars($bk['status']).'</p>';
$html .= '<p>Created: '.htmlspecialchars($bk['created_at']).'</p>';
$html .= '</body></html>';

// If Dompdf is installed via Composer and PDF explicitly requested, use it to generate a PDF
$wantPdf = isset($_GET['download']) && $_GET['download'] === 'pdf';
if ($wantPdf && file_exists(__DIR__.'/vendor/autoload.php')) {
	require_once __DIR__.'/vendor/autoload.php';
	try {
		$dompdf = new Dompdf\Dompdf();
		$dompdf->loadHtml($html);
		$dompdf->setPaper('A4', 'portrait');
		$dompdf->render();
		$dompdf->stream('receipt_'.$booking_id.'.pdf');
		exit;
	} catch (Exception $e) {
		error_log('Dompdf error: '.$e->getMessage());
	}
}

// Fallback: send HTML file for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="receipt_'.$booking_id.'.html"');
echo $html;

?>