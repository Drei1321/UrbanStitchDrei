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
$host = '127.0.0.1';
$dbname = 'urbanstitch_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Gmail SMTP Configuration
$smtpHost = 'smtp.gmail.com';
$smtpUsername = 'albaandrei0903@gmail.com';
$smtpPassword = 'vgva duzu hkil hold';
$smtpPort = 587;
$fromEmail = 'albaandrei0903@gmail.com';
$fromName = 'UrbanStitch Security';

// Get email from URL parameter or session
$email = $_GET['email'] ?? $_SESSION['reset_email'] ?? '';
$error = '';
$success = '';

// Redirect if no email provided
if (empty($email)) {
    header('Location: forgot-password.php');
    exit;
}

// Get user information and check reset token
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: forgot-password.php');
    exit;
}

// Check if reset token exists and is not expired
$reset_valid = false;
if ($user['password_reset_token'] && $user['password_reset_expires']) {
    $reset_valid = strtotime($user['password_reset_expires']) > time();
}

if (!$reset_valid && !isset($_POST['resend_code'])) {
    $error = 'Reset code has expired or is invalid. Please request a new one.';
}

// Functions
function generateResetCode() {
    return sprintf('%06d', mt_rand(100000, 999999));
}

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
        $mail->Subject = 'New Password Reset Code - UrbanStitch Security';
        $mail->Body = getNewResetEmailTemplate($to_name, $reset_code, $to_email);
        $mail->AltBody = "New Password Reset Code\n\nYour new verification code is: $reset_code\n\nThis code will expire in 15 minutes.";
        
        $mail->send();
        return 'sent';
    } catch (Exception $e) {
        error_log("Reset email failed: " . $e->getMessage());
        $_SESSION['dev_reset_code'] = $reset_code;
        return 'failed';
    }
}

