<?php
// Email Configuration
class EmailConfig {
    // For development - using PHP mail() function
    // For production, you would use SMTP (PHPMailer recommended)
    
    public static function sendEmail($to, $subject, $message, $from = null) {
        if ($from === null) {
            $from = 'noreply@techshop.com';
        }
        
        $headers = "From: TechShop <$from>\r\n";
        $headers .= "Reply-To: support@techshop.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // In development, we'll log emails instead of sending
        if (self::isDevelopment()) {
            return self::logEmail($to, $subject, $message);
        } else {
            return mail($to, $subject, $message, $headers);
        }
    }
    
    private static function isDevelopment() {
        return $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;
    }
    
    private static function logEmail($to, $subject, $message) {
        $logDir = __DIR__ . '/email_logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $filename = $logDir . '/email_' . date('Y-m-d_H-i-s') . '.html';
        $content = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Email Log: $subject</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
                .email-content { border: 1px solid #ddd; padding: 20px; border-radius: 10px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>📧 Email Simulation (Development Mode)</h2>
                <p><strong>To:</strong> $to</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            </div>
            <div class='email-content'>
                $message
            </div>
        </body>
        </html>
        ";
        
        file_put_contents($filename, $content);
        return true;
    }
}

// Email Templates Class
class EmailTemplates {
    public static function orderConfirmation($order, $order_items, $user) {
        $order_total = number_format($order['total_amount'], 2);
        $order_date = date('F j, Y', strtotime($order['created_at']));
        
        $items_html = '';
        foreach ($order_items as $item) {
            $items_html .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['product_name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>$" . number_format($item['product_price'], 2) . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>$" . number_format($item['total_price'], 2) . "</td>
            </tr>
            ";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .order-info { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
                .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .table th { background: #667eea; color: white; padding: 12px; text-align: left; }
                .summary { background: white; padding: 20px; border-radius: 8px; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                .status-badge { background: #28a745; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; display: inline-block; }
                .btn { background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Order Confirmed!</h1>
                    <p>Thank you for your purchase, {$user['first_name']}!</p>
                </div>
                
                <div class='content'>
                    <div class='order-info'>
                        <h2>Order #{$order['order_number']}</h2>
                        <p><strong>Order Date:</strong> $order_date</p>
                        <p><strong>Status:</strong> <span class='status-badge'>Confirmed</span></p>
                    </div>
                    
                    <h3>Order Details</h3>
                    <table class='table'>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            $items_html
                        </tbody>
                    </table>
                    
                    <div class='summary'>
                        <h3>Order Summary</h3>
                        <p><strong>Subtotal:</strong> $" . number_format($order['subtotal'], 2) . "</p>
                        <p><strong>Tax:</strong> $" . number_format($order['tax_amount'], 2) . "</p>
                        <p><strong>Shipping:</strong> $" . number_format($order['shipping_amount'], 2) . "</p>
                        <p style='font-size: 18px;'><strong>Total Amount:</strong> $$order_total</p>
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost/techshop/orders.php' class='btn'>View My Orders</a>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>If you have any questions, please contact our support team.</p>
                    <p>© 2024 TechShop. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    public static function adminNewOrderNotification($order, $order_items, $user, $payment_method, $mobile_number = null) {
        $items_html = '';
        foreach ($order_items as $item) {
            $items_html .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['product_name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>$" . number_format($item['product_price'], 2) . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>$" . number_format($item['total_price'], 2) . "</td>
            </tr>
            ";
        }
        
        $payment_badge_color = $payment_method == 'MoMo' ? '#ffc107' : ($payment_method == 'Airtel Money' ? '#dc3545' : '#28a745');
        $payment_badge_text_color = $payment_method == 'MoMo' ? '#000' : '#fff';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; background: #f8f9fa; }
                .customer-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
                .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .table th { background: #667eea; color: white; padding: 12px; text-align: left; }
                .table td { padding: 12px; border-bottom: 1px solid #ddd; }
                .summary { background: white; padding: 20px; border-radius: 8px; margin-top: 20px; }
                .badge { 
                    display: inline-block; 
                    padding: 5px 10px; 
                    border-radius: 15px; 
                    font-size: 12px; 
                    font-weight: bold;
                    background: $payment_badge_color;
                    color: $payment_badge_text_color;
                }
                .btn { background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>🛍️ New Order Received</h2>
                <p>Order #: {$order['order_number']}</p>
                <p>Date: " . date('F j, Y, g:i a') . "</p>
            </div>
            
            <div class='content'>
                <div class='customer-info'>
                    <h3 style='margin-top:0;'>👤 Customer Information</h3>
                    <p><strong>Name:</strong> {$user['first_name']} {$user['last_name']}</p>
                    <p><strong>Email:</strong> {$user['email']}</p>
                    <p><strong>Phone:</strong> " . ($user['phone'] ?? 'Not provided') . "</p>
                    <p><strong>Shipping Address:</strong><br>" . nl2br($order['shipping_address']) . "</p>
                </div>
                
                <h3>📦 Order Details</h3>
                <table class='table'>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        $items_html
                    </tbody>
                </table>
                
                <div class='summary'>
                    <h4>💰 Order Summary</h4>
                    <p><strong>Subtotal:</strong> $" . number_format($order['subtotal'], 2) . "</p>
                    <p><strong>Tax (8%):</strong> $" . number_format($order['tax_amount'], 2) . "</p>
                    <p><strong>Shipping:</strong> $" . number_format($order['shipping_amount'], 2) . "</p>
                    <p style='font-size: 18px;'><strong>Total Amount:</strong> <span style='color: #28a745;'>$" . number_format($order['total_amount'], 2) . "</span></p>
                </div>
                
                <div class='summary'>
                    <h4>💳 Payment Information</h4>
                    <p><strong>Payment Method:</strong> $payment_method <span class='badge'>" . ($payment_method == 'MoMo' ? 'MTN MoMo' : ($payment_method == 'Airtel Money' ? 'Airtel Money' : 'Credit Card')) . "</span></p>
                    <p><strong>Payment Status:</strong> <span style='color: " . ($order['payment_status'] == 'paid' ? '#28a745' : '#ffc107') . ";'>" . ucfirst($order['payment_status']) . "</span></p>
                    " . ($mobile_number ? "<p><strong>Mobile Money Number:</strong> +250 $mobile_number</p>" : "") . "
                </div>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='http://localhost/techshop/orders.php' class='btn'>View All Orders</a>
                </div>
                
                <div style='margin-top: 30px; text-align: center; color: #666; font-size: 12px;'>
                    <hr>
                    <p>This is an automated notification from TechShop. Please process this order accordingly.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    public static function orderStatusUpdate($order, $user, $old_status, $new_status) {
        $status_icons = [
            'confirmed' => '✅',
            'processing' => '🏭', 
            'shipped' => '🚚',
            'delivered' => '📦',
            'cancelled' => '❌'
        ];
        
        $icon = $status_icons[$new_status] ?? '📧';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .status-update { background: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                .btn { background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>$icon Order Status Updated</h1>
                    <p>Your order status has been updated</p>
                </div>
                
                <div class='content'>
                    <div class='status-update'>
                        <h2>Order #{$order['order_number']}</h2>
                        <p>Status changed from <strong>" . ucfirst($old_status) . "</strong> to</p>
                        <h3 style='color: #667eea;'>" . ucfirst($new_status) . "</h3>
                    </div>
                    
                    <p><strong>What's next?</strong></p>
                    <ul>
                        <li>You'll receive another update when your order ships</li>
                        <li>Track your order anytime using the link below</li>
                        <li>Contact support if you have any questions</li>
                    </ul>
                    
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost/techshop/orders.php' class='btn'>View My Orders</a>
                    </p>
                </div>
                
                <div class='footer'>
                    <p>Thank you for shopping with TechShop!</p>
                    <p>© 2024 TechShop. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    public static function passwordReset($user, $reset_token) {
        $reset_link = "http://localhost/techshop/reset_password.php?token=$reset_token";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .reset-info { background: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                .warning { color: #e74c3c; font-weight: bold; }
                .btn { background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Password Reset Request</h1>
                    <p>We received a request to reset your password</p>
                </div>
                
                <div class='content'>
                    <div class='reset-info'>
                        <h2>Hello, {$user['first_name']}!</h2>
                        <p>You requested to reset your password for your TechShop account.</p>
                        
                        <p style='margin: 30px 0;'>
                            <a href='$reset_link' class='btn'>Reset Your Password</a>
                        </p>
                        
                        <p class='warning'>This link will expire in 1 hour for security reasons.</p>
                        
                        <p>If you didn't request a password reset, please ignore this email.</p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>For security, this link expires in 1 hour.</p>
                    <p>© 2024 TechShop. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>