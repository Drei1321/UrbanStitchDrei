<?php
// Enhanced UrbanStitch E-commerce - User Profile Page with Picture Upload
require_once 'config.php';
require_once 'xml_operations.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload_avatar') {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/avatars/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            $fileType = $_FILES['avatar']['type'];
            $fileSize = $_FILES['avatar']['size'];
            $tempName = $_FILES['avatar']['tmp_name'];
            
            // Validation
            if (!in_array($fileType, $allowedTypes)) {
                $message = 'Please upload a valid image file (JPEG, PNG, GIF, or WebP)';
                $messageType = 'error';
            } elseif ($fileSize > $maxSize) {
                $message = 'File size must be less than 5MB';
                $messageType = 'error';
            } else {
                // Generate unique filename
                $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $filename;
                
                // Verify it's actually an image
                $imageInfo = getimagesize($tempName);
                if ($imageInfo === false) {
                    $message = 'Invalid image file';
                    $messageType = 'error';
                } else {
                    // Move uploaded file
                    if (move_uploaded_file($tempName, $targetPath)) {
                        // Remove old avatar if exists
                        try {
                            $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
                            $stmt->execute([$userId]);
                            $oldAvatar = $stmt->fetchColumn();
                            
                            if ($oldAvatar && file_exists($oldAvatar)) {
                                unlink($oldAvatar);
                            }
                        } catch (Exception $e) {
                            // Continue even if old file deletion fails
                        }
                        
                        // Update database
                        try {
                            $stmt = $pdo->prepare("UPDATE users SET avatar_url = ?, updated_at = NOW() WHERE id = ?");
                            if ($stmt->execute([$targetPath, $userId])) {
                                $message = 'Profile picture updated successfully!';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to update profile picture in database';
                                $messageType = 'error';
                            }
                        } catch (Exception $e) {
                            error_log("Avatar update error: " . $e->getMessage());
                            $message = 'Database error occurred';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Failed to upload file';
                        $messageType = 'error';
                    }
                }
            }
        } else {
            $message = 'Please select a file to upload';
            $messageType = 'error';
        }
    } elseif ($action === 'remove_avatar') {
        try {
            $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $oldAvatar = $stmt->fetchColumn();
            
            // Remove file
            if ($oldAvatar && file_exists($oldAvatar)) {
                unlink($oldAvatar);
            }
            
            // Update database
            $stmt = $pdo->prepare("UPDATE users SET avatar_url = NULL, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $message = 'Profile picture removed successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to remove profile picture';
                $messageType = 'error';
            }
        } catch (Exception $e) {
            error_log("Avatar removal error: " . $e->getMessage());
            $message = 'Error removing profile picture';
            $messageType = 'error';
        }
    } elseif ($action === 'update_profile') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($firstName)) {
            $errors[] = 'First name is required';
        }
        
        if (empty($lastName)) {
            $errors[] = 'Last name is required';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        } else {
            // Check if email exists for other users
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'Email address is already in use';
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, 
                        address = ?, city = ?, postal_code = ?, country = ?, 
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$firstName, $lastName, $email, $phone, $address, $city, $postalCode, $country, $userId])) {
                    // Update session email if changed
                    if ($_SESSION['email'] !== $email) {
                        $_SESSION['email'] = $email;
                    }
                    
                    $message = 'Profile updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update profile. Please try again.';
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                $message = 'An error occurred while updating your profile.';
                $messageType = 'error';
            }
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'error';
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        $errors = [];
        
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters long';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }
        
        if (empty($errors)) {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($currentPassword, $user['password'])) {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    
                    if ($stmt->execute([$hashedPassword, $userId])) {
                        $message = 'Password changed successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to change password. Please try again.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Current password is incorrect';
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                error_log("Password change error: " . $e->getMessage());
                $message = 'An error occurred while changing your password.';
                $messageType = 'error';
            }
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'error';
        }
    }
}

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: logout.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error loading user data: " . $e->getMessage());
    $message = 'Error loading profile data';
    $messageType = 'error';
    $user = [];
}

// Get user statistics
$userStats = [
    'total_orders' => 0,
    'completed_orders' => 0,
    'total_spent' => 0,
    'wishlist_items' => 0,
    'cart_items' => 0
];

