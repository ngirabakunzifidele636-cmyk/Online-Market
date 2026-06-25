<?php
session_start();
if (!isset($_SESSION['message'])) {
    header('Location: register.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Online Market</title>
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
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            text-align: center;
        }
        
        .success-icon {
            font-size: 64px;
            color: #27ae60;
            margin-bottom: 20px;
        }
        
        h2 {
            color: #333;
            margin-bottom: 15px;
        }
        
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 5px;
        }
        
        .btn-outline {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        
        <div class="message <?php echo $_SESSION['message_type']; ?>">
            <?php echo $_SESSION['message']; ?>
        </div>
        
        <h2>Registration Successful</h2>
        <p>We've sent a verification link to your email address. Please check your inbox and click the verification link to activate your account.</p>
        
        <div>
            <a href="login.html" class="btn">Go to Login</a>
            <a href="index.html" class="btn btn-outline">Back to Home</a>
        </div>
        
        <p style="margin-top: 20px; font-size: 14px; color: #999;">
            Didn't receive the email? Check your spam folder or <a href="resend_verification.php">click here to resend</a>.
        </p>
    </div>
</body>
</html>
<?php
// Clear the message after displaying
unset($_SESSION['message']);
unset($_SESSION['message_type']);
?>