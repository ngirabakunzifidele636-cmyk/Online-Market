<?php 
include 'header.php'; 

// Your WhatsApp number (with country code, no plus sign)
$whatsapp_number = "250781013415"; // Your number

// Default message
$default_message = "Hello TechShop, I have a question about...";

// WhatsApp brand colors
$whatsapp_green = "#25D366";
$whatsapp_dark_green = "#128C7E";
$whatsapp_light_green = "#DCF8C6";
$whatsapp_bg = "#ECE5DD";
?>

<style>
:root {
    --whatsapp-green: #25D366;
    --whatsapp-dark-green: #128C7E;
    --whatsapp-light-green: #DCF8C6;
    --whatsapp-bg: #ECE5DD;
    --whatsapp-teal: #075E54;
}

/* WhatsApp Floating Button */
.whatsapp-float {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: var(--whatsapp-green);
    color: white;
    border-radius: 60px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
    z-index: 1000;
    animation: pulse 2s infinite;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 2px solid white;
}
.whatsapp-float:hover {
    background: var(--whatsapp-dark-green);
    color: white;
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 15px 35px rgba(37, 211, 102, 0.6);
}
.whatsapp-float-content {
    display: flex;
    align-items: center;
    padding: 15px 30px;
    gap: 12px;
}
.whatsapp-float i {
    font-size: 28px;
}
.whatsapp-float span {
    font-weight: 600;
    font-size: 18px;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* WhatsApp Card Styling */
.whatsapp-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    margin-bottom: 25px;
    border: none;
}
.whatsapp-card-header {
    background: var(--whatsapp-teal);
    color: white;
    padding: 20px;
    font-size: 18px;
    font-weight: 600;
}
.whatsapp-card-header i {
    margin-right: 10px;
    color: var(--whatsapp-light-green);
}
.whatsapp-card-body {
    padding: 25px;
}

/* WhatsApp Buttons */
.btn-whatsapp {
    background: var(--whatsapp-green);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s;
    box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
}
.btn-whatsapp:hover {
    background: var(--whatsapp-dark-green);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37, 211, 102, 0.4);
}
.btn-whatsapp-outline {
    background: transparent;
    color: var(--whatsapp-dark-green);
    border: 2px solid var(--whatsapp-green);
    padding: 10px 20px;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s;
}
.btn-whatsapp-outline:hover {
    background: var(--whatsapp-green);
    color: white;
}

