<?php
include 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Username '$username' is already taken. Please choose another.";
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email '$email' is already registered. Please use another email.";
            }
            
            if (empty($errors)) {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password]);
                
                $new_user_id = $pdo->lastInsertId();
                
                // ===== CREATE NOTIFICATIONS TABLE IF NOT EXISTS =====
                try {
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS notifications (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            title VARCHAR(255) NOT NULL,
                            message TEXT NOT NULL,
                            type VARCHAR(50) DEFAULT 'info',
                            icon VARCHAR(50) DEFAULT 'fa-bell',
                            category VARCHAR(50) DEFAULT 'system',
                            is_read TINYINT DEFAULT 0,
                            action_url VARCHAR(255) NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            INDEX idx_user_read (user_id, is_read)
                        )
                    ");
                } catch (PDOException $e) {
                    // Table might already exist
                }
                
                // ===== ADD WELCOME NOTIFICATIONS =====
                try {
                    $notif_stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, type, icon, category, action_url, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    // Welcome notification
                    $notif_stmt->execute([
                        $new_user_id,
                        '🎉 Welcome to TechShop!',
                        'Thank you for registering, ' . $username . '! Start exploring our amazing products and enjoy shopping.',
                        'success',
                        'fa-user-plus',
                        'welcome',
                        'products.php'
                    ]);
                    
                    // Special offers notification
                    $notif_stmt->execute([
                        $new_user_id,
                        '🔥 Special Offers Just for You',
                        'Check out our latest deals on smartphones, laptops, and accessories with up to 30% off!',
                        'warning',
                        'fa-tags',
                        'offers',
                        'products.php'
                    ]);
                    
                    // Shopping tips notification
                    $notif_stmt->execute([
                        $new_user_id,
                        '🛍️ Quick Shopping Tips',
                        'Add items to your cart, use filters to find products, and track your orders from your dashboard.',
                        'info',
                        'fa-lightbulb',
                        'system',
                        'how_to_shop.php'
                    ]);
                    
                } catch (PDOException $e) {
                    // Log error but don't stop registration
                    error_log("Failed to create welcome notifications: " . $e->getMessage());
                }
                // ===== END NOTIFICATIONS =====
                
                // Set success message and redirect to login page
                $_SESSION['success_message'] = "🎉 Account created successfully! Please login with your credentials.";
                header('Location: login.php');
                exit;
            }
            
        } catch(PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: 0.6s popup  linear; 
        }
        .register-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        .register-body {
            padding: 2rem;
            background: white;
        }
        .back-to-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        @keyframes popup {
            0%{
                transform: scale(0.1);
            }
            30%{
                transform: scale(0.6);
            }
            60%{
                transform: scale(0.9);
            }
            90{
                transform: scale(1);
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="register-card">
                    <div class="register-header">
                        <h2 class="mb-2"><i class="fas fa-user-plus me-2"></i>Join TechShop</h2>
                        <p class="mb-0">Create your account</p>
                    </div>
                    
                    <div class="register-body">
                        <?php
                        // Display errors
                        if (!empty($errors)) {
                            echo '<div class="alert alert-danger">';
                            foreach ($errors as $error) {
                                echo '<i class="fas fa-exclamation-triangle me-2"></i>' . $error . '<br>';
                            }
                            echo '</div>';
                        }
                        ?>
                        
                        <form method="post" action="register.php" id="registerForm">
                            <div class="mb-3">
                                <label for="username" class="form-label fw-bold">Username *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-user text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" required 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                           placeholder="Choose a unique username">
                                </div>
                                <small class="text-muted">Username must be at least 3 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">Email Address *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-envelope text-muted"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" required 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                           placeholder="Enter your email">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required 
                                           minlength="6" placeholder="At least 6 characters">
                                </div>
                                <div class="password-strength" id="passwordStrength"></div>
                                <small class="text-muted">Use at least 6 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label fw-bold">Confirm Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                           placeholder="Confirm your password">
                                </div>
                                <small class="text-muted" id="passwordMatch"></small>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="terms.php" class="text-decoration-none">Terms & Conditions</a> and 
                                    <a href="privacy.php" class="text-decoration-none">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg w-100 py-3" id="submitBtn">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="text-decoration-none fw-bold">Sign in here</a>
                            </p>
                        </div>
                        
                        <div class="text-center mt-3">
                            <p class="small text-muted">
                                By creating an account, you'll receive notifications about your orders and special offers.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
                strengthBar.style.backgroundColor = 'transparent';
                return;
            }
            
            let strength = 0;
            
            // Length check
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            
            // Character variety checks
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
            
            // Set color based on strength
            let color = '#dc3545'; // Weak - Red
            if (strength >= 4) color = '#ffc107'; // Medium - Yellow
            if (strength >= 6) color = '#28a745'; // Strong - Green
            
            strengthBar.style.width = (strength * 10) + '%';
            strengthBar.style.backgroundColor = color;
        });
        
        // Password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            const matchMsg = document.getElementById('passwordMatch');
            
            if (confirm.length === 0) {
                matchMsg.innerHTML = '';
                return;
            }
            
            if (password === confirm) {
                matchMsg.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Passwords match</span>';
            } else {
                matchMsg.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Passwords do not match</span>';
            }
        });
        
        // Form validation before submit
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (!document.getElementById('terms').checked) {
                e.preventDefault();
                alert('You must agree to the Terms & Conditions');
                return false;
            }
        });
    </script>
</body>
</html>