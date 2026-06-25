<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "Debug level $level; message: $str<br>";
    };

    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your.email@gmail.com'; // Your Gmail
    $mail->Password = 'your_app_password'; // App password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('test@test.com', 'Test');
    $mail->addAddress('your.test.email@gmail.com', 'Test User');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test Email';
    $mail->Body = 'This is a test email from PHPMailer.';

    $mail->send();
    echo '<h2 style="color: green;">✅ Email sent successfully!</h2>';
} catch (Exception $e) {
    echo '<h2 style="color: red;">❌ Error: ' . $e->getMessage() . '</h2>';
}
?>