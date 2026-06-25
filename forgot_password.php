<?php
session_start();

// Simple database configuration
$host = 'localhost';
$dbname = 'online_market';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        try {
            // Check if user exists
            $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Save token to database
                $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                $update_stmt->execute([$reset_token, $token_expires, $user['id']]);
                
                // Create reset link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $reset_token;
                
                // Email configuration
                $to = $email; // Send to the user who requested reset
                $subject = "Password Reset Request - Online Market";
                
                $body = "
                Password Reset Request

                Hello " . $user['username'] . ",

                You have requested to reset your password for your Online Market account.

                Reset Link: $reset_link

                This link will expire in 1 hour.

                If you didn't request this password reset, please ignore this email.

                Best regards,
                The Online Market Team
                ";

                // SMTP Configuration
                $smtpHost = "smtp.gmail.com";
                $smtpPort = 587;
                $username = "ngirabakunzifidele636@gmail.com"; // Your Gmail
                $appPass  = "luit knpq epda qnxe";  // Your 16-character app password

                // Try to send email with PHPMailer
                $email_sent = false;
                $email_error = '';

                // Check if PHPMailer exists
                if (file_exists('PHPMailer/PHPMailerAutoload.php') || 
                    file_exists('vendor/autoload.php') || 
                    file_exists('PHPMailer/src/PHPMailer.php')) {
                    
                    try {
                        // Load PHPMailer - try different possible paths
                        if (file_exists('PHPMailer/PHPMailerAutoload.php')) {
                            require 'PHPMailer/PHPMailerAutoload.php';
                            $mail = new PHPMailer;
                        } elseif (file_exists('vendor/autoload.php')) {
                            // require 'vendor/autoload.php';
                            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        } else {
                            require 'PHPMailer/src/PHPMailer.php';
                            require 'PHPMailer/src/SMTP.php';
                            require 'PHPMailer/src/Exception.php';
                            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        }

                        // SMTP configuration
                        $mail->isSMTP();
                        $mail->Host = $smtpHost;
                        $mail->SMTPAuth = true;
                        $mail->Username = $username;
                        $mail->Password = $appPass;
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = $smtpPort;
                        $mail->SMTPDebug = 0;

                        // Email content
                        $mail->setFrom($username, 'Online Market');
                        $mail->addAddress($to, $user['username']);
                        $mail->addReplyTo($username, 'Support');

                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        
                        // HTML version of email
                        $html_body = "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <title>Password Reset Request</title>
                        </head>
                        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0;'>
                            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background: white;'>
                                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center;'>
                                    <h1 style='margin: 0; font-size: 28px;'>Online Market</h1>
                                    <h2 style='margin: 10px 0 0 0; font-size: 18px; font-weight: 300;'>Password Reset Request</h2>
                                </div>
                                <div style='padding: 30px; background: #f9f9f9;'>
                                    <p>Hello <strong>" . htmlspecialchars($user['username']) . "</strong>,</p>
                                    <p>You have requested to reset your password for your Online Market account.</p>
                                    
                                    <div style='text-align: center; margin: 25px 0;'>
                                        <a href='$reset_link' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: 600; font-size: 16px;'>Reset Your Password</a>
                                    </div>
                                    
                                    <p>Or copy and paste this link in your browser:</p>
                                    <div style='background: #fff; border: 2px dashed #667eea; padding: 15px; border-radius: 8px; margin: 20px 0; word-break: break-all;'>
                                        <strong>Reset Link:</strong><br>
                                        $reset_link
                                    </div>
                                    
                                    <div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                                        <strong>⚠️ Important:</strong> This link will expire in 1 hour.
                                    </div>
                                    
                                    <p>If you didn't request this password reset, please ignore this email.</p>
                                    
                                    <p>Best regards,<br><strong>The Online Market Team</strong></p>
                                </div>
                                <div style='padding: 20px; text-align: center; color: #666; font-size: 12px; background: #f5f5f5;'>
                                    <p>This is an automated message. Please do not reply to this email.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                        ";
                        
                        $mail->Body = $html_body;
                        $mail->AltBody = $body;

                        if ($mail->send()) {
                            $email_sent = true;
                        } else {
                            $email_error = $mail->ErrorInfo;
                            $email_sent = false;
                        }
                        
                    } catch (Exception $e) {
                        $email_error = $e->getMessage();
                        $email_sent = false;
                    }
                } else {
                    $email_error = "PHPMailer not found. Please install PHPMailer or check file paths.";
                    $email_sent = false;
                }

                // Show appropriate message based on email sending result
                if ($email_sent) {
                    $success = "
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <div style='font-size: 48px; margin-bottom: 10px;'>✅</div>
                        <h3 style='color: #155724; margin-bottom: 15px;'>Email Sent Successfully!</h3>
                    </div>
                    
                    <div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;'>
                        <strong>Password reset link has been sent to:</strong><br>
                        <span style='font-size: 18px; color: #0c5460;'>" . htmlspecialchars($email) . "</span>
                    </div>
                    
                    <div style='background: #e7f3ff; border-left: 4px solid #667eea; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                        <strong>📧 Check your email:</strong>
                        <ul style='margin: 10px 0 0 20px;'>
                            <li>Look in your inbox at <strong>" . htmlspecialchars($email) . "</strong></li>
                            <li>Check spam folder if not found</li>
                            <li>Link expires in 1 hour</li>
                        </ul>
                    </div>";
                } else {
                    // Show manual reset link if email fails
                    $success = "
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <div style='font-size: 48px; margin-bottom: 10px;'>🔧</div>
                        <h3 style='color: #856404; margin-bottom: 15px;'>Use This Reset Link</h3>
                    </div>
                    
                    <div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                        <strong>Email configuration needed:</strong> " . htmlspecialchars($email_error) . "
                        <br>For now, use this reset link:
                    </div>
                    
                    <div style='background: #f8f9fa; border: 2px dashed #667eea; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                        <div style='text-align: center; margin-bottom: 15px;'>
                            <strong style='color: #333;'>Reset Password Link:</strong>
                        </div>
                        <div style='background: white; padding: 15px; border-radius: 8px; word-break: break-all; font-family: monospace;'>
                            $reset_link
                        </div>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='$reset_link' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: 600;'>
                            Click to Reset Password
                        </a>
                    </div>";
                    
                    // <div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin-top: 20px;'>
                    //     <strong>To enable email sending:</strong>
                    //     <ol style='margin: 10px 0 0 20px;'>
                    //         <li>Install PHPMailer</li>
                    //         <li>Enable 2FA on your Gmail</li>
                    //         <li>Generate App Password and update the code</li>
                    //     </ol>
                    // </div>";
                }
                
            } else {
                $error = "No account found with that email address.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Online Market</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Arial', sans-serif; 
        }
        
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }
        
        .container { 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            padding: 40px; 
            width: 100%; 
            max-width: 500px; 
        }
        
        .icon { 
            text-align: center; 
            font-size: 48px; 
            margin-bottom: 20px; 
        }
        
        h1 { 
            text-align: center; 
            color: #333; 
            margin-bottom: 10px; 
            font-size: 28px; 
        }
        
        .subtitle { 
            text-align: center; 
            color: #666; 
            margin-bottom: 30px; 
        }
        
        .form-group { 
            margin-bottom: 20px; 
        }
        
        label { 
            display: block; 
            margin-bottom: 8px; 
            color: #555; 
            font-weight: 500; 
        }
        
        input[type="email"] { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #ddd; 
            border-radius: 8px; 
            font-size: 16px; 
            transition: border-color 0.3s; 
        }
        
        input:focus { 
            outline: none; 
            border-color: #667eea; 
        }
        
        .btn { 
            width: 100%; 
            padding: 12px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: transform 0.2s; 
        }
        
        .btn:hover { 
            transform: translateY(-2px); 
        }
        
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            text-align: center; 
        }
        
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 12px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            text-align: center; 
        }
        
        .login-link { 
            text-align: center; 
            margin-top: 20px; 
        }
        
        .login-link a { 
            color: #667eea; 
            text-decoration: none; 
            font-weight: 500; 
        }
        
        .email-note { 
            background: #e7f3ff; 
            color: #0066cc; 
            padding: 12px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            text-align: center; 
            font-size: 14px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔑</div>
        <h1>Reset Password</h1>
        <p class="subtitle">Enter your email to receive a reset link</p>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <div class="email-note">
                <strong>📧 We'll send a reset link to your email</strong><br>
                Check your inbox and spam folder
            </div>
            
            <form method="post" action="forgot_password.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your email address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </form>
        <?php endif; ?>
        
        <div class="login-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>