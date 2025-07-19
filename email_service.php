<?php
// email_service.php - Centralized email service for UrbanStitch
class EmailService {
    private $smtpHost = 'smtp.gmail.com';
    private $smtpUsername = 'albaandrei0903@gmail.com';
    private $smtpPassword = 'vgva duzu hkil hold'; // App Password
    private $smtpPort = 587;
    private $fromEmail = 'albaandrei0903@gmail.com';
    private $fromName = 'UrbanStitch';
    private $phpmailerAvailable = false;

    public function __construct() {
        $this->checkPHPMailerAvailability();
    }

    private function checkPHPMailerAvailability() {
    $phpmailer_available = false;
    
    // Try multiple paths for PHPMailer (remove hardcoded Windows path)
    $phpmailer_paths = [
        'PHPMailer/src/',
        'PHPMailer\\src\\',
        './PHPMailer/src/',
        '../PHPMailer/src/',
        'vendor/phpmailer/phpmailer/src/',
        __DIR__ . '/PHPMailer/src/',
        __DIR__ . '\\PHPMailer\\src\\',
        __DIR__ . '/../PHPMailer/src/',
        __DIR__ . '\\..\\PHPMailer\\src\\',
        'includes/PHPMailer/src/',
        'lib/PHPMailer/src/',
        'libraries/PHPMailer/src/',
        'C:\\Users\\jopet\\OneDrive\\Desktop\\xmpp\\PHPMailer\\PHPMailer\\src\\' // Keep your current path as fallback
    ];
    
    foreach ($phpmailer_paths as $path) {
        $phpmailer_file = $path . 'PHPMailer.php';
        $smtp_file = $path . 'SMTP.php';
        $exception_file = $path . 'Exception.php';
        
        // Check if all required PHPMailer files exist in this path
        if (file_exists($phpmailer_file) && file_exists($smtp_file) && file_exists($exception_file)) {
            try {
                require_once $phpmailer_file;
                require_once $smtp_file;
                require_once $exception_file;
                
                $this->phpmailerAvailable = true;
                $phpmailer_available = true;
                
                // Log successful path for debugging
                error_log("PHPMailer loaded successfully from: " . $path);
                break;
                
            } catch (Exception $e) {
                error_log("PHPMailer loading failed from path '$path': " . $e->getMessage());
                continue;
            }
        }
    }
    
    if (!$phpmailer_available) {
        error_log("PHPMailer not found in any of the attempted paths");
        $this->phpmailerAvailable = false;
    }
    
    return $phpmailer_available;
}

