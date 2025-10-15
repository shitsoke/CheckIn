<?php
// mail.php - PHPMailer wrapper or fallback to mail()
function send_mail($to, $subject, $body, $isHtml=false) {
  // try PHPMailer if available
  if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $config = require __DIR__ . '/smtp_config.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host = $config['host'];
      $mail->SMTPAuth = true;
      $mail->Username = $config['username'];
      $mail->Password = $config['password'];
      $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = $config['port'];
      $mail->setFrom($config['from_email'], $config['from_name']);
      $mail->addAddress($to);
      $mail->Subject = $subject;
      if ($isHtml) $mail->isHTML(true);
      $mail->Body = $body;
      $mail->send();
      return true;
    } catch (Exception $e) {
      error_log('Mail error: '.$e->getMessage());
      return false;
    }
  }
  // fallback to mail()
  $headers = "From: CheckIn <noreply@example.com>\r\n";
  if ($isHtml) $headers .= "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
  return mail($to, $subject, $body, $headers);
}
?>