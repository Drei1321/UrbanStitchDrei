<?php
// UrbanStitch E-commerce - Enhanced Product Detail Page (FIXED to match index.php functionality)
require_once 'config.php';
require_once 'xml_operations.php';

// FIXED: Enhanced AJAX requests handling with proper JSON responses (EXACTLY matching index.php)
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_GET['ajax'];
    
    if (!isLoggedIn()) {
        echo json_encode(['count' => 0, 'total' => 0]);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    switch ($action) {
        case 'get_cart_count':
            try {
                $stmt = $pdo->prepare("SELECT SUM(quantity) as total_count FROM cart_items WHERE user_id = ?");
                $stmt->execute([$userId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['count' => (int)($result['total_count'] ?? 0)]);
            } catch (Exception $e) {
                echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
            }
            exit;
            
        case 'get_wishlist_count':
            try {
                $wishlistItems = $db->getWishlistItems($userId);
                $count = count($wishlistItems);
                echo json_encode(['count' => $count]);
            } catch (Exception $e) {
                echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
            }
            exit;
            
        case 'get_cart_total':
            try {
                $stmt = $pdo->prepare("
                    SELECT SUM(
                        (p.price + COALESCE(ps.price_adjustment, 0)) * ci.quantity
                    ) as total_amount
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.id
                    LEFT JOIN product_sizes ps ON p.id = ps.product_id AND ci.selected_size = ps.size_code
                    WHERE ci.user_id = ?
                ");
                $stmt->execute([$userId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['total' => (float)($result['total_amount'] ?? 0)]);
            } catch (Exception $e) {
                echo json_encode(['total' => 0, 'error' => $e->getMessage()]);
            }
            exit;
            
        case 'get_product_sizes':
            $productId = (int)$_GET['product_id'];
            
            try {
                $stmt = $pdo->prepare("
                    SELECT ps.*, pt.size_type 
                    FROM product_sizes ps 
                    JOIN products p ON ps.product_id = p.id
                    LEFT JOIN product_types pt ON p.product_type_id = pt.id
                    WHERE ps.product_id = ? AND ps.is_available = 1 
                    ORDER BY 
                        CASE 
                            WHEN ps.size_code IN ('XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL') THEN 
                                FIELD(ps.size_code, 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL')
                            WHEN ps.size_code REGEXP '^[0-9]+(\.[0-9]+)?$' THEN 
                                CAST(ps.size_code AS DECIMAL(4,1))
                            ELSE 999
                        END
                ");
                $stmt->execute([$productId]);
                $sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['sizes' => $sizes]);
            } catch (Exception $e) {
                echo json_encode(['sizes' => [], 'error' => $e->getMessage()]);
            }
            exit;
    }
}

// Get product ID from URL
$productId = (int)($_GET['id'] ?? 0);

// FIXED: Enhanced POST requests with proper validation and stock checking (EXACTLY matching index.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    // Check if user is logged in for cart/wishlist actions
    if (in_array($action, ['add_to_cart', 'add_to_wishlist', 'remove_from_cart', 'remove_from_wishlist', 'update_quantity'])) {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'redirect' => 'login.php', 'message' => 'Please login to continue']);
            exit;
        }
    }
    
    $userId = $_SESSION['user_id'] ?? null;
    
    switch ($action) {
        case 'add_to_cart':
            $productId = (int)$_POST['product_id'];
            $quantity = (int)($_POST['quantity'] ?? 1);
            $selectedSize = $_POST['selected_size'] ?? null;
            
            try {
                // FIXED: Enhanced product validation with size checking
                $stmt = $pdo->prepare("
                    SELECT p.*, COUNT(ps.id) as size_count 
                    FROM products p 
                    LEFT JOIN product_sizes ps ON p.id = ps.product_id AND ps.is_available = 1
                    WHERE p.id = ? 
                    GROUP BY p.id
                ");
                $stmt->execute([$productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    echo json_encode(['success' => false, 'message' => 'Product not found']);
                    exit;
                }
                
                // FIXED: Check if product requires size selection
                // FIXED: Check if product requires size selection (auto-select for accessories and single-size)
if ($product['size_count'] > 0 && empty($selectedSize)) {
    // Get category and available sizes for auto-selection logic
    $stmt = $pdo->prepare("
        SELECT c.name as category_name, ps.size_code 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN product_sizes ps ON p.id = ps.product_id AND ps.is_available = 1
        WHERE p.id = ?
        ORDER BY ps.size_code
    ");
    $stmt->execute([$productId]);
    $productInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($productInfo)) {
        $categoryName = strtolower($productInfo[0]['category_name'] ?? '');
        $availableSizes = array_unique(array_column($productInfo, 'size_code'));
        $availableSizes = array_filter($availableSizes); // Remove empty values
        
        // Auto-select size if it's accessories or only one size available
        if ($categoryName === 'accessories' || count($availableSizes) === 1) {
            $selectedSize = reset($availableSizes); // Get first available size
            error_log("Auto-selected size '{$selectedSize}' for product {$productId} (category: {$categoryName}, available sizes: " . count($availableSizes) . ")");
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Please select a size for this product', 
                'requires_size' => true,
                'product_id' => $productId
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Please select a size for this product', 
            'requires_size' => true,
            'product_id' => $productId
        ]);
        exit;
    }
}
                
                // FIXED: Validate size availability and stock if size was selected
                if (!empty($selectedSize)) {
                    $stmt = $pdo->prepare("
                        SELECT stock_quantity, size_name 
                        FROM product_sizes 
                        WHERE product_id = ? AND size_code = ? AND is_available = 1
                    ");
                    $stmt->execute([$productId, $selectedSize]);
                    $sizeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$sizeInfo) {
                        echo json_encode(['success' => false, 'message' => 'Selected size is not available']);
                        exit;
                    }
                    
                    // FIXED: Check current cart quantity for this size
                    $stmt = $pdo->prepare("SELECT SUM(quantity) as current_quantity FROM cart_items WHERE user_id = ? AND product_id = ? AND selected_size = ?");
                    $stmt->execute([$userId, $productId, $selectedSize]);
                    $currentCart = $stmt->fetch(PDO::FETCH_ASSOC);
                    $currentQuantity = (int)($currentCart['current_quantity'] ?? 0);
                    
                    if (($currentQuantity + $quantity) > $sizeInfo['stock_quantity']) {
                        echo json_encode([
                            'success' => false, 
                            'message' => "Not enough stock. Available: {$sizeInfo['stock_quantity']}, In cart: {$currentQuantity}"
                        ]);
                        exit;
                    }
                }
                
                // FIXED: Check if product with same size already exists in cart
                $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE user_id = ? AND product_id = ? AND (selected_size = ? OR (selected_size IS NULL AND ? IS NULL))");
                $stmt->execute([$userId, $productId, $selectedSize, $selectedSize]);
                $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingItem) {
                    // Update quantity
                    $newQuantity = $existingItem['quantity'] + $quantity;
                    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$newQuantity, $existingItem['id']])) {
                        echo json_encode(['success' => true, 'message' => 'Product quantity updated in cart']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
                    }
                } else {
                    // Add new item to cart
                    $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity, selected_size, created_at) VALUES (?, ?, ?, ?, NOW())");
                    if ($stmt->execute([$userId, $productId, $quantity, $selectedSize])) {
                        $message = 'Product added to cart';
                        if ($selectedSize) {
                            $message .= ' (Size: ' . $selectedSize . ')';
                        }
                        echo json_encode(['success' => true, 'message' => $message]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
                    }
                }
                
            } catch (Exception $e) {
                error_log("Cart add error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error occurred']);
            }
            exit;
            
        case 'add_to_wishlist':
            $productId = (int)$_POST['product_id'];
            try {
                if ($db->addToWishlist($userId, $productId)) {
                    echo json_encode(['success' => true, 'message' => 'Product added to wishlist']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Product already in wishlist']);
                }
            } catch (Exception $e) {
                error_log("Wishlist add error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist']);
            }
            exit;
            
        case 'remove_from_cart':
            $productId = (int)$_POST['product_id'];
            $selectedSize = $_POST['selected_size'] ?? null;
            
            try {
                if ($selectedSize) {
                    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ? AND selected_size = ?");
                    $success = $stmt->execute([$userId, $productId, $selectedSize]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ? AND (selected_size IS NULL OR selected_size = '')");
                    $success = $stmt->execute([$userId, $productId]);
                }
                
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Product removed from cart']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to remove product']);
                }
            } catch (Exception $e) {
                error_log("Cart remove error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to remove product']);
            }
            exit;
            
        case 'remove_from_wishlist':
            $productId = (int)$_POST['product_id'];
            try {
                if ($db->removeFromWishlist($userId, $productId)) {
                    echo json_encode(['success' => true, 'message' => 'Product removed from wishlist']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to remove product']);
                }
            } catch (Exception $e) {
                error_log("Wishlist remove error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to remove from wishlist']);
            }
            exit;
            
        case 'update_quantity':
            $cartItemId = (int)$_POST['cart_item_id'];
            $quantity = (int)$_POST['quantity'];
            
            try {
                if ($quantity <= 0) {
                    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
                    $success = $stmt->execute([$cartItemId, $userId]);
                    
                    if ($success) {
                        echo json_encode(['success' => true, 'message' => 'Product removed from cart']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to remove product']);
                    }
                } else {
                    // FIXED: Check stock before updating quantity
                    $stmt = $pdo->prepare("
                        SELECT ci.*, ps.stock_quantity 
                        FROM cart_items ci
                        LEFT JOIN product_sizes ps ON ci.product_id = ps.product_id AND ci.selected_size = ps.size_code
                        WHERE ci.id = ? AND ci.user_id = ?
                    ");
                    $stmt->execute([$cartItemId, $userId]);
                    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$cartItem) {
                        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                        exit;
                    }
                    
                    // Check stock if item has size
                    if ($cartItem['selected_size'] && $cartItem['stock_quantity'] && $quantity > $cartItem['stock_quantity']) {
                        echo json_encode([
                            'success' => false, 
                            'message' => "Not enough stock. Available: {$cartItem['stock_quantity']}"
                        ]);
                        exit;
                    }
                    
                    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                    $success = $stmt->execute([$quantity, $cartItemId, $userId]);
                    
                    if ($success) {
                        echo json_encode(['success' => true, 'message' => 'Quantity updated']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update quantity']);
                    }
                }
            } catch (Exception $e) {
                error_log("Quantity update error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to update quantity']);
            }
            exit;
    }
}

// Get product from database
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               c.name as category_name,
               pt.name as product_type_name, 
               pt.size_type,
               COUNT(ps.id) as size_count,
               GROUP_CONCAT(
                   CONCAT(ps.size_code, ':', ps.size_name, ':', ps.stock_quantity) 
                   ORDER BY 
                       CASE 
                           WHEN ps.size_code IN ('XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL') THEN 
                               FIELD(ps.size_code, 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL')
                           WHEN ps.size_code REGEXP '^[0-9]+(\.[0-9]+)?$' THEN 
                               CAST(ps.size_code AS DECIMAL(4,1))
                           ELSE 999
                       END
                   SEPARATOR '|'
               ) as available_sizes
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN product_types pt ON p.product_type_id = pt.id
        LEFT JOIN product_sizes ps ON p.id = ps.product_id AND ps.is_available = 1
        WHERE p.id = ?
        GROUP BY p.id
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading product: " . $e->getMessage());
    $product = [];
}

// Check if product exists
if (!$product) {
    header('Location: index.php');
    exit;
}

// Process size data for each product
$product['sizes'] = [];
if (!empty($product['available_sizes'])) {
    $sizeData = explode('|', $product['available_sizes']);
    foreach ($sizeData as $size) {
        $sizeParts = explode(':', $size);
        if (count($sizeParts) === 3) {
            $product['sizes'][] = [
                'code' => $sizeParts[0],
                'name' => $sizeParts[1],
                'stock' => (int)$sizeParts[2]
            ];
        }
    }
}

// Calculate cart and wishlist counts
$cartCount = 0;
$cartTotal = 0;
$wishlistCount = 0;
$isInWishlist = false;
$cartItems = [];
$wishlistItems = [];

if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("
            SELECT ci.*, p.name, p.price, p.image_url, p.original_price,
                   ps.size_name, ps.price_adjustment
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            LEFT JOIN product_sizes ps ON p.id = ps.product_id AND ci.selected_size = ps.size_code
            WHERE ci.user_id = ?
            ORDER BY ci.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cartItems as $item) {
            $cartCount += $item['quantity'];
            $itemPrice = $item['price'] + ($item['price_adjustment'] ?? 0);
            $cartTotal += $itemPrice * $item['quantity'];
        }
        
        $wishlistItems = $db->getWishlistItems($_SESSION['user_id']);
        $wishlistCount = count($wishlistItems);
        $isInWishlist = $db->isInWishlist($_SESSION['user_id'], $productId);
    } catch (Exception $e) {
        error_log("Error loading cart/wishlist: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - UrbanStitch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header (same as index.php) -->
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
                    <li><a href="index.php?category=winter-wear" class="nav-link">WINTER WEAR</a></li>
                    <li><a href="blog.php" class="nav-link">BLOG</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Product Detail Content -->
    <div style="max-width: 1200px; margin: 0 auto; padding: 32px 24px;">
        <a href="index.php" style="display: inline-flex; align-items: center; color: #666; text-decoration: none; margin-bottom: 24px; transition: color 0.2s;" 
           onmouseover="this.style.color='#00ff00'" onmouseout="this.style.color='#666'">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
            Back to Products
        </a>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: start;">
            <!-- Product Image -->
            <div style="aspect-ratio: 1; overflow: hidden; border-radius: 12px; background: white; box-shadow: 0 8px 24px rgba(0,0,0,0.1); position: relative;">
                <?php if (isset($product['is_featured']) && $product['is_featured']): ?>
                <span style="position: absolute; top: 16px; left: 16px; background: #00ff00; color: #1a1a1a; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; z-index: 2;">FEATURED</span>
                <?php endif; ?>
                <?php if (isset($product['is_trending']) && $product['is_trending']): ?>
                <span style="position: absolute; top: <?php echo (isset($product['is_featured']) && $product['is_featured']) ? '50px' : '16px'; ?>; left: 16px; background: #ff6b35; color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; z-index: 2;">TRENDING</span>
                <?php endif; ?>
                <?php if (isset($product['original_price']) && $product['original_price'] > 0 && $product['original_price'] > $product['price']): ?>
                <span style="position: absolute; top: 16px; right: 16px; background: #ff4444; color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; z-index: 2;">
                    <?php echo round((($product['original_price'] - $product['price']) / $product['original_price']) * 100); ?>% OFF
                </span>
                <?php endif; ?>
                <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     style="width: 100%; height: 100%; object-fit: cover;">
            </div>

            <!-- Product Details -->
            <div class="product-card enhanced-product-card" 
                 data-product-id="<?php echo $product['id']; ?>"
                 data-has-sizes="<?php echo (!empty($product['sizes']) && count($product['sizes']) > 0) ? 'true' : 'false'; ?>"
                 style="padding: 24px 0;">
                
                <h1 style="font-size: 32px; font-weight: 900; color: #1a1a1a; margin-bottom: 16px;">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>
                
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 24px;">
                    <div style="display: flex; gap: 2px;">
                        <?php 
                        $rating = isset($product['rating']) ? (int)$product['rating'] : 0;
                        for ($i = 0; $i < $rating; $i++): ?>
                        <i class="fas fa-star" style="color: #fbbf24;"></i>
                        <?php endfor; 
                        for ($i = $rating; $i < 5; $i++): ?>
                        <i class="far fa-star" style="color: #fbbf24;"></i>
                        <?php endfor; ?>
                    </div>
                    <span style="color: #666; font-size: 14px;">(<?php echo isset($product['reviews_count']) ? $product['reviews_count'] : 0; ?> reviews)</span>
                    <span style="background: #f0f0f0; color: #666; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">
                        <?php echo isset($product['stock_quantity']) ? $product['stock_quantity'] : 0; ?> in stock
                    </span>
                </div>

                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
                    <span style="font-size: 28px; font-weight: 900; color: #00ff00;">
                        ₱<?php echo number_format($product['price'], 2); ?>
                    </span>
                    <?php if (isset($product['original_price']) && $product['original_price'] > 0 && $product['original_price'] > $product['price']): ?>
                    <span style="font-size: 20px; color: #666; text-decoration: line-through;">
                        ₱<?php echo number_format($product['original_price'], 2); ?>
                    </span>
                    <span style="background: #ff6b35; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: 700;">
                        <?php echo round((($product['original_price'] - $product['price']) / $product['original_price']) * 100); ?>% OFF
                    </span>
                    <?php endif; ?>
                </div>

                <div style="margin-bottom: 24px;">
                    <?php if (isset($product['is_featured']) && $product['is_featured']): ?>
                    <span style="background: #00ff00; color: #1a1a1a; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; margin-right: 8px;">
                        FEATURED
                    </span>
                    <?php endif; ?>
                    <?php if (isset($product['is_trending']) && $product['is_trending']): ?>
                    <span style="background: #ff6b35; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; margin-right: 8px;">
                        TRENDING
                    </span>
                    <?php endif; ?>
                    <span style="background: #1a1a1a; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 700;">
                        <?php echo strtoupper($product['category_name'] ?? 'UNCATEGORIZED'); ?>
                    </span>
                </div>

                <p style="color: #666; line-height: 1.6; margin-bottom: 32px;">
                    <?php echo htmlspecialchars($product['description']); ?>
                </p>

                <!-- FIXED: SIZE SELECTION SECTION - EXACTLY MATCHING index.php -->
                <?php if (!empty($product['sizes']) && count($product['sizes']) > 0): ?>
                <div class="size-selector-uniqlo" style="margin: 24px 0; padding: 16px; background: #fafafa; border-radius: 8px; border: 1px solid #e0e0e0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <label class="size-label" style="font-size: 16px; font-weight: 600; color: #333; margin: 0;">Size:</label>
                        <span class="selected-size-display" style="font-size: 14px; color: #666; font-weight: 500;"></span>
                    </div>
                    
                    <div class="size-options-uniqlo" style="display: flex; flex-wrap: wrap; gap: 12px;">
                        <?php foreach ($product['sizes'] as $size): ?>
                        <button type="button" 
                                class="size-option-uniqlo <?php echo $size['stock'] <= 0 ? 'out-of-stock' : ($size['stock'] <= 5 ? 'low-stock' : ''); ?>"
                                data-size-code="<?php echo htmlspecialchars($size['code']); ?>"
                                data-size-name="<?php echo htmlspecialchars($size['name']); ?>"
                                data-stock="<?php echo $size['stock']; ?>"
                                data-product-id="<?php echo $product['id']; ?>"
                                <?php echo $size['stock'] <= 0 ? 'disabled' : ''; ?>
                                style="
                                    min-width: 50px;
                                    height: 50px;
                                    border: 2px solid #ddd;
                                    background: white;
                                    border-radius: 6px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 16px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    transition: all 0.2s ease;
                                    color: #333;
                                    position: relative;
                                    <?php if ($size['stock'] <= 0): ?>
                                    background: #f5f5f5;
                                    color: #ccc;
                                    cursor: not-allowed;
                                    text-decoration: line-through;
                                    <?php endif; ?>
                                ">
                            <?php echo htmlspecialchars($size['code']); ?>
                            
                            <?php if ($size['stock'] > 0 && $size['stock'] <= 5): ?>
                            <span style="position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; background: #ff6b35; border-radius: 50%; border: 1px solid white;"></span>
                            <?php endif; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (array_filter($product['sizes'], function($s) { return $s['stock'] <= 5 && $s['stock'] > 0; })): ?>
                    <div style="margin-top: 8px; font-size: 12px; color: #ff6b35; display: flex; align-items: center; gap: 6px;">
                        <span style="width: 8px; height: 8px; background: #ff6b35; border-radius: 50%;"></span>
                        Low stock items
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Size Required Warning -->
                <div class="size-required-warning" style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px 16px; border-radius: 6px; font-size: 14px; margin: 12px 0; display: none; animation: fadeIn 0.3s ease;">
                    <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                    Please select a size before adding to cart
                </div>
                <?php endif; ?>

                <div style="display: flex; gap: 16px; margin-bottom: 32px;">
                    <?php if (isLoggedIn()): ?>
                    <button class="add-to-cart-btn-uniqlo"
                            data-product-id="<?php echo $product['id']; ?>"
                            style="
                                flex: 1;
                                padding: 16px 24px; 
                                background: linear-gradient(135deg, #1a1a1a, #2d2d2d); 
                                color: white; 
                                border: none; 
                                border-radius: 8px; 
                                font-weight: 600; 
                                cursor: pointer; 
                                transition: all 0.3s; 
                                display: flex; 
                                align-items: center; 
                                justify-content: center; 
                                gap: 12px;
                                font-size: 16px;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            "
                            onmouseover="this.style.background='linear-gradient(135deg, #00ff00, #00cc00)'; this.style.color='#1a1a1a'; this.style.transform='translateY(-2px)';"
                            onmouseout="this.style.background='linear-gradient(135deg, #1a1a1a, #2d2d2d)'; this.style.color='white'; this.style.transform='translateY(0)';">
                        <i class="fas fa-shopping-cart"></i>
                        Add to Cart
                    </button>
                    
                    <button class="wishlist-btn-uniqlo"
                            data-product-id="<?php echo $product['id']; ?>"
                            style="
                                padding: 16px 20px;
                                background: <?php echo $isInWishlist ? '#ff4444' : 'rgba(255,255,255,0.9)'; ?>;
                                border: 2px solid #ff6b35;
                                border-radius: 8px;
                                color: <?php echo $isInWishlist ? 'white' : '#ff6b35'; ?>;
                                cursor: pointer;
                                transition: all 0.3s;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            "
                            onmouseover="this.style.background='#ff6b35'; this.style.color='white';"
                            onmouseout="this.style.background='<?php echo $isInWishlist ? '#ff4444' : 'rgba(255,255,255,0.9)'; ?>'; this.style.color='<?php echo $isInWishlist ? 'white' : '#ff6b35'; ?>';">
                        <i class="fas fa-heart" style="font-size: 18px;"></i>
                    </button>
                    <?php else: ?>
                    <button onclick="window.location.href='login.php'" style="flex: 1; padding: 16px; background: linear-gradient(135deg, #666, #888); color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 16px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-user-lock"></i>
                        Login to Purchase
                    </button>
                    
                    <button onclick="window.location.href='login.php'" style="padding: 16px; background: #666; color: white; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                        <i class="fas fa-heart"></i>
                    </button>
                    <?php endif; ?>
                </div>

                <div style="border-top: 1px solid #eee; padding-top: 24px; color: #666; font-size: 14px; line-height: 1.6;">
                    <p style="margin-bottom: 8px;"><strong style="color: #1a1a1a;">Free shipping</strong> on orders over ₱2,500</p>
                    <p style="margin-bottom: 8px;"><strong style="color: #1a1a1a;">Easy returns</strong> within 30 days</p>
                    <p><strong style="color: #1a1a1a;">Secure checkout</strong> with SSL encryption</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- FIXED: Enhanced Cart Sidebar (EXACTLY matching index.php) -->
    <div class="enhanced-sidebar" id="cartSidebar">
        <div class="sidebar-header">
            <h3>
                <div class="header-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                Shopping Cart
            </h3>
            <button class="close-btn" onclick="toggleCart()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="items-container" id="cartItems">
            <?php if (isLoggedIn()): ?>
                <?php if (empty($cartItems)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h4>Your cart is empty</h4>
                    <p>Discover amazing products and start your shopping journey</p>
                    <a href="index.php" class="shop-link">
                        <i class="fas fa-arrow-left"></i>
                        Continue Shopping
                    </a>
                </div>
                <?php else: ?>
                <?php foreach ($cartItems as $item): ?>
                <div class="sidebar-item" data-cart-item-id="<?php echo $item['id']; ?>">
                    <div class="item-content">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            
                            <?php if (!empty($item['selected_size'])): ?>
                            <div class="cart-item-size" style="font-size: 11px; color: #6c757d; background: #f8f9fa; padding: 2px 6px; border-radius: 3px; margin: 4px 0; display: inline-block;">
                                Size: <?php echo htmlspecialchars($item['size_name'] ?? $item['selected_size']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="item-price">
                                <?php $itemPrice = $item['price'] + ($item['price_adjustment'] ?? 0); ?>
                                <span class="price-current">₱<?php echo number_format($itemPrice, 2); ?></span>
                            </div>
                            <div class="quantity-controls">
                                <div class="quantity-group">
                                    <button class="quantity-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                    <span class="quantity-display"><?php echo $item['quantity']; ?></span>
                                    <button class="quantity-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                </div>
                                <div class="item-actions">
                                    <button class="action-btn btn-danger" onclick="removeFromCartWithSize(<?php echo $item['product_id']; ?>, '<?php echo htmlspecialchars($item['selected_size'] ?? ''); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-user-lock"></i>
                </div>
                <h4>Please login to view your cart</h4>
                <p>Sign in to access your saved items and continue shopping</p>
                <a href="login.php" class="shop-link">
                    <i class="fas fa-sign-in-alt"></i>
                    Login Now
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (isLoggedIn() && !empty($cartItems)): ?>
        <div class="sidebar-footer">
            <div class="total-section">
                <span class="total-label">Total:</span>
                <span class="total-amount">₱<?php echo number_format($cartTotal, 2); ?></span>
            </div>
            <button class="checkout-btn" onclick="location.href='checkout.php'">
                <i class="fas fa-lock"></i>
                Secure Checkout
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- FIXED: Enhanced Wishlist Sidebar (EXACTLY matching index.php) -->
    <div class="wishlist-sidebar enhanced-sidebar" id="wishlistSidebar">
        <div class="sidebar-header">
            <h3>
                <div class="header-icon">
                    <i class="fas fa-heart"></i>
                </div>
                Wishlist
            </h3>
            <button class="close-btn" onclick="toggleWishlist()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="items-container">
            <?php if (isLoggedIn()): ?>
                <?php if (empty($wishlistItems)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h4>Your wishlist is empty</h4>
                    <p>Save your favorite items for later and never miss out</p>
                    <a href="index.php" class="shop-link">
                        <i class="fas fa-heart"></i>
                        Discover Products
                    </a>
                </div>
                <?php else: ?>
                <?php foreach ($wishlistItems as $item): ?>
                <div class="sidebar-item">
                    <div class="item-content">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-price">
                               <span class="price-current">₱<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            <div class="item-actions">
                                <button class="action-btn btn-primary" onclick="handleCartAddUniqlo(<?php echo $item['product_id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i>
                                    Add to Cart
                                </button>
                                <button class="action-btn btn-danger" onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-user-lock"></i>
                </div>
                <h4>Please login to view your wishlist</h4>
                <p>Sign in to save your favorite items and access your wishlist</p>
                <a href="login.php" class="shop-link">
                    <i class="fas fa-sign-in-alt"></i>
                    Login Now
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="closeSidebars()"></div>

    <!-- FIXED: Use the EXACT same script.js as index.php -->
    <script src="script.js"></script>

    <!-- FIXED: Enhanced Debug and Testing Script (EXACTLY matching index.php) -->
    <script>
    console.log("🔍 Product Page Debug Info:");
    console.log("Current URL:", window.location.href);
    console.log("User logged in:", <?php echo isLoggedIn() ? 'true' : 'false'; ?>);
    console.log("Cart count:", <?php echo $cartCount; ?>);
    console.log("Product ID:", <?php echo $product['id']; ?>);
    console.log("Has sizes:", <?php echo (!empty($product['sizes']) && count($product['sizes']) > 0) ? 'true' : 'false'; ?>);

    // Test add to cart function
    window.testAddToCart = function(productId, selectedSize = null) {
      console.log("Testing add to cart for product:", productId, "size:", selectedSize);
      
      const formData = new FormData();
      formData.append("action", "add_to_cart");
      formData.append("product_id", productId);
      formData.append("quantity", "1");
      if (selectedSize) {
        formData.append("selected_size", selectedSize);
      }
      
      console.log("Sending test request...");
      
      fetch(window.location.pathname, {
        method: "POST",
        body: formData
      })
      .then(response => {
        console.log("Test response status:", response.status);
        return response.text();
      })
      .then(text => {
        console.log("Test response text:", text);
        try {
          const data = JSON.parse(text);
          console.log("Test response JSON:", data);
        } catch (e) {
          console.log("Response is not JSON");
        }
      })
      .catch(error => {
        console.error("Test error:", error);
      });
    }

    // Add enhanced click handlers as backup
    document.addEventListener('DOMContentLoaded', function() {
      console.log("DOM loaded, setting up enhanced backup handlers");
      
      // Enhanced size selection handlers
      document.querySelectorAll('.size-option-uniqlo').forEach(btn => {
        btn.addEventListener('click', function(e) {
          console.log("Size selected:", this.dataset.sizeCode);
          
          // Remove selected state from all size buttons
          document.querySelectorAll('.size-option-uniqlo').forEach(b => {
            b.style.borderColor = '#ddd';
            b.style.background = 'white';
            b.classList.remove('selected');
          });
          
          // Add selected state to clicked button
          if (!this.disabled) {
            this.style.borderColor = '#00ff00';
            this.style.background = '#f0fff0';
            this.classList.add('selected');
            
            // Update selected size display
            const display = document.querySelector('.selected-size-display');
            if (display) {
              display.textContent = this.dataset.sizeName || this.dataset.sizeCode;
            }
            
            // Hide size warning if visible
            const warning = document.querySelector('.size-required-warning');
            if (warning) {
              warning.style.display = 'none';
            }
          }
        });
      });
      
      // Enhanced add to cart handlers
      document.querySelectorAll('.add-to-cart-btn-uniqlo').forEach(btn => {
        btn.addEventListener('click', function(e) {
          console.log("Enhanced cart handler triggered");
          
          const productId = this.dataset.productId;
          const productCard = this.closest('[data-product-id]');
          const hasSize = productCard?.dataset.hasSizes === 'true';
          
          let selectedSize = null;
          
          if (hasSize) {
            const selectedSizeBtn = document.querySelector('.size-option-uniqlo.selected');
            if (!selectedSizeBtn) {
              // Show size required warning
              const warning = document.querySelector('.size-required-warning');
              if (warning) {
                warning.style.display = 'block';
                setTimeout(() => {
                  if (warning) warning.style.display = 'none';
                }, 3000);
              }
              console.log("Size required but not selected");
              return;
            }
            selectedSize = selectedSizeBtn.dataset.sizeCode;
          }
          
          if (productId) {
            window.testAddToCart(productId, selectedSize);
          }
        });
      });
      
      // Enhanced wishlist handlers
      document.querySelectorAll('.wishlist-btn-uniqlo').forEach(btn => {
        btn.addEventListener('click', function(e) {
          console.log("Enhanced wishlist handler triggered");
          
          const productId = this.dataset.productId;
          if (productId) {
            const formData = new FormData();
            formData.append("action", "add_to_wishlist");
            formData.append("product_id", productId);
            
            fetch(window.location.pathname, {
              method: "POST",
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              console.log("Wishlist response:", data);
              if (data.success) {
                // Update button appearance
                this.style.background = '#ff4444';
                this.style.color = 'white';
              }
            })
            .catch(error => {
              console.error("Wishlist error:", error);
            });
          }
        });
      });
    });
    </script>

    <style>
        @media (max-width: 768px) {
            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
                gap: 24px !important;
            }
            
            h1 {
                font-size: 24px !important;
            }
            
            div[style*="display: flex; gap: 16px"] {
                flex-direction: column !important;
                gap: 12px !important;
            }
        }
        
        /* Enhanced size selector styles */
        .size-option-uniqlo:hover:not(:disabled) {
            border-color: #00ff00 !important;
            background: #f0fff0 !important;
            transform: translateY(-1px);
        }
        
        .size-option-uniqlo.selected {
            border-color: #00ff00 !important;
            background: #f0fff0 !important;
        }
        
        .size-required-warning {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Enhanced cart button styles */
        .add-to-cart-btn-uniqlo:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,255,0,0.3);
        }
        
        .add-to-cart-btn-uniqlo:active {
            transform: translateY(0);
        }
    </style>
</body>
</html>