/* Contact Info Icons */
.contact-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.icon-whatsapp { background: var(--whatsapp-green); color: white; }
.icon-phone { background: #007bff; color: white; }
.icon-email { background: #dc3545; color: white; }
.icon-map { background: #ffc107; color: white; }

/* Social Media Icons */
.social-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.3s;
}
.social-icon:hover {
    transform: translateY(-5px) scale(1.1);
}
.facebook { background: #1877F2; color: white; }
.twitter { background: #1DA1F2; color: white; }
.instagram { background: linear-gradient(45deg, #F58529, #DD2A7B, #8134AF, #515BD4); color: white; }
.linkedin { background: #0077B5; color: white; }
.whatsapp-social { background: var(--whatsapp-green); color: white; }

/* Form Styling */
.form-control:focus {
    border-color: var(--whatsapp-green);
    box-shadow: 0 0 0 0.2rem rgba(37, 211, 102, 0.25);
}

/* Quick Stats */
.stat-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    transition: all 0.3s;
}
.stat-box:hover {
    background: var(--whatsapp-light-green);
    transform: translateY(-3px);
}
.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: var(--whatsapp-teal);
}
.stat-label {
    color: #666;
    font-size: 14px;
}

/* Hero Section */
.contact-hero {
    background: linear-gradient(135deg, #075E54 0%, #128C7E 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(7, 94, 84, 0.3);
}
</style>

<!-- Hero Section with WhatsApp Theme -->
<div class="contact-hero text-center">
    <div class="container">
        <i class="fab fa-whatsapp fa-4x mb-3" style="color: #DCF8C6;"></i>
        <h1 class="display-4 mb-3">Contact Us</h1>
        <p class="lead mb-0">Get instant responses via WhatsApp! 🇷🇼</p>
    </div>
</div>

<div class="container mb-5">
    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-box">
                <div class="stat-number">&lt; 5 min</div>
                <div class="stat-label">Avg Response Time</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-box">
                <div class="stat-number">24/7</div>
                <div class="stat-label">WhatsApp Support</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-box">
                <div class="stat-number">100%</div>
                <div class="stat-label">Satisfaction</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-box">
                <div class="stat-number">🇷🇼</div>
                <div class="stat-label">Local Support</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Contact Form Column -->
        <div class="col-lg-8">
            <div class="whatsapp-card">
                <div class="whatsapp-card-header">
                    <i class="fab fa-whatsapp"></i> Send us a Message
                </div>
                <div class="whatsapp-card-body">
                    
                    <form method="post" action="contact.php" id="contactForm" onsubmit="return sendToWhatsApp()">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       placeholder="John Doe">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       placeholder="john@example.com">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subject <span class="text-danger">*</span></label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Select a subject...</option>
                                <option value="General Inquiry">General Inquiry</option>
                                <option value="Product Question">Product Question</option>
                                <option value="Order Support">Order Support</option>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Billing Question">Billing Question</option>
                                <option value="Returns & Refunds">Returns & Refunds</option>
                                <option value="Wholesale">Wholesale Inquiry</option>
                                <option value="Partnership">Partnership</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Your Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="5" required 
                                      placeholder="Please write your message here..."></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-whatsapp btn-lg" id="submitBtn">
                                <i class="fab fa-whatsapp me-2"></i>Send via WhatsApp
                            </button>
                        </div>
                        
                        <p class="text-muted text-center mt-3 mb-0 small">
                            <i class="fas fa-shield-alt me-1"></i>
                            Your message will be sent directly to our WhatsApp
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Contact Information Column -->
        <div class="col-lg-4">
            <!-- Direct WhatsApp Card -->
            <div class="whatsapp-card">
                <div class="whatsapp-card-header">
                    <i class="fab fa-whatsapp"></i> WhatsApp Quick Chat
                </div>
                <div class="whatsapp-card-body text-center">
                    <i class="fab fa-whatsapp fa-5x mb-3" style="color: <?php echo $whatsapp_green; ?>;"></i>
                    <h4 class="mb-3" style="color: <?php echo $whatsapp_teal; ?>;">Chat Instantly</h4>
                    <p class="text-muted mb-4">Get immediate responses via WhatsApp</p>
                    
                    <a href="https://wa.me/<?php echo $whatsapp_number; ?>?text=<?php echo urlencode($default_message); ?>" 
                       target="_blank" 
                       class="btn btn-whatsapp w-100 mb-2">
                        <i class="fab fa-whatsapp me-2"></i>Start WhatsApp Chat
                    </a>
                    
                    <p class="small text-muted mt-3 mb-0">
                        <i class="fas fa-clock me-1"></i>
                        Typically replies within 5 minutes
                    </p>
                </div>
            </div>
            
            <!-- Contact Info Card -->
            <div class="whatsapp-card">
                <div class="whatsapp-card-header">
                    <i class="fas fa-address-card"></i> Contact Information
                </div>
                <div class="whatsapp-card-body">
                    
                    <div class="d-flex align-items-center mb-4">
                        <div class="contact-icon icon-whatsapp me-3">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div>
                            <div class="small text-muted">WhatsApp</div>
                            <div class="fw-bold">
                                <a href="https://wa.me/<?php echo $whatsapp_number; ?>" class="text-decoration-none" style="color: <?php echo $whatsapp_dark_green; ?>;">
                                    +250 781 013 415
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-4">
                        <div class="contact-icon icon-phone me-3">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Phone</div>
                            <div class="fw-bold">+250 780 364 234</div>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-4">
                        <div class="contact-icon icon-email me-3">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Email</div>
                            <div class="fw-bold">support@techshop.com</div>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-4">
                        <div class="contact-icon icon-map me-3">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Address</div>
                            <div class="fw-bold">KG570 Street, Kigali</div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <h6 class="fw-bold mb-3">Business Hours</h6>
                        <div class="d-flex justify-content-between small">
                            <span>Mon - Fri:</span>
                            <span class="fw-bold">9AM - 10PM</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span>Saturday:</span>
                            <span class="fw-bold">10AM - 5PM</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span>Sunday:</span>
                            <span class="fw-bold text-danger">Closed</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Social Media Card -->
            <div class="whatsapp-card">
                <div class="whatsapp-card-header">
                    <i class="fas fa-share-alt"></i> Connect With Us
                </div>
                <div class="whatsapp-card-body">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="https://web.facebook.com/ttolee2023" target="_blank" class="social-icon facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://x.com/tech" target="_blank" class="social-icon twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.instagram.com/willy_gentleshoes_store/" target="_blank" class="social-icon instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://www.linkedin.com/in/ngirabakunzi-fidele-724654295/" target="_blank" class="social-icon linkedin">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank" class="social-icon whatsapp-social">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p class="small text-muted mb-0">
                            <i class="fas fa-star text-warning me-1"></i>
                            Follow us for updates and offers
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating WhatsApp Button -->
<a href="https://wa.me/<?php echo $whatsapp_number; ?>?text=<?php echo urlencode('Hello TechShop, I need help with...'); ?>" 
   target="_blank" 
   class="whatsapp-float">
    <div class="whatsapp-float-content">
        <i class="fab fa-whatsapp"></i>
        <span>Chat with us</span>
    </div>
</a>

<script>
function sendToWhatsApp() {
    // Get form values
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const subject = document.getElementById('subject').value;
    const message = document.getElementById('message').value.trim();
    
    // Validate form
    if (!name || !email || !subject || !message) {
        alert('Please fill in all required fields');
        return false;
    }
    
    // Create simple WhatsApp message - NO EMOJIS, NO FORMATTING
    let whatsappMessage = "New Contact Form Message\n\n";
    whatsappMessage += "Name: " + name + "\n";
    whatsappMessage += "Email: " + email + "\n";
    whatsappMessage += "Subject: " + subject + "\n\n";
    whatsappMessage += "Message:\n" + message + "\n\n";
    whatsappMessage += "Time: " + new Date().toLocaleString() + "\n";
    whatsappMessage += "From: Website";
    
    // Encode for URL
    const encodedMessage = encodeURIComponent(whatsappMessage);
    
    // Your WhatsApp number
    const whatsappNumber = "<?php echo $whatsapp_number; ?>";
    
    // Create WhatsApp URL
    const whatsappUrl = "https://wa.me/" + whatsappNumber + "?text=" + encodedMessage;
    
    // Open WhatsApp in new tab
    window.open(whatsappUrl, '_blank');
    
    return false; // Prevent form from submitting traditionally
}

// Auto-fill from URL parameters (for product inquiries)
const urlParams = new URLSearchParams(window.location.search);
const productParam = urlParams.get('product');
if (productParam) {
    document.getElementById('subject').value = 'Product Question';
    document.getElementById('message').value = 
        `I'm interested in ${productParam}. Please provide more information about:\n\n- Price\n- Availability\n- Specifications`;
}
</script>

<?php include 'footer.php'; ?>