    /**
     * Send order status update email to customer
     * 
     * @param array $orderData Order information
     * @param string $newStatus New order status
     * @param string $adminNotes Admin notes (optional)
     * @param array $adminInfo Admin information
     * @return array Result with success status and message
     */
    public function sendOrderStatusUpdate($orderData, $newStatus, $adminNotes = '', $adminInfo = null) {
        if (!$this->phpmailerAvailable) {
            // Fallback to development mode - log the email content
            $emailContent = $this->generateOrderUpdateEmailTemplate($orderData, $newStatus, $adminNotes, $adminInfo);
            error_log("Order Status Update Email (Development Mode):");
            error_log("To: " . $orderData['email']);
            error_log("Subject: Order Update - " . $orderData['order_number']);
            error_log("Content: " . strip_tags($emailContent));
            
            return [
                'success' => true,
                'message' => 'Email logged (Development Mode)',
                'mode' => 'development'
            ];
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;

            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($orderData['email'], $orderData['customer_name']);
            $mail->addReplyTo($this->fromEmail, $this->fromName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Order Update - ' . $orderData['order_number'];
            $mail->Body = $this->generateOrderUpdateEmailTemplate($orderData, $newStatus, $adminNotes, $adminInfo);
            $mail->AltBody = $this->generatePlainTextOrderUpdate($orderData, $newStatus, $adminNotes);

            $mail->send();
            
            return [
                'success' => true,
                'message' => 'Order update email sent successfully',
                'mode' => 'production'
            ];

        } catch (Exception $e) {
            error_log("Order update email failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
                'mode' => 'failed'
            ];
        }
    }

    /**
     * Generate HTML email template for order status update
     */
    private function generateOrderUpdateEmailTemplate($orderData, $newStatus, $adminNotes, $adminInfo) {
        $statusInfo = $this->getStatusDisplayInfo($newStatus);
        $orderItems = $this->formatOrderItems($orderData['items'] ?? []);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Order Update - UrbanStitch</title>
            <style>
                body { 
                    margin: 0; 
                    padding: 0; 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    background-color: #f4f4f4; 
                    line-height: 1.6;
                }
                .container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background-color: #ffffff; 
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #1a1a1a, #2d2d2d); 
                    padding: 40px 30px; 
                    text-align: center; 
                }
                .logo { 
                    color: #00ff00; 
                    font-size: 42px; 
                    font-weight: bold; 
                    margin-bottom: 10px; 
                }
                .header-text { 
                    color: #ffffff; 
                    font-size: 18px; 
                }
                .content { 
                    padding: 40px 30px; 
                }
                .status-update {
                    background: " . $statusInfo['background'] . ";
                    color: " . $statusInfo['color'] . ";
                    padding: 30px;
                    border-radius: 15px;
                    text-align: center;
                    margin: 30px 0;
                    border: 2px solid " . $statusInfo['border'] . ";
                }
                .status-icon {
                    font-size: 48px;
                    margin-bottom: 15px;
                }
                .status-title {
                    font-size: 24px;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                .order-info {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 10px;
                    margin: 20px 0;
                }
                .order-info h3 {
                    color: #1a1a1a;
                    margin-bottom: 15px;
                    font-size: 18px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                    padding: 5px 0;
                    border-bottom: 1px solid #eee;
                }
                .info-label {
                    font-weight: bold;
                    color: #666;
                }
                .info-value {
                    color: #1a1a1a;
                }
                .admin-notes {
                    background: #e3f2fd;
                    padding: 20px;
                    border-radius: 10px;
                    margin: 20px 0;
                    border-left: 4px solid #2196f3;
                }
                .order-items {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 10px;
                    margin: 20px 0;
                }
                .item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 10px 0;
                    border-bottom: 1px solid #eee;
                }
                .item:last-child {
                    border-bottom: none;
                }
                .footer { 
                    background-color: #1a1a1a; 
                    padding: 30px; 
                    text-align: center; 
                    color: #999;
                    font-size: 14px;
                }
                .cta-button {
                    display: inline-block;
                    background: linear-gradient(135deg, #00ff00, #00cc00);
                    color: #1a1a1a;
                    padding: 15px 30px;
                    text-decoration: none;
                    border-radius: 25px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .tracking-info {
                    background: #fff3cd;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 15px 0;
                    border-left: 4px solid #ffc107;
                }
                @media (max-width: 600px) {
                    .content { padding: 20px; }
                    .info-row { flex-direction: column; }
                    .item { flex-direction: column; align-items: flex-start; }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>UrbanStitch</div>
                    <p class='header-text'>📦 Order Status Update</p>
                </div>
                
                <div class='content'>
                    <h2>Hi " . htmlspecialchars($orderData['customer_name']) . ",</h2>
                    
                    <p>Great news! We have an update on your UrbanStitch order.</p>
                    
                    <div class='status-update'>
                        <div class='status-icon'>" . $statusInfo['icon'] . "</div>
                        <div class='status-title'>Your order is now " . ucfirst($newStatus) . "</div>
                        <p>" . $statusInfo['description'] . "</p>
                    </div>
                    
                    <div class='order-info'>
                        <h3>📋 Order Details</h3>
                        <div class='info-row'>
                            <span class='info-label'>Order Number:</span>
                            <span class='info-value'>" . htmlspecialchars($orderData['order_number']) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Order Date:</span>
                            <span class='info-value'>" . date('F j, Y', strtotime($orderData['created_at'])) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Total Amount:</span>
                            <span class='info-value'>₱" . number_format($orderData['total_amount'], 2) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Payment Method:</span>
                            <span class='info-value'>" . strtoupper($orderData['payment_method']) . "</span>
                        </div>
                    </div>";

        if (!empty($adminNotes)) {
            $return .= "
                    <div class='admin-notes'>
                        <h4>📝 Additional Information</h4>
                        <p>" . nl2br(htmlspecialchars($adminNotes)) . "</p>
                    </div>";
        }

        if ($newStatus === 'shipped') {
            $return .= "
                    <div class='tracking-info'>
                        <h4>🚚 Shipping Information</h4>
                        <p>Your order is on its way! You can expect delivery within 3-7 business days.</p>
                        <p><strong>Tracking:</strong> We'll send you tracking information once available.</p>
                    </div>";
        }

        if (!empty($orderItems)) {
            $return .= "
                    <div class='order-items'>
                        <h3>🛍️ Order Items</h3>
                        " . $orderItems . "
                    </div>";
        }

        $return .= "
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . $this->getSiteUrl() . "/orders.php' class='cta-button'>
                            📱 Track Your Order
                        </a>
                    </div>
                    
                    <p>Thank you for shopping with UrbanStitch! If you have any questions about your order, please don't hesitate to contact our customer service team.</p>
                </div>
                
                <div class='footer'>
                    <strong>UrbanStitch</strong> - Your ultimate destination for street fashion<br>
                    Email sent: " . date('Y-m-d H:i:s') . "<br>
                    Order managed by: " . ($adminInfo['username'] ?? 'Admin') . "
                </div>
            </div>
        </body>
        </html>";

        return $return;
    }

    /**
     * Generate plain text version of order update
     */
    private function generatePlainTextOrderUpdate($orderData, $newStatus, $adminNotes) {
        $statusInfo = $this->getStatusDisplayInfo($newStatus);
        
        $text = "UrbanStitch - Order Status Update\n\n";
        $text .= "Hi " . $orderData['customer_name'] . ",\n\n";
        $text .= "Your order " . $orderData['order_number'] . " has been updated.\n\n";
        $text .= "New Status: " . ucfirst($newStatus) . "\n";
        $text .= "Description: " . $statusInfo['description'] . "\n\n";
        
        if (!empty($adminNotes)) {
            $text .= "Additional Information:\n" . $adminNotes . "\n\n";
        }
        
        $text .= "Order Details:\n";
        $text .= "- Order Number: " . $orderData['order_number'] . "\n";
        $text .= "- Total Amount: ₱" . number_format($orderData['total_amount'], 2) . "\n";
        $text .= "- Payment Method: " . strtoupper($orderData['payment_method']) . "\n\n";
        
        $text .= "You can track your order at: " . $this->getSiteUrl() . "/orders.php\n\n";
        $text .= "Thank you for shopping with UrbanStitch!\n\n";
        $text .= "Best regards,\nUrbanStitch Team";
        
        return $text;
    }

    /**
     * Get status display information (icon, color, description)
     */
    private function getStatusDisplayInfo($status) {
        $statusMap = [
            'pending' => [
                'icon' => '⏳',
                'color' => '#856404',
                'background' => '#fff3cd',
                'border' => '#ffc107',
                'description' => 'We have received your order and are preparing it for processing.'
            ],
            'confirmed' => [
                'icon' => '✅',
                'color' => '#0c5460',
                'background' => '#d1ecf1',
                'border' => '#17a2b8',
                'description' => 'Your payment has been verified and your order is confirmed!'
            ],
            'processing' => [
                'icon' => '⚙️',
                'color' => '#004085',
                'background' => '#cce5ff',
                'border' => '#007bff',
                'description' => 'Your order is being prepared and will be shipped soon.'
            ],
            'shipped' => [
                'icon' => '🚚',
                'color' => '#383d41',
                'background' => '#e2e3e5',
                'border' => '#6c757d',
                'description' => 'Your order has been shipped and is on its way to you!'
            ],
            'delivered' => [
                'icon' => '🏠',
                'color' => '#155724',
                'background' => '#d4edda',
                'border' => '#28a745',
                'description' => 'Your order has been successfully delivered. Enjoy your purchase!'
            ],
            'completed' => [
                'icon' => '🎉',
                'color' => '#155724',
                'background' => '#d4edda',
                'border' => '#28a745',
                'description' => 'Your order is complete! Thank you for shopping with UrbanStitch.'
            ],
            'cancelled' => [
                'icon' => '❌',
                'color' => '#721c24',
                'background' => '#f8d7da',
                'border' => '#dc3545',
                'description' => 'Your order has been cancelled. If you have any questions, please contact us.'
            ]
        ];

        return $statusMap[$status] ?? [
            'icon' => '📦',
            'color' => '#495057',
            'background' => '#f8f9fa',
            'border' => '#6c757d',
            'description' => 'Your order status has been updated.'
        ];
    }

    /**
     * Format order items for email display
     */
    private function formatOrderItems($items) {
        if (empty($items)) {
            return '<p>Order items information not available.</p>';
        }

        $html = '';
        foreach ($items as $item) {
            $html .= "
                <div class='item'>
                    <div>
                        <strong>" . htmlspecialchars($item['name'] ?? 'Product') . "</strong><br>
                        <small>Qty: " . ($item['quantity'] ?? 1) . "</small>
                    </div>
                    <div>₱" . number_format($item['price'] ?? 0, 2) . "</div>
                </div>";
        }

        return $html;
    }

    /**
     * Get site URL (you might want to make this configurable)
     */
    private function getSiteUrl() {
        return 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
    }

    /**
     * Send verification email (existing functionality from verify.php)
     */
    public function sendVerificationEmail($to_email, $to_name, $verification_code) {
        if (!$this->phpmailerAvailable) {
            error_log("PHPMailer not available. Verification code for $to_email: $verification_code");
            $_SESSION['dev_verification_code'] = $verification_code;
            return 'fallback';
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;

            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to_email, $to_name);
            $mail->addReplyTo($this->fromEmail, $this->fromName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email - UrbanStitch';
            $mail->Body = $this->getVerificationEmailTemplate($to_name, $verification_code, $to_email);
            $mail->AltBody = "Your UrbanStitch verification code is: $verification_code\n\nThis code will expire in 15 minutes.";

            $mail->send();
            return 'sent';
        } catch (Exception $e) {
            error_log("Verification email failed: " . $e->getMessage());
            $_SESSION['dev_verification_code'] = $verification_code;
            return 'failed';
        }
    }

    /**
     * Get verification email template (from verify.php)
     */
    private function getVerificationEmailTemplate($name, $code, $email) {
        // Use the same template from verify.php
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Verify Your Email - UrbanStitch</title>
            <style>
                body { 
                    margin: 0; 
                    padding: 0; 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    background-color: #f4f4f4; 
                    line-height: 1.6;
                }
                .container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background-color: #ffffff; 
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #1a1a1a, #2d2d2d); 
                    padding: 40px 30px; 
                    text-align: center; 
                }
                .logo { 
                    color: #00ff00; 
                    font-size: 42px; 
                    font-weight: bold; 
                    margin-bottom: 10px; 
                }
                .header-text { 
                    color: #ffffff; 
                    font-size: 18px; 
                }
                .content { 
                    padding: 40px 30px; 
                    text-align: center;
                }
                .code-container { 
                    background: linear-gradient(135deg, #00ff00, #00cc00); 
                    padding: 30px; 
                    border-radius: 15px; 
                    margin: 30px 0; 
                    box-shadow: 0 5px 15px rgba(0, 255, 0, 0.3);
                }
                .code { 
                    font-size: 42px; 
                    font-weight: bold; 
                    color: #1a1a1a; 
                    letter-spacing: 8px; 
                    margin: 15px 0; 
                    font-family: 'Courier New', monospace;
                }
                .footer { 
                    background-color: #1a1a1a; 
                    padding: 30px; 
                    text-align: center; 
                    color: #999;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>UrbanStitch</div>
                    <p class='header-text'>Welcome to UrbanStitch!</p>
                </div>
                
                <div class='content'>
                    <h2>Hi " . htmlspecialchars($name) . "!</h2>
                    <p>Thank you for registering with UrbanStitch. Please verify your email address using the code below:</p>
                    
                    <div class='code-container'>
                        <div class='code'>" . $code . "</div>
                    </div>
                    
                    <p>This code will expire in 15 minutes for security purposes.</p>
                </div>
                
                <div class='footer'>
                    <strong>UrbanStitch</strong> - Your ultimate destination for street fashion<br>
                    Email sent to " . htmlspecialchars($email) . "
                </div>
            </div>
        </body>
        </html>";
    }
}
?>