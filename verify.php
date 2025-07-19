<?php
session_start();

// Try multiple paths for PHPMailer (remove hardcoded Windows path)
$phpmailer_paths = [
    'PHPMailer/src/',
    'PHPMailer\\src\\',
    './PHPMailer/src/',
    '../PHPMailer/src/',
    'vendor/phpmailer/phpmailer/src/',
    __DIR__ . '/PHPMailer/src/',
    __DIR__ . '\\PHPMailer\\src\\'
];

foreach ($phpmailer_paths as $path) {
    if (file_exists($path . 'PHPMailer.php') && 
        file_exists($path . 'SMTP.php') && 
        file_exists($path . 'Exception.php')) {
        try {
            require_once $path . 'PHPMailer.php';
            require_once $path . 'SMTP.php';
            require_once $path . 'Exception.php';
            $phpmailer_available = true;
            error_log("PHPMailer loaded successfully from: " . $path);
            break;
        } catch (Exception $e) {
            error_log("PHPMailer loading failed from $path: " . $e->getMessage());
            continue;
        }
    }
}

include 'config.php';

// Gmail SMTP Configuration
$smtpHost = 'smtp.gmail.com';
$smtpUsername = 'albaandrei0903@gmail.com';
$smtpPassword = 'vgva duzu hkil hold'; // App Password
$smtpPort = 587;
$fromEmail = 'albaandrei0903@gmail.com';
$fromName = 'UrbanStitch';

// Get email from URL parameter or session
$email = $_GET['email'] ?? $_SESSION['registration_email'] ?? '';
$error = '';
$success = '';

// Redirect if no email provided
if (empty($email)) {
    header('Location: register.php');
    exit;
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: register.php');
    exit;
}

// If already verified, redirect to login
if ($user['email_verified']) {
    header('Location: login.php?verified=already');
    exit;
}

// Functions
function generateVerificationCode() {
    return sprintf('%06d', mt_rand(100000, 999999));
}

function sendVerificationEmail($to_email, $to_name, $verification_code) {
    global $smtpHost, $smtpUsername, $smtpPassword, $smtpPort, $fromEmail, $fromName, $phpmailer_available;
    
    if (!$phpmailer_available) {
        // Fallback to development mode
        error_log("PHPMailer not available. Verification code for $to_email: $verification_code");
        $_SESSION['dev_verification_code'] = $verification_code;
        return 'fallback';
    }
    
    // Use PHPMailer without namespace
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
        $mail->Subject = 'New Verification Code - UrbanStitch';
        $mail->Body = getResendEmailTemplate($to_name, $verification_code, $to_email);
        $mail->AltBody = " Your new UrbanStitch verification code is: $verification_code\n\nThis code will expire in 15 minutes.";
        
        $mail->send();
        return 'sent';
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: " . $e->getMessage());
        $_SESSION['dev_verification_code'] = $verification_code;
        return 'failed';
    }
}

