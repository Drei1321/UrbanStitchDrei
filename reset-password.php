<?php
session_start();

include 'config.php';

// Check if user is verified for password reset
if (!isset($_SESSION['verified_reset_user_id']) || !isset($_SESSION['verified_reset_email'])) {
    // If no verified session, redirect to forgot password page
    header('Location: forgot-password.php');
    exit;
}

$user_id = $_SESSION['verified_reset_user_id'];
$email = $_SESSION['verified_reset_email'];

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND email = ?");
$stmt->execute([$user_id, $email]);
$user = $stmt->fetch();

if (!$user) {
    // Clear invalid session and redirect
    unset($_SESSION['verified_reset_user_id']);
    unset($_SESSION['verified_reset_email']);
    header('Location: forgot-password.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (password_verify($new_password, $user['password'])) {
        $error = 'New password cannot be the same as your current password. Please choose a different password.';
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and clear reset tokens
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $user_id])) {
            // Try to clear reset tokens (different column names possible)
            try {
                $stmt = $pdo->prepare("UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
                $stmt->execute([$user_id]);
            } catch(PDOException $e) {
                // Try alternative column name
                try {
                    $stmt = $pdo->prepare("UPDATE users SET email_verification_token = NULL WHERE id = ?");
                    $stmt->execute([$user_id]);
                } catch(PDOException $e2) {
                    // Ignore if columns don't exist
                }
            }
            
            // Clear session variables
            unset($_SESSION['verified_reset_user_id']);
            unset($_SESSION['verified_reset_email']);
            
            $success = 'Password reset successful! You can now log in with your new password.';
            
            // Auto-redirect to login page
            echo "<script>
                setTimeout(function(){ 
                    window.location.href = 'login.php?reset=success'; 
                }, 3000);
            </script>";
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - UrbanStitch</title>
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

        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .reset-header {
            background: linear-gradient(135deg, #10b981, #059669);
            padding: 40px 30px;
            color: white;
        }

        .reset-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .reset-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .reset-subtitle {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.5;
        }

        .reset-form {
            padding: 40px 30px;
        }

        .user-info {
            background: #f0fdf4;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #a7f3d0;
        }

        .user-info h3 {
            color: #065f46;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .user-email {
            color: #10b981;
            font-weight: bold;
            font-size: 16px;
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
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
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
            cursor: pointer;
            transition: color 0.3s;
        }

        .input-icon i:hover {
            color: #10b981;
        }

        .input-icon .form-input {
            padding-right: 55px;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .password-strength.weak { 
            color: #ef4444; 
        }
        
        .password-strength.medium { 
            color: #f59e0b; 
        }
        
        .password-strength.strong { 
            color: #10b981; 
        }

        .match-indicator {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
        }

        .match-indicator.match { 
            color: #10b981; 
        }
        
        .match-indicator.no-match { 
            color: #ef4444; 
        }

        .reset-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #10b981, #059669);
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
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
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
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .form-footer a:hover {
            color: #059669;
        }

        .security-tips {
            background: #fef3c7;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: left;
            border-left: 4px solid #f59e0b;
        }

        .security-tips h4 {
            color: #92400e;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .security-tips ul {
            color: #a16207;
            padding-left: 20px;
            font-size: 14px;
            line-height: 1.6;
        }

        .security-tips li {
            margin-bottom: 6px;
        }

        .redirect-notice {
            background: #e0f2fe;
            border: 2px solid #0ea5e9;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
            font-weight: 500;
        }

        .redirect-notice h3 {
            color: #0c4a6e;
            margin-bottom: 10px;
        }

        .old-password-warning {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #fecaca;
            display: none;
        }

        .old-password-warning.show {
            display: block;
        }

        @media (max-width: 480px) {
            .reset-container {
                max-width: 400px;
            }

            .reset-form {
                padding: 30px 20px;
            }

            .reset-header {
                padding: 30px 20px;
            }

            .reset-icon {
                font-size: 48px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="reset-icon">
                <i class="fas fa-key"></i>
            </div>
            <h1 class="reset-title">Reset Your Password</h1>
            <p class="reset-subtitle">Create a new secure password for your account</p>
        </div>

        <div class="reset-form">
            <div class="user-info">
                <h3><i class="fas fa-user-check"></i> Resetting password for:</h3>
                <div class="user-email"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                <div style="color: #6b7280; font-size: 14px; margin-top: 5px;"><?php echo htmlspecialchars($email); ?></div>
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
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>

            <div class="redirect-notice">
                <h3>🎉 Success!</h3>
                <p>You'll be redirected to the login page in a few seconds...</p>
            </div>
            <?php else: ?>

            <div class="security-tips">
                <h4><i class="fas fa-shield-alt"></i> Password Requirements:</h4>
                <ul>
                    <li>At least 6 characters long</li>
                    <li>Must be different from your current password</li>
                    <li>Use a mix of letters and numbers</li>
                    <li>Make it unique and secure</li>
                </ul>
            </div>

            <form method="POST" id="resetForm">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div class="input-icon">
                        <input type="password" name="new_password" class="form-input" required
                               placeholder="Enter your new password" id="newPassword" minlength="6">
                        <i class="fas fa-eye" id="toggleNewPassword"></i>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                    <div class="old-password-warning" id="oldPasswordWarning">
                        <i class="fas fa-exclamation-triangle"></i> This is your current password. Please choose a different password for security.
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <div class="input-icon">
                        <input type="password" name="confirm_password" class="form-input" required
                               placeholder="Confirm your new password" id="confirmPassword">
                        <span class="match-indicator" id="matchIndicator"></span>
                    </div>
                </div>

                <button type="submit" class="reset-button" id="submitButton" disabled>
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>

            <?php endif; ?>
        </div>

        <div class="form-footer">
            <p>Remember your password? <a href="login.php">Sign in here</a></p>
            <br>
            <a href="index.php" style="color: #666;">
                <i class="fas fa-home"></i> Back to Store
            </a>
        </div>
    </div>

    <script>
        // Password visibility toggle
        document.getElementById('toggleNewPassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('newPassword');
            const icon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            const strengthElement = document.getElementById('passwordStrength');
            
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            if (password.length === 0) {
                strengthElement.textContent = '';
                strengthElement.className = 'password-strength';
            } else if (strength <= 2) {
                strengthElement.textContent = 'Weak password';
                strengthElement.className = 'password-strength weak';
            } else if (strength <= 4) {
                strengthElement.textContent = 'Medium strength';
                strengthElement.className = 'password-strength medium';
            } else {
                strengthElement.textContent = 'Strong password';
                strengthElement.className = 'password-strength strong';
            }
            
            return password.length >= 6;
        }

        // Check if password is same as old password via AJAX
        function checkIfOldPassword(password) {
            if (password.length < 6) return Promise.resolve(false);
            
            return fetch('check-old-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'password=' + encodeURIComponent(password)
            })
            .then(response => response.json())
            .then(data => data.is_old_password)
            .catch(() => false);
        }

        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('newPassword')?.value || '';
            const confirmPassword = document.getElementById('confirmPassword')?.value || '';
            const matchIndicator = document.getElementById('matchIndicator');
            const submitButton = document.getElementById('submitButton');
            
            if (!matchIndicator || !submitButton) return;
            
            if (confirmPassword.length === 0) {
                matchIndicator.textContent = '';
                matchIndicator.className = 'match-indicator';
            } else if (password === confirmPassword) {
                matchIndicator.innerHTML = '<i class="fas fa-check"></i>';
                matchIndicator.className = 'match-indicator match';
            } else {
                matchIndicator.innerHTML = '<i class="fas fa-times"></i>';
                matchIndicator.className = 'match-indicator no-match';
            }
            
            // Enable/disable submit button
            const passwordValid = checkPasswordStrength(password);
            const passwordsMatch = password === confirmPassword && confirmPassword.length > 0;
            submitButton.disabled = !(passwordValid && passwordsMatch);
        }

        // Event listeners
        document.getElementById('newPassword')?.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });

        document.getElementById('confirmPassword')?.addEventListener('input', checkPasswordMatch);

        // Auto-focus on new password input
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('newPassword');
            if (newPasswordInput) {
                newPasswordInput.focus();
            }
        });

        // Form submission with loading state
        document.getElementById('resetForm')?.addEventListener('submit', function() {
            const submitButton = document.getElementById('submitButton');
            if (submitButton && !submitButton.disabled) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting Password...';
                submitButton.disabled = true;
            }
        });

        // Success redirect countdown
        <?php if ($success): ?>
        let countdown = 3;
        const redirectMessage = document.createElement('div');
        redirectMessage.style.cssText = 'margin-top: 15px; padding: 15px; background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; color: #0c4a6e; text-align: center; font-weight: bold;';
        redirectMessage.innerHTML = `<i class="fas fa-clock"></i> Redirecting to login page in <span id="countdownTimer">${countdown}</span> seconds...`;
        
        const redirectNotice = document.querySelector('.redirect-notice');
        if (redirectNotice) {
            redirectNotice.appendChild(redirectMessage);
        }

        const timer = setInterval(() => {
            countdown--;
            const timerElement = document.getElementById('countdownTimer');
            if (timerElement) {
                timerElement.textContent = countdown;
            }
            
            if (countdown <= 0) {
                clearInterval(timer);
                redirectMessage.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecting now...';
                window.location.href = 'login.php?reset=success';
            }
        }, 1000);
        <?php endif; ?>

        console.log('🔒 Password reset page loaded successfully');
    </script>
</body>
</html>