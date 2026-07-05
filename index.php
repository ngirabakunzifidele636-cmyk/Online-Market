<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
<?php include 'header.php'; ?>

<!-- Hero Section -->
<div class="hero-section bg-primary text-white py-5 rounded mb-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container text-center">
        <h1 class="display-4 fw-bold">Welcome to TechShop! 🚀</h1>
        <p class="lead">Discover the latest gadgets and technology at amazing prices.</p>
        <a href="products.php" class="btn btn-light btn-lg mt-3">
            <i class="fas fa-shopping-bag"></i> Shop Now
        </a>
    </div>
</div>

<?php 
// Display success message if exists
// if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
//     echo '<div class="container"><div class="alert alert-success alert-dismissible fade show" role="alert">
//             <i class="fas fa-check-circle me-2"></i>' . $_SESSION['success_message'] . '
//             <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
//           </div></div>';
//     // Clear the message after displaying
//     $_SESSION['success_message'] = '';
// }                                                
?>

<!-- Rest of your index.php content remains the same -->
<div class="container">
    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-shipping-fast fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">🚀 Fast Shipping</h5>
                    <p class="card-text">Free shipping on orders over $50</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">💳 Secure Payment</h5>
                    <p class="card-text">Your payment information is safe with us</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-undo-alt fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">⭐ Quality Products</h5>
                    <p class="card-text">30-day money-back guarantee</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>