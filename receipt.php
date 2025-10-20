<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
$booking_id = intval($_GET['booking_id'] ?? 0);
if ($booking_id <= 0) die('Invalid booking id');
// fetch booking
$stmt = $conn->prepare("SELECT b.*, r.room_number, t.name as room_type, t.hourly_rate, u.first_name, u.middle_name, u.last_name FROM bookings b JOIN rooms r ON b.room_id=r.id JOIN room_types t ON r.room_type_id=t.id JOIN users u ON b.user_id=u.id WHERE b.id=? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$bk = $stmt->get_result()->fetch_assoc();
if (!$bk) die('Booking not found');
// verify current user owns booking or is admin
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $bk['user_id']) die('Unauthorized');

// Build formatted values (escape for HTML)
$fullName = htmlspecialchars($bk['first_name'] . (!empty($bk['middle_name']) ? ' '.$bk['middle_name'] : '') . ' ' . $bk['last_name']);
$roomNumber = htmlspecialchars($bk['room_number']);
$roomType = htmlspecialchars($bk['room_type']);
$hours = intval($bk['hours']);
$total = number_format($bk['total_amount'], 2);
$payment = htmlspecialchars($bk['payment_method']);
$status = htmlspecialchars($bk['status']);
$created = htmlspecialchars($bk['created_at']);
$bookingRef = htmlspecialchars($bk['id']);

