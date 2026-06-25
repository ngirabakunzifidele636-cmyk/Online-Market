// cart_notification.js
class CartNotification {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.checkForRecentAdditions();
    }
    
    setupEventListeners() {
        // Listen for cart additions on all forms
        document.addEventListener('submit', (e) => {
            if (e.target.matches('form[action*=".php"]')) {
                this.handleCartAddition(e.target);
            }
        });
    }
    
    handleCartAddition(form) {
        const addToCartBtn = form.querySelector('button[name="add_to_cart"]');
        if (!addToCartBtn) return;
        
        const productName = form.querySelector('input[name="product_name"]')?.value;
        const productPrice = form.querySelector('input[name="product_price"]')?.value;
        const productImage = form.querySelector('input[name="product_image"]')?.value;
        
        if (productName) {
            this.storeCartAddition(productName, productPrice, productImage);
            this.showImmediateNotification(productName);
        }
    }
    
    storeCartAddition(name, price, image) {
        const addition = {
            name: name,
            price: price,
            image: image,
            time: new Date().toISOString()
        };
        
        // Store in session storage
        sessionStorage.setItem('lastCartAddition', JSON.stringify(addition));
        
        // Also add to localStorage history (for notifications page)
        this.addToNotificationHistory(addition);
    }
    
    addToNotificationHistory(addition) {
        let history = JSON.parse(localStorage.getItem('cartNotifications') || '[]');
        history.unshift({
            ...addition,
            id: Date.now(),
            read: false
        });
        
        // Keep only last 50 notifications
        if (history.length > 50) {
            history = history.slice(0, 50);
        }
        
        localStorage.setItem('cartNotifications', JSON.stringify(history));
    }
    
    checkForRecentAdditions() {
        const lastAddition = sessionStorage.getItem('lastCartAddition');
        if (lastAddition) {
            const addition = JSON.parse(lastAddition);
            const timeDiff = (new Date() - new Date(addition.time)) / 1000;
            
            if (timeDiff < 10) {
                this.addToNotificationDropdown(addition);
                sessionStorage.removeItem('lastCartAddition');
            }
        }
    }
    
    addToNotificationDropdown(addition) {
        // This would update the header notification dropdown
        // Implementation depends on your header structure
        console.log('Adding notification for:', addition.name);
    }
    
    showImmediateNotification(productName) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'toast show position-fixed top-0 end-0 m-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="toast-header">
                <i class="fas fa-cart-plus text-success me-2"></i>
                <strong class="me-auto">Added to Cart</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                <strong>${productName}</strong> has been added to your cart.
                <div class="mt-2 pt-2 border-top">
                    <a href="cart.php" class="btn btn-sm btn-success">View Cart</a>
                </div>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
        
        // Add close functionality
        toast.querySelector('.btn-close').addEventListener('click', () => {
            toast.remove();
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CartNotification();
});