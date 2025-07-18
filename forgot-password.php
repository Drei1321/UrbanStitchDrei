<?php
session_start();

// Use existing PHPMailer installation
$phpmailer_available = false;

// Check if PHPMailer files exist
if (file_exists('C:\Users\jopet\OneDrive\Desktop\xmpp\PHPMailer\PHPMailer\src\PHPMailer.php')) {
    try {
        require_once 'C:\Users\jopet\OneDrive\Desktop\xmpp\PHPMailer\PHPMailer\src\PHPMailer.php';
        require_once 'C:\Users\jopet\OneDrive\Desktop\xmpp\PHPMailer\PHPMailer\src\SMTP.php';
        require_once 'C:\Users\jopet\OneDrive\Desktop\xmpp\PHPMailer\PHPMailer\src\Exception.php';
        $phpmailer_available = true;
    } catch (Exception $e) {
        error_log("PHPMailer loading failed: " . $e->getMessage());
        $phpmailer_available = false;
    }
}

// Database connection
include 'config.php';

// Gmail SMTP Configuration
$smtpHost = 'smtp.gmail.com';
$smtpUsername = 'albaandrei0903@gmail.com';
$smtpPassword = 'vgva duzu hkil hold'; // App Password
$smtpPort = 587;
$fromEmail = 'albaandrei0903@gmail.com';
$fromName = 'UrbanStitch Security';

// Function to generate reset code
function generateResetCode() {
    return sprintf('%06d', mt_rand(100000, 999999));
}

// Function to send password reset email
function sendPasswordResetEmail($to_email, $to_name, $reset_code) {
    global $smtpHost, $smtpUsername, $smtpPassword, $smtpPort, $fromEmail, $fromName, $phpmailer_available;
    
    if (!$phpmailer_available) {
        error_log("PHPMailer not available. Reset code for $to_email: $reset_code");
        $_SESSION['dev_reset_code'] = $reset_code;
        return 'fallback';
    }
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;
        
        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo($fromEmail, $fromName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = '🔒 Password Reset Request - UrbanStitch Security Alert';
        $mail->Body = getPasswordResetEmailTemplate($to_name, $reset_code, $to_email);
        $mail->AltBody = "Password Reset Request\n\nHi $to_name,\n\nYour password reset verification code is: $reset_code\n\nThis code will expire in 15 minutes.\n\nIf you didn't request this, please ignore this email.\n\nUrbanStitch Security Team";
        
        $mail->send();
        return 'sent';
    } catch (Exception $e) {
        error_log("Password reset email failed: " . $e->getMessage());
        $_SESSION['dev_reset_code'] = $reset_code;
        return 'failed';
    }
}

