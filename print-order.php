<?php
// Print Order Page - UrbanStitch E-commerce
require_once 'config.php';

// Enhanced error handling and debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors on the page
ini_set('log_errors', 1);

// Function to safely check if user is logged in
function safeIsLoggedIn() {
    if (function_exists('isLoggedIn')) {
        return isLoggedIn();
    }
    // Fallback check
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is logged in
if (!safeIsLoggedIn()) {
    ?>
    <!DOCTYPE html>
    <html><head><title>Access Denied</title></head>
    <body style="font-family: Arial; text-align: center; padding: 50px;">
        <h2>Access Denied</h2>
        <p>You must be logged in to view this page.</p>
        <a href="login.php">Login Here</a>
    </body></html>
    <?php
    exit;
}

$userId = $_SESSION['user_id'];
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$orderId) {
    ?>
    <!DOCTYPE html>
    <html><head><title>Invalid Order</title></head>
    <body style="font-family: Arial; text-align: center; padding: 50px;">
        <h2>Invalid Order ID</h2>
        <p>Please provide a valid order ID.</p>
        <a href="orders.php">Back to Orders</a>
    </body></html>
    <?php
    exit;
}

// Initialize variables
$order = null;
$orderItems = [];
$errorMessage = '';

// Get order details with enhanced error handling
try {
    // Check database connection
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Database connection not available');
    }
    
    // Try different query approaches based on available columns
    $queries = [
        // Full query with all user details
        "SELECT o.*, u.username, u.email, u.full_name
         FROM orders o
         JOIN users u ON o.user_id = u.id
         WHERE o.id = ? AND o.user_id = ?",
        
        // Fallback without full_name if column doesn't exist
        "SELECT o.*, u.username, u.email, u.username as full_name
         FROM orders o
         JOIN users u ON o.user_id = u.id
         WHERE o.id = ? AND o.user_id = ?",
        
        // Basic query with just order data
        "SELECT o.*, '' as username, '' as email, '' as full_name
         FROM orders o
         WHERE o.id = ? AND o.user_id = ?"
    ];
    
    $order = null;
    foreach ($queries as $index => $sql) {
        try {
            $stmt = $pdo->prepare($sql);
            if ($stmt && $stmt->execute([$orderId, $userId])) {
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($order) {
                    error_log("Print order: Query $index succeeded");
                    break;
                }
            }
        } catch (Exception $e) {
            error_log("Print order: Query $index failed: " . $e->getMessage());
            continue;
        }
    }
    
    if (!$order) {
        // Check if order exists at all
        try {
            $checkStmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
            if ($checkStmt && $checkStmt->execute([$orderId])) {
                $checkOrder = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($checkOrder) {
                    $errorMessage = 'Access denied - This order belongs to another user';
                } else {
                    $errorMessage = 'Order not found in database';
                }
            } else {
                $errorMessage = 'Unable to verify order existence';
            }
        } catch (Exception $e) {
            $errorMessage = 'Database error during order verification';
        }
    } else {
        // Get order items with fallback queries
        $itemQueries = [
            // Full query with product details
            "SELECT oi.*, p.name, p.image_url, p.description
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?
             ORDER BY oi.id",
            
            // Fallback without product description
            "SELECT oi.*, p.name, p.image_url, '' as description
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?
             ORDER BY oi.id",
            
            // Basic query without product join
            "SELECT oi.*, 'Product Name Not Available' as name, '' as image_url, '' as description
             FROM order_items oi
             WHERE oi.order_id = ?
             ORDER BY oi.id"
        ];
        
        foreach ($itemQueries as $index => $sql) {
            try {
                $stmt = $pdo->prepare($sql);
                if ($stmt && $stmt->execute([$orderId])) {
                    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("Print order: Items query $index succeeded, found " . count($orderItems) . " items");
                    break;
                }
            } catch (Exception $e) {
                error_log("Print order: Items query $index failed: " . $e->getMessage());
                continue;
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Print order: Critical error: " . $e->getMessage());
    $errorMessage = 'Unable to load order details due to system error';
}

// If we have an error, show error page
if (!$order || $errorMessage) {
    ?>
    <!DOCTYPE html>
    <html><head><title>Order Error</title></head>
    <body style="font-family: Arial; text-align: center; padding: 50px;">
        <h2>Unable to Load Order</h2>
        <p><?php echo htmlspecialchars($errorMessage ?: 'Order not found or access denied'); ?></p>
        <p><strong>Order ID:</strong> <?php echo $orderId; ?></p>
        <p><strong>User ID:</strong> <?php echo $userId; ?></p>
        <div style="margin-top: 30px;">
            <a href="orders.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Back to Orders</a>
            <button onclick="window.close()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin-left: 10px;">Close Window</button>
        </div>
    </body></html>
    <?php
    exit;
}

// Calculate totals with safety checks
$subtotal = 0;
if (!empty($orderItems)) {
    foreach ($orderItems as $item) {
        $quantity = isset($item['quantity']) ? (float)$item['quantity'] : 0;
        $price = isset($item['price']) ? (float)$item['price'] : 0;
        $subtotal += $quantity * $price;
    }
}

$shipping = 0; // No shipping in your structure
$tax = 0; // No tax in your structure
$discount = 0; // No discount in your structure
$total = isset($order['total_amount']) ? (float)$order['total_amount'] : $subtotal;

// Helper function for status display with safe defaults
function getStatusDisplay($status) {
    $status = $status ?: 'unknown';
    $statuses = [
        'pending' => ['text' => 'Pending Review', 'color' => '#ffc107'],
        'confirmed' => ['text' => 'Payment Confirmed', 'color' => '#17a2b8'],
        'processing' => ['text' => 'Processing', 'color' => '#007bff'],
        'shipped' => ['text' => 'Shipped', 'color' => '#6c757d'],
        'delivered' => ['text' => 'Delivered', 'color' => '#28a745'],
        'completed' => ['text' => 'Completed', 'color' => '#28a745'],
        'cancelled' => ['text' => 'Cancelled', 'color' => '#dc3545']
    ];
    
    return $statuses[$status] ?? ['text' => ucfirst($status), 'color' => '#6c757d'];
}

// Safe data extraction with defaults
$orderNumber = isset($order['order_number']) ? $order['order_number'] : 'N/A';
$orderStatus = isset($order['status']) ? $order['status'] : 'unknown';
$createdAt = isset($order['created_at']) ? $order['created_at'] : date('Y-m-d H:i:s');
$customerName = '';

// Try different name combinations
if (!empty($order['full_name'])) {
    $customerName = $order['full_name'];
} elseif (!empty($order['username'])) {
    $customerName = $order['username'];
} else {
    $customerName = 'Customer #' . $userId;
}

$customerEmail = isset($order['email']) ? $order['email'] : 'Not available';
$paymentMethod = isset($order['payment_method']) ? ucfirst($order['payment_method']) : 'Not specified';
$shippingAddress = isset($order['shipping_address']) ? $order['shipping_address'] : '';
$adminNotes = isset($order['admin_notes']) ? $order['admin_notes'] : '';
$statusUpdatedAt = isset($order['status_updated_at']) ? $order['status_updated_at'] : $createdAt;

$statusDisplay = getStatusDisplay($orderStatus);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo htmlspecialchars($orderNumber); ?> - UrbanStitch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
            font-size: 14px;
        }

        /* Print styles */
        @media print {
            body {
                margin: 0;
                padding: 20px;
                font-size: 12px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            .print-container {
                margin: 0;
                padding: 0;
                box-shadow: none;
                border: none;
            }
            
            .invoice-header {
                border-bottom: 2px solid #000;
                margin-bottom: 20px;
            }
            
            .status-badge {
                border: 1px solid #333 !important;
                color: #000 !important;
                background: #fff !important;
            }
        }

        /* Screen styles */
        @media screen {
            body {
                background: #f5f5f5;
                padding: 20px;
            }
            
            .print-actions {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                display: flex;
                gap: 10px;
            }
            
            .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s;
            }
            
            .btn-print {
                background: #007bff;
                color: white;
            }
            
            .btn-print:hover {
                background: #0056b3;
            }
            
            .btn-close {
                background: #6c757d;
                color: white;
            }
            
            .btn-close:hover {
                background: #545b62;
            }
        }

        /* Invoice styles */
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .company-info h1 {
            font-size: 32px;
            font-weight: 900;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .company-info h1 span {
            color: #00cc00;
        }

        .company-tagline {
            font-size: 14px;
            color: #666;
            font-style: italic;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .invoice-number {
            font-size: 18px;
            color: #666;
            margin-bottom: 5px;
        }

        .invoice-date {
            font-size: 14px;
            color: #999;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 10px;
            border: 1px solid;
        }

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .detail-section h3 {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-item {
            margin-bottom: 8px;
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .detail-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }

        .items-table th {
            background: #f8f9fa;
            padding: 15px 10px;
            text-align: left;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
        }

        .items-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            background: #f8f9fa;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .item-description {
            font-size: 12px;
            color: #666;
            line-height: 1.4;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-weight-bold {
            font-weight: 700;
        }

        .totals-section {
            margin-left: auto;
            width: 300px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .total-row:last-child {
            border-bottom: none;
            border-top: 2px solid #333;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: 900;
        }

        .total-row.final {
            color: #00cc00;
        }

        .footer-info {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #666;
            font-size: 12px;
        }

        .footer-info p {
            margin-bottom: 5px;
        }

        .notes-section {
            margin-top: 30px;
            padding: 20px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
        }

        .notes-title {
            font-weight: 700;
            color: #856404;
            margin-bottom: 10px;
        }

        .notes-content {
            color: #856404;
            line-height: 1.5;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .print-container {
                padding: 20px;
                margin: 10px;
            }
            
            .invoice-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .order-details {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .items-table {
                font-size: 12px;
            }
            
            .items-table th,
            .items-table td {
                padding: 8px 5px;
            }
            
            .totals-section {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Print Actions (only visible on screen) -->
    <div class="print-actions no-print">
        <button class="btn btn-print" onclick="window.print()">
            <i class="fas fa-print"></i>
            Print Invoice
        </button>
        <button class="btn btn-close" onclick="window.close()">
            <i class="fas fa-times"></i>
            Close
        </button>
    </div>

    <div class="print-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1>Urban<span>Stitch</span></h1>
                <div class="company-tagline">Street Fashion & Urban Culture</div>
                <div style="margin-top: 10px; font-size: 12px; color: #666;">
                    <div><i class="fas fa-map-marker-alt"></i> Philippines</div>
                    <div><i class="fas fa-envelope"></i> orders@urbanstitch.com</div>
                    <div><i class="fas fa-phone"></i> +63 XXX XXX XXXX</div>
                </div>
            </div>
            <div class="invoice-title">
                <h2>INVOICE</h2>
                <div class="invoice-number">Order #<?php echo htmlspecialchars($orderNumber); ?></div>
                <div class="invoice-date"><?php echo date('F j, Y', strtotime($createdAt)); ?></div>
                <div class="status-badge" style="background-color: <?php echo $statusDisplay['color']; ?>; border-color: <?php echo $statusDisplay['color']; ?>; color: white;">
                    <?php echo $statusDisplay['text']; ?>
                </div>
            </div>
        </div>

        <!-- Order Details -->
        <div class="order-details">
            <div class="detail-section">
                <h3>Bill To</h3>
                <div class="detail-item">
                    <span class="detail-label">Customer Name</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customerName); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customerEmail); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Customer ID</span>
                    <span class="detail-value">#<?php echo str_pad($userId, 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <?php if (!empty($shippingAddress)): ?>
                <div class="detail-item">
                    <span class="detail-label">Shipping Address</span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($shippingAddress)); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="detail-section">
                <h3>Order Information</h3>
                <div class="detail-item">
                    <span class="detail-label">Order Date</span>
                    <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($createdAt)); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Payment Method</span>
                    <span class="detail-value"><?php echo htmlspecialchars($paymentMethod); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Order Status</span>
                    <span class="detail-value"><?php echo $statusDisplay['text']; ?></span>
                </div>
                <?php if (!empty($statusUpdatedAt) && $statusUpdatedAt !== $createdAt): ?>
                <div class="detail-item">
                    <span class="detail-label">Last Updated</span>
                    <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($statusUpdatedAt)); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Image</th>
                    <th>Item Description</th>
                    <th style="width: 80px;" class="text-center">Qty</th>
                    <th style="width: 100px;" class="text-right">Unit Price</th>
                    <th style="width: 100px;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orderItems)): ?>
                <?php foreach ($orderItems as $item): 
                    $itemName = isset($item['name']) ? $item['name'] : 'Product Name Not Available';
                    $itemImage = isset($item['image_url']) ? $item['image_url'] : '';
                    $itemDescription = isset($item['description']) ? $item['description'] : '';
                    $itemQuantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                    $itemPrice = isset($item['price']) ? (float)$item['price'] : 0;
                    $itemSize = isset($item['selected_size']) ? $item['selected_size'] : '';
                ?>
                <tr>
                    <td class="text-center">
                        <img src="<?php echo htmlspecialchars($itemImage ?: 'https://via.placeholder.com/50x50?text=No+Image'); ?>" 
                             alt="<?php echo htmlspecialchars($itemName); ?>" 
                             class="item-image"
                             onerror="this.src='https://via.placeholder.com/50x50?text=No+Image'">
                    </td>
                    <td>
                        <div class="item-name"><?php echo htmlspecialchars($itemName); ?></div>
                        <?php if (!empty($itemDescription)): ?>
                        <div class="item-description"><?php echo htmlspecialchars(substr($itemDescription, 0, 100)) . (strlen($itemDescription) > 100 ? '...' : ''); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($itemSize)): ?>
                        <div class="item-description">Size: <?php echo htmlspecialchars($itemSize); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center font-weight-bold"><?php echo $itemQuantity; ?></td>
                    <td class="text-right">₱<?php echo number_format($itemPrice, 2); ?></td>
                    <td class="text-right font-weight-bold">₱<?php echo number_format($itemQuantity * $itemPrice, 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center" style="padding: 40px; color: #666;">
                        <i class="fas fa-box-open" style="font-size: 48px; display: block; margin-bottom: 16px; opacity: 0.5;"></i>
                        No items found for this order
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Order Totals -->
        <div class="totals-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>₱<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php if ($shipping > 0): ?>
            <div class="total-row">
                <span>Shipping:</span>
                <span>₱<?php echo number_format($shipping, 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($tax > 0): ?>
            <div class="total-row">
                <span>Tax:</span>
                <span>₱<?php echo number_format($tax, 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($discount > 0): ?>
            <div class="total-row" style="color: #00cc00;">
                <span>Discount:</span>
                <span>-₱<?php echo number_format($discount, 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row final">
                <span>TOTAL:</span>
                <span>₱<?php echo number_format($total, 2); ?></span>
            </div>
        </div>

        <!-- Order Notes (if any) -->
        <?php if (!empty($adminNotes)): ?>
        <div class="notes-section">
            <div class="notes-title">Order Notes:</div>
            <div class="notes-content"><?php echo nl2br(htmlspecialchars($adminNotes)); ?></div>
        </div>
        <?php endif; ?>

        <!-- Footer Information -->
        <div class="footer-info">
            <p><strong>Thank you for shopping with UrbanStitch!</strong></p>
            <p>For questions about your order, please contact us at orders@urbanstitch.com</p>
            <p>This is a computer-generated invoice and does not require a signature.</p>
            <p style="margin-top: 15px; font-size: 10px; color: #999;">
                Invoice generated on <?php echo date('F j, Y g:i A'); ?> | 
                UrbanStitch &copy; <?php echo date('Y'); ?> | 
                All Rights Reserved
            </p>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); };
        
        // Handle print button click
        function printInvoice() {
            window.print();
        }
        
        // Handle close button click
        function closeWindow() {
            if (window.opener) {
                window.close();
            } else {
                window.history.back();
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            if (e.key === 'Escape') {
                closeWindow();
            }
        });
    </script>
</body>
</html>