function getResendEmailTemplate($name, $code, $email) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title> New Verification Code - UrbanStitch</title>
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
            .resend-title { 
                font-size: 28px; 
                color: #1a1a1a; 
                margin-bottom: 20px; 
            }
            .message { 
                font-size: 16px; 
                color: #555; 
                line-height: 1.8; 
                margin-bottom: 30px; 
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
            .code-label { 
                color: #1a1a1a; 
                font-size: 16px; 
                font-weight: bold; 
            }
            .security-note {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .footer { 
                background-color: #1a1a1a; 
                padding: 30px; 
                text-align: center; 
                color: #999;
                font-size: 14px;
            }
            .urgent-notice {
                background-color: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                font-weight: bold;
            }
            @media (max-width: 600px) {
                .content { padding: 20px; }
                .code { font-size: 32px; letter-spacing: 6px; }
                .header { padding: 30px 15px; }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'>UrbanStitch</div>
                <p class='header-text'>🔄 New Verification Code Requested</p>
            </div>
            
            <div class='content'>
                <h2 class='resend-title'>New Code Generated! 🆕</h2>
                
                <p class='message'>
                    Hi " . htmlspecialchars($name) . ",<br><br>
                    You requested a new verification code for your UrbanStitch account. Your previous code has been invalidated.
                </p>
                
                <div class='urgent-notice'>
                    ⚡ This is your <strong>NEW</strong> verification code - use this one instead of the previous code!
                </div>
                
                <div class='code-container'>
                    <div class='code-label'>🔐 YOUR NEW VERIFICATION CODE</div>
                    <div class='code'>" . $code . "</div>
                </div>
                
                <div class='security-note'>
                    <strong>🔒 Security Notice:</strong><br>
                    This new verification code will expire in <strong>15 minutes</strong>. 
                    Your previous verification code has been automatically invalidated for security.
                </div>
                
                <p class='message'>
                    Simply copy this code and paste it into the verification field on the UrbanStitch website to complete your registration.
                </p>
            </div>
            
            <div class='footer'>
                <strong>UrbanStitch</strong> - Your ultimate destination for street fashion<br>
                This email was sent to " . htmlspecialchars($email) . " | Generated at " . date('Y-m-d H:i:s') . "
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
        } else {
            if ($user['email_verification_token'] === $verification_code) {
                // Verify the account
                $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, email_verification_token = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Clean up session
                unset($_SESSION['registration_email']);
                unset($_SESSION['registration_name']);
                unset($_SESSION['dev_verification_code']);
                
                header('Location: login.php?registered=success');
                exit;
            } else {
                $error = 'Invalid verification code. Please check your email and try again, or request a new code.';
            }
        }
    } elseif (isset($_POST['resend_code'])) {
        $new_code = generateVerificationCode();
        
        // Update verification code in database
        $stmt = $pdo->prepare("UPDATE users SET email_verification_token = ? WHERE id = ?");
        $stmt->execute([$new_code, $user['id']]);
        
        // Send new verification email
        $email_result = sendVerificationEmail($user['email'], $user['first_name'], $new_code);
        
        if ($email_result === 'sent') {
            $success = "✅ New verification code sent! Please check your email inbox (and spam folder).";
        } elseif ($email_result === 'failed') {
            $success = "Email sending failed, but here's your new verification code: <strong style='background: yellow; padding: 2px 5px; font-family: monospace;'>$new_code</strong>";
        } else {
            $success = "New verification code generated: <strong style='background: yellow; padding: 2px 5px; font-family: monospace;'>$new_code</strong>";
        }
        
        // Refresh user data to get new code
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }
}