function getNewResetEmailTemplate($name, $code, $email) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title> New Password Reset Code - UrbanStitch </title>
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
            .logo { 
                color: #ffffff; 
                font-size: 32px; 
                font-weight: bold; 
                margin-bottom: 10px; 
            }
            .header-text { 
                color: #fecaca; 
                font-size: 16px; 
            }
            .content { 
                padding: 40px 30px; 
                text-align: center;
            }
            .resend-title { 
                font-size: 28px; 
                color: #dc2626; 
                margin-bottom: 20px; 
                font-weight: bold;
            }
            .message { 
                font-size: 16px; 
                color: #374151; 
                line-height: 1.8; 
                margin-bottom: 30px; 
            }
            .code-container { 
                background: linear-gradient(135deg, #1f2937, #374151); 
                padding: 35px; 
                border-radius: 15px; 
                margin: 30px 0; 
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            }
            .code { 
                font-size: 48px; 
                font-weight: bold; 
                color: #f59e0b; 
                letter-spacing: 12px; 
                margin: 20px 0; 
                font-family: 'Courier New', monospace;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            }
            .code-label { 
                color: #d1d5db; 
                font-size: 14px; 
                font-weight: bold; 
                text-transform: uppercase;
            }
            .urgent-notice {
                background: #fee2e2;
                border: 2px solid #ef4444;
                color: #991b1b;
                padding: 20px;
                border-radius: 12px;
                margin: 20px 0;
                font-weight: bold;
            }
            .footer { 
                background: linear-gradient(135deg, #1f2937, #111827); 
                padding: 30px; 
                text-align: center; 
                color: #9ca3af;
                font-size: 14px;
            }
            @media (max-width: 600px) {
                .content { padding: 20px; }
                .code { font-size: 36px; letter-spacing: 8px; }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'>🔄 UrbanStitch</div>
                <p class='header-text'> New Password Reset Code Generated</p>
            </div>
            
            <div class='content'>
                <h2 class='resend-title'>🆕 New Reset Code!</h2>
                
                <p class='message'>
                    Hi <strong>" . htmlspecialchars($name) . "</strong>,<br><br>
                    You requested a new password reset code. Your previous code has been invalidated for security.
                </p>
                
                <div class='urgent-notice'>
                    ⚡ This is your <strong>NEW</strong> password reset code - use this instead of the previous one!
                </div>
                
                <div class='code-container'>
                    <div class='code-label'>🔐 Your New Reset Code</div>
                    <div class='code'>" . $code . "</div>
                </div>
                
                <p class='message'>
                    Enter this code on the UrbanStitch password reset page to continue with resetting your password. This code will expire in 15 minutes.
                </p>
            </div>
            
            <div class='footer'>
                <strong>UrbanStitch Security Team</strong><br>
                Generated at " . date('Y-m-d H:i:s') . " | Valid for 15 minutes
            </div>
        </div>
    </body>
    </html>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        $verification_code = trim($_POST['verification_code']);
        
        if (empty($verification_code)) {
            $error = 'Please enter the verification code.';
        } elseif (!$reset_valid) {
            $error = 'Reset code has expired. Please request a new one.';
        } else {
            // Check both possible column names for reset token
            $reset_token = $user['password_reset_token'] ?? $user['email_verification_token'] ?? '';
            
            // Debug info (remove in production)
            error_log("Entered code: " . $verification_code);
            error_log("Database token: " . $reset_token);
            error_log("Tokens match: " . ($reset_token === $verification_code ? 'YES' : 'NO'));
            
            if ($reset_token === $verification_code) {
                // Code is correct, store in session and redirect to reset password page
                $_SESSION['verified_reset_user_id'] = $user['id'];
                $_SESSION['verified_reset_email'] = $user['email'];
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['dev_reset_code']);
                
                // Clear the reset token from database (try both column names)
                try {
                    $stmt = $pdo->prepare("UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
                    $stmt->execute([$user['id']]);
                } catch(PDOException $e) {
                    // Try with the other column name
                    $stmt = $pdo->prepare("UPDATE users SET email_verification_token = NULL WHERE id = ?");
                    $stmt->execute([$user['id']]);
                }
                
                // Show success message before redirect
                $success = "✅ Code verified! Redirecting to password reset page...";
                echo "<script>
                    setTimeout(function(){ 
                        window.location.href = 'reset-password.php'; 
                    }, 2000);
                </script>";
                
                // Don't exit immediately, let user see success message
                // header('Location: reset-password.php');
                // exit;
            } else {
                $error = 'Invalid verification code. Please check your email and try again, or request a new code.';
            }
        }
    } elseif (isset($_POST['resend_code'])) {
        $new_code = generateResetCode();
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Update reset code in database
        $stmt = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
        $stmt->execute([$new_code, $expires_at, $user['id']]);
        
        // Send new verification email
        $email_result = sendPasswordResetEmail($user['email'], $user['first_name'], $new_code);
        
        if ($email_result === 'sent') {
            $success = "✅ New reset code sent! Please check your email inbox (and spam folder).";
        } elseif ($email_result === 'failed') {
            $success = "Email sending failed, but here's your new reset code: <strong style='background: yellow; padding: 2px 5px; font-family: monospace;'>$new_code</strong>";
        } else {
            $success = "New reset code generated: <strong style='background: yellow; padding: 2px 5px; font-family: monospace;'>$new_code</strong>";
        }
        
        // Refresh user data to get new code and expiry
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $reset_valid = strtotime($user['password_reset_expires']) > time();
    }
}

// Get the current reset code for development display
$current_code = $user['password_reset_token'] ?? $user['email_verification_token'] ?? '';
$expires_at = $user['password_reset_expires'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reset Code - UrbanStitch</title>
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

        .verify-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .verify-header {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            padding: 40px 30px;
            color: white;
        }

        .verify-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .verify-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .verify-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .verify-form {
            padding: 40px 30px;
        }

        .email-info {
            background: #fee2e2;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #fecaca;
        }

        .email-info h3 {
            color: #991b1b;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .email-address {
            color: #dc2626;
            font-weight: bold;
            font-size: 16px;
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

        .dev-code {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 16px;
            border: 1px solid #ffeaa7;
        }

        .dev-code .code {
            font-family: 'Courier New', monospace;
            font-size: 24px;
            font-weight: bold;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border: 2px solid #dc2626;
            color: #1a1a1a;
        }

        .verification-code-input {
            width: 100%;
            padding: 20px;
            font-size: 28px;
            text-align: center;
            letter-spacing: 8px;
            border: 3px solid #e5e5e5;
            border-radius: 12px;
            margin-bottom: 25px;
            background: #f8f9fa;
            font-family: 'Courier New', monospace;
        }

        .verification-code-input:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
            background: white;
        }

        .verify-button {
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
            margin-bottom: 15px;
        }

        .verify-button:hover {
            background: linear-gradient(135deg, #b91c1c, #7f1d1d);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
        }

        .resend-button {
            background: transparent;
            color: #dc2626;
            border: 2px solid #dc2626;
        }

        .resend-button:hover {
            background: #dc2626;
            color: white;
        }

        .verify-button:disabled {
            background: #ccc !important;
            cursor: not-allowed !important;
            transform: none !important;
            box-shadow: none !important;
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
        }

        .countdown {
            color: #dc2626;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .instructions {
            background: #fee2e2;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: left;
            border: 1px solid #fecaca;
        }

        .instructions h4 {
            color: #991b1b;
            margin-bottom: 10px;
        }

        .instructions ul {
            color: #7f1d1d;
            padding-left: 20px;
        }

        .instructions li {
            margin-bottom: 5px;
        }

        .expired-notice {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            font-weight: bold;
        }

        @media (max-width: 480px) {
            .verify-container {
                max-width: 400px;
            }

            .verify-form {
                padding: 30px 20px;
            }

            .verification-code-input {
                font-size: 24px;
                letter-spacing: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-header">
            <div class="verify-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="verify-title">Verify Reset Code</h1>
            <p class="verify-subtitle">Enter the code sent to your email</p>
        </div>

        <div class="verify-form">
            <div class="email-info">
                <h3>🔒 Security verification for:</h3>
                <div class="email-address"><?php echo htmlspecialchars($email); ?></div>
            </div>

            <!-- PHPMailer Status -->
            <div class="phpmailer-status <?php echo $phpmailer_available ? 'phpmailer-enabled' : 'phpmailer-disabled'; ?>">
                <i class="fas fa-<?php echo $phpmailer_available ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php if ($phpmailer_available): ?>
                    <strong>✅ Security System Active:</strong> Reset codes sent via secure Gmail SMTP.
                <?php else: ?>
                    <strong>⚠️ Development Mode:</strong> Reset codes displayed on screen for testing.
                <?php endif; ?>
            </div>

            <?php if (!$phpmailer_available && $current_code): ?>
            <!-- Development Code Display -->
            <div class="dev-code">
                <strong>🧑‍💻 Development Mode - Your Reset Code:</strong>
                <div class="code"><?php echo htmlspecialchars($current_code); ?></div>
                <small>Copy this code and paste it in the field below</small>
            </div>
            <?php endif; ?>

            <?php if (!$reset_valid && !isset($_POST['resend_code'])): ?>
            <div class="expired-notice">
                <h4>⏰ Reset Code Expired</h4>
                <p>Your password reset code has expired for security reasons. Please request a new one below.</p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <div><?php echo $success; ?></div>
            </div>
            <?php endif; ?>

            <div class="instructions">
                <h4><i class="fas fa-info-circle"></i> How to verify:</h4>
                <ul>
                    <?php if ($phpmailer_available): ?>
                    <li>Check your email inbox for the reset code</li>
                    <li>⚠️ Don't forget to check your <strong>spam/junk folder</strong></li>
                    <li>Copy the 6-digit code from the email</li>
                    <?php else: ?>
                    <li>Copy the reset code shown above</li>
                    <?php endif; ?>
                    <li>Paste it in the field below</li>
                    <li>Click 'Verify Code' to proceed to password reset</li>
                </ul>
            </div>

            <?php if ($expires_at): ?>
            <div class="countdown" id="countdown">
                Code expires: <span id="timer"></span>
            </div>
            <?php endif; ?>

            <form method="POST" id="verifyForm">
                <input type="text" name="verification_code" class="verification-code-input" 
                       placeholder="000000" maxlength="6" required 
                       pattern="[0-9]{6}" title="Enter the 6-digit reset code"
                       autocomplete="off" id="codeInput"
                       <?php if (!$phpmailer_available && $current_code): ?>value="<?php echo htmlspecialchars($current_code); ?>"<?php endif; ?>
                       <?php if (!$reset_valid): ?>disabled<?php endif; ?>>

                <button type="submit" name="verify_code" class="verify-button" id="verifyButton"
                        <?php if (!$reset_valid): ?>disabled<?php endif; ?>>
                    <i class="fas fa-check"></i> Verify Code & Continue
                </button>

                <button type="submit" name="resend_code" class="verify-button resend-button" id="resendButton">
                    <i class="fas fa-paper-plane"></i> 
                    <?php echo $phpmailer_available ? 'Send New Code' : 'Generate New Code'; ?>
                </button>
            </form>
        </div>

        <div class="form-footer">
            <p>Having trouble? Try requesting a new code or contact security support</p>
            <a href="forgot-password.php">← Go back to password reset</a>
            <br><br>
            <a href="login.php" style="color: #666;">
                <i class="fas fa-sign-in-alt"></i> Back to Login
            </a>
            <br><br>
            <a href="index.php" style="color: #666;">
                <i class="fas fa-home"></i> Back to Store
            </a>
        </div>
    </div>

    <script>
        // Prevent form auto-submit and page reload issues
        let isSubmitting = false;

        // Auto-focus on code input
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('codeInput');
            if (codeInput && !codeInput.disabled) {
                codeInput.focus();
                <?php if (!$phpmailer_available && $current_code): ?>
                codeInput.select();
                <?php endif; ?>
            }
        });

        // Format verification code input (numbers only)
        document.getElementById('codeInput').addEventListener('input', function(e) {
            if (isSubmitting) return;
            
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Visual feedback when 6 digits
            if (this.value.length === 6) {
                this.style.borderColor = '#10b981';
                this.style.backgroundColor = '#f0fdf4';
                showCodeReadyMessage();
            } else {
                this.style.borderColor = '#e5e5e5';
                this.style.backgroundColor = '#f8f9fa';
                removeCodeReadyMessage();
            }
        });

        // Show code ready message
        function showCodeReadyMessage() {
            if (!document.getElementById('codeReadyMessage')) {
                const message = document.createElement('div');
                message.id = 'codeReadyMessage';
                message.style.cssText = 'margin-top: 10px; color: #10b981; font-weight: bold; font-size: 14px; text-align: center;';
                message.innerHTML = '<i class="fas fa-check-circle"></i> Code ready! Click "Verify Code" to continue.';
                document.getElementById('codeInput').parentNode.appendChild(message);
            }
        }

        // Remove code ready message
        function removeCodeReadyMessage() {
            const existingMessage = document.getElementById('codeReadyMessage');
            if (existingMessage) {
                existingMessage.remove();
            }
        }

        // Handle verify button click
        document.getElementById('verifyButton').addEventListener('click', function(e) {
            e.preventDefault();
            
            if (isSubmitting || this.disabled) return;
            
            const code = document.getElementById('codeInput').value;
            if (code.length !== 6) {
                alert('Please enter a 6-digit reset code.');
                return;
            }
            
            isSubmitting = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying Code...';
            this.disabled = true;
            
            // Submit the form
            const form = document.getElementById('verifyForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'verify_code';
            input.value = '1';
            form.appendChild(input);
            form.submit();
        });

        // Handle resend button click
        document.getElementById('resendButton').addEventListener('click', function(e) {
            e.preventDefault();
            
            if (isSubmitting) return;
            
            isSubmitting = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + 
                             (<?php echo $phpmailer_available ? 'true' : 'false'; ?> ? 'Sending Email...' : 'Generating...');
            this.disabled = true;
            
            // Submit the form
            const form = document.getElementById('verifyForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'resend_code';
            input.value = '1';
            form.appendChild(input);
            form.submit();
        });

        // Countdown timer for expiry
        <?php if ($expires_at && $reset_valid): ?>
        const expiryTime = new Date('<?php echo date('c', strtotime($expires_at)); ?>').getTime();
        const timerElement = document.getElementById('timer');
        const verifyButton = document.getElementById('verifyButton');
        const codeInput = document.getElementById('codeInput');

        function updateTimer() {
            const now = new Date().getTime();
            const timeLeft = expiryTime - now;
            
            if (timeLeft <= 0) {
                timerElement.textContent = 'EXPIRED';
                timerElement.style.color = '#ef4444';
                document.getElementById('countdown').innerHTML = '⚠️ <strong>Code has expired.</strong> Please generate a new one.';
                
                // Disable verify button and input
                verifyButton.disabled = true;
                verifyButton.style.background = '#ccc';
                codeInput.disabled = true;
                codeInput.style.background = '#f3f4f6';
                
                // Highlight resend button
                const resendButton = document.getElementById('resendButton');
                resendButton.style.background = '#dc2626';
                resendButton.style.borderColor = '#dc2626';
                resendButton.style.color = 'white';
                resendButton.innerHTML = '<i class="fas fa-refresh"></i> Code Expired - Get New One';
                return;
            }
            
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Change color when less than 5 minutes left
            if (timeLeft <= 300000) { // 5 minutes
                timerElement.style.color = '#ef4444';
                
                // Add urgency message
                if (timeLeft <= 300000 && timeLeft > 299000) {
                    const urgentMessage = document.createElement('div');
                    urgentMessage.style.cssText = 'background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 14px; font-weight: bold; border: 1px solid #ffeaa7;';
                    urgentMessage.innerHTML = '⏰ Less than 5 minutes left! Please verify soon.';
                    document.getElementById('countdown').appendChild(urgentMessage);
                }
            }
            
            setTimeout(updateTimer, 1000);
        }

        // Start the countdown
        updateTimer();
        <?php endif; ?>

        // Paste event handler
        document.getElementById('codeInput').addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const numericPaste = paste.replace(/[^0-9]/g, '').slice(0, 6);
            this.value = numericPaste;
            
            if (numericPaste.length === 6) {
                this.style.borderColor = '#10b981';
                this.style.backgroundColor = '#f0fdf4';
                showCodeReadyMessage();
            }
        });

        // Visual feedback for code input
        document.getElementById('codeInput').addEventListener('focus', function() {
            if (!this.disabled) {
                this.style.borderColor = '#dc2626';
                this.style.boxShadow = '0 0 0 4px rgba(220, 38, 38, 0.1)';
            }
        });

        document.getElementById('codeInput').addEventListener('blur', function() {
            if (this.value.length !== 6 && !this.disabled) {
                this.style.borderColor = '#e5e5e5';
                this.style.boxShadow = 'none';
            }
        });

        // Enter key submission
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !isSubmitting) {
                const code = document.getElementById('codeInput').value;
                const verifyBtn = document.getElementById('verifyButton');
                if (code.length === 6 && !verifyBtn.disabled) {
                    verifyBtn.click();
                }
            }
        });

        // Keyboard input restrictions
        document.getElementById('codeInput').addEventListener('keydown', function(e) {
            // Allow backspace, delete, tab, escape, enter, arrows
            if ([8, 9, 27, 13, 46, 37, 38, 39, 40].indexOf(e.keyCode) !== -1 ||
                // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true)) {
                return;
            }
            
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        // Success message auto-refresh notification
        <?php if (isset($_POST['resend_code']) && $success): ?>
        setTimeout(function() {
            // Clear the input field for new code
            const codeInput = document.getElementById('codeInput');
            codeInput.value = '';
            codeInput.disabled = false;
            codeInput.style.background = '#f8f9fa';
            codeInput.focus();
            removeCodeReadyMessage();
            
            // Enable verify button
            const verifyBtn = document.getElementById('verifyButton');
            verifyBtn.disabled = false;
            verifyBtn.style.background = 'linear-gradient(135deg, #dc2626, #991b1b)';
            verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verify Code & Continue';
            
            // Show notification
            const notification = document.createElement('div');
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #dc2626; color: white; padding: 15px; border-radius: 8px; font-weight: bold; z-index: 1000; box-shadow: 0 5px 15px rgba(220,38,38,0.3);';
            notification.innerHTML = '<i class="fas fa-check"></i> New reset code ready! <?php echo $phpmailer_available ? "Check your email." : "Code updated above."; ?>';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 4000);
        }, 1000);
        <?php endif; ?>

        // Debug logging
        console.log('<?php echo $phpmailer_available ? "✅ PHPMailer active" : "⚠️ Development mode"; ?>');
        
        // Auto-highlight code for easy copying (development mode)
        <?php if (!$phpmailer_available && $current_code): ?>
        setTimeout(function() {
            const input = document.getElementById('codeInput');
            if (input && !input.disabled) {
                input.select();
                input.setSelectionRange(0, 99999); // For mobile devices
            }
        }, 500);
        <?php endif; ?>

        // Redirect success message
        <?php if ($phpmailer_available && isset($_POST['resend_code']) && $success): ?>
        setTimeout(function() {
            const helpMessage = document.createElement('div');
            helpMessage.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 8px; font-size: 14px; max-width: 300px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); z-index: 1000;';
            helpMessage.innerHTML = '<strong>🔒 Security Tip:</strong> Check your spam folder if you don\'t see the new reset email within 2-3 minutes.';
            document.body.appendChild(helpMessage);
            
            setTimeout(() => {
                helpMessage.remove();
            }, 6000);
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>