// Function to get password reset email template
function getPasswordResetEmailTemplate($name, $code, $email) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Password Reset - UrbanStitch Security</title>
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
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            }
            .header { 
                background: linear-gradient(135deg, #dc2626, #991b1b); 
                padding: 40px 30px; 
                text-align: center; 
                color: white;
            }
            .security-icon {
                font-size: 48px;
                margin-bottom: 15px;
            }
            .logo { 
                color: #ffffff; 
                font-size: 32px; 
                font-weight: bold; 
                margin-bottom: 10px; 
            }
            .header-text { 
                color: #fecaca; 
                font-size: 16px; 
                margin-bottom: 0;
            }
            .content { 
                padding: 40px 30px; 
            }
            .alert-title { 
                font-size: 28px; 
                color: #dc2626; 
                margin-bottom: 20px; 
                text-align: center;
                font-weight: bold;
            }
            .message { 
                font-size: 16px; 
                color: #374151; 
                line-height: 1.8; 
                margin-bottom: 30px; 
                text-align: center;
            }
            .security-notice {
                background: linear-gradient(135deg, #fef3c7, #fde68a);
                border: 2px solid #f59e0b;
                padding: 20px;
                border-radius: 12px;
                margin: 25px 0;
                text-align: center;
            }
            .security-notice h3 {
                color: #92400e;
                margin-bottom: 15px;
                font-size: 18px;
            }
            .security-notice p {
                color: #a16207;
                margin: 0;
                font-weight: 500;
            }
            .code-container { 
                background: linear-gradient(135deg, #1f2937, #374151); 
                padding: 35px; 
                border-radius: 15px; 
                text-align: center; 
                margin: 30px 0; 
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
                border: 2px solid #6b7280;
            }
            .code { 
                font-size: 48px; 
                font-weight: bold; 
                color: #f59e0b; 
                letter-spacing: 12px; 
                margin: 20px 0; 
                font-family: 'Courier New', monospace;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
                background: rgba(245, 158, 11, 0.1);
                padding: 15px;
                border-radius: 8px;
            }
            .code-label { 
                color: #d1d5db; 
                font-size: 14px; 
                font-weight: bold; 
                margin-bottom: 10px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .code-expiry {
                color: #9ca3af;
                font-size: 12px;
                margin-top: 10px;
                font-style: italic;
            }
            .instructions { 
                background: linear-gradient(135deg, #f0f9ff, #e0f2fe); 
                padding: 25px; 
                border-radius: 12px; 
                margin: 25px 0; 
                border-left: 4px solid #0ea5e9;
            }
            .instructions h3 { 
                color: #0c4a6e; 
                margin-bottom: 15px; 
                font-size: 18px;
            }
            .instructions ol { 
                color: #075985; 
                padding-left: 20px; 
                margin: 0;
            }
            .instructions li { 
                margin-bottom: 8px; 
                font-size: 15px;
                font-weight: 500;
            }
            .warning-box {
                background: linear-gradient(135deg, #fee2e2, #fecaca);
                border: 2px solid #ef4444;
                color: #991b1b;
                padding: 20px;
                border-radius: 12px;
                margin: 25px 0;
                text-align: center;
            }
            .warning-box h4 {
                margin-bottom: 10px;
                font-size: 16px;
                font-weight: bold;
            }
            .warning-box p {
                margin: 0;
                font-size: 14px;
            }
            .footer { 
                background: linear-gradient(135deg, #1f2937, #111827); 
                padding: 30px; 
                text-align: center; 
            }
            .footer-text { 
                color: #9ca3af; 
                font-size: 14px; 
                line-height: 1.6; 
                margin-bottom: 20px;
            }
            .security-team {
                color: #f59e0b;
                font-weight: bold;
                margin-bottom: 15px;
            }
            .contact-info {
                background: rgba(75, 85, 99, 0.5);
                padding: 15px;
                border-radius: 8px;
                margin: 15px 0;
            }
            .contact-info a { 
                color: #60a5fa; 
                text-decoration: none; 
            }
            .disclaimer { 
                color: #6b7280; 
                font-size: 12px; 
                margin-top: 20px; 
                line-height: 1.4; 
                border-top: 1px solid #374151;
                padding-top: 20px;
            }
            .timestamp {
                color: #9ca3af;
                font-size: 12px;
                margin-top: 15px;
                font-family: monospace;
            }
            @media (max-width: 600px) {
                .content { padding: 25px 20px; }
                .code { 
                    font-size: 36px; 
                    letter-spacing: 8px; 
                    padding: 10px;
                }
                .header { padding: 30px 20px; }
                .security-icon { font-size: 36px; }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='security-icon'>🔒</div>
                <div class='logo'>UrbanStitch</div>
                <p class='header-text'>Security Alert - Password Reset Request</p>
            </div>
            
            <div class='content'>
                <h2 class='alert-title'>🚨 Password Reset Requested</h2>
                
                <p class='message'>
                    Hi <strong>" . htmlspecialchars($name) . "</strong>,<br><br>
                    We received a request to reset the password for your UrbanStitch account. If this was you, use the verification code below to proceed with resetting your password.
                </p>
                
                <div class='security-notice'>
                    <h3>🛡️ Security Verification Required</h3>
                    <p>For your security, we need to verify this password reset request with the code below.</p>
                </div>
                
                <div class='code-container'>
                    <div class='code-label'>🔐 Password Reset Verification Code</div>
                    <div class='code'>" . $code . "</div>
                    <div class='code-expiry'>⏰ Expires in 15 minutes</div>
                </div>
                
                <div class='instructions'>
                    <h3>📋 How to reset your password:</h3>
                    <ol>
                        <li>Go back to the UrbanStitch password reset page</li>
                        <li>Enter the 6-digit verification code above</li>
                        <li>Click 'Verify Code' to proceed</li>
                        <li>Create your new secure password</li>
                        <li>Log in with your new password</li>
                    </ol>
                </div>
                
                <div class='warning-box'>
                    <h4>⚠️ IMPORTANT SECURITY NOTICE</h4>
                    <p><strong>If you did NOT request this password reset:</strong><br>
                    • Ignore this email - your password will remain unchanged<br>
                    • Consider changing your password if you suspect unauthorized access<br>
                    • Contact our security team immediately if you have concerns</p>
                </div>
                
                <p class='message'>
                    <strong>Why reset your password?</strong><br>
                    Regular password updates help keep your account secure and protect your personal information, order history, and payment methods.
                </p>
            </div>
            
            <div class='footer'>
                <div class='security-team'>🛡️ UrbanStitch Security Team</div>
                
                <div class='contact-info'>
                    <strong>Need Help?</strong><br>
                    📧 <a href='mailto:security@urbanstitch.com'>security@urbanstitch.com</a><br>
                    📞 1-800-URBAN-SEC (24/7 Security Hotline)<br>
                    🌐 <a href='#'>urbanstitch.com/security</a>
                </div>
                
                <div class='footer-text'>
                    <strong>UrbanStitch</strong> - Your trusted streetwear destination<br>
                    Protecting your account is our priority
                </div>
                
                <div class='timestamp'>
                    Security Alert Generated: " . date('Y-m-d H:i:s T') . "<br>
                    Request IP: Secured for privacy
                </div>
                
                <div class='disclaimer'>
                    This email was sent to " . htmlspecialchars($email) . " because a password reset was requested for this UrbanStitch account. 
                    This verification code will expire automatically after 15 minutes for security purposes. 
                    If you did not request this reset, no action is required and your account remains secure.
                </div>
            </div>
        </div>
    </body>
    </html>";
}

$error = '';
$success = '';
$step = 'email'; // email, verify, success

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // For security, don't reveal if email exists or not
            $success = "If an account with this email exists, we've sent a password reset code. Please check your email and spam folder.";
            $step = 'verify';
            $_SESSION['reset_email'] = $email;
        } else {
            // Generate reset code
            $reset_code = generateResetCode();
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Store reset code in database
            try {
                $stmt = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE email = ?");
                $stmt->execute([$reset_code, $expires_at, $email]);
                
                // Send reset email
                $email_result = sendPasswordResetEmail($email, $user['first_name'], $reset_code);
                
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_user_id'] = $user['id'];
                
                if ($email_result === 'sent') {
                    $success = "Password reset code sent! Please check your email inbox (and spam folder) for the verification code.";
                } elseif ($email_result === 'failed') {
                    $success = "Email sending failed. Your reset code is: <strong style='background: yellow; padding: 2px 5px; font-family: monospace;'>$reset_code</strong>";
                } else {
                    $success = "Your password reset code is: <strong style='background: yellow; padding: 2px 5px; font-family: monospace;'>$reset_code</strong>";
                }
                
                $step = 'verify';
                
                // Auto-redirect to verification page
                echo "<script>setTimeout(function(){ window.location.href = 'verify-reset.php?email=" . urlencode($email) . "'; }, 3000);</script>";
                
            } catch(PDOException $e) {
                $error = 'Database error. Please try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - UrbanStitch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .forgot-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .forgot-header {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            padding: 40px 30px;
            color: white;
        }

        .security-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .forgot-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .forgot-subtitle {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.5;
        }

        .forgot-form {
            padding: 40px 30px;
        }

        .form-description {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: left;
        }

        .form-description h3 {
            color: #1a1a1a;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .form-description ul {
            color: #666;
            padding-left: 20px;
            line-height: 1.6;
        }

        .form-description li {
            margin-bottom: 8px;
        }

        .phpmailer-status {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .phpmailer-enabled {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .phpmailer-disabled {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1a1a1a;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 18px 20px;
            border: 2px solid #e5e5e5;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
            background: white;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #ccc;
            font-size: 18px;
        }

        .input-icon .form-input {
            padding-right: 55px;
        }

        .reset-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .reset-button:hover {
            background: linear-gradient(135deg, #b91c1c, #7f1d1d);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
        }

        .reset-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .error-message, .success-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-weight: 600;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .success-message {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            line-height: 1.6;
        }

        .form-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            color: #666;
            text-align: center;
        }

        .form-footer a {
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .form-footer a:hover {
            color: #991b1b;
        }

        .security-tips {
            background: #e0f2fe;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: left;
            border-left: 4px solid #0ea5e9;
        }

        .security-tips h4 {
            color: #0c4a6e;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .security-tips ul {
            color: #075985;
            padding-left: 20px;
            font-size: 14px;
            line-height: 1.6;
        }

        .security-tips li {
            margin-bottom: 6px;
        }

        .verification-notice {
            background: #fff7ed;
            border: 2px solid #fb923c;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
        }

        .verification-notice h3 {
            color: #c2410c;
            margin-bottom: 10px;
        }

        .verification-notice p {
            color: #ea580c;
            margin: 0;
            font-weight: 500;
        }

        @media (max-width: 480px) {
            .forgot-container {
                max-width: 400px;
            }

            .forgot-form {
                padding: 30px 20px;
            }

            .forgot-header {
                padding: 30px 20px;
            }

            .security-icon {
                font-size: 48px;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <div class="security-icon">🔒</div>
            <h1 class="forgot-title">Forgot Password?</h1>
            <p class="forgot-subtitle">No worries! We'll help you reset your password securely.</p>
        </div>

        <div class="forgot-form">
            <?php if ($step === 'email'): ?>
            
            <div class="form-description">
                <h3><i class="fas fa-info-circle"></i> How password reset works:</h3>
                <ul>
                    <li>Enter your email address below</li>
                    <li>We'll send a 6-digit verification code to your email</li>
                    <li>Enter the code to verify your identity</li>
                    <li>Create a new secure password</li>
                    <li>Log in with your new password</li>
                </ul>
            </div>

            <!-- PHPMailer Status -->
            <div class="phpmailer-status <?php echo $phpmailer_available ? 'phpmailer-enabled' : 'phpmailer-disabled'; ?>">
                <i class="fas fa-<?php echo $phpmailer_available ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php if ($phpmailer_available): ?>
                    <strong>✅ Email System Ready:</strong> Reset codes will be sent via secure Gmail SMTP.
                <?php else: ?>
                    <strong>⚠️ Development Mode:</strong> Reset codes will be displayed on screen for testing.
                <?php endif; ?>
            </div>

            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
            <?php endif; ?>

            <form method="POST" id="forgotForm">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-icon">
                        <input type="email" name="email" class="form-input" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               placeholder="Enter your registered email address">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>

                <button type="submit" class="reset-button" id="submitButton">
                    <i class="fas fa-paper-plane"></i> Send Reset Code
                </button>
            </form>

            <div class="security-tips">
                <h4><i class="fas fa-shield-alt"></i> Security Tips:</h4>
                <ul>
                    <li>Never share your reset code with anyone</li>
                    <li>The code expires in 15 minutes for security</li>
                    <li>Check your spam folder if you don't see the email</li>
                    <li>Contact support if you don't receive the code</li>
                </ul>
            </div>

            <?php elseif ($step === 'verify'): ?>
            
            <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <div><?php echo $success; ?></div>
            </div>
            <?php endif; ?>

            <div class="verification-notice">
                <h3>📧 Check Your Email!</h3>
                <p>We've sent a verification code to <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong></p>
            </div>

            <div class="form-description">
                <h3><i class="fas fa-clock"></i> Next Steps:</h3>
                <ul>
                    <li>Check your email inbox (and spam folder)</li>
                    <li>Find the email from UrbanStitch Security</li>
                    <li>Copy the 6-digit verification code</li>
                    <li>You'll be redirected to enter the code shortly</li>
                </ul>
            </div>

            <?php endif; ?>
        </div>

        <div class="form-footer">
            <p>Remember your password? <a href="login.php">Sign in here</a></p>
            <br>
            <a href="register.php" style="color: #666;">
                <i class="fas fa-user-plus"></i> Create New Account
            </a>
            <br><br>
            <a href="index.php" style="color: #666;">
                <i class="fas fa-home"></i> Back to Store
            </a>
        </div>
    </div>

    <script>
        // Auto-focus on email input
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput) {
                emailInput.focus();
            }
        });

        // Email validation
        document.querySelector('input[name="email"]')?.addEventListener('input', function() {
            const email = this.value;
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            
            if (email.length > 0) {
                if (isValid) {
                    this.style.borderColor = '#10b981';
                    this.style.backgroundColor = '#f0fdf4';
                } else {
                    this.style.borderColor = '#ef4444';
                    this.style.backgroundColor = '#fef2f2';
                }
            } else {
                this.style.borderColor = '#e5e5e5';
                this.style.backgroundColor = '#f8f9fa';
            }
        });

        // Form submission with loading state
        document.getElementById('forgotForm')?.addEventListener('submit', function() {
            const submitButton = document.getElementById('submitButton');
            if (submitButton) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending Reset Code...';
                submitButton.disabled = true;
            }
        });

        // Auto-redirect countdown for verification step
        <?php if ($step === 'verify' && $success): ?>
        let countdown = 3;
        const redirectMessage = document.createElement('div');
        redirectMessage.style.cssText = 'margin-top: 20px; padding: 15px; background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; color: #0c4a6e; text-align: center; font-weight: bold;';
        redirectMessage.innerHTML = `<i class="fas fa-clock"></i> Redirecting to verification page in <span id="countdownTimer">${countdown}</span> seconds...`;
        document.querySelector('.forgot-form').appendChild(redirectMessage);

        const timer = setInterval(() => {
            countdown--;
            document.getElementById('countdownTimer').textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                redirectMessage.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecting now...';
                window.location.href = 'verify-reset.php?email=<?php echo urlencode($_SESSION['reset_email']); ?>';
            }
        }, 1000);
        <?php endif; ?>

        // Enter key submission
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const form = document.getElementById('forgotForm');
                if (form) {
                    form.submit();
                }
            }
        });

        // Visual feedback for form interaction
        const emailInput = document.querySelector('input[name="email"]');
        if (emailInput) {
            emailInput.addEventListener('focus', function() {
                this.style.borderColor = '#dc2626';
                this.style.boxShadow = '0 0 0 4px rgba(220, 38, 38, 0.1)';
            });

            emailInput.addEventListener('blur', function() {
                if (this.value.length === 0) {
                    this.style.borderColor = '#e5e5e5';
                    this.style.boxShadow = 'none';
                    this.style.backgroundColor = '#f8f9fa';
                }
            });
        }

        // Show helpful message about email checking
        <?php if ($phpmailer_available && $step === 'verify'): ?>
        setTimeout(function() {
            const helpMessage = document.createElement('div');
            helpMessage.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 8px; font-size: 14px; max-width: 300px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); z-index: 1000;';
            helpMessage.innerHTML = '<strong>💡 Tip:</strong> Check your spam folder if you don\'t see the email within 2-3 minutes. Gmail sometimes filters security emails.';
            document.body.appendChild(helpMessage);
            
            setTimeout(() => {
                helpMessage.remove();
            }, 8000);
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>