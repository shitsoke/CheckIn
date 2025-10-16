Instructions:

smtp_config.php (Instructions para SMTP mo gana send email ni para forget password og email verify)

Go to Google Account → Security
Turn on 2-Step Verification.
Then go to App Passwords → Select App: Mail → Device: Other (PHP Mailer).
Google will give you a 16-character App Password — use that instead of your Gmail password.


Setup (Windows / XAMPP) - Composer, PHPMailer and Dompdf
1. Install Composer if you don't have it: https://getcomposer.org/download/
Guide parts:https://www.youtube.com/watch?v=-3Xz7tuKyMI
2. Open a command prompt in the project folder (D:\Xampp\htdocs\checkin) and run:
	composer update
	composer install
	composer require phpmailer/phpmailer
	composer require dompdf/dompdf:^2.0 (command prompt/powershell basta terminal na dili ari sa vsc)
	git push origin main --force
	This will install PHPMailer and Dompdf into the `vendor/` folder. The app will detect Dompdf automatically when `vendor/autoload.php` exists.
3. Ensure PHP CLI is available on your PATH so commands like `php -v` work. For XAMPP, add `C:\xampp\php` to your Windows PATH environment variable or use the full path `C:\xampp\php\php.exe` when running commands.
4. Configure SMTP settings in `includes/smtp_config.php` (host, port, username, password). Example values are present as placeholders in that file.
5. If you prefer not to use SMTP/PHPMailer, the code falls back to PHP's `mail()` function, but SMTP is recommended for reliable delivery.

Generating PDF receipts
- The receipts page (`receipt.php`) will attempt to generate a PDF when you click links that include `&download=pdf` (for example from the admin panel or your bookings page). If Dompdf is not installed, the page will fall back to providing an HTML download.

Next recommended tasks:
- (1) Run `composer install` in the project root.
- (2) Populate SMTP credentials in `includes/smtp_config.php`.
- (3) Ensure `C:\xampp\php` is on your PATH for CLI usage.

TODO :
Error pages Error handling
UI --Dark mode/light mode ana pama
Gcash 50/50 Mock
Laravel