try {
    // Orders statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount END), 0) as total_spent
        FROM orders 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $orderStats = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($orderStats) {
        $userStats = array_merge($userStats, $orderStats);
    }
    
    // Wishlist count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist_items WHERE user_id = ?");
    $stmt->execute([$userId]);
    $wishlistCount = $stmt->fetch(PDO::FETCH_ASSOC);
    $userStats['wishlist_items'] = $wishlistCount['count'] ?? 0;
    
    // Cart count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cartCount = $stmt->fetch(PDO::FETCH_ASSOC);
    $userStats['cart_items'] = $cartCount['count'] ?? 0;
    
} catch (Exception $e) {
    error_log("Error loading user statistics: " . $e->getMessage());
}

// Calculate cart and wishlist counts for header
$cartCount = 0;
$wishlistCount = 0;

try {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total_count FROM cart_items WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cartCount = (int)($result['total_count'] ?? 0);
    
    $wishlistItems = $db->getWishlistItems($userId);
    $wishlistCount = count($wishlistItems);
} catch (Exception $e) {
    error_log("Error loading header counts: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - UrbanStitch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            color: white;
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0,255,0,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .profile-info {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .profile-avatar-section {
            position: relative;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #1a1a1a;
            font-weight: 900;
            border: 4px solid #00ff00;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            overflow: hidden;
        }
        
        .avatar-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
        }
        
        .profile-avatar:hover .avatar-upload-overlay {
            opacity: 1;
        }
        
        .avatar-controls {
            position: absolute;
            bottom: -10px;
            right: -10px;
            display: flex;
            gap: 8px;
        }
        
        .avatar-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .avatar-btn.upload {
            background: #00ff00;
            color: #1a1a1a;
        }
        
        .avatar-btn.remove {
            background: #ff4444;
            color: white;
        }
        
        .avatar-btn:hover {
            transform: scale(1.1);
        }
        
        .profile-details {
            flex: 1;
        }
        
        .profile-name {
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 8px;
        }
        
        .profile-email {
            font-size: 16px;
            opacity: 0.8;
            margin-bottom: 16px;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 900;
            color: #00ff00;
            display: block;
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .profile-content {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 32px;
        }
        
        .profile-sidebar {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li {
            margin-bottom: 8px;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #666;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: #f0fff0;
            color: #00cc00;
            transform: translateX(4px);
        }
        
        .profile-main {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .section {
            display: none;
        }
        
        .section.active {
            display: block;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 900;
            color: #1a1a1a;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s;
            background: #fafafa;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #00ff00;
            background: white;
            box-shadow: 0 0 0 3px rgba(0,255,0,0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .avatar-upload-section {
            background: #f8f9fa;
            border: 2px dashed #e0e0e0;
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            margin-bottom: 24px;
            transition: all 0.3s;
        }
        
        .avatar-upload-section:hover {
            border-color: #00ff00;
            background: #f0fff0;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #00cc00;
            margin-bottom: 16px;
        }
        
        .upload-text {
            color: #666;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        
        .file-input {
            display: none;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00ff00, #00cc00);
            color: #1a1a1a;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #00cc00, #00aa00);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,255,0,0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: #ff4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #cc3333;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            display: none;
        }
        
        .password-strength-bar {
            height: 100%;
            background: #ff4444;
            width: 0%;
            transition: all 0.3s;
        }
        
        .password-strength.weak .password-strength-bar {
            width: 33%;
            background: #ff4444;
        }
        
        .password-strength.medium .password-strength-bar {
            width: 66%;
            background: #ff6b35;
        }
        
        .password-strength.strong .password-strength-bar {
            width: 100%;
            background: #00ff00;
        }
        
        @media (max-width: 768px) {
            .profile-content {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .profile-sidebar {
                order: 2;
            }
            
            .sidebar-nav {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 8px;
            }
            
            .sidebar-nav a {
                text-align: center;
                padding: 8px 12px;
                font-size: 12px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .profile-info {
                flex-direction: column;
                text-align: center;
                gap: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="top-banner">
            <span class="animate-pulse-neon">FREE SHIPPING THIS WEEK ORDER OVER • ₱2,500</span>
            <div style="float: right; margin-right: 16px;">
                <select style="background: transparent; color: white; border: none; margin-right: 8px;">
                    <option>PHP ₱</option>
                    <option>EUR €</option>
                </select>
                <select style="background: transparent; color: white; border: none;">
                    <option>ENGLISH</option>
                    <option>SPANISH</option>
                </select>
            </div>
        </div>
        
        <div class="main-header">
            <a href="index.php" class="logo">Urban<span>Stitch</span></a>
            
            <div class="search-container">
                <form method="GET" action="index.php">
                    <input type="text" class="search-input" name="search" placeholder="Search street fashion...">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="user-actions">
                <div class="user-menu" style="position: relative; display: inline-block;">
                    <button class="action-btn user-menu-btn" style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user"></i>
                        <span style="font-size: 14px;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </button>
                    <div class="user-dropdown" id="userDropdown" style="position: absolute; top: 100%; right: 0; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px; padding: 8px; min-width: 150px; display: none; z-index: 1000;">
                        <a href="profile.php" style="display: block; padding: 8px 12px; color: #00cc00; text-decoration: none; border-radius: 4px; transition: background 0.2s; font-weight: 600;">
                            <i class="fas fa-user" style="margin-right: 8px;"></i>Profile
                        </a>
                        <a href="orders.php" style="display: block; padding: 8px 12px; color: #666; text-decoration: none; border-radius: 4px; transition: background 0.2s;">
                            <i class="fas fa-box" style="margin-right: 8px;"></i>Orders
                        </a>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <hr style="margin: 8px 0; border: none; border-top: 1px solid #eee;">
                        <a href="adminDashboard.php" style="display: block; padding: 8px 12px; color: #007bff; text-decoration: none; border-radius: 4px; transition: background 0.2s;">
                            <i class="fas fa-cog" style="margin-right: 8px;"></i>Admin Panel
                        </a>
                        <?php endif; ?>
                        <hr style="margin: 8px 0; border: none; border-top: 1px solid #eee;">
                        <a href="logout.php" style="display: block; padding: 8px 12px; color: #ff4444; text-decoration: none; border-radius: 4px; transition: background 0.2s;">
                            <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i>Logout
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
        
        <nav class="nav">
            <div class="nav-container">
                <ul class="nav-list">
                    <li><a href="index.php" class="nav-link">HOME</a></li>
                    <li><a href="categories.php" class="nav-link">CATEGORIES</a></li>
                    <li><a href="index.php?category=streetwear" class="nav-link">STREETWEAR</a></li>
                    <li><a href="index.php?category=footwear" class="nav-link">FOOTWEAR</a></li>
                    <li><a href="index.php?category=accessories" class="nav-link">ACCESSORIES</a></li>
                    <li><a href="index.php?category=winter-wear" class="nav-link">WINTER WEAR</a></li>
                    <li><a href="blog.php" class="nav-link">BLOG</a></li>
                
                </ul>
            </div>
        </nav>
    </header>

    <!-- Profile Content -->
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-info">
                <div class="profile-avatar-section">
                    <div class="profile-avatar" style="<?php 
                        if (!empty($user['avatar_url']) && file_exists($user['avatar_url'])) {
                            echo 'background-image: url(' . htmlspecialchars($user['avatar_url']) . '); background-color: #f0f0f0;';
                        } else {
                            echo 'background: #00ff00;';
                        }
                    ?>">
                        <?php if (empty($user['avatar_url']) || !file_exists($user['avatar_url'])): ?>
                            <?php echo strtoupper(substr($user['first_name'] ?? $user['username'], 0, 1)); ?>
                        <?php endif; ?>
                        
                        <div class="avatar-upload-overlay">
                            <i class="fas fa-camera" style="color: white; font-size: 24px;"></i>
                        </div>
                    </div>
                    
                    <div class="avatar-controls">
                        <button type="button" class="avatar-btn upload" onclick="document.getElementById('avatar-upload').click()" title="Upload Photo">
                            <i class="fas fa-camera"></i>
                        </button>
                        <?php if (!empty($user['avatar_url']) && file_exists($user['avatar_url'])): ?>
                        <button type="button" class="avatar-btn remove" onclick="removeAvatar()" title="Remove Photo">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="profile-name">
                        <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '') ?: $user['username']); ?>
                    </div>
                    <div class="profile-email">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $userStats['total_orders']; ?></span>
                            <span class="stat-label">Total Orders</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">₱<?php echo number_format($userStats['total_spent'], 2); ?></span>
                            <span class="stat-label">Total Spent</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $userStats['wishlist_items']; ?></span>
                            <span class="stat-label">Wishlist</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $userStats['cart_items']; ?></span>
                            <span class="stat-label">Cart Items</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-content">
            <!-- Sidebar Navigation -->
            <div class="profile-sidebar">
                <ul class="sidebar-nav">
                    <li>
                        <a href="#" onclick="showSection('personal-info')" class="nav-link active" data-section="personal-info">
                            <i class="fas fa-user"></i>
                            Personal Info
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="showSection('avatar-settings')" class="nav-link" data-section="avatar-settings">
                            <i class="fas fa-camera"></i>
                            Profile Picture
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="showSection('security')" class="nav-link" data-section="security">
                            <i class="fas fa-lock"></i>
                            Security
                        </a>
                    </li>
                    <li>
                        <a href="orders.php" class="nav-link">
                            <i class="fas fa-box"></i>
                            Order History
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" class="nav-link" style="color: #ff4444;">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="profile-main">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <!-- Personal Information Section -->
                <div id="personal-info" class="section active">
                    <h2 class="section-title">Personal Information</h2>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address" class="form-label">Address</label>
                            <textarea id="address" name="address" class="form-input form-textarea" 
                                      placeholder="Enter your full address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="city" class="form-label">City</label>
                                <input type="text" id="city" name="city" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" id="country" name="country" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Update Profile
                            </button>
                            <span style="color: #666; font-size: 14px;">
                                <i class="fas fa-info-circle"></i>
                                Fields marked with * are required
                            </span>
                        </div>
                    </form>
                </div>

                <!-- Avatar Settings Section -->
                <div id="avatar-settings" class="section">
                    <h2 class="section-title">Profile Picture</h2>
                    
                    <!-- Current Avatar Display -->
                    <div style="display: flex; align-items: center; gap: 24px; margin-bottom: 32px;">
                        <div class="profile-avatar" style="<?php 
                            if (!empty($user['avatar_url']) && file_exists($user['avatar_url'])) {
                                echo 'background-image: url(' . htmlspecialchars($user['avatar_url']) . '); background-color: #f0f0f0;';
                            } else {
                                echo 'background: #00ff00;';
                            }
                        ?>">
                            <?php if (empty($user['avatar_url']) || !file_exists($user['avatar_url'])): ?>
                                <?php echo strtoupper(substr($user['first_name'] ?? $user['username'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <h3 style="margin: 0 0 8px 0; color: #1a1a1a;">Current Profile Picture</h3>
                            <?php if (!empty($user['avatar_url']) && file_exists($user['avatar_url'])): ?>
                                <p style="margin: 0; color: #666; font-size: 14px;">
                                    <i class="fas fa-check-circle" style="color: #00cc00; margin-right: 6px;"></i>
                                    Custom photo uploaded
                                </p>
                            <?php else: ?>
                                <p style="margin: 0; color: #666; font-size: 14px;">
                                    <i class="fas fa-user-circle" style="color: #999; margin-right: 6px;"></i>
                                    Using default avatar
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Upload New Avatar -->
                    <div class="avatar-upload-section">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">
                            <strong>Upload a new profile picture</strong><br>
                            Choose a photo that represents you. It will be displayed on your profile and in comments.
                            <br><br>
                            <small style="color: #999;">
                                Supported formats: JPEG, PNG, GIF, WebP • Max size: 5MB
                            </small>
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data" id="avatar-form">
                            <input type="hidden" name="action" value="upload_avatar">
                            <input type="file" id="avatar-upload" name="avatar" class="file-input" 
                                   accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewAvatar(this)">
                            
                            <button type="button" onclick="document.getElementById('avatar-upload').click()" class="btn btn-primary">
                                <i class="fas fa-upload"></i>
                                Choose Photo
                            </button>
                        </form>
                        
                        <!-- Preview Section -->
                        <div id="avatar-preview" style="display: none; margin-top: 24px;">
                            <h4 style="margin: 0 0 16px 0; color: #1a1a1a;">Preview</h4>
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <img id="preview-image" src="" alt="Preview" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #00ff00;">
                                <div>
                                    <button type="button" onclick="submitAvatar()" class="btn btn-primary">
                                        <i class="fas fa-check"></i>
                                        Upload This Photo
                                    </button>
                                    <button type="button" onclick="cancelPreview()" class="btn btn-secondary" style="margin-left: 8px;">
                                        <i class="fas fa-times"></i>
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($user['avatar_url']) && file_exists($user['avatar_url'])): ?>
                    <!-- Remove Avatar Option -->
                    <div style="padding: 20px; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 8px;">
                        <h4 style="margin: 0 0 8px 0; color: #e53e3e;">Remove Profile Picture</h4>
                        <p style="margin: 0 0 16px 0; color: #666; font-size: 14px;">
                            This will remove your current profile picture and switch back to the default avatar.
                        </p>
                        <button type="button" onclick="removeAvatar()" class="btn btn-danger">
                            <i class="fas fa-trash"></i>
                            Remove Current Photo
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Security Section -->
                <div id="security" class="section">
                    <h2 class="section-title">Security Settings</h2>
                    
                    <div style="background: #f8f9fa; padding: 24px; border-radius: 8px; margin-bottom: 24px;">
                        <h3 style="margin: 0 0 12px 0; color: #333; font-size: 18px; font-weight: 600;">
                            <i class="fas fa-shield-alt" style="color: #00cc00; margin-right: 8px;"></i>
                            Account Security
                        </h3>
                        <p style="margin: 0; color: #666; line-height: 1.5;">
                            Keep your account secure by using a strong password and updating it regularly.
                            Last login: <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                        </p>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" class="form-input" required>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password *</label>
                                <input type="password" id="new_password" name="new_password" class="form-input" 
                                       minlength="6" required>
                                <div class="password-strength" id="passwordStrength">
                                    <div class="password-strength-bar"></div>
                                </div>
                                <small style="color: #666; font-size: 12px; margin-top: 4px; display: block;">
                                    Password must be at least 6 characters long
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                       minlength="6" required>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i>
                                Change Password
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearPasswordForm()">
                                <i class="fas fa-times"></i>
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>Urban<span>Stitch</span></h3>
                    <p class="footer-description">
                        Your ultimate destination for street fashion and urban culture. 
                        We bring you the latest trends and authentic streetwear from around the globe.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="blog.php">Blog</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Customer Service</h4>
                    <ul class="footer-links">
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="shipping.php">Shipping Info</a></li>
                        <li><a href="returns.php">Returns</a></li>
                        <li><a href="size-guide.php">Size Guide</a></li>
                        <li><a href="track-order.php">Track Order</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Newsletter</h4>
                    <p>Stay updated with the latest drops and exclusive offers.</p>
                    <div class="newsletter">
                        <input type="email" class="newsletter-input" placeholder="Enter your email">
                        <button class="newsletter-btn">Subscribe</button>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 UrbanStitch. All rights reserved. | Privacy Policy | Terms of Service</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        // Profile page functionality
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to corresponding nav link
            document.querySelector(`[data-section="${sectionId}"]`).classList.add('active');
        }

        // Avatar upload functionality
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload a valid image file (JPEG, PNG, GIF, or WebP)');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                    document.getElementById('avatar-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        function submitAvatar() {
            document.getElementById('avatar-form').submit();
        }

        function cancelPreview() {
            document.getElementById('avatar-upload').value = '';
            document.getElementById('avatar-preview').style.display = 'none';
        }

        function removeAvatar() {
            if (confirm('Are you sure you want to remove your profile picture?')) {
                // Create form to remove avatar
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'remove_avatar';
                
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Password strength checker
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthIndicator.style.display = 'none';
                return;
            }
            
            strengthIndicator.style.display = 'block';
            
            let score = 0;
            
            // Length check
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            
            // Character diversity
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            // Remove existing strength classes
            strengthIndicator.classList.remove('weak', 'medium', 'strong');
            
            if (score <= 2) {
                strengthIndicator.classList.add('weak');
            } else if (score <= 4) {
                strengthIndicator.classList.add('medium');
            } else {
                strengthIndicator.classList.add('strong');
            }
        });

        // Password confirmation check
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
                this.style.borderColor = '#ff4444';
            } else {
                this.setCustomValidity('');
                this.style.borderColor = '#e0e0e0';
            }
        });

        // Clear password form
        function clearPasswordForm() {
            document.getElementById('current_password').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            document.getElementById('passwordStrength').style.display = 'none';
        }

        // User menu toggle
        document.querySelector('.user-menu-btn').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        // Close user menu when clicking outside
        document.addEventListener('click', function(e) {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            
            if (userMenu && dropdown && !userMenu.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Form validation feedback
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('invalid', function() {
                this.style.borderColor = '#ff4444';
            });
            
            input.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.style.borderColor = '#e0e0e0';
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });

        // Drag and drop functionality for avatar upload
        const uploadSection = document.querySelector('.avatar-upload-section');
        
        uploadSection.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#00ff00';
            this.style.background = '#f0fff0';
        });
        
        uploadSection.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#e0e0e0';
            this.style.background = '#f8f9fa';
        });
        
        uploadSection.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#e0e0e0';
            this.style.background = '#f8f9fa';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = document.getElementById('avatar-upload');
                fileInput.files = files;
                previewAvatar(fileInput);
            }
        });
    </script>
</body>
</html>