<?php
// Enhanced UrbanStitch E-commerce - FIXED Categories Page with Proper Database Integration
require_once 'config.php';
require_once 'xml_operations.php';

// Enhanced AJAX requests handling
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
    }
}

// Enhanced POST requests with size support
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
                // Enhanced product validation with size checking
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
                
                // Check if product requires size selection
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
                        $availableSizes = array_filter($availableSizes);
                        
                        // Auto-select size if it's accessories or only one size available
                        if ($categoryName === 'accessories' || count($availableSizes) === 1) {
                            $selectedSize = reset($availableSizes);
                            error_log("Auto-selected size '{$selectedSize}' for product {$productId}");
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
                
                // Validate size availability and stock if size was selected
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
                    
                    // Check current cart quantity for this size
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
                
                // Check if product with same size already exists in cart
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
                    // Check stock before updating quantity
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

// FIXED: Enhanced categories loading with proper deduplication and error handling
try {
    // Get all categories with product counts in a single optimized query
    $categoriesQuery = "
        SELECT 
            c.id,
            c.name,
            c.slug,
            c.description,
            c.icon,
            c.color,
            COUNT(DISTINCT p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id, c.name, c.slug, c.description, c.icon, c.color
        HAVING c.name IS NOT NULL AND c.name != ''
        ORDER BY c.name ASC
    ";
    
    $stmt = $pdo->prepare($categoriesQuery);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug logging
    error_log("Categories loaded from database: " . count($categories));
    foreach ($categories as $cat) {
        error_log("Category: {$cat['name']} (ID: {$cat['id']}, Slug: {$cat['slug']}, Products: {$cat['product_count']})");
    }
    
    // Remove any potential duplicates based on name (just in case)
    $uniqueCategories = [];
    $seenNames = [];
    
    foreach ($categories as $category) {
        $categoryName = strtolower($category['name']);
        if (!in_array($categoryName, $seenNames)) {
            $seenNames[] = $categoryName;
            $uniqueCategories[] = $category;
        } else {
            error_log("Skipping duplicate category: {$category['name']} (ID: {$category['id']})");
        }
    }
    
    $categories = $uniqueCategories;
    error_log("Final unique categories: " . count($categories));
    
} catch (Exception $e) {
    error_log("Error loading categories: " . $e->getMessage());
    $categories = [];
}

// Calculate cart and wishlist counts with size support
$cartCount = 0;
$cartTotal = 0;
$wishlistCount = 0;
$cartItems = [];

if (isLoggedIn()) {
    try {
        // Enhanced cart items query with size information
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
    } catch (Exception $e) {
        error_log("Error loading cart/wishlist: " . $e->getMessage());
        $cartItems = [];
        $wishlistItems = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - UrbanStitch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="top-banner">
            <span class="animate-pulse-neon">FREE SHIPPING THIS WEEK ORDER OVER • ₱2,500</span>
            <div style="float: right; margin-right: 16px;">
                <select style="background: transparent; color: white; border: none; margin-right: 8px;">
                    <option>PHP ₱</option>
                    <option>USD $</option>
                </select>
                <select style="background: transparent; color: white; border: none;">
                    <option>ENGLISH</option>
                    <option>SPANISH</option>
                </select>
            </div>
        </div>
        
        <div class="main-header">
            <a href="index.php" class="logo">Urban<span>Stitch</span></a>
            
            <div class="search-container enhanced-search">
                <form method="GET" action="index.php" class="search-form">
                    <div class="search-input-wrapper">
                        <input type="text" 
                               class="search-input enhanced-search-input" 
                               name="search" 
                               placeholder="Search products or categories..." 
                               autocomplete="off"
                               id="searchInput">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="user-actions">
                <?php if (isLoggedIn()): ?>
                <div class="user-menu" style="position: relative; display: inline-block;">
                    <button class="action-btn user-menu-btn" style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user"></i>
                        <span style="font-size: 14px;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="profile.php">
                            <i class="fas fa-user" style="margin-right: 8px;"></i>Profile
                        </a>
                        <a href="orders.php">
                            <i class="fas fa-box" style="margin-right: 8px;"></i>Orders
                        </a>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <hr style="margin: 8px 0; border: none; border-top: 1px solid #eee;">
                        <a href="adminDashboard.php">
                            <i class="fas fa-cog" style="margin-right: 8px;"></i>Admin Panel
                        </a>
                        <?php endif; ?>
                        <hr style="margin: 8px 0; border: none; border-top: 1px solid #eee;">
                        <a href="logout.php">
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
                    <li><a href="categories.php" class="nav-link" style="color: #00ff00;">CATEGORIES</a></li>
                    <li><a href="index.php?category=streetwear" class="nav-link">STREETWEAR</a></li>
                    <li><a href="index.php?category=footwear" class="nav-link">FOOTWEAR</a></li>
                    <li><a href="index.php?category=winter-wear" class="nav-link">WINTER WEAR</a></li>
                    <li><a href="blog.php" class="nav-link">BLOG</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Categories Content -->
    <div style="max-width: 1200px; margin: 0 auto; padding: 48px 24px;">
        <div style="text-align: center; margin-bottom: 48px;">
            <h1 style="font-size: 48px; font-weight: 900; color: #1a1a1a; margin-bottom: 16px;">
                Shop by <span style="color: #00ff00;">Category</span>
            </h1>
            <p style="font-size: 18px; color: #666; max-width: 600px; margin: 0 auto;">
                Discover our curated collections of urban streetwear, accessories, and lifestyle products 
                that define modern street culture.
            </p>
        </div>

        <!-- FIXED: Categories display section with debug info and deduplication -->
        <div class="category-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 32px;">
            <?php if (empty($categories)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                <h3 style="color: #666; margin-bottom: 10px;">No categories available</h3>
                <p style="color: #999; margin-bottom: 20px;">Categories are being updated. Please check back later.</p>
                <a href="index.php" style="display: inline-block; padding: 12px 24px; background: #00ff00; color: #1a1a1a; text-decoration: none; border-radius: 8px; font-weight: 600;">
                    <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
                    Back to Home
                </a>
            </div>
            <?php else: ?>
            
            <!-- Debug info (remove in production) -->
            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
            <div style="grid-column: 1 / -1; background: #f8f9fa; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px;">
                <h4>🐛 Debug: Categories Being Displayed</h4>
                <?php foreach ($categories as $cat): ?>
                <div>• ID: <?php echo $cat['id']; ?>, Name: "<?php echo htmlspecialchars($cat['name']); ?>", Slug: "<?php echo htmlspecialchars($cat['slug']); ?>", Products: <?php echo $cat['product_count']; ?></div>
                <?php endforeach; ?>
                <p><strong>Total categories:</strong> <?php echo count($categories); ?></p>
            </div>
            <?php endif; ?>
            
            <?php 
            // FIXED: Use a simple foreach with explicit deduplication tracking
            $displayedCategoryIds = [];
            foreach ($categories as $category): 
                // Skip if we've already displayed this category ID
                if (in_array($category['id'], $displayedCategoryIds)) {
                    error_log("Skipping duplicate display of category ID: {$category['id']} ({$category['name']})");
                    continue;
                }
                $displayedCategoryIds[] = $category['id'];
            ?>
            <div class="category-card" 
                 onclick="location.href='index.php?category=<?php echo htmlspecialchars($category['slug']); ?>'" 
                 style="background: white; padding: 32px; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); text-align: center; cursor: pointer; transition: all 0.3s; position: relative; overflow: hidden;"
                 onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 16px 48px rgba(0,0,0,0.15)';"
                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';"
                 data-category-id="<?php echo $category['id']; ?>"
                 data-category-name="<?php echo htmlspecialchars($category['name']); ?>">
                
                <!-- Background decoration -->
                <div style="position: absolute; top: -50px; right: -50px; width: 100px; height: 100px; background: linear-gradient(45deg, #00ff00, #ff6b35); border-radius: 50%; opacity: 0.1;"></div>
                
                <div class="category-icon <?php echo htmlspecialchars($category['color'] ?? 'text-neon-green'); ?>" style="font-size: 48px; margin-bottom: 24px; position: relative; z-index: 1;">
                    <i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-tag'); ?>"></i>
                </div>
                
                <h3 style="font-size: 24px; font-weight: 900; color: #1a1a1a; margin-bottom: 12px; position: relative; z-index: 1;">
                    <?php echo htmlspecialchars($category['name']); ?>
                </h3>
                
                <p style="color: #666; margin-bottom: 16px; line-height: 1.5; position: relative; z-index: 1;">
                    <?php echo htmlspecialchars($category['description'] ?? 'Discover amazing products in this category'); ?>
                </p>
                
                <div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
                    <span style="background: #f0f0f0; color: #666; padding: 6px 12px; border-radius: 20px; font-size: 14px; font-weight: 600;">
                        <?php echo $category['product_count']; ?> Products
                    </span>
                    <span style="color: #00ff00; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        Shop Now <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
                
                <!-- Debug info on card (remove in production) -->
                <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                <div style="position: absolute; top: 5px; left: 5px; background: rgba(0,0,0,0.7); color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; z-index: 10;">
                    ID: <?php echo $category['id']; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Debug link (remove in production) -->
        <div style="text-align: center; margin: 20px 0;">
            <a href="<?php echo $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?'); ?>debug=1" 
               style="font-size: 12px; color: #007bff;">[Debug Mode]</a>
        </div>

        <!-- Featured Categories Section -->
        <div style="margin-top: 80px; text-align: center;">
            <h2 style="font-size: 36px; font-weight: 900; color: #1a1a1a; margin-bottom: 32px;">
                Most Popular <span style="color: #00ff00;">Categories</span>
            </h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; margin-bottom: 48px;">
                <?php 
                // Get top 4 categories with most products
                $popularCategories = array_slice(
                    array_filter($categories, function($cat) { return $cat['product_count'] > 0; }),
                    0, 
                    4
                );
                foreach ($popularCategories as $category): 
                ?>
                <div onclick="location.href='index.php?category=<?php echo htmlspecialchars($category['slug']); ?>'" 
                     style="background: linear-gradient(135deg, #1a1a1a, #2d2d2d); color: white; padding: 24px; border-radius: 12px; cursor: pointer; transition: all 0.3s; text-align: center;"
                     onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 12px 32px rgba(0,0,0,0.3)';"
                     onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">
                    
                    <i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-tag'); ?>" style="font-size: 32px; color: #00ff00; margin-bottom: 16px;"></i>
                    <h4 style="font-size: 16px; font-weight: 700; margin-bottom: 8px;"><?php echo htmlspecialchars($category['name']); ?></h4>
                    <p style="font-size: 14px; color: #ccc;"><?php echo $category['product_count']; ?> items</p>
                </div>
                <?php endforeach; ?>
            </div>

            <a href="index.php" style="display: inline-block; padding: 16px 32px; background: #00ff00; color: #1a1a1a; text-decoration: none; border-radius: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s;"
               onmouseover="this.style.background='#1a1a1a'; this.style.color='#00ff00';"
               onmouseout="this.style.background='#00ff00'; this.style.color='#1a1a1a';">
                View All Products
            </a>
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

    <!-- FIXED: Enhanced Cart Sidebar -->
    <div class="enhanced-sidebar" id="cartSidebar">
        <div class="sidebar-header">
            <div class="header-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h3>Shopping Cart (<span id="cartItemCount"><?php echo $cartCount; ?></span>)</h3>
            <button class="close-btn" onclick="closeSidebars()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="items-container" id="cartItems">
            <?php if (!isLoggedIn()): ?>
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
            <?php elseif (empty($cartItems)): ?>
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
                        <h4 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h4>
                        
                        <?php if (!empty($item['selected_size'])): ?>
                        <div class="cart-item-size">Size: <?php echo htmlspecialchars($item['size_name'] ?? $item['selected_size']); ?></div>
                        <?php endif; ?>
                        
                        <div class="item-price">
                            <?php $itemPrice = $item['price'] + ($item['price_adjustment'] ?? 0); ?>
                            <span class="price-current">₱<?php echo number_format($itemPrice, 2); ?></span>
                        </div>
                        
                        <div class="quantity-controls">
                            <div class="quantity-group">
                                <button class="quantity-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="quantity-display"><?php echo $item['quantity']; ?></span>
                                <button class="quantity-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <button class="action-btn btn-danger" onclick="removeFromCartWithSize(<?php echo $item['product_id']; ?>, '<?php echo htmlspecialchars($item['selected_size'] ?? ''); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (isLoggedIn() && !empty($cartItems)): ?>
        <div class="sidebar-footer">
            <div class="total-section">
                <span class="total-label">Total:</span>
                <span class="total-amount">₱<?php echo number_format($cartTotal, 2); ?></span>
            </div>
            <button class="checkout-btn" onclick="location.href='checkout.php'">
                <i class="fas fa-lock" style="margin-right: 8px;"></i>
                Secure Checkout
            </button>
            <a href="cart.php" class="export-link">
                <i class="fas fa-external-link-alt"></i>
                View Full Cart
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- FIXED: Enhanced Wishlist Sidebar -->
    <div class="enhanced-sidebar wishlist-sidebar" id="wishlistSidebar">
        <div class="sidebar-header">
            <div class="header-icon">
                <i class="fas fa-heart"></i>
            </div>
            <h3>Wishlist (<span id="wishlistItemCount"><?php echo $wishlistCount; ?></span>)</h3>
            <button class="close-btn" onclick="closeSidebars()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="items-container">
            <?php if (!isLoggedIn()): ?>
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
            <?php elseif (empty($wishlistItems)): ?>
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
                        <h4 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h4>
                        <div class="item-price">
                            <span class="price-current">₱<?php echo number_format($item['price'], 2); ?></span>
                            <?php if (isset($item['original_price']) && $item['original_price'] > 0): ?>
                            <span class="price-original">₱<?php echo number_format($item['original_price'], 2); ?></span>
                            <span class="discount-badge"><?php echo round((($item['original_price'] - $item['price']) / $item['original_price']) * 100); ?>% OFF</span>
                            <?php endif; ?>
                        </div>
                        <div class="item-actions">
                            <button class="action-btn btn-primary" onclick="handleCartAddUniqlo(<?php echo $item['product_id']; ?>)">
                                <i class="fas fa-cart-plus"></i>
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
        </div>
    </div>

    <!-- FIXED: Overlay -->
    <div class="overlay" id="overlay" onclick="closeSidebars()"></div>

    <!-- Scripts -->
    <script src="script.js"></script>

    <style>
        @media (max-width: 768px) {
            .category-grid {
                grid-template-columns: 1fr !important;
                gap: 24px !important;
            }
            
            h1 {
                font-size: 36px !important;
            }
            
            h2 {
                font-size: 28px !important;
            }
            
            .category-card {
                padding: 24px !important;
            }
        }
        
        /* Enhanced category card styles */
        .category-card:hover .category-icon {
            transform: scale(1.1);
            transition: transform 0.3s ease;
        }
        
        /* Debug styles */
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
        }
    </style>

    <!-- Debug Information -->
    <script>
    console.log("🔍 Categories Page Debug:");
    console.log("Total categories loaded:", <?php echo count($categories); ?>);
    console.log("User logged in:", <?php echo isLoggedIn() ? 'true' : 'false'; ?>);
    console.log("Cart count:", <?php echo $cartCount; ?>);
    console.log("Wishlist count:", <?php echo $wishlistCount; ?>);
    
    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
    console.log("Categories data:", <?php echo json_encode($categories, JSON_PRETTY_PRINT); ?>);
    <?php endif; ?>
    </script>
</body>
</html>