// Primary receipt HTML (for browser rendering)
$pageHtml = '<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Receipt — CheckIn #'.$bookingRef.'</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background: #f4f6f8; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; color: #222; }
  .receipt-wrap { max-width: 900px; margin: 32px auto; padding: 20px; }
  .receipt-card { background:#fff; border-radius:12px; box-shadow:0 12px 30px rgba(20,20,30,0.06); padding:22px; }
  .brand { display:flex; align-items:center; gap:14px; }
  .brand .logo { width:56px; height:56px; border-radius:10px; background:rgba(220,53,69,1); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:20px; box-shadow:0 8px 24px rgba(220,53,69,0.14); }
  .meta { color:#666; font-size:13px; }
  .section-title { font-weight:700; font-size:14px; color:#111; margin-bottom:8px; }
  .table td, .table th { vertical-align: middle; border-top: 0; }
  .total-row { font-weight:700; font-size:18px; }
  .actions { display:flex; gap:10px; margin-top:14px; }
  .btn-ghost { background: #fff; border:1px solid rgba(0,0,0,0.06); color:#333; }
  @media print {
    body { background: #fff; }
    .actions { display:none; }
    .receipt-wrap { margin: 0; box-shadow: none; }
  }
</style>
</head>
<body>
  <div class="receipt-wrap">
    <div class="receipt-card">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="brand">
          <div class="logo">CI</div>
          <div>
            <div style="font-weight:700; font-size:18px;">CheckIn / Redwood Motel</div>
            <div class="meta">Official receipt</div>
          </div>
        </div>
        <div class="text-end meta">
          <div>Receipt #: <strong>'.$bookingRef.'</strong></div>
          <div>Issued: '.$created.'</div>
          <div>Status: <strong>'.$status.'</strong></div>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6 mb-2">
          <div class="section-title">Guest</div>
          <div>'.$fullName.'</div>
          <div class="meta">User ID: '.htmlspecialchars($bk['user_id']).'</div>
        </div>
        <div class="col-md-6 mb-2">
          <div class="section-title">Booking details</div>
          <div>Room: <strong>'.$roomNumber.' — '.$roomType.'</strong></div>
          <div class="meta">Check-in: '.htmlspecialchars($bk['check_in'] ?? 'N/A').' &nbsp; • &nbsp; Check-out: '.htmlspecialchars($bk['check_out'] ?? 'N/A').'</div>
          <div class="meta">Hours: '.$hours.'</div>
        </div>
      </div>

      <div class="mb-3">
        <div class="section-title">Charges</div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th class="w-50">Description</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Room — '. $roomType .'</td>
                <td class="text-end">₱'.number_format($bk['hourly_rate'],2).'</td>
                <td class="text-end">'.$hours.'</td>
                <td class="text-end">₱'.number_format(($bk['hourly_rate'] * $hours),2).'</td>
              </tr>';


// include any other line items if present (discounts, extras)
$lineTotal = ($bk['hourly_rate'] * $hours);
$extraRows = '';
if (!empty($bk['extra_charges'])) {
    // if stored as JSON or simple text, attempt to decode
    $extras = json_decode($bk['extra_charges'], true);
    if (is_array($extras)) {
        foreach ($extras as $label => $amt) {
            $extraRows .= '<tr><td>'.htmlspecialchars($label).'</td><td class="text-end">₱'.number_format($amt,2).'</td><td class="text-end">1</td><td class="text-end">₱'.number_format($amt,2).'</td></tr>';
            $lineTotal += floatval($amt);
        }
    } else {
        // fallback: show as note
        $extraRows .= '<tr><td colspan="4" class="text-muted small">Extras: '.htmlspecialchars($bk['extra_charges']).'</td></tr>';
    }
}
$pageHtml .= $extraRows;

$pageHtml .= '
            </tbody>
            <tfoot>
              <tr class="total-row">
                <td colspan="3" class="text-end">Subtotal</td>
                <td class="text-end">₱'.number_format($lineTotal,2).'</td>
              </tr>';
// taxes or fees
$feesTotal = 0;
if (!empty($bk['tax']) || !empty($bk['service_fee'])) {
    $tax = floatval($bk['tax'] ?? 0);
    $service = floatval($bk['service_fee'] ?? 0);
    if ($tax) {
        $feesTotal += $tax;
        $pageHtml .= '<tr><td colspan="3" class="text-end">Tax</td><td class="text-end">₱'.number_format($tax,2).'</td></tr>';
    }
    if ($service) {
        $feesTotal += $service;
        $pageHtml .= '<tr><td colspan="3" class="text-end">Service fee</td><td class="text-end">₱'.number_format($service,2).'</td></tr>';
    }
}
$grandTotal = floatval(str_replace(',', '', $total)); // already formatted
$pageHtml .= '
              <tr class="total-row">
                <td colspan="3" class="text-end">Total</td>
                <td class="text-end">₱'.number_format($grandTotal,2).'</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="row align-items-center">
        <div class="col-md-8">
          <div class="meta"><strong>Payment:</strong> '.$payment.'</div>
          <div class="meta mt-2">Thank you for choosing Redwood Motel. For questions about this receipt or your booking, contact the front desk.</div>
        </div>
        <div class="col-md-4 text-md-end">
          <div class="actions">
            <button onclick="window.print()" class="btn btn-primary">Print</button>
            <a href="?booking_id='.$bookingRef.'&download=pdf" class="btn btn-outline-secondary">Download PDF</a>
            <a href="?booking_id='.$bookingRef.'&download=html" class="btn btn-ghost">Download HTML</a>
          </div>
        </div>
      </div>

    </div>

    <div class="text-center mt-3 small text-muted">Receipt generated by CheckIn • <a href="dashboard.php">Back to dashboard</a></div>
  </div>
</body>
</html>';

// If Dompdf is installed via Composer and PDF explicitly requested, use it to generate a PDF
$wantPdf = isset($_GET['download']) && $_GET['download'] === 'pdf';
$wantHtmlDownload = isset($_GET['download']) && $_GET['download'] === 'html';
if ($wantPdf && file_exists(__DIR__.'/vendor/autoload.php')) {
    require_once __DIR__.'/vendor/autoload.php';
    try {
        $dompdf = new Dompdf\Dompdf();
        // use the printable portion only (receipt-card)
        $dompdf->loadHtml($pageHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('receipt_'.$bookingRef.'.pdf');
        exit;
    } catch (Exception $e) {
        error_log('Dompdf error: '.$e->getMessage());
        // fallback to render HTML page
    }
}

// If user requested raw HTML download
if ($wantHtmlDownload) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="receipt_'.$bookingRef.'.html"');
    echo $pageHtml;
    exit;
}

// Default: render page in browser
echo $pageHtml;
?>