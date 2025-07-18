<?php
// Enhanced UrbanStitch E-commerce - Help & Support Page
require_once 'config.php';
require_once 'xml_operations.php';

// Handle contact form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'contact_support') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $messageText = trim($_POST['message'] ?? '');
        $category = $_POST['category'] ?? 'general';
        
        // Validation
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Name is required';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        }
        
        if (empty($subject)) {
            $errors[] = 'Subject is required';
        }
        
        if (empty($messageText)) {
            $errors[] = 'Message is required';
        }
        
        if (empty($errors)) {
            try {
                // Store support ticket in database (you might want to create a support_tickets table)
                // For now, we'll just show a success message
                $message = 'Your message has been sent successfully! We\'ll get back to you within 24 hours.';
                $messageType = 'success';
                
                // Clear form data
                $name = $email = $subject = $messageText = '';
                $category = 'general';
                
            } catch (Exception $e) {
                error_log("Support form error: " . $e->getMessage());
                $message = 'There was an error sending your message. Please try again.';
                $messageType = 'error';
            }
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'error';
        }
    }
}

// Calculate cart and wishlist counts for header
$cartCount = 0;
$wishlistCount = 0;

if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total_count FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cartCount = (int)($result['total_count'] ?? 0);
        
        $wishlistItems = $db->getWishlistItems($_SESSION['user_id']);
        $wishlistCount = count($wishlistItems);
    } catch (Exception $e) {
        error_log("Error loading header counts: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - UrbanStitch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .help-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }
        
        .help-header {
            text-align: center;
            margin-bottom: 48px;
        }
        
        .help-title {
            font-size: 48px;
            font-weight: 900;
            color: #1a1a1a;
            margin-bottom: 16px;
        }
        
        .help-subtitle {
            font-size: 18px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .help-search {
            max-width: 600px;
            margin: 32px auto;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 16px 24px 16px 56px;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 16px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #00ff00;
            box-shadow: 0 4px 24px rgba(0,255,0,0.2);
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
        }
        
        .help-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }
        
        .faq-section {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 32px;
            font-weight: 900;
            color: #1a1a1a;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .faq-categories {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }
        
        .category-filter {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 14px;
        }
        
        .category-filter.active,
        .category-filter:hover {
            border-color: #00ff00;
            background: #f0fff0;
            color: #00cc00;
        }
        
        .faq-list {
            display: grid;
            gap: 16px;
        }
        
        .faq-item {
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .faq-item.active {
            border-color: #00ff00;
            box-shadow: 0 4px 16px rgba(0,255,0,0.1);
        }
        
        .faq-question {
            padding: 20px 24px;
            background: #fafafa;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #333;
            transition: all 0.2s;
        }
        
        .faq-question:hover {
            background: #f0fff0;
            color: #00cc00;
        }
        
        .faq-icon {
            font-size: 14px;
            transition: transform 0.3s;
        }
        
        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }
        
        .faq-answer {
            padding: 0 24px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s;
            background: white;
        }
        
        .faq-item.active .faq-answer {
            padding: 24px;
            max-height: 500px;
        }
        
        .faq-answer p {
            color: #666;
            line-height: 1.6;
            margin: 0;
        }
        
        .contact-section {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            color: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .contact-form {
            display: grid;
            gap: 20px;
        }
        
        .form-group {
            display: grid;
            gap: 8px;
        }
        
        .form-label {
            font-weight: 600;
            color: #00ff00;
            font-size: 14px;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            padding: 12px 16px;
            border: 2px solid #444;
            border-radius: 8px;
            background: #333;
            color: white;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #00ff00;
            background: #2a2a2a;
            box-shadow: 0 0 0 3px rgba(0,255,0,0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #00ff00, #00cc00);
            color: #1a1a1a;
            border: none;
            padding: 16px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #00cc00, #00aa00);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,255,0,0.3);
        }
        
        .contact-info {
            margin-top: 32px;
            padding-top: 32px;
            border-top: 1px solid #444;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            color: #ccc;
        }
        
        .contact-icon {
            width: 40px;
            height: 40px;
            background: #00ff00;
            color: #1a1a1a;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        
        .quick-help {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            margin-bottom: 48px;
        }
        
        .help-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        
        .help-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .help-card:hover {
            background: #f0fff0;
            border-color: #00ff00;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,255,0,0.2);
        }
        
        .help-card-icon {
            font-size: 32px;
            color: #00cc00;
            margin-bottom: 16px;
        }
        
        .help-card-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        .help-card-text {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
        
        /* Enhance print styles */
        @media print {
            .header, .footer, .contact-section, .help-search, .faq-categories, button {
                display: none !important;
            }
            
            .faq-item {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            
            .faq-answer {
                max-height: none !important;
                padding: 15px !important;
            }
        }
        
        /* Improve accessibility */
        .faq-question:focus {
            outline: 2px solid #00ff00 !important;
            outline-offset: 2px;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: 2px solid #00ff00 !important;
            outline-offset: 2px;
        }
        
        /* Add hover effects for better UX */
        .contact-item:hover {
            background: rgba(0, 255, 0, 0.1);
            border-radius: 8px;
            padding: 8px;
            margin: -8px;
            transition: all 0.2s;
        }
        
        @media (max-width: 768px) {
            .help-container {
                padding: 24px 16px;
            }
            
            .help-title {
                font-size: 32px;
            }
            
            .help-sections {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            
            .section-title {
                font-size: 24px;
            }
            
            .faq-section,
            .contact-section {
                padding: 24px;
            }
            
            .help-cards {
                grid-template-columns: 1fr;
            }
            
            .faq-categories {
                justify-content: center;
            }
        }
        
        /* Responsive improvements */
        @media (max-width: 480px) {
            .help-title {
                font-size: 24px !important;
            }
            
            .section-title {
                font-size: 20px !important;
            }
            
            .help-card {
                padding: 16px !important;
            }
            
            .faq-question {
                padding: 16px !important;
                font-size: 14px;
            }
            
            .faq-answer {
                padding: 16px !important;
            }
            
            .contact-section {
                padding: 20px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="top-banner">
            <span class="animate-pulse-neon">FREE SHIPPING THIS WEEK ORDER OVER • $50</span>
            <div style="float: right; margin-right: 16px;">
                <select style="background: transparent; color: white; border: none; margin-right: 8px;">
                    <option>USD $</option>
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
                <?php if (isLoggedIn()): ?>
                <div class="user-menu" style="position: relative; display: inline-block;">
                    <button class="action-btn user-menu-btn" style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user"></i>
                        <span style="font-size: 14px;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </button>
                    <div class="user-dropdown" id="userDropdown" style="position: absolute; top: 100%; right: 0; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px; padding: 8px; min-width: 150px; display: none; z-index: 1000;">
                        <a href="profile.php" style="display: block; padding: 8px 12px; color: #666; text-decoration: none; border-radius: 4px; transition: background 0.2s;">
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
                <?php else: ?>
                <a href="login.php" class="action-btn" style="text-decoration: none; color: inherit;">
                    <i class="fas fa-user"></i>
                </a>
                <?php endif; ?>
                
                <button class="action-btn wishlist-btn">
                    <i class="fas fa-heart"></i>
                    <span class="badge orange" id="wishlistCount"><?php echo $wishlistCount; ?></span>
                </button>
                <button class="action-btn cart-btn">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="badge" id="cartCount"><?php echo $cartCount; ?></span>
                </button>
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
                    <li><a href="offers.php" class="nav-link hot">HOT OFFERS</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Help Content -->
    <div class="help-container">
        <!-- Header -->
        <div class="help-header">
            <h1 class="help-title">Help & <span style="color: #00ff00;">Support</span></h1>
            <p class="help-subtitle">
                Need assistance? We're here to help! Browse our frequently asked questions or contact our support team directly.
            </p>
        </div>

        <!-- Search -->
        <div class="help-search">
            <div style="position: relative;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search for help topics..." id="helpSearch">
            </div>
        </div>

        <!-- Quick Help Cards -->
        <div class="quick-help">
            <h2 class="section-title">
                <i class="fas fa-bolt" style="color: #00ff00;"></i>
                Quick Help
            </h2>
            <div class="help-cards">
                <div class="help-card" onclick="filterFAQ('orders')">
                    <div class="help-card-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <h3 class="help-card-title">Order Help</h3>
                    <p class="help-card-text">Track orders, returns, refunds, and delivery information</p>
                </div>
                <div class="help-card" onclick="filterFAQ('account')">
                    <div class="help-card-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3 class="help-card-title">Account & Profile</h3>
                    <p class="help-card-text">Manage your account, password, and personal information</p>
                </div>
                <div class="help-card" onclick="filterFAQ('payment')">
                    <div class="help-card-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3 class="help-card-title">Payment & Billing</h3>
                    <p class="help-card-text">Payment methods, billing issues, and transaction help</p>
                </div>
                <div class="help-card" onclick="filterFAQ('products')">
                    <div class="help-card-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <h3 class="help-card-title">Products & Sizing</h3>
                    <p class="help-card-text">Size guides, product information, and recommendations</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="help-sections">
            <!-- FAQ Section -->
            <div class="faq-section">
                <h2 class="section-title">
                    <i class="fas fa-question-circle" style="color: #00ff00;"></i>
                    Frequently Asked Questions
                </h2>

                <!-- Category Filters -->
                <div class="faq-categories">
                    <button class="category-filter active" onclick="filterFAQ('all')">All</button>
                    <button class="category-filter" onclick="filterFAQ('orders')">Orders</button>
                    <button class="category-filter" onclick="filterFAQ('account')">Account</button>
                    <button class="category-filter" onclick="filterFAQ('payment')">Payment</button>
                    <button class="category-filter" onclick="filterFAQ('products')">Products</button>
                    <button class="category-filter" onclick="filterFAQ('shipping')">Shipping</button>
                </div>

                <!-- FAQ Items -->
                <div class="faq-list" id="faqList">
                    <!-- Orders FAQs -->
                    <div class="faq-item" data-category="orders">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>How can I track my order?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Once your order is shipped, you'll receive an email with tracking information. You can also check your order status by visiting the "My Orders" section in your account. If you're logged in, you can access this from the user menu in the top right corner.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="orders">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>Can I cancel or modify my order?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>You can cancel or modify your order within 1 hour of placing it. After this time, the order enters our fulfillment process and cannot be changed. Please contact our support team immediately if you need to make changes.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="orders">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>What is your return policy?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>We offer a 30-day return policy for unworn items in original condition with tags attached. Returns are free for defective items or our error. For other returns, a small processing fee may apply. Visit our Returns page for detailed instructions.</p>
                        </div>
                    </div>

                    <!-- Account FAQs -->
                    <div class="faq-item" data-category="account">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>How do I reset my password?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Click on "Forgot Password" on the login page and enter your email address. You'll receive an email with instructions to reset your password. If you don't receive the email within 10 minutes, check your spam folder or contact support.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="account">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>How do I update my profile information?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Log into your account and click on your username in the top right corner, then select "Profile". You can update your name, email, phone number, address, and other personal information from there.</p>
                        </div>
                    </div>

                    <!-- Payment FAQs -->
                    <div class="faq-item" data-category="payment">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>What payment methods do you accept?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>We accept all major credit cards (Visa, MasterCard, American Express), PayPal, Apple Pay, and Google Pay. All transactions are secured with SSL encryption for your protection.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="payment">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>Is it safe to shop on your website?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Absolutely! We use industry-standard SSL encryption to protect your personal and payment information. We never store your credit card details on our servers, and all payments are processed through secure, PCI-compliant payment processors.</p>
                        </div>
                    </div>

                    <!-- Products FAQs -->
                    <div class="faq-item" data-category="products">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>How do I choose the right size?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Each product page includes a detailed size guide. We recommend measuring yourself and comparing to our size charts. If you're between sizes, we generally recommend sizing up for a more comfortable fit. You can also contact our support team for personalized sizing advice.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="products">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>Are your products authentic?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes, all our products are 100% authentic. We work directly with authorized distributors and brands to ensure authenticity. Every item comes with proper tags and authentication when applicable.</p>
                        </div>
                    </div>

                    <!-- Shipping FAQs -->
                    <div class="faq-item" data-category="shipping">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>How long does shipping take?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Standard shipping takes 3-7 business days. Express shipping (2-3 business days) and overnight shipping are also available. Free shipping is offered on orders over $50. International shipping times vary by location.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="shipping">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>Do you ship internationally?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes, we ship to most countries worldwide. Shipping costs and delivery times vary by destination. International customers are responsible for any customs duties or taxes. Check our shipping page for specific country information.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="contact-section">
                <h2 class="section-title" style="color: white;">
                    <i class="fas fa-headset" style="color: #00ff00;"></i>
                    Contact Support
                </h2>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <form class="contact-form" method="POST" action="">
                    <input type="hidden" name="action" value="contact_support">
                    
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-input" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-input" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="general" <?php echo (isset($category) && $category === 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="order" <?php echo (isset($category) && $category === 'order') ? 'selected' : ''; ?>>Order Issue</option>
                            <option value="product" <?php echo (isset($category) && $category === 'product') ? 'selected' : ''; ?>>Product Question</option>
                            <option value="technical" <?php echo (isset($category) && $category === 'technical') ? 'selected' : ''; ?>>Technical Support</option>
                            <option value="billing" <?php echo (isset($category) && $category === 'billing') ? 'selected' : ''; ?>>Billing Issue</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Subject *</label>
                        <input type="text" class="form-input" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Message *</label>
                        <textarea class="form-textarea" name="message" placeholder="Please describe your issue or question in detail..." required><?php echo htmlspecialchars($messageText ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Send Message
                    </button>
                </form>

                <div class="contact-info">
                    <h3 style="color: #00ff00; margin-bottom: 16px; font-size: 18px;">Other Ways to Reach Us</h3>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <strong>Email</strong><br>
                            support@urbanstitch.com
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <strong>Phone</strong><br>
                            1-800-URBAN-ST<br>
                            <small>Mon-Fri 9AM-6PM EST</small>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div>
                            <strong>Live Chat</strong><br>
                            Available 24/7 on our website
                        </div>
                    </div>
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
                        <li><a href="help.php" style="color: #00cc00; font-weight: 600;">Help Center</a></li>
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
        // Help page functionality
        
        // User menu toggle
        document.querySelector('.user-menu-btn')?.addEventListener('click', function(e) {
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

        // FAQ toggle functionality
        function toggleFAQ(element) {
            const faqItem = element.closest('.faq-item');
            const isActive = faqItem.classList.contains('active');
            
            // Close all FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Open clicked item if it wasn't active
            if (!isActive) {
                faqItem.classList.add('active');
            }
        }

        // FAQ category filtering
        function filterFAQ(category) {
            const faqItems = document.querySelectorAll('.faq-item');
            const categoryButtons = document.querySelectorAll('.category-filter');
            
            // Update active category button
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            
            // Find and activate the clicked button
            categoryButtons.forEach(btn => {
                if (btn.textContent.toLowerCase() === category.toLowerCase() || 
                    (category === 'all' && btn.textContent.toLowerCase() === 'all')) {
                    btn.classList.add('active');
                }
            });
            
            // Show/hide FAQ items
            faqItems.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                    item.classList.remove('active'); // Close if hidden
                }
            });
            
            // Clear search if filtering
            const searchInput = document.getElementById('helpSearch');
            if (searchInput && category !== 'all') {
                searchInput.value = '';
            }
        }

        // Help search functionality
        document.getElementById('helpSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer p').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                    
                    // Highlight search term if found
                    if (searchTerm.length > 2) {
                        highlightSearchTerm(item, searchTerm);
                    }
                } else {
                    item.style.display = 'none';
                    item.classList.remove('active');
                }
            });
            
            // Reset category filter if searching
            if (searchTerm.length > 0) {
                document.querySelectorAll('.category-filter').forEach(btn => {
                    btn.classList.remove('active');
                });
                // Activate "All" button when searching
                document.querySelector('.category-filter').classList.add('active');
            }
        });

        // Highlight search terms
        function highlightSearchTerm(element, term) {
            const question = element.querySelector('.faq-question span');
            const answer = element.querySelector('.faq-answer p');
            
            [question, answer].forEach(el => {
                if (el) {
                    const text = el.textContent;
                    const regex = new RegExp(`(${term})`, 'gi');
                    const highlightedText = text.replace(regex, '<mark style="background: #00ff00; color: #1a1a1a;">$1</mark>');
                    
                    if (text !== highlightedText) {
                        el.innerHTML = highlightedText;
                    }
                }
            });
        }

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

        // Form validation
        document.querySelector('.contact-form')?.addEventListener('submit', function(e) {
            const name = this.querySelector('[name="name"]').value.trim();
            const email = this.querySelector('[name="email"]').value.trim();
            const subject = this.querySelector('[name="subject"]').value.trim();
            const message = this.querySelector('[name="message"]').value.trim();
            
            if (!name || !email || !subject || !message) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Keyboard navigation for FAQ
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                const focusedElement = document.activeElement;
                if (focusedElement.classList.contains('faq-question')) {
                    e.preventDefault();
                    toggleFAQ(focusedElement);
                }
            }
        });

        // Make FAQ questions focusable for accessibility
        document.querySelectorAll('.faq-question').forEach(question => {
            question.setAttribute('tabindex', '0');
            question.setAttribute('role', 'button');
            question.setAttribute('aria-expanded', 'false');
            
            question.addEventListener('focus', function() {
                this.style.outline = '2px solid #00ff00';
            });
            
            question.addEventListener('blur', function() {
                this.style.outline = 'none';
            });
        });

        // Update aria-expanded when FAQ is toggled
        const originalToggleFAQ = window.toggleFAQ;
        window.toggleFAQ = function(element) {
            originalToggleFAQ(element);
            const faqItem = element.closest('.faq-item');
            const isActive = faqItem.classList.contains('active');
            element.setAttribute('aria-expanded', isActive.toString());
        };

        // Add loading state to contact form
        document.querySelector('.contact-form')?.addEventListener('submit', function() {
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // Re-enable after 3 seconds in case of error
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });

        // Initialize help page
        document.addEventListener('DOMContentLoaded', function() {
            // Add some nice entrance animations
            const elements = document.querySelectorAll('.help-card, .faq-item');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    el.style.transition = 'all 0.5s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add click handlers for help cards
            document.querySelectorAll('.help-card').forEach(card => {
                card.addEventListener('click', function() {
                    const onclickAttr = this.getAttribute('onclick');
                    if (onclickAttr) {
                        const match = onclickAttr.match(/filterFAQ\('(.+)'\)/);
                        if (match) {
                            const category = match[1];
                            filterFAQ(category);
                            
                            // Scroll to FAQ section
                            document.querySelector('.faq-section').scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            });

            // Auto-expand first FAQ if no search or filter
            const firstFaq = document.querySelector('.faq-item');
            if (firstFaq && !window.location.search) {
                setTimeout(() => {
                    toggleFAQ(firstFaq.querySelector('.faq-question'));
                }, 500);
            }
        });

        // Live chat simulation (you can replace with real chat widget)
        function openLiveChat() {
            alert('Live chat feature coming soon! For now, please use the contact form or email us at support@urbanstitch.com');
        }

        // Add live chat button
        const liveChatBtn = document.createElement('div');
        liveChatBtn.innerHTML = `
            <button style="
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: linear-gradient(135deg, #00ff00, #00cc00);
                color: #1a1a1a;
                border: none;
                border-radius: 50px;
                padding: 16px 24px;
                font-weight: 700;
                cursor: pointer;
                box-shadow: 0 4px 16px rgba(0,255,0,0.3);
                z-index: 1000;
                display: flex;
                align-items: center;
                gap: 8px;
                transition: all 0.3s;
                font-size: 14px;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,255,0,0.4)'" 
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(0,255,0,0.3)'"
               onclick="openLiveChat()">
                <i class="fas fa-comments"></i>
                Live Chat
            </button>
        `;
        document.body.appendChild(liveChatBtn);

        // Add tooltips to form fields
        const formInputs = document.querySelectorAll('.form-input, .form-select, .form-textarea');
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                let tooltip = '';
                
                switch(this.name) {
                    case 'name':
                        tooltip = 'Enter your full name for better assistance';
                        break;
                    case 'email':
                        tooltip = 'We\'ll use this email to respond to your inquiry';
                        break;
                    case 'category':
                        tooltip = 'Select the category that best describes your inquiry';
                        break;
                    case 'subject':
                        tooltip = 'Brief description of your issue or question';
                        break;
                    case 'message':
                        tooltip = 'Please provide as much detail as possible to help us assist you better';
                        break;
                }
                
                if (tooltip) {
                    this.setAttribute('title', tooltip);
                }
            });
        });

        // Add character counter for message textarea
        const messageTextarea = document.querySelector('[name="message"]');
        if (messageTextarea) {
            const counter = document.createElement('div');
            counter.style.cssText = 'text-align: right; font-size: 12px; color: #999; margin-top: 4px;';
            counter.textContent = '0 characters';
            messageTextarea.parentNode.appendChild(counter);
            
            messageTextarea.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = `${length} characters`;
                
                if (length > 500) {
                    counter.style.color = '#00ff00';
                } else if (length > 250) {
                    counter.style.color = '#ff6b35';
                } else {
                    counter.style.color = '#999';
                }
            });
        }

        // Add copy-to-clipboard for contact information
        document.querySelectorAll('.contact-item').forEach(item => {
            const text = item.textContent;
            if (text.includes('support@urbanstitch.com') || text.includes('1-800-URBAN-ST')) {
                item.style.cursor = 'pointer';
                item.setAttribute('title', 'Click to copy');
                
                item.addEventListener('click', function() {
                    let textToCopy = '';
                    if (text.includes('support@urbanstitch.com')) {
                        textToCopy = 'support@urbanstitch.com';
                    } else if (text.includes('1-800-URBAN-ST')) {
                        textToCopy = '1-800-URBAN-ST';
                    }
                    
                    if (textToCopy && navigator.clipboard) {
                        navigator.clipboard.writeText(textToCopy).then(() => {
                            // Show temporary success message
                            const originalText = this.innerHTML;
                            this.style.background = '#00ff00';
                            this.style.color = '#1a1a1a';
                            this.style.borderRadius = '8px';
                            this.style.padding = '8px';
                            
                            setTimeout(() => {
                                this.style.background = '';
                                this.style.color = '';
                                this.style.borderRadius = '';
                                this.style.padding = '';
                            }, 1000);
                        });
                    }
                });
            }
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('helpSearch').focus();
            }
            
            // Escape to close any expanded FAQ
            if (e.key === 'Escape') {
                document.querySelectorAll('.faq-item.active').forEach(item => {
                    item.classList.remove('active');
                    const question = item.querySelector('.faq-question');
                    question.setAttribute('aria-expanded', 'false');
                });
            }
        });

        // Add print functionality
        function printFAQ() {
            const printWindow = window.open('', '_blank');
            const faqContent = document.querySelector('.faq-section').innerHTML;
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>UrbanStitch FAQ</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .faq-item { margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; }
                        .faq-question { background: #f5f5f5; padding: 15px; font-weight: bold; }
                        .faq-answer { padding: 15px; }
                        .section-title { color: #333; margin-bottom: 20px; }
                        .faq-categories { display: none; }
                    </style>
                </head>
                <body>
                    <h1>UrbanStitch - Frequently Asked Questions</h1>
                    ${faqContent}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.print();
        }

        // Add print button
        const printBtn = document.createElement('button');
        printBtn.innerHTML = '<i class="fas fa-print"></i> Print FAQ';
        printBtn.style.cssText = `
            position: absolute;
            top: 20px;
            right: 20px;
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        `;
        printBtn.onclick = printFAQ;
        document.querySelector('.faq-section').style.position = 'relative';
        document.querySelector('.faq-section').appendChild(printBtn);

        // Add analytics tracking (placeholder)
        function trackHelpAction(action, data = {}) {
            // Replace with your analytics tracking code
            console.log('Help Action:', action, data);
            
            // Example: Google Analytics
            // gtag('event', action, {
            //     event_category: 'Help',
            //     event_label: data.label || '',
            //     value: data.value || 0
            // });
        }

        // Track FAQ interactions
        const originalToggle = window.toggleFAQ;
        window.toggleFAQ = function(element) {
            originalToggle(element);
            const question = element.querySelector('span').textContent;
            trackHelpAction('faq_toggle', { label: question });
        };

        // Track search usage
        let searchTimeout;
        document.getElementById('helpSearch').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (e.target.value.length > 2) {
                    trackHelpAction('faq_search', { label: e.target.value });
                }
            }, 1000);
        });

        // Track form submission
        document.querySelector('.contact-form')?.addEventListener('submit', function() {
            const category = this.querySelector('[name="category"]').value;
            trackHelpAction('contact_form_submit', { label: category });
        });

        // Add success animation for form submission
        if (window.location.search.includes('success=1')) {
            // Show success animation
            const successDiv = document.createElement('div');
            successDiv.innerHTML = `
                <div style="
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: white;
                    padding: 40px;
                    border-radius: 16px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                    text-align: center;
                    z-index: 10000;
                    animation: fadeIn 0.5s ease;
                ">
                    <div style="font-size: 48px; color: #00ff00; margin-bottom: 16px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 style="color: #1a1a1a; margin-bottom: 8px;">Message Sent!</h3>
                    <p style="color: #666; margin: 0;">We'll get back to you within 24 hours.</p>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="margin-top: 20px; background: #00ff00; color: #1a1a1a; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                        OK
                    </button>
                </div>
                <div style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.5);
                    z-index: 9999;
                " onclick="this.parentElement.remove()"></div>
            `;
            document.body.appendChild(successDiv);
            
            setTimeout(() => {
                if (successDiv.parentElement) {
                    successDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>