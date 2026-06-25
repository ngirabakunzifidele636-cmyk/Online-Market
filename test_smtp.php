<?php
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your.email@gmail.com';
    $mail->Password = 'your_app_password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->SMTPDebug = 2; // Enable verbose debug output
    
    $mail->setFrom('test@test.com', 'Test');
    $mail->addAddress('your.test.email@gmail.com');
    $mail->Subject = 'SMTP Test';
    $mail->Body = 'Testing SMTP';
    
    $mail->send();
    echo "✅ Email sent!";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>