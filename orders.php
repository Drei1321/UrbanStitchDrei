<?php
// Enhanced UrbanStitch E-commerce - User Orders Page (Fixed for actual DB structure)
require_once 'config.php';
require_once 'xml_operations.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle order placement success message
$orderPlacedSuccess = isset($_GET['order_placed']) && $_GET['order_placed'] == '1';
$orderNumber = $_GET['order_number'] ?? '';

// Get orders with items - FIXED: Use actual status column
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               GROUP_CONCAT(
                   CONCAT(p.name, ' (', oi.quantity, 'x)')
                   SEPARATOR ', '
               ) as items_summary
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // REMOVED: No longer derive status from admin_notes
    // Just use the status from database as-is
    
} catch (Exception $e) {
    error_log("Error loading orders: " . $e->getMessage());
    $orders = [];
}

// Enhanced Order Details Ajax Handler - FIXED: Use actual status column
if (isset($_GET['ajax']) && $_GET['ajax'] == '1' && isset($_GET['order_id'])) {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to continue']);
        exit;
    }
    
    $orderId = (int)$_GET['order_id'];
    $userId = $_SESSION['user_id'];
    
    try {
        // Get detailed order information - FIXED: Select actual status column
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   COALESCE(o.shipping_address, CONCAT('Address not specified')) as full_shipping_address,
                   '' as email, '' as phone
            FROM orders o
            WHERE o.id = ? AND o.user_id = ?
        ");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        
        // REMOVED: No longer derive status from admin_notes
        // The status column from database is used directly
        
        // Get order items with detailed product information
        $stmt = $pdo->prepare("
            SELECT oi.*, 
                   p.name, p.image_url, p.stock_quantity,
                   (oi.quantity * oi.price) as item_total
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ");
        $stmt->execute([$orderId]);
        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate order summary
        $subtotal = array_sum(array_column($orderItems, 'item_total'));
        $shipping = 0; // No shipping fee column in your structure
        $tax = 0; // No tax column in your structure
        $discount = 0; // No discount column in your structure
        
        // Create basic timeline from order status
        $timeline = [];
        $timeline[] = [
            'status' => 'pending', 
            'date' => $order['created_at'], 
            'description' => 'Order placed and awaiting payment confirmation'
        ];
        
        // FIXED: Use actual status column instead of derived status
        if ($order['status'] && $order['status'] != 'pending') {
            $timeline[] = [
                'status' => $order['status'], 
                'date' => $order['status_updated_at'] ?? $order['created_at'], 
                'description' => $order['admin_notes'] ?: getStatusDescription($order['status'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $orderItems,
            'summary' => [
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $order['total_amount']
            ],
            'timeline' => $timeline
        ]);
        
    } catch (Exception $e) {
        error_log("Error loading order details: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    exit;
}

// Calculate cart and wishlist counts for header
$cartCount = 0;
$wishlistCount = 0;

try {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total_count FROM cart_items WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cartCount = (int)($result['total_count'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $wishlistCount = (int)($result['count'] ?? 0);
} catch (Exception $e) {
    error_log("Error loading header counts: " . $e->getMessage());
    $cartCount = 0;
    $wishlistCount = 0;
}

// Helper function to get status badge
function getStatusBadge($status) {
    $badges = [
        'pending' => ['class' => 'badge-warning', 'icon' => 'clock', 'text' => 'Pending Review'],
        'confirmed' => ['class' => 'badge-info', 'icon' => 'check', 'text' => 'Payment Confirmed'],
        'processing' => ['class' => 'badge-primary', 'icon' => 'cog', 'text' => 'Processing'],
        'shipped' => ['class' => 'badge-secondary', 'icon' => 'truck', 'text' => 'Shipped'],
        'delivered' => ['class' => 'badge-success', 'icon' => 'home', 'text' => 'Delivered'],
        'completed' => ['class' => 'badge-success', 'icon' => 'check-circle', 'text' => 'Completed'],
        'cancelled' => ['class' => 'badge-danger', 'icon' => 'times-circle', 'text' => 'Cancelled']
    ];
    
    return $badges[$status] ?? ['class' => 'badge-secondary', 'icon' => 'question', 'text' => ucfirst($status)];
}

// Helper function to get status descriptions
function getStatusDescription($status) {
    $descriptions = [
        'pending' => 'Order placed and awaiting payment confirmation',
        'confirmed' => 'Payment confirmed, order is being prepared',
        'processing' => 'Order is being processed and prepared for shipment',
        'shipped' => 'Order has been shipped and is on the way',
        'delivered' => 'Order has been delivered successfully',
        'completed' => 'Order completed successfully',
        'cancelled' => 'Order has been cancelled'
    ];
    
    return $descriptions[$status] ?? 'Status updated';
}

// Handle reorder action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reorder') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to continue']);
        exit;
    }
    
    $orderId = (int)$_POST['order_id'];
    $userId = $_SESSION['user_id'];
    
    try {
        // Verify the order belongs to this user
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        
        // Get order items
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.stock_quantity
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($orderItems)) {
            echo json_encode(['success' => false, 'message' => 'No items found in this order']);
            exit;
        }
        
        $addedItems = 0;
        $failedItems = [];
        
        foreach ($orderItems as $item) {
            // Check if product is still available
            if ($item['stock_quantity'] < $item['quantity']) {
                $failedItems[] = $item['name'];
                continue;
            }
            
            // Check if item already exists in cart
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $item['product_id']]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem['quantity'] + $item['quantity'];
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
                $stmt->execute([$newQuantity, $existingItem['id']]);
            } else {
                // Add new item
                $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$userId, $item['product_id'], $item['quantity']]);
            }
            
            $addedItems++;
        }
        
        // Get updated cart count
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total_count FROM cart_items WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cartCount = (int)($result['total_count'] ?? 0);
        
        $message = "$addedItems items added to cart";
        if (!empty($failedItems)) {
            $message .= ". Some items are out of stock: " . implode(', ', $failedItems);
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'cart_count' => $cartCount
        ]);
        
    } catch (Exception $e) {
        error_log("Reorder error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to add items to cart']);
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - UrbanStitch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }
        
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 900;
            color: #1a1a1a;
            margin: 0;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: #00cc00;
        }
        
        .orders-grid {
            display: grid;
            gap: 24px;
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            border-color: #00ff00;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-number {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .order-date {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
        }
        
        .order-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-primary {
            background: #cce7ff;
            color: #004085;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .order-total {
            font-size: 18px;
            font-weight: 900;
            color: #00cc00;
        }
        
        .order-items {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
        }
        
        .items-summary {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }
        
        .order-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary {
            background: #00ff00;
            color: #1a1a1a;
        }
        
        .btn-primary:hover {
            background: #00cc00;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .empty-icon {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 24px;
        }
        
        .empty-title {
            font-size: 24px;
            font-weight: 700;
            color: #666;
            margin-bottom: 12px;
        }
        
        .empty-text {
            font-size: 16px;
            color: #999;
            margin-bottom: 32px;
            line-height: 1.5;
        }
        
        .shop-btn {
            background: linear-gradient(135deg, #00ff00, #00cc00);
            color: #1a1a1a;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .shop-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,255,0,0.3);
        }
        
        /* Order Detail Modal */
        .order-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
            backdrop-filter: blur(4px);
        }

        .order-modal.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            padding: 24px 32px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 900;
            color: #1a1a1a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, #00ff00, #00cc00);
            border-radius: 2px;
        }

        .close-btn {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            font-size: 20px;
            color: #666;
            cursor: pointer;
            padding: 12px;
            border-radius: 50%;
            transition: all 0.2s;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-btn:hover {
            background: #ff4444;
            border-color: #ff4444;
            color: white;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 32px;
            overflow-y: auto;
            max-height: calc(90vh - 100px);
        }

        /* Animation keyframes */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .orders-container {
                padding: 24px 16px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .order-card {
                padding: 16px;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .order-info {
                grid-template-columns: 1fr;
            }
            
            .order-actions {
                flex-direction: column;
            }
            
            .modal-content {
                margin: 0;
                border-radius: 0;
                max-height: 100vh;
                height: 100vh;
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
                        <a href="profile.php" style="display: block; padding: 8px 12px; color: #666; text-decoration: none; border-radius: 4px; transition: background 0.2s;">
                            <i class="fas fa-user" style="margin-right: 8px;"></i>Profile
                        </a>
                        <a href="orders.php" style="display: block; padding: 8px 12px; color: #00cc00; text-decoration: none; border-radius: 4px; transition: background 0.2s; font-weight: 600;">
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
                    <li><a href="offers.php" class="nav-link hot">HOT OFFERS</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <?php if ($orderPlacedSuccess): ?>
        <div style="background: linear-gradient(135deg, #00ff00, #00cc00); color: #1a1a1a; padding: 20px; border-radius: 12px; margin-bottom: 24px; text-align: center;">
            <div style="font-size: 24px; font-weight: 900; margin-bottom: 8px;">
                <i class="fas fa-check-circle"></i> Order Placed Successfully!
            </div>
            <div style="font-size: 16px; margin-bottom: 12px;">
                Your order <?php echo htmlspecialchars($orderNumber); ?> has been submitted and is pending approval.
            </div>
            <div style="font-size: 14px; opacity: 0.8;">
                <i class="fas fa-clock"></i> Our team will review your GCash payment within 24 hours and update your order status.
            </div>
        </div>
    <?php endif; ?>

    <!-- Orders Content -->
    <div class="orders-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Orders</h1>
                <a href="profile.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Profile
                </a>
            </div>
        </div>

        <?php if (empty($orders)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-box-open"></i>
            </div>
            <h2 class="empty-title">No Orders Yet</h2>
            <p class="empty-text">
                You haven't placed any orders yet. Start shopping to see your order history here.
            </p>
            <a href="index.php" class="shop-btn">
                <i class="fas fa-shopping-bag"></i>
                Start Shopping
            </a>
        </div>
        <?php else: ?>
        <!-- Orders List -->
        <div class="orders-grid">
            <?php foreach ($orders as $order): 
                $statusBadge = getStatusBadge($order['status']);
            ?>
            <div class="order-card" onclick="showOrderDetails(<?php echo $order['id']; ?>)">
                <div class="order-header">
                    <div>
                        <div class="order-number">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
                        <div class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="order-status">
                        <span class="badge <?php echo $statusBadge['class']; ?>">
                            <i class="fas fa-<?php echo $statusBadge['icon']; ?>"></i>
                            <?php echo $statusBadge['text']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="order-info">
                    <div class="info-item">
                        <span class="info-label">Items</span>
                        <span class="info-value"><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total</span>
                        <span class="info-value order-total">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Payment</span>
                        <span class="info-value"><?php echo ucfirst($order['payment_method']); ?></span>
                    </div>
                </div>
                
                <div class="order-items">
                    <div class="items-summary">
                        <strong>Items:</strong> <?php echo htmlspecialchars($order['items_summary']); ?>
                    </div>
                </div>
                
                <?php if (!empty($order['admin_notes'])): ?>
                <div style="margin-top: 12px; padding: 8px 12px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #00cc00;">
                    <div style="font-size: 12px; color: #666; font-weight: 600; margin-bottom: 4px;">ORDER NOTES:</div>
                    <div style="font-size: 14px; color: #333;"><?php echo htmlspecialchars($order['admin_notes']); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="order-actions">
                    <button class="btn btn-primary" onclick="event.stopPropagation(); showOrderDetails(<?php echo $order['id']; ?>)">
                        <i class="fas fa-eye"></i>
                        View Details
                    </button>
                    <?php if ($order['status'] === 'completed'): ?>
                    <button class="btn btn-secondary" onclick="event.stopPropagation(); reorderItems(<?php echo $order['id']; ?>)">
                        <i class="fas fa-redo"></i>
                        Reorder
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Order Details Modal -->
    <div class="order-modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Order Details</h2>
                <button class="close-btn" onclick="closeOrderModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Dynamic content will be loaded here -->
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
        // Orders page functionality
        
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

        // Show order details function
        async function showOrderDetails(orderId) {
            console.log('🔍 Starting order details fetch for order ID:', orderId);
            
            const modal = document.getElementById('orderModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            // Show loading state
            modalTitle.textContent = 'Loading Order Details...';
            modalBody.innerHTML = `
                <div style="text-align: center; padding: 60px 20px;">
                    <div style="display: inline-block; position: relative;">
                        <i class="fas fa-circle-notch fa-spin" style="font-size: 48px; color: #00cc00;"></i>
                    </div>
                    <div style="margin-top: 16px; color: #666; font-size: 16px;">Loading your order details...</div>
                </div>
            `;
            modal.classList.add('active');
            
            try {
                console.log('📡 Making fetch request to:', `orders.php?order_id=${orderId}&ajax=1`);
                
                // Fetch order details
                const response = await fetch(`orders.php?order_id=${orderId}&ajax=1`);
                
                console.log('📊 Response status:', response.status);
                console.log('📊 Response ok:', response.ok);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                // Get the raw response text first
                const responseText = await response.text();
                console.log('📄 Raw response text:', responseText);
                
                // Try to parse as JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                    console.log('✅ Parsed JSON data:', data);
                } catch (parseError) {
                    console.error('❌ JSON Parse Error:', parseError);
                    console.error('📄 Response that failed to parse:', responseText);
                    throw new Error('Invalid JSON response');
                }
                
                if (data.success) {
                    console.log('🎉 Success! Order data received:', data.order);
                    console.log('📦 Items received:', data.items);
                    console.log('💰 Summary received:', data.summary);
                    console.log('⏰ Timeline received:', data.timeline);
                    
                    modalTitle.textContent = `Order #${data.order.order_number}`;
                    modalBody.innerHTML = generateOrderDetailsHTML(data.order, data.items, data.summary, data.timeline);
                } else {
                    console.error('❌ Server returned error:', data.message);
                    modalTitle.textContent = 'Error';
                    modalBody.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #ff4444;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px;"></i>
                            <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Failed to Load Order</div>
                            <div style="font-size: 14px;">${data.message || 'An error occurred while loading the order details.'}</div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('💥 Fetch error:', error);
                console.error('💥 Error stack:', error.stack);
                modalTitle.textContent = 'Connection Error';
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #ff4444;">
                        <i class="fas fa-wifi" style="font-size: 48px; margin-bottom: 16px;"></i>
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Connection Error</div>
                        <div style="font-size: 14px; margin-bottom: 16px;">Error: ${error.message}</div>
                        <div style="font-size: 12px; margin-bottom: 16px; color: #999;">Check browser console for details</div>
                        <button class="btn btn-primary" onclick="showOrderDetails(${orderId})">
                            <i class="fas fa-redo"></i>
                            Retry
                        </button>
                    </div>
                `;
            }
        }

        // Enhanced order details modal generation
        function generateOrderDetailsHTML(order, items, summary, timeline) {
            const statusBadge = getStatusBadgeInfo(order.status);
            
            let html = `
                <div class="order-detail-container">
                    <!-- Order Header -->
                    <div class="order-detail-header" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 20px; border-radius: 8px; margin-bottom: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div>
                                <h3 style="margin: 0; color: #1a1a1a; font-size: 20px;">Order #${order.order_number}</h3>
                                <p style="margin: 4px 0 0 0; color: #666; font-size: 14px;">Placed on ${formatDate(order.created_at)}</p>
                            </div>
                            <span class="badge ${statusBadge.class}" style="font-size: 14px; padding: 8px 16px;">
                                <i class="fas fa-${statusBadge.icon}"></i>
                                ${statusBadge.text}
                            </span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <div>
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Payment Method</div>
                                <div style="font-size: 16px; font-weight: 600; color: #1a1a1a;">
                                    <i class="fas fa-${order.payment_method === 'gcash' ? 'mobile-alt' : 'credit-card'}"></i>
                                    ${order.payment_method.charAt(0).toUpperCase() + order.payment_method.slice(1)}
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Total Amount</div>
                                <div style="font-size: 18px; font-weight: 900; color: #00cc00;">₱${parseFloat(order.total_amount).toFixed(2)}</div>
                            </div>
                            ${order.full_shipping_address && order.full_shipping_address !== 'Address not specified' ? `
                            <div style="grid-column: 1 / -1;">
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Shipping Address</div>
                                <div style="font-size: 14px; color: #1a1a1a; line-height: 1.4;">${order.full_shipping_address}</div>
                            </div>
                            ` : ''}
                        </div>
                        
                        ${order.admin_notes ? `
                        <div style="margin-top: 16px; padding: 12px; background: rgba(0, 204, 0, 0.1); border-left: 4px solid #00cc00; border-radius: 4px;">
                            <div style="font-size: 12px; color: #666; font-weight: 600; margin-bottom: 4px;">ORDER NOTES:</div>
                            <div style="font-size: 14px; color: #333; line-height: 1.4;">${order.admin_notes}</div>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Order Timeline (only show if order is not completed) -->
                    ${order.status !== 'completed' && order.status !== 'cancelled' ? generateTimelineHTML(timeline) : ''}
                    
                    <!-- Order Items -->
                    <div class="order-items-section" style="margin-bottom: 24px;">
                        <h4 style="font-size: 18px; font-weight: 700; margin-bottom: 16px; color: #1a1a1a; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-box"></i>
                            Order Items (${items.length})
                        </h4>
                        <div class="order-detail-items" style="border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden;">
            `;
            
            items.forEach((item, index) => {
                html += `
                    <div class="order-item" style="padding: 16px; ${index !== items.length - 1 ? 'border-bottom: 1px solid #f0f0f0;' : ''} display: flex; gap: 16px; align-items: center;">
                        <div style="flex-shrink: 0;">
                            <img src="${item.image_url || 'https://via.placeholder.com/80x80?text=No+Image'}" alt="${item.name}" class="item-image" 
                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; background: #f8f9fa;"
                                 onerror="this.src='https://via.placeholder.com/80x80?text=No+Image'">
                        </div>
                        <div class="item-details" style="flex: 1; min-width: 0;">
                            <div class="item-name" style="font-size: 16px; font-weight: 600; color: #1a1a1a; margin-bottom: 4px;">${item.name}</div>
                            ${item.selected_size ? `<div style="font-size: 14px; color: #666; margin-bottom: 4px;">Size: ${item.selected_size}</div>` : ''}
                            <div class="item-quantity" style="font-size: 14px; color: #666; margin-bottom: 8px;">
                                Quantity: <strong>${item.quantity}</strong>
                            </div>
                            ${item.stock_quantity ? `
                            <div style="font-size: 12px; color: #999;">
                                Stock: ${item.stock_quantity > 0 ? `${item.stock_quantity} available` : 'Out of stock'}
                            </div>
                            ` : ''}
                        </div>
                        <div style="text-align: right; flex-shrink: 0;">
                            <div style="font-size: 14px; color: #666; margin-bottom: 4px;">₱${parseFloat(item.price).toFixed(2)} each</div>
                            <div style="font-size: 16px; font-weight: 700; color: #1a1a1a;">
                                ₱${parseFloat(item.item_total || (item.price * item.quantity)).toFixed(2)}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="order-summary" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
                        <h4 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; color: #1a1a1a;">Order Summary</h4>
                        <div style="space-y: 8px;">
                            <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                <span style="color: #666;">Subtotal:</span>
                                <span style="font-weight: 600;">₱${parseFloat(summary.subtotal).toFixed(2)}</span>
                            </div>
                            ${summary.shipping > 0 ? `
                            <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                <span style="color: #666;">Shipping:</span>
                                <span style="font-weight: 600;">₱${parseFloat(summary.shipping).toFixed(2)}</span>
                            </div>
                            ` : ''}
                            ${summary.tax > 0 ? `
                            <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                <span style="color: #666;">Tax:</span>
                                <span style="font-weight: 600;">₱${parseFloat(summary.tax).toFixed(2)}</span>
                            </div>
                            ` : ''}
                            ${summary.discount > 0 ? `
                            <div style="display: flex; justify-content: space-between; padding: 4px 0; color: #00cc00;">
                                <span>Discount:</span>
                                <span style="font-weight: 600;">-₱${parseFloat(summary.discount).toFixed(2)}</span>
                            </div>
                            ` : ''}
                            <hr style="margin: 12px 0; border: none; border-top: 1px solid #dee2e6;">
                            <div style="display: flex; justify-content: space-between; padding: 8px 0; font-size: 18px; font-weight: 900;">
                                <span style="color: #1a1a1a;">Total:</span>
                                <span style="color: #00cc00;">₱${parseFloat(summary.total).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div style="margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap;">
                        ${order.status === 'completed' ? `
                        <button class="btn btn-primary" onclick="reorderItems(${order.id}); closeOrderModal();">
                            <i class="fas fa-redo"></i>
                            Reorder Items
                        </button>
                        ` : ''}
                        ${order.status === 'pending' && order.payment_method === 'gcash' ? `
                        <button class="btn btn-secondary" onclick="showPaymentInstructions('${order.order_number}');">
                            <i class="fas fa-info-circle"></i>
                            Payment Instructions
                        </button>
                        ` : ''}
                        <button class="btn btn-secondary" onclick="printOrder(${order.id});">
                            <i class="fas fa-print"></i>
                            Print Order
                        </button>
                    </div>
                </div>
            `;
            
            return html;
        }

        // Generate timeline HTML for incomplete orders
        function generateTimelineHTML(timeline) {
            if (!timeline || timeline.length === 0) return '';
            
            let html = `
                <div class="order-timeline" style="margin-bottom: 24px; background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
                    <h4 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; color: #1a1a1a; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-clock"></i>
                        Order Progress
                    </h4>
                    <div style="position: relative; padding-left: 24px;">
            `;
            
            timeline.forEach((item, index) => {
                const isCompleted = index < timeline.length - 1 || item.status === 'completed' || item.status === 'delivered';
                const isCurrent = index === timeline.length - 1 && item.status !== 'completed' && item.status !== 'delivered';
                
                html += `
                    <div style="position: relative; padding-bottom: ${index === timeline.length - 1 ? '0' : '20px'};">
                        <div style="position: absolute; left: -24px; top: 0; width: 16px; height: 16px; border-radius: 50%; background: ${isCompleted ? '#00cc00' : isCurrent ? '#ffc107' : '#e9ecef'}; border: 3px solid white; box-shadow: 0 0 0 2px ${isCompleted ? '#00cc00' : isCurrent ? '#ffc107' : '#e9ecef'};"></div>
                        ${index !== timeline.length - 1 ? `<div style="position: absolute; left: -16px; top: 16px; width: 2px; height: 100%; background: ${isCompleted ? '#00cc00' : '#e9ecef'};"></div>` : ''}
                        <div>
                            <div style="font-weight: 600; color: #1a1a1a; margin-bottom: 4px;">${getStatusTitle(item.status)}</div>
                            <div style="font-size: 14px; color: #666; margin-bottom: 2px;">${item.description}</div>
                            <div style="font-size: 12px; color: #999;">${formatDate(item.date)}</div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
            
            return html;
        }

        // Get status title for timeline
        function getStatusTitle(status) {
            const titles = {
                'pending': 'Order Placed',
                'confirmed': 'Payment Confirmed',
                'processing': 'Order Processing',
                'shipped': 'Order Shipped',
                'delivered': 'Order Delivered',
                'completed': 'Order Completed',
                'cancelled': 'Order Cancelled'
            };
            
            return titles[status] || status.charAt(0).toUpperCase() + status.slice(1);
        }

        // Additional helper functions
        function showPaymentInstructions(orderNumber) {
            const modal = document.getElementById('orderModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = 'GCash Payment Instructions';
            modalBody.innerHTML = `
                <div style="padding: 20px;">
                    <div style="background: #e3f2fd; padding: 16px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                        <h4 style="margin: 0 0 8px 0; color: #1976d2;">
                            <i class="fas fa-info-circle"></i>
                            Complete Your Payment
                        </h4>
                        <p style="margin: 0; color: #1976d2; font-size: 14px;">Send payment to our GCash number and upload your receipt</p>
                    </div>
                    
                    <div style="text-align: center; margin-bottom: 24px;">
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Send Payment To:</div>
                        <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; border: 2px dashed #00cc00;">
                            <div style="font-size: 24px; font-weight: 900; color: #00cc00;">09XX-XXX-XXXX</div>
                            <div style="font-size: 14px; color: #666; margin-top: 4px;">UrbanStitch Store</div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <h5 style="margin-bottom: 12px;">Instructions:</h5>
                        <ol style="padding-left: 20px; line-height: 1.6;">
                            <li>Open your GCash app</li>
                            <li>Send the exact amount</li>
                            <li>Use reference: <strong>${orderNumber}</strong></li>
                            <li>Take a screenshot of your receipt</li>
                            <li>Send the receipt to our email or upload via our website</li>
                        </ol>
                    </div>
                    
                    <div style="background: #fff3cd; padding: 16px; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <p style="margin: 0; color: #856404; font-size: 14px;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Important:</strong> Your order will be processed within 24 hours after payment confirmation.
                        </p>
                    </div>
                </div>
            `;
        }

        function printOrder(orderId) {
            window.open(`print-order.php?order_id=${orderId}`, '_blank');
        }

        // Get status badge info
        function getStatusBadgeInfo(status) {
            const badges = {
                'pending': {class: 'badge-warning', icon: 'clock', text: 'Pending'},
                'confirmed': {class: 'badge-info', icon: 'check', text: 'Confirmed'},
                'processing': {class: 'badge-primary', icon: 'cog', text: 'Processing'},
                'shipped': {class: 'badge-secondary', icon: 'truck', text: 'Shipped'},
                'delivered': {class: 'badge-success', icon: 'home', text: 'Delivered'},
                'completed': {class: 'badge-success', icon: 'check-circle', text: 'Completed'},
                'cancelled': {class: 'badge-danger', icon: 'times-circle', text: 'Cancelled'}
            };
            
            return badges[status] || {class: 'badge-secondary', icon: 'question', text: status.charAt(0).toUpperCase() + status.slice(1)};
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Close order modal
        function closeOrderModal() {
            document.getElementById('orderModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderModal();
            }
        });

        // Reorder items functionality
        async function reorderItems(orderId) {
            if (!confirm('Add all items from this order to your cart?')) {
                return;
            }

            try {
                const response = await fetch('orders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=reorder&order_id=${orderId}`
                });

                const data = await response.json();

                if (data.success) {
                    // Show success notification
                    showNotification('Items added to cart successfully!', 'success');
                    
                    // Update cart count if available
                    if (data.cart_count !== undefined) {
                        const cartCountElement = document.getElementById('cartCount');
                        if (cartCountElement) {
                            cartCountElement.textContent = data.cart_count;
                        }
                    }
                } else {
                    showNotification(data.message || 'Failed to add items to cart', 'error');
                }
            } catch (error) {
                console.error('Reorder error:', error);
                showNotification('An error occurred while adding items to cart', 'error');
            }
        }

        // Simple notification function
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#00ff00' : '#ff4444'};
                color: ${type === 'success' ? '#1a1a1a' : 'white'};
                padding: 16px 20px;
                border-radius: 8px;
                font-weight: 600;
                z-index: 10001;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}" style="margin-right: 8px;"></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Handle keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeOrderModal();
            }
        });
    </script>
</body>
</html>