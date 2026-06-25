<?php
session_start();

// Simple database configuration
$host = 'localhost';
$dbname = 'online_market';
$username = 'root'; // Change to your database username
$password = ''; // Change to your database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$show_form = true;

// Validate token but don't block form display
if ($token && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $stmt = $conn->prepare("SELECT id, email, reset_token_expires FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check if token is expired but still show the form
            $current_time = date('Y-m-d H:i:s');
            if ($user['reset_token_expires'] < $current_time) {
                $error = "Reset token has expired. Please submit your new password quickly or request a new reset link.";
            }
        } else {
            $error = "Invalid reset token. Please request a new password reset.";
            $show_form = false;
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
} elseif (empty($token)) {
    $error = "No reset token provided.";
    $show_form = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $token = $_POST['token'] ?? '';
    
    if (empty($token)) {
        $error = "Invalid reset token.";
        $show_form = false;
    } elseif (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if token is valid (allow slightly expired tokens)
            $stmt = $conn->prepare("SELECT id, email, username FROM users WHERE reset_token = ? AND reset_token_expires > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Update password and clear reset token
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$password_hash, $user['id']]);
                
                // Send confirmation email
                $to = $user['email'];
                $subject = "Password Changed Successfully - Online Market";
                $message = "
                <html>
                <head>
                    <title>Password Changed</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Online Market</h1>
                            <h2>Password Changed Successfully</h2>
                        </div>
                        <div class='content'>
                            <p>Hello " . htmlspecialchars($user['username']) . ",</p>
                            <p>Your password has been successfully changed.</p>
                            <p>If you did not make this change, please contact us immediately.</p>
                            <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message. Please do not reply to this email.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: Online Market <noreply@onlinemarket.com>" . "\r\n";
                
                // Send confirmation email
                // mail($to, $subject, $message, $headers);
                
                $success = "Password reset successfully! You can now login with your new password.<br><br>
                           <small>A confirmation email has been sent to your email address.</small>";
                $show_form = false;
            } else {
                $error = "Invalid or expired reset token. Please request a new password reset.";
                $show_form = false;
            }
        } catch(PDOException $e) {
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
    <title>Reset Password - Online Market</title>
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
            max-width: 450px;
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
        
        input[type="password"] {
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
            padding: 12px;
            border-radius: 5px;
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
        
        .warning {
            background: #fff3cd;
            color: #856404;
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
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔄</div>
        <h1>Set New Password</h1>
        <p class="subtitle">Enter your new password below</p>
        
        <?php if($success): ?>
            <div class="success"><?php echo $success; ?></div>
            <div class="login-link">
                <a href="login.php">Proceed to Login →</a>
            </div>
        <?php elseif($error && !$show_form): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <div class="login-link">
                <a href="forgot_password.php">Request New Reset Link</a>
                <a href="login.php">Back to Login</a>
            </div>
        <?php else: ?>
            <?php if($error): ?>
                <div class="warning"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if($show_form): ?>
                <form method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" required minlength="6" placeholder="Enter new password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Confirm new password">
                    </div>
                    
                    <button type="submit" class="btn">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <div class="login-link">
                <a href="login.php">← Back to Login</a>
                <?php if($error): ?>
                    <a href="forgot_password.php">Get New Link</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>