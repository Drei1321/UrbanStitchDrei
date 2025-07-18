<?php
// Enhanced UrbanStitch E-commerce - Checkout Page with GCash
require_once 'config.php';
require_once 'xml_operations.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'place_order') {
        try {
            $pdo->beginTransaction();
            
            // Get cart items
            $stmt = $pdo->prepare("
                SELECT ci.*, p.name, p.price, p.stock_quantity, p.image_url, ps.price_adjustment
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                LEFT JOIN product_sizes ps ON (ci.product_id = ps.product_id AND ci.selected_size = ps.size_code)
                WHERE ci.user_id = ?
            ");
            $stmt->execute([$userId]);
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($cartItems)) {
                throw new Exception('Your cart is empty');
            }
            
            // Validate stock and calculate total
            $totalAmount = 0;
            foreach ($cartItems as $item) {
                $actualPrice = $item['price'] + ($item['price_adjustment'] ?? 0);
                $totalAmount += $actualPrice * $item['quantity'];
                
                // Check stock
                if ($item['selected_size']) {
                    $stmt = $pdo->prepare("SELECT stock_quantity FROM product_sizes WHERE product_id = ? AND size_code = ?");
                    $stmt->execute([$item['product_id'], $item['selected_size']]);
                    $sizeStock = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$sizeStock || $sizeStock['stock_quantity'] < $item['quantity']) {
                        throw new Exception("Insufficient stock for {$item['name']} (Size: {$item['selected_size']})");
                    }
                } else {
                    if ($item['stock_quantity'] < $item['quantity']) {
                        throw new Exception("Insufficient stock for {$item['name']}");
                    }
                }
            }
            
            // Get form data
            $billingInfo = [
                'first_name' => trim($_POST['first_name']),
                'last_name' => trim($_POST['last_name']),
                'email' => trim($_POST['email']),
                'phone' => trim($_POST['phone']),
                'address' => trim($_POST['address']),
                'city' => trim($_POST['city']),
                'postal_code' => trim($_POST['postal_code']),
                'province' => trim($_POST['province'])
            ];
            
            $gcashNumber = trim($_POST['gcash_number']);
            $referenceNumber = trim($_POST['reference_number']);
            
            // Validate required fields
            foreach ($billingInfo as $key => $value) {
                if (empty($value)) {
                    throw new Exception("Please fill in all required fields");
                }
            }
            
            if (empty($gcashNumber) || empty($referenceNumber)) {
                throw new Exception("Please provide GCash number and reference number");
            }
            
            // Generate order number
            $orderNumber = 'US' . date('Y') . sprintf('%06d', mt_rand(100000, 999999));
            
            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, order_number, total_amount, status, payment_method,
                    billing_info, gcash_number, gcash_reference, 
                    admin_notes, created_at
                ) VALUES (?, ?, ?, 'pending', 'gcash', ?, ?, ?, 'Payment verification pending', NOW())
            ");
            
            $billingJson = json_encode($billingInfo);
            $stmt->execute([
                $userId, $orderNumber, $totalAmount, $billingJson, 
                $gcashNumber, $referenceNumber
            ]);
            
            $orderId = $pdo->lastInsertId();
            
            // Create order items and update stock
            foreach ($cartItems as $item) {
                $actualPrice = $item['price'] + ($item['price_adjustment'] ?? 0);
                
                // Insert order item
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (
                        order_id, product_id, quantity, price, selected_size
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $orderId, $item['product_id'], $item['quantity'], 
                    $actualPrice, $item['selected_size']
                ]);
                
                // Update stock
                if ($item['selected_size']) {
                    $stmt = $pdo->prepare("
                        UPDATE product_sizes 
                        SET stock_quantity = stock_quantity - ? 
                        WHERE product_id = ? AND size_code = ?
                    ");
                    $stmt->execute([$item['quantity'], $item['product_id'], $item['selected_size']]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET stock_quantity = stock_quantity - ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $pdo->commit();
            
            // Redirect to orders page with success message
            header("Location: orders.php?order_placed=1&order_number=" . urlencode($orderNumber));
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Get cart items for display
try {
    $stmt = $pdo->prepare("
        SELECT ci.*, p.name, p.price, p.image_url, p.slug, ps.price_adjustment, ps.size_name
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_sizes ps ON (ci.product_id = ps.product_id AND ci.selected_size = ps.size_code)
        WHERE ci.user_id = ?
        ORDER BY ci.created_at ASC
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading cart: " . $e->getMessage());
    $cartItems = [];
}

// Calculate totals
$subtotal = 0;
$totalItems = 0;
foreach ($cartItems as $item) {
    $actualPrice = $item['price'] + ($item['price_adjustment'] ?? 0);
    $subtotal += $actualPrice * $item['quantity'];
    $totalItems += $item['quantity'];
}

$shippingFee = $subtotal >= 2500 ? 0 : 150; // Free shipping over ₱2,500
$total = $subtotal + $shippingFee;

// Get user info for pre-filling
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = [];
}

// Redirect if cart is empty
if (empty($cartItems)) {
    header('Location: cart.php?empty=1');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - UrbanStitch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 48px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 900;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        .page-subtitle {
            font-size: 16px;
            color: #666;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 48px;
            align-items: start;
        }
        
        .checkout-form {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 32px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #00ff00;
        }
        
        .form-input.error {
            border-color: #ff4444;
        }
        
        .gcash-payment {
            background: linear-gradient(135deg, #0066cc, #004499);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        
        .gcash-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .gcash-logo {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            color: #0066cc;
            font-size: 14px;
        }
        
        .gcash-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }
        
        .gcash-instructions {
            background: rgba(255,255,255,0.1);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .gcash-step {
            display: flex;
            align-items: start;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .step-number {
            background: rgba(255,255,255,0.2);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .gcash-amount {
            text-align: center;
            padding: 16px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .amount-label {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 8px;
        }
        
        .amount-value {
            font-size: 32px;
            font-weight: 900;
            color: #00ff00;
        }
        
        .order-summary {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
            position: sticky;
            top: 24px;
        }
        
        .summary-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 16px;
        }
        
        .summary-items {
            margin-bottom: 16px;
        }
        
        .summary-item {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            background: #f0f0f0;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        
        .item-info {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
        }
        
        .item-price {
            font-size: 14px;
            font-weight: 700;
            color: #00cc00;
        }
        
        .summary-totals {
            border-top: 2px solid #f0f0f0;
            padding-top: 16px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .total-label {
            font-size: 14px;
            color: #666;
        }
        
        .total-value {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .final-total {
            border-top: 1px solid #e0e0e0;
            padding-top: 12px;
            margin-top: 12px;
        }
        
        .final-total .total-label {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .final-total .total-value {
            font-size: 18px;
            font-weight: 900;
            color: #00cc00;
        }
        
        .shipping-info {
            background: #e8f5e8;
            color: #2d5a2d;
            padding: 12px;
            border-radius: 8px;
            margin: 16px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .place-order-btn {
            width: 100%;
            background: linear-gradient(135deg, #00ff00, #00cc00);
            color: #1a1a1a;
            border: none;
            padding: 16px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,255,0,0.3);
        }
        
        .place-order-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .error-message {
            background: #ffe6e6;
            color: #cc0000;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-to-cart {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 24px;
            transition: color 0.2s;
        }
        
        .back-to-cart:hover {
            color: #00cc00;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                padding: 24px 16px;
            }
            
            .checkout-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            
            .order-summary {
                order: -1;
                position: static;
            }
            
            .checkout-form {
                padding: 24px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .processing-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        
        .processing-content {
            background: white;
            padding: 32px;
            border-radius: 12px;
            text-align: center;
            max-width: 400px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f0f0f0;
            border-top: 4px solid #00ff00;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="top-banner">
            <span class="animate-pulse-neon">FREE SHIPPING THIS WEEK ORDER OVER • ₱2,500</span>
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
                <div class="user-menu">
                    <button class="action-btn user-menu-btn">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </button>
                </div>
                
                <button class="action-btn wishlist-btn">
                    <i class="fas fa-heart"></i>
                    <span class="badge orange">0</span>
                </button>
                <a href="cart.php" class="action-btn cart-btn">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="badge"><?php echo $totalItems; ?></span>
                </a>
            </div>
        </div>
    </header>

    <!-- Checkout Content -->
    <div class="checkout-container">
        <div class="page-header">
            <h1 class="page-title">Checkout</h1>
            <p class="page-subtitle">Complete your order with secure GCash payment</p>
        </div>

        <a href="cart.php" class="back-to-cart">
            <i class="fas fa-arrow-left"></i>
            Back to Cart
        </a>

        <?php if (isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="checkoutForm" class="checkout-grid">
            <input type="hidden" name="action" value="place_order">
            
            <!-- Checkout Form -->
            <div class="checkout-form">
                <!-- Billing Information -->
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-user"></i>
                        Billing Information
                    </h2>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                   placeholder="+63 9XX XXX XXXX" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">Address *</label>
                            <input type="text" name="address" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" 
                                   placeholder="Street, Barangay" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">City *</label>
                            <input type="text" name="city" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Province *</label>
                            <input type="text" name="province" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Postal Code *</label>
                            <input type="text" name="postal_code" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- GCash Payment -->
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-mobile-alt"></i>
                        GCash Payment
                    </h2>
                    
                    <div class="gcash-payment">
                        <div class="gcash-header">
                            <div class="gcash-logo">G</div>
                            <h3 class="gcash-title">Pay with GCash</h3>
                        </div>
                        
                        <div class="gcash-amount">
                            <div class="amount-label">Total Amount to Pay</div>
                            <div class="amount-value">₱<?php echo number_format($total, 2); ?></div>
                        </div>
                        
                        <div class="gcash-instructions">
                            <div class="gcash-step">
                                <div class="step-number">1</div>
                                <div>Open your GCash app and send ₱<?php echo number_format($total, 2); ?> to <strong>09XX XXX XXXX</strong></div>
                            </div>
                            <div class="gcash-step">
                                <div class="step-number">2</div>
                                <div>Take a screenshot of your transaction</div>
                            </div>
                            <div class="gcash-step">
                                <div class="step-number">3</div>
                                <div>Fill in your GCash details below</div>
                            </div>
                            <div class="gcash-step">
                                <div class="step-number">4</div>
                                <div>Wait for admin approval (usually within 24 hours)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Your GCash Number *</label>
                            <input type="tel" name="gcash_number" class="form-input" 
                                   placeholder="09XX XXX XXXX" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">GCash Reference Number *</label>
                            <input type="text" name="reference_number" class="form-input" 
                                   placeholder="Enter reference number from GCash" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h2 class="summary-title">Order Summary</h2>
                
                <div class="summary-items">
                    <?php foreach ($cartItems as $item): 
                        $actualPrice = $item['price'] + ($item['price_adjustment'] ?? 0);
                        $itemTotal = $actualPrice * $item['quantity'];
                    ?>
                    <div class="summary-item">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="item-image"
                             onerror="this.src='https://via.placeholder.com/50x50?text=No+Image'">
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <?php if ($item['selected_size']): ?>
                            <div class="item-info">Size: <?php echo htmlspecialchars($item['size_name'] ?? $item['selected_size']); ?></div>
                            <?php endif; ?>
                            <div class="item-info">Qty: <?php echo $item['quantity']; ?></div>
                            <div class="item-price">₱<?php echo number_format($itemTotal, 2); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="summary-totals">
                    <div class="total-row">
                        <span class="total-label">Subtotal (<?php echo $totalItems; ?> items)</span>
                        <span class="total-value">₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">Shipping Fee</span>
                        <span class="total-value">
                            <?php if ($shippingFee > 0): ?>
                                ₱<?php echo number_format($shippingFee, 2); ?>
                            <?php else: ?>
                                FREE
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if ($subtotal >= 2500): ?>
                    <div class="shipping-info">
                        <i class="fas fa-truck"></i>
                        Congratulations! You qualified for FREE shipping!
                    </div>
                    <?php endif; ?>
                    
                    <div class="total-row final-total">
                        <span class="total-label">Total</span>
                        <span class="total-value">₱<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
                
                <button type="submit" class="place-order-btn" id="placeOrderBtn">
                    <i class="fas fa-lock"></i>
                    Place Order Securely
                </button>
                
                <div style="text-align: center; margin-top: 16px; font-size: 12px; color: #666;">
                    <i class="fas fa-shield-alt"></i>
                    Your payment information is secure and encrypted
                </div>
            </div>
        </form>
    </div>

    <!-- Processing Overlay -->
    <div class="processing-overlay" id="processingOverlay">
        <div class="processing-content">
            <div class="spinner"></div>
            <h3>Processing Your Order...</h3>
            <p>Please wait while we process your payment and create your order.</p>
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
                    </p>
                </div>
                
                <div class="footer-section">
                    <h4>Customer Service</h4>
                    <ul class="footer-links">
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="shipping.php">Shipping Info</a></li>
                        <li><a href="returns.php">Returns</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 UrbanStitch. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Form validation and submission
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show processing overlay
            document.getElementById('processingOverlay').style.display = 'flex';
            
            // Validate form
            const requiredFields = this.querySelectorAll('input[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                document.getElementById('processingOverlay').style.display = 'none';
                alert('Please fill in all required fields');
                return;
            }
            
            // Validate phone number format
            const phoneField = document.querySelector('input[name="phone"]');
            const phoneRegex = /^(\+63|0)\d{10}$/;
            if (!phoneRegex.test(phoneField.value.replace(/\s/g, ''))) {
                phoneField.classList.add('error');
                document.getElementById('processingOverlay').style.display = 'none';
                alert('Please enter a valid Philippine phone number');
                return;
            }
            
            // Validate GCash number
            const gcashField = document.querySelector('input[name="gcash_number"]');
            const gcashRegex = /^09\d{9}$/;
            if (!gcashRegex.test(gcashField.value.replace(/\s/g, ''))) {
                gcashField.classList.add('error');
                document.getElementById('processingOverlay').style.display = 'none';
                alert('Please enter a valid GCash number (09XXXXXXXXX)');
                return;
            }
            
            // If all validations pass, submit the form
            setTimeout(() => {
                this.submit();
            }, 1000);
        });
        
        // Format phone numbers as user types
        document.querySelector('input[name="phone"]').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.startsWith('63')) {
                value = '+' + value;
            } else if (value.startsWith('9')) {
                value = '0' + value;
            }
            this.value = value;
        });
        
        document.querySelector('input[name="gcash_number"]').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            if (value.length >= 2 && !value.startsWith('09')) {
                if (value.startsWith('9')) {
                    value = '0' + value;
                }
            }
            this.value = value;
        });
        
        // Auto-format reference number to uppercase
        document.querySelector('input[name="reference_number"]').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>