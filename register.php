<?php
session_start();

// Use existing PHPMailer installation - Simple approach
// Try multiple paths for PHPMailer
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

// reCAPTCHA Configuration
// Your actual reCAPTCHA keys (now that domains are properly configured)
$recaptcha_site_key = '6LeryYcrAAAAAHtuRs2P_dbkUdBChomEm413FLBc';
$recaptcha_secret_key = '6LeryYcrAAAAACta2qkkvyCXAhaSrmbyBaJEzo_6';

// For testing only - Google's test keys
// $recaptcha_site_key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
// $recaptcha_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

// Function to verify reCAPTCHA
function verifyRecaptcha($response) {
    global $recaptcha_secret_key;
    
    if (empty($response)) {
        error_log("reCAPTCHA: No response provided");
        return false;
    }
    
    $data = array(
        'secret' => $recaptcha_secret_key,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    );
    
    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );
    
    $context = stream_context_create($options);
    $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    
    if ($result === FALSE) {
        error_log("reCAPTCHA: Failed to contact Google API");
        return false;
    }
    
    $json = json_decode($result, true);
    error_log("reCAPTCHA Response: " . print_r($json, true));
    
    return $json['success'] === true;
}

// Function to generate verification code
function generateVerificationCode() {
    return sprintf('%06d', mt_rand(100000, 999999));
}

// Function to send verification email using PHPMailer (without namespace)
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
        $mail->Subject = '🎯 Verify Your UrbanStitch Account - Code: ' . $verification_code;
        $mail->Body = getVerificationEmailTemplate($to_name, $verification_code, $to_email);
        $mail->AltBody = "Welcome to UrbanStitch!\n\nYour verification code is: $verification_code\n\nPlease enter this code to complete your registration.\n\nThis code will expire in 15 minutes.";
        
        $mail->send();
        return 'sent';
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: " . $e->getMessage());
        // Store code in session as fallback
        $_SESSION['dev_verification_code'] = $verification_code;
        return 'failed';
    }
}