// Get the current verification code for development display
$current_code = $user['email_verification_token'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - UrbanStitch</title>
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
            background: linear-gradient(135deg, #00ff00, #00cc00);
            padding: 40px 30px;
            color: #1a1a1a;
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
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .email-info h3 {
            color: #1a1a1a;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .email-address {
            color: #00ff00;
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
            border: 2px solid #00ff00;
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
            border-color: #00ff00;
            box-shadow: 0 0 0 4px rgba(0, 255, 0, 0.1);
            background: white;
        }

        .verify-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
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
            background: linear-gradient(135deg, #00ff00, #00cc00);
            color: #1a1a1a;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 255, 0, 0.3);
        }

        .resend-button {
            background: transparent;
            color: #00ff00;
            border: 2px solid #00ff00;
        }

        .resend-button:hover {
            background: #00ff00;
            color: #1a1a1a;
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
            background: #ff4444;
            color: white;
        }

        .success-message {
            background: #00ff00;
            color: #1a1a1a;
            line-height: 1.6;
        }

        .form-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            color: #666;
            text-align: center;
        }

        .form-footer a {
            color: #00ff00;
            text-decoration: none;
            font-weight: 600;
        }

        .countdown {
            color: #ff6b6b;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .instructions {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: left;
        }

        .instructions h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }

        .instructions ul {
            color: #666;
            padding-left: 20px;
        }

        .instructions li {
            margin-bottom: 5px;
        }

        .tips {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
            color: #0369a1;
            border-left: 4px solid #0ea5e9;
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
                <i class="fas fa-envelope-circle-check"></i>
            </div>
            <h1 class="verify-title">Check Your Email</h1>
            <p class="verify-subtitle">We've sent you a verification code</p>
        </div>

        <div class="verify-form">
            <div class="email-info">
                <h3>📧 Verification code sent to:</h3>
                <div class="email-address"><?php echo htmlspecialchars($email); ?></div>
            </div>

            <!-- PHPMailer Status -->
            <div class="phpmailer-status <?php echo $phpmailer_available ? 'phpmailer-enabled' : 'phpmailer-disabled'; ?>">
                <i class="fas fa-<?php echo $phpmailer_available ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php if ($phpmailer_available): ?>
                    <strong>✅ Email System Active:</strong> Real emails are being sent via Gmail SMTP.
                <?php else: ?>
                    <strong>⚠️ PHPMailer Issue:</strong> Displaying verification codes on screen for testing.
                <?php endif; ?>
            </div>

            <?php if (!$phpmailer_available): ?>
            <!-- Development Code Display -->
            <div class="dev-code">
                <strong>🧑‍💻 Development Mode - Your Verification Code:</strong>
                <div class="code"><?php echo htmlspecialchars($current_code); ?></div>
                <small>Copy this code and paste it in the field below</small>
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
                    <li>Check your email inbox for the verification code</li>
                    <li>⚠️ Don't forget to check your <strong>spam/junk folder</strong></li>
                    <li>Copy the 6-digit verification code from the email</li>
                    <?php else: ?>
                    <li>Copy the verification code shown above</li>
                    <?php endif; ?>
                    <li>Paste it in the field below</li>
                    <li>Click 'Verify Account' to complete registration</li>
                </ul>
            </div>

            <?php if ($phpmailer_available): ?>
            <div class="tips">
                <strong>💡 Tips:</strong> If you don't see the email, try checking your spam folder or requesting a new code. Gmail sometimes takes 1-2 minutes to deliver emails.
            </div>
            <?php endif; ?>

            <div class="countdown" id="countdown">
                Code expires in: <span id="timer">15:00</span>
            </div>

            <form method="POST" id="verifyForm">
                <input type="text" name="verification_code" class="verification-code-input" 
                       placeholder="000000" maxlength="6" required 
                       pattern="[0-9]{6}" title="Enter the 6-digit code"
                       autocomplete="off" id="codeInput"
                       <?php if (!$phpmailer_available): ?>value="<?php echo htmlspecialchars($current_code); ?>"<?php endif; ?>>

                <button type="submit" name="verify_code" class="verify-button" id="verifyButton">
                    <i class="fas fa-check"></i> Verify Account
                </button>

                <button type="submit" name="resend_code" class="verify-button resend-button" id="resendButton">
                    <i class="fas fa-paper-plane"></i> 
                    <?php echo $phpmailer_available ? 'Send New Code' : 'Generate New Code'; ?>
                </button>
            </form>
        </div>

        <div class="form-footer">
            <p>Having trouble? Try requesting a new code or contact support</p>
            <a href="register.php">← Go back to registration</a>
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
            document.getElementById('codeInput').focus();
            <?php if (!$phpmailer_available): ?>
            document.getElementById('codeInput').select();
            <?php endif; ?>
        });

        // Format verification code input (numbers only, no auto-submit)
        document.getElementById('codeInput').addEventListener('input', function(e) {
            // Prevent submission during input
            if (isSubmitting) return;
            
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Visual feedback when 6 digits
            if (this.value.length === 6) {
                this.style.borderColor = '#00ff00';
                this.style.backgroundColor = '#f0fff0';
                
                // Show ready message
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
                message.style.cssText = 'margin-top: 10px; color: #00ff00; font-weight: bold; font-size: 14px; text-align: center;';
                message.innerHTML = '<i class="fas fa-check-circle"></i> Code ready! Click "Verify Account" to continue.';
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
            
            if (isSubmitting) return;
            
            const code = document.getElementById('codeInput').value;
            if (code.length !== 6) {
                alert('Please enter a 6-digit verification code.');
                return;
            }
            
            isSubmitting = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
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

        // Countdown timer (15 minutes)
        let timeLeft = 15 * 60; // 15 minutes in seconds
        const timerElement = document.getElementById('timer');
        const resendButton = document.getElementById('resendButton');

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                timerElement.textContent = 'EXPIRED';
                timerElement.style.color = '#ff4444';
                document.getElementById('countdown').innerHTML = '⚠️ <strong>Code has expired.</strong> Please generate a new one.';
                resendButton.style.background = '#ff4444';
                resendButton.style.borderColor = '#ff4444';
                resendButton.style.color = 'white';
                resendButton.innerHTML = '<i class="fas fa-refresh"></i> Code Expired - Get New One';
                return;
            }
            
            // Change color when less than 5 minutes left
            if (timeLeft <= 300) {
                timerElement.style.color = '#ff6b6b';
                
                // Add urgency message
                if (timeLeft === 300) {
                    const urgentMessage = document.createElement('div');
                    urgentMessage.style.cssText = 'background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 14px; font-weight: bold;';
                    urgentMessage.innerHTML = '⏰ Less than 5 minutes left! Please verify soon.';
                    document.getElementById('countdown').appendChild(urgentMessage);
                }
            }
            
            timeLeft--;
            setTimeout(updateTimer, 1000);
        }

        // Start the countdown
        updateTimer();

        // Paste event handler
        document.getElementById('codeInput').addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const numericPaste = paste.replace(/[^0-9]/g, '').slice(0, 6);
            this.value = numericPaste;
            
            if (numericPaste.length === 6) {
                this.style.borderColor = '#00ff00';
                this.style.backgroundColor = '#f0fff0';
                showCodeReadyMessage();
            }
        });

        // Visual feedback for code input
        document.getElementById('codeInput').addEventListener('focus', function() {
            this.style.borderColor = '#00ff00';
            this.style.boxShadow = '0 0 0 4px rgba(0, 255, 0, 0.1)';
        });

        document.getElementById('codeInput').addEventListener('blur', function() {
            if (this.value.length !== 6) {
                this.style.borderColor = '#e5e5e5';
                this.style.boxShadow = 'none';
            }
        });

        // Enter key submission
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !isSubmitting) {
                const code = document.getElementById('codeInput').value;
                if (code.length === 6) {
                    document.getElementById('verifyButton').click();
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
            document.getElementById('codeInput').value = '';
            document.getElementById('codeInput').focus();
            removeCodeReadyMessage();
            
            // Show notification
            const notification = document.createElement('div');
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #00ff00; color: #1a1a1a; padding: 15px; border-radius: 8px; font-weight: bold; z-index: 1000; box-shadow: 0 5px 15px rgba(0,255,0,0.3);';
            notification.innerHTML = '<i class="fas fa-check"></i> New code ready! <?php echo $phpmailer_available ? "Check your email." : "Code updated above."; ?>';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 4000);
        }, 1000);
        <?php endif; ?>

        // Debug logging
        console.log('<?php echo $phpmailer_available ? "✅ PHPMailer active" : "⚠️ Development mode"; ?>');
        
        // Auto-highlight code for easy copying (development mode)
        <?php if (!$phpmailer_available): ?>
        setTimeout(function() {
            const input = document.getElementById('codeInput');
            input.select();
            input.setSelectionRange(0, 99999); // For mobile devices
        }, 500);
        <?php endif; ?>
    </script>
</body>
</html>