<?php 
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Get current user data
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $_SESSION['error_message'] = "User not logged in!";
    header('Location: login.php');
    exit();
}

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error_message'] = "User not found!";
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if username or email already exists
    try {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check_stmt->execute([$username, $email, $user_id]);
        if ($check_stmt->fetch()) {
            $errors[] = "Username or email already exists";
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
    
    // Handle profile image upload
    $profile_image = $user['profile_image'] ?? '';

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleProfileImageUpload($_FILES['profile_image'], $user_id);
        
        if ($upload_result['success']) {
            $profile_image = $upload_result['file_path'];
            
            // Delete old profile image if it exists
            if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                @unlink($user['profile_image']);
            }
        } else {
            $errors[] = $upload_result['error'];
        }
    }
    
    if (empty($errors)) {
        try {
            // Update user data
            $update_stmt = $conn->prepare("
                UPDATE users 
                SET username = ?, email = ?, full_name = ?, phone = ?, address = ?, profile_image = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $update_stmt->execute([$username, $email, $full_name, $phone, $address, $profile_image, $user_id]);
            
            $_SESSION['success_message'] = "Profile updated successfully!";
            
            // Update session
            $_SESSION['username'] = $username;
            
            // Refresh user data
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $errors[] = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 700px;
            margin: 30px auto;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .profile-image-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        .upload-btn {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .upload-btn input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="profile-container">
            <h2 class="text-center mb-4"><i class="fas fa-user-edit me-2"></i>Edit Profile</h2>
            
            <?php if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php $_SESSION['success_message'] = ''; ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="profile-image-container">
                    <img src="<?= !empty($user['profile_image']) && file_exists($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'https://via.placeholder.com/150?text=Upload+Photo' ?>" 
                         alt="Profile Image" 
                         class="profile-image mb-3"
                         id="profileImagePreview"
                         onclick="document.getElementById('profileImage').click()">
                    
                    <div class="upload-btn mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-camera me-1"></i> Change Photo
                        </button>
                        <input type="file" name="profile_image" id="profileImage" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <div class="form-text">Max file size: 5MB. JPG, PNG, GIF allowed</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-user me-1"></i>Username *</label>
                        <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-envelope me-1"></i>Email *</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-id-card me-1"></i>Full Name</label>
                    <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-phone me-1"></i>Phone Number</label>
                    <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-map-marker-alt me-1"></i>Address</label>
                    <textarea class="form-control" name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="profile.php" class="btn btn-secondary me-md-2"><i class="fas fa-arrow-left me-1"></i>Back to Profile</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Profile</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImagePreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>