// Function to get verification email template
function getVerificationEmailTemplate($name, $code, $email) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Verify Your Account - UrbanStitch</title>
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
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            }
            .header-text { 
                color: #ffffff; 
                font-size: 18px; 
                margin-bottom: 0;
            }
            .content { 
                padding: 40px 30px; 
            }
            .welcome { 
                font-size: 28px; 
                color: #1a1a1a; 
                margin-bottom: 20px; 
                text-align: center;
            }
            .message { 
                font-size: 16px; 
                color: #555; 
                line-height: 1.8; 
                margin-bottom: 30px; 
                text-align: center;
            }
            .code-container { 
                background: linear-gradient(135deg, #00ff00, #00cc00); 
                padding: 30px; 
                border-radius: 15px; 
                text-align: center; 
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
                text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            }
            .code-label { 
                color: #1a1a1a; 
                font-size: 16px; 
                font-weight: bold; 
                margin-bottom: 10px;
            }
            .instructions { 
                background-color: #f8f9fa; 
                padding: 25px; 
                border-radius: 10px; 
                margin: 25px 0; 
                border-left: 4px solid #00ff00;
            }
            .instructions h3 { 
                color: #1a1a1a; 
                margin-bottom: 15px; 
                font-size: 18px;
            }
            .instructions ol { 
                color: #555; 
                padding-left: 20px; 
                margin: 0;
            }
            .instructions li { 
                margin-bottom: 8px; 
                font-size: 15px;
            }
            .security-note {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                text-align: center;
            }
            .security-note strong {
                color: #533002;
            }
            .footer { 
                background-color: #1a1a1a; 
                padding: 30px; 
                text-align: center; 
            }
            .footer-text { 
                color: #999; 
                font-size: 14px; 
                line-height: 1.6; 
                margin-bottom: 20px;
            }
            .social-links { 
                margin: 20px 0; 
            }
            .social-links a { 
                color: #00ff00; 
                text-decoration: none; 
                margin: 0 15px; 
                font-size: 16px; 
                display: inline-block;
            }
            .disclaimer { 
                color: #666; 
                font-size: 12px; 
                margin-top: 20px; 
                line-height: 1.4; 
                border-top: 1px solid #333;
                padding-top: 20px;
            }
            @media (max-width: 600px) {
                .content { padding: 20px; }
                .code { font-size: 32px; letter-spacing: 6px; }
                .header { padding: 30px 15px; }
                .logo { font-size: 36px; }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'>UrbanStitch</div>
                <p class='header-text'>🎯 Street Fashion Revolution</p>
            </div>
            
            <div class='content'>
                <h2 class='welcome'>Welcome, " . htmlspecialchars($name) . "! 🎉</h2>
                
                <p class='message'>
                    Thank you for joining the UrbanStitch community! You're just one step away from accessing our exclusive streetwear collection and member-only benefits.
                </p>
                
                <div class='code-container'>
                    <div class='code-label'>🔐 YOUR VERIFICATION CODE</div>
                    <div class='code'>" . $code . "</div>
                </div>
                
                <div class='instructions'>
                    <h3>📋 How to verify your account:</h3>
                    <ol>
                        <li>Go back to the UrbanStitch verification page</li>
                        <li>Enter the 6-digit code above in the verification field</li>
                        <li>Click 'Verify Account' to complete your registration</li>
                        <li>Start shopping our exclusive urban fashion collection!</li>
                    </ol>
                </div>
                
                <div class='security-note'>
                    <strong>🔒 Security Notice:</strong><br>
                    This verification code will expire in <strong>15 minutes</strong> for your security. 
                    If you didn't request this, please ignore this email.
                </div>
                
                <p class='message'>
                    <strong>Why verify your email?</strong><br>
                    Email verification helps us keep your account secure and ensures you receive important updates about your orders, exclusive deals, and new arrivals.
                </p>
            </div>
            
            <div class='footer'>
                <div class='social-links'>
                    <a href='#'>📘 Facebook</a>
                    <a href='#'>📷 Instagram</a>
                    <a href='#'>🐦 Twitter</a>
                    <a href='#'>🛍️ Shop Now</a>
                </div>
                
                <div class='footer-text'>
                    <strong>UrbanStitch</strong><br>
                    Your ultimate destination for street fashion<br>
                    📧 support@urbanstitch.com | 📞 1-800-URBAN-ST<br>
                    🌐 www.urbanstitch.com
                </div>
                
                <div class='disclaimer'>
                    This email was sent to " . htmlspecialchars($email) . " because you requested to create an account at UrbanStitch. 
                    If you didn't request this, please ignore this email and your information will be automatically removed. 
                    This verification code will expire automatically after 15 minutes.
                </div>
            </div>
        </div>
    </body>
    </html>";
}

$error = '';
$success = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    // Validate reCAPTCHA first
    if (!verifyRecaptcha($recaptcha_response)) {
        $error = 'Please complete the reCAPTCHA verification.';
    } elseif (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            // Generate verification code
            $verification_code = generateVerificationCode();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user with verification token
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, email_verified, email_verification_token, is_admin) VALUES (?, ?, ?, ?, ?, 0, ?, 0)");
            
            if ($stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $verification_code])) {
                // Send verification email
                $email_result = sendVerificationEmail($email, $first_name, $verification_code);
                
                $_SESSION['registration_email'] = $email;
                $_SESSION['registration_name'] = $first_name;
                
                if ($email_result === 'sent') {
                    $success = "🎉 Registration successful! We've sent a verification code to your email address. Please check your inbox (and spam folder) to complete registration.";
                } elseif ($email_result === 'failed') {
                    $success = "Registration successful but email sending failed. Your verification code is: <strong style='background: yellow; padding: 2px 5px; font-family: monospace;'>$verification_code</strong>";
                } else {
                    $success = "Registration successful! Your verification code is: <strong style='background: yellow; padding: 2px 5px; font-family: monospace;'>$verification_code</strong>";
                }
                
                // Redirect to verification page after 3 seconds
                echo "<script>setTimeout(function(){ window.location.href = 'verify.php?email=" . urlencode($email) . "'; }, 3000);</script>";
            } else {
                $error = 'Registration failed. Please try again.';
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
    <title>Sign Up - UrbanStitch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
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

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 700px;
        }

        .register-brand {
            background: linear-gradient(135deg, #00ff00, #00cc00);
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #1a1a1a;
        }

        .brand-logo {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 20px;
        }

        .brand-tagline {
            font-size: 20px;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .brand-features {
            list-style: none;
            text-align: left;
        }

        .brand-features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .register-form {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .form-subtitle {
            color: #666;
            font-size: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
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
            padding: 12px 16px;
            border: 2px solid #e5e5e5;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #00ff00;
            box-shadow: 0 0 0 4px rgba(0, 255, 0, 0.1);
            background: white;
        }

        .recaptcha-container {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
            padding: 10px;
        }

        .register-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ccc, #999);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: not-allowed;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .register-button.enabled {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            cursor: pointer;
        }

        .register-button.enabled:hover {
            background: linear-gradient(135deg, #00ff00, #00cc00);
            color: #1a1a1a;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 255, 0, 0.3);
        }

        .error-message, .success-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 14px;
        }

        .error-message {
            background: #ff4444;
            color: white;
        }

        .success-message {
            background: #00ff00;
            color: #1a1a1a;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .form-footer a {
            color: #00ff00;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .form-footer a:hover {
            color: #00cc00;
        }

        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 968px) {
            .register-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }

            .register-form {
                padding: 30px;
            }

            .register-brand {
                padding: 30px;
            }

            .brand-logo {
                font-size: 36px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        @media (max-width: 480px) {
            .recaptcha-container {
                transform: scale(0.8);
                transform-origin: center;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-brand">
            <div class="brand-logo">Urban<span style="color: #1a1a1a;">Stitch</span></div>
            <p class="brand-tagline">Join the Urban Revolution</p>
            <ul class="brand-features">
                <li><i class="fas fa-user-plus"></i> Create your account</li>
                <li><i class="fas fa-shopping-bag"></i> Shop exclusive items</li>
                <li><i class="fas fa-heart"></i> Build your wishlist</li>
                <li><i class="fas fa-truck"></i> Track your orders</li>
                <li><i class="fas fa-star"></i> Member-only deals</li>
                <li><i class="fas fa-shield-alt"></i> Secure & protected</li>
            </ul>
        </div>

        <div class="register-form">
            <div class="form-header">
                <h1 class="form-title">Create Account</h1>
                <p class="form-subtitle">Join UrbanStitch and start your style journey</p>
            </div>

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

            <form method="POST" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-input" required
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                               placeholder="Enter your first name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-input" required
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                               placeholder="Enter your last name">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" required
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           placeholder="Choose a username">
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="Enter your email address">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" required
                           placeholder="Create a password">
                    <div class="password-requirements">
                        Must be at least 6 characters long
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-input" required
                           placeholder="Confirm your password">
                </div>

                <!-- reCAPTCHA -->
                <div class="recaptcha-container">
                    <div class="g-recaptcha" 
                         data-sitekey="<?php echo $recaptcha_site_key; ?>" 
                         data-callback="enableSubmitButton"
                         data-expired-callback="disableSubmitButton"
                         data-theme="light">
                    </div>
                </div>

                <button type="submit" class="register-button" id="submitBtn" disabled>
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="form-footer">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
                <br>
                <a href="index.php" style="color: #666;">
                    <i class="fas fa-arrow-left"></i> Back to Store
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus on first input
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="first_name"]').focus();
        });

        // Password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.querySelector('input[name="password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            
            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            password.addEventListener('input', validatePassword);
            confirmPassword.addEventListener('input', validatePassword);
        });

        // reCAPTCHA callback functions
        function enableSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = false;
            submitBtn.classList.add('enabled');
        }

        function disableSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.classList.remove('enabled');
        }

        // Form submission validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn.disabled) {
                e.preventDefault();
                alert('Please complete the reCAPTCHA verification before submitting.');
                return false;
            }
        });

        // reCAPTCHA onload callback
        var onloadCallback = function() {
            grecaptcha.render(document.querySelector('.g-recaptcha'), {
                'sitekey': '<?php echo $recaptcha_site_key; ?>',
                'callback': enableSubmitButton,
                'expired-callback': disableSubmitButton,
                'theme': 'light'
            });
        };
    </script>
</body>
</html>