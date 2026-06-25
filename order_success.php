<?php
session_start();
if (!isset($_SESSION['order_success'])) {
    header('Location: cart.php');
    exit();
}

$order_number = $_SESSION['order_number'];
unset($_SESSION['order_success']);
unset($_SESSION['order_number']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card">
                    <div class="card-body py-5">
                        <div class="text-success mb-4">
                            <i class="fas fa-check-circle fa-5x"></i>
                        </div>
                        <h2 class="text-success">Order Successful!</h2>
                        <p class="lead">Thank you for your purchase</p>
                        <p>Your order number is: <strong><?= $order_number ?></strong></p>
                        <p>You will receive an email confirmation shortly.</p>
                        
                        <div class="mt-4">
                            <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                            <a href="dashboard.php" class="btn btn-outline-secondary">View Orders</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>