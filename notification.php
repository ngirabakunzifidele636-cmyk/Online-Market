<?php
// notification.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$notification_id = $_GET['id'] ?? 0;

// Mark notification as read
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->execute([$notification_id, $_SESSION['user_id']]);

// Get notification details
$stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ? AND user_id = ?");
$stmt->execute([$notification_id, $_SESSION['user_id']]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    header('Location: notifications.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notification - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h3><?= htmlspecialchars($notification['title']) ?></h3>
                <small class="text-muted"><?= date('F j, Y g:i A', strtotime($notification['created_at'])) ?></small>
            </div>
            <div class="card-body">
                <p><?= htmlspecialchars($notification['message']) ?></p>
                <a href="notifications.php" class="btn btn-primary">Back to Notifications</a>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>