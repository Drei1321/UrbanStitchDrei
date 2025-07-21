<?php
// Enhanced UrbanStitch E-commerce - FIXED Main Index File
require_once 'config.php';
require_once 'xml_operations.php';

// FIXED: Enhanced AJAX requests handling with proper JSON responses
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_GET['ajax'];
    
    if (!isLoggedIn()) {
        echo json_encode(['count' => 0, 'total' => 0]);
        exit;
    }
    // testing


//testing 2

    
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

// FIXED: Enhanced POST requests with proper validation and stock checking
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

// FIXED: Get data from database with proper error handling
try {
    $categories = $db->getAllCategories();
} catch (Exception $e) {
    error_log("Error loading categories: " . $e->getMessage());
    $categories = [];
}

// FIXED: Enhanced product query with size information and error handling
try {
    $stmt = $pdo->query("
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
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading products: " . $e->getMessage());
    $products = [];
}

// Process size data for each product
foreach ($products as &$product) {
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
}

// FIXED: Enhanced product query with proper category filtering
try {
    // Base query - get all products with their category information
    $baseQuery = "
        SELECT p.*, 
               c.name as category_name,
               c.slug as category_slug,
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
    ";
    
    // FIXED: Add category filtering with proper WHERE clause
    $whereClause = "";
    $queryParams = [];
    
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $categorySlug = $_GET['category'];
        
        // First, get the category ID from the slug
        $categoryQuery = "SELECT id, name, slug FROM categories WHERE slug = ?";
        $categoryStmt = $pdo->prepare($categoryQuery);
        $categoryStmt->execute([$categorySlug]);
        $selectedCategory = $categoryStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selectedCategory) {
            $whereClause = " WHERE p.category_id = ?";
            $queryParams[] = $selectedCategory['id'];
            
            // Log for debugging
            error_log("Category filter applied: {$selectedCategory['name']} (ID: {$selectedCategory['id']}, Slug: {$categorySlug})");
        } else {
            error_log("Category not found for slug: {$categorySlug}");
            // If category doesn't exist, show no products
            $whereClause = " WHERE 1 = 0";
        }
    }
    
    // Complete the query
    $fullQuery = $baseQuery . $whereClause . " GROUP BY p.id ORDER BY p.created_at DESC";
    
    error_log("Final query: " . $fullQuery);
    error_log("Query params: " . print_r($queryParams, true));
    
    $stmt = $pdo->prepare($fullQuery);
    $stmt->execute($queryParams);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Total products found: " . count($products));
    
} catch (Exception $e) {
    error_log("Error loading products: " . $e->getMessage());
    $products = [];
}

// Process size data for each product
foreach ($products as &$product) {
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
}

// FIXED: Remove the duplicate filtering that was causing issues
$filteredProducts = $products;

// Get search results
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = strtolower(trim($_GET['search']));
    
    // Check if search term matches any category names
    $matchingCategories = array_filter($categories, function($category) use ($searchTerm) {
        return strpos(strtolower($category['name']), $searchTerm) !== false ||
               strpos(strtolower($category['slug']), $searchTerm) !== false;
    });
    
    // If we found matching categories, get products from those categories
    if (!empty($matchingCategories)) {
        $categoryIds = array_column($matchingCategories, 'id');
        $categoryFilteredProducts = array_filter($products, function($product) use ($categoryIds) {
            return in_array($product['category_id'], $categoryIds);
        });
        
        // Also search for products by name/description
        $productFilteredProducts = array_filter($products, function($product) use ($searchTerm) {
            return strpos(strtolower($product['name']), $searchTerm) !== false ||
                   strpos(strtolower($product['description'] ?? ''), $searchTerm) !== false ||
                   strpos(strtolower($product['tags'] ?? ''), $searchTerm) !== false;
        });
        
        // Merge results and remove duplicates
        $searchResults = array_merge($categoryFilteredProducts, $productFilteredProducts);
        $filteredProducts = array_unique($searchResults, SORT_REGULAR);
        
        // Set flags for display
        $searchResultType = 'mixed';
        $foundCategories = $matchingCategories;
    } else {
        // No category matches, search only products
        $filteredProducts = array_filter($products, function($product) use ($searchTerm) {
            return strpos(strtolower($product['name']), $searchTerm) !== false ||
                   strpos(strtolower($product['description'] ?? ''), $searchTerm) !== false ||
                   strpos(strtolower($product['tags'] ?? ''), $searchTerm) !== false;
        });
        
        $searchResultType = 'products';
        $foundCategories = [];
    }
}

// PAGINATION LOGIC
$productsPerPage = 6; // 2x3 grid (2 columns, 3 rows)

$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalProducts = count($filteredProducts);
$totalPages = ceil($totalProducts / $productsPerPage);
$offset = ($currentPage - 1) * $productsPerPage;

// Get products for current page
$paginatedProducts = array_slice($filteredProducts, $offset, $productsPerPage);

// Build pagination URL parameters
$urlParams = [];
if (isset($_GET['category'])) $urlParams['category'] = $_GET['category'];
if (isset($_GET['search'])) $urlParams['search'] = $_GET['search'];
$baseUrl = 'index.php' . (!empty($urlParams) ? '?' . http_build_query($urlParams) : '');
$paginationSeparator = !empty($urlParams) ? '&' : '?';

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
    <title>UrbanStitch - Street Fashion E-commerce</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    
</head>
<body>
<!-- UrbanStitch Green Blob Loading Screen - Add right after <body> tag -->
<div id="urbanstitch-loading-screen">
    <div class="loading-blob-container">
        <div class="loading-blob"></div>
        <div class="loading-blob-extra"></div>
        <div class="loading-blob-extra"></div>
        <div class="loading-blob-extra"></div>
    </div>
    <div class="loading-text">Urban<span>Stitch</span></div>
    <div class="loading-subtitle">Loading your street fashion experience...</div>
    <div class="loading-dots">
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
    </div>
</div>
    <!-- Header -->
    <header class="header">
        <div class="top-banner">
            <span class="animate-pulse-neon">FREE SHIPPING THIS WEEK ORDER OVER • ₱2,500</span>
            <div style="float: right; margin-right: 16px;">
                <select style="background: transparent; color: white; border: none; margin-right: 8px;">
                    <option>PHP ₱</option>
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
                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                   autocomplete="off"
                   id="searchInput">
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
            </button>
            
            <!-- Search Suggestions Dropdown -->
            <div class="search-suggestions" id="searchSuggestions" style="display: none;">
                <div class="suggestions-header">
                    <span>Categories</span>
                </div>
                <div class="category-suggestions" id="categorySuggestions"></div>
                
                <div class="suggestions-header" style="margin-top: 10px;">
                    <span>Recent Searches</span>
                </div>
                <div class="recent-searches" id="recentSearches"></div>
            </div>
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
                    <li><a href="categories.php" class="nav-link">CATEGORIES</a></li>
                    <li><a href="index.php?category=streetwear" class="nav-link">STREETWEAR</a></li>
                    <li><a href="index.php?category=footwear" class="nav-link">FOOTWEAR</a></li>
                    <li><a href="index.php?category=winter-wear" class="nav-link">WINTER WEAR</a></li>
                    <li><a href="blog.php" class="nav-link">BLOG</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <?php if (!isset($_GET['category']) && !isset($_GET['search'])): ?>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <p class="hero-subtitle">Trending Streetwear</p>
                <h2 class="hero-title">
                    URBAN<br>
                    <span>SUNGLASSES</span>
                </h2>
                <p class="hero-price">
                    starting at <strong>₱25.00</strong>
                </p>
                <a href="index.php?category=glasses-lens" class="btn">Shop Now</a>
                <div class="hero-tag">
                    <span class="tag">STREET FRESH</span>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1577803645773-f96470509666?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=800&h=600" 
                     alt="Modern urban sunglasses collection">
            </div>
        </div>
    </section>

    <!-- Category Icons -->
    <section class="categories">
        <div class="category-grid">
            <?php foreach (array_slice($categories, 0, 4) as $category): ?>
            <div class="category-card" onclick="location.href='index.php?category=<?php echo htmlspecialchars($category['slug'] ?? ''); ?>'">
                <div class="category-icon <?php echo htmlspecialchars($category['color'] ?? 'text-gray'); ?>">
                    <i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-tag'); ?>"></i>
                </div>
                <h3 class="category-name"><?php echo htmlspecialchars($category['name'] ?? 'Category'); ?></h3>
                <p class="category-count">(<?php echo isset($category['product_count']) ? $category['product_count'] : 0; ?>)</p>
                <p class="category-shop">Shop All</p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3 class="sidebar-title">CATEGORY</h3>
                <ul class="category-list">
                    <?php foreach ($categories as $category): ?>
                    <li class="category-item" onclick="location.href='index.php?category=<?php echo htmlspecialchars($category['slug'] ?? ''); ?>'">
                        <div style="display: flex; align-items: center; flex: 1;">
                            <div class="category-dot <?php echo str_replace('text-', 'dot-', $category['color'] ?? 'text-gray'); ?>"></div>
                            <span><?php echo htmlspecialchars($category['name'] ?? 'Category'); ?></span>
                        </div>
                        <i class="fas fa-plus" style="font-size: 12px; color: #666;"></i>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h3 class="sidebar-title">BEST SELLERS</h3>
                <div class="product-list">
                    <?php 
                    $bestSellers = array_slice($products, 0, 4);
                    foreach ($bestSellers as $product): 
                    ?>
                    <div class="product-item" onclick="location.href='product.php?id=<?php echo $product['id']; ?>'">
                        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        <div class="product-info">
                            <h4 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h4>
                            <div class="product-rating">
                                <?php 
                                $rating = isset($product['rating']) ? (int)$product['rating'] : 0;
                                for ($i = 0; $i < $rating; $i++): ?>
                                <i class="fas fa-star star"></i>
                                <?php endfor; 
                                for ($i = $rating; $i < 5; $i++): ?>
                                <i class="far fa-star star"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="product-price">
                                <span class="price-current">₱<?php echo number_format($product['price'], 2); ?></span>
                                <?php if (isset($product['original_price']) && $product['original_price'] > 0): ?>
                                <span class="price-original">₱<?php echo number_format($product['original_price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- Product Sections -->
        <main class="product-sections">
            <?php if (isset($_GET['category']) || isset($_GET['search'])): ?>
            <!-- FIXED: Filtered Products with Enhanced Size Selection -->
           <h3 class="section-title">
    <?php 
    if (isset($_GET['category'])) {
        $cat = $db->getCategoryBySlug($_GET['category']);
        echo $cat ? htmlspecialchars($cat['name']) : 'Category';
    } elseif (isset($_GET['search'])) {
        echo 'Search Results for "' . htmlspecialchars($_GET['search']) . '"';
    }
    ?>
    <span style="color: #666; font-size: 14px; font-weight: normal; margin-left: 10px;">
        (<?php echo $totalProducts; ?> products found
        <?php if (isset($foundCategories) && !empty($foundCategories)): ?>
            in <?php echo count($foundCategories); ?> categories
        <?php endif; ?>
        - Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>)
    </span>
</h3>

<?php if (isset($_GET['search']) && !empty($_GET['search']) && isset($foundCategories) && !empty($foundCategories)): ?>
<div class="matching-categories" style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
    <h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px;">Matching Categories:</h4>
    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        <?php foreach ($foundCategories as $category): ?>
        <a href="index.php?category=<?php echo urlencode($category['slug']); ?>" 
           class="category-chip"
           style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: white; border: 2px solid #e0e0e0; border-radius: 20px; text-decoration: none; color: #333; font-size: 13px; font-weight: 500; transition: all 0.2s;">
            <div class="category-dot <?php echo str_replace('text-', 'dot-', $category['color'] ?? 'text-gray'); ?>" style="width: 8px; height: 8px; border-radius: 50%;"></div>
            <?php echo htmlspecialchars($category['name']); ?>
            <span style="color: #666; font-size: 11px;">(<?php echo $category['product_count'] ?? 0; ?>)</span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
                <div class="product-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; max-width: 100%;">

<?php if (empty($paginatedProducts)): ?>
<div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
    <i class="fas fa-search" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
    <h3 style="color: #666; margin-bottom: 10px;">No products found</h3>
    <p style="color: #999; margin-bottom: 20px;">Try adjusting your search or browse our categories</p>
    <a href="index.php" style="display: inline-block; padding: 12px 24px; background: #00ff00; color: #1a1a1a; text-decoration: none; border-radius: 8px; font-weight: 600;">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
        Back to Home
    </a>
</div>
<?php else: ?>

<?php foreach ($paginatedProducts as $product): ?>
<div class="product-card enhanced-product-card" 
     data-product-id="<?php echo $product['id']; ?>"
     data-has-sizes="<?php echo (!empty($product['sizes']) && count($product['sizes']) > 0) ? 'true' : 'false'; ?>"
     style="background: white; border-radius: 12px; padding: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s; position: relative; overflow: hidden;" 
     onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.15)';"
     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';">
    
    <!-- Product badges -->
    <div style="position: absolute; top: 12px; left: 12px; z-index: 2; display: flex; flex-direction: column; gap: 4px;">
        <?php if (isset($product['is_featured']) && $product['is_featured']): ?>
        <span style="background: #00ff00; color: #1a1a1a; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 700;">FEATURED</span>
        <?php endif; ?>
        
        <?php if (isset($product['is_trending']) && $product['is_trending']): ?>
        <span style="background: #ff6b35; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 700;">TRENDING</span>
        <?php endif; ?>
        
        <?php if (isset($product['original_price']) && $product['original_price'] > 0 && $product['original_price'] > $product['price']): ?>
        <span style="background: #ff4444; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 700;">
            <?php echo round((($product['original_price'] - $product['price']) / $product['original_price']) * 100); ?>% OFF
        </span>
        <?php endif; ?>
        
        <?php if (!empty($product['sizes']) && count($product['sizes']) > 0): ?>
        <span style="background: #007bff; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 700;">
            <?php echo count($product['sizes']); ?> SIZES
        </span>
        <?php endif; ?>
    </div>
    
    <!-- FIXED: Enhanced Wishlist button -->
    <button class="wishlist-btn-uniqlo"
            data-product-id="<?php echo $product['id']; ?>"
            style="position: absolute; top: 12px; right: 12px; z-index: 3; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; backdrop-filter: blur(10px);">
        <i class="fas fa-heart" style="font-size: 14px;"></i>
    </button>
    
    <!-- Product Image -->
    <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
         style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 12px; cursor: pointer;"
         onclick="location.href='product.php?id=<?php echo $product['id']; ?>'">
    
    <!-- Product Name -->
    <h4 style="font-weight: 600; margin-bottom: 8px; color: #1a1a1a; font-size: 16px; line-height: 1.3; cursor: pointer;"
        onclick="location.href='product.php?id=<?php echo $product['id']; ?>'">
        <?php echo htmlspecialchars($product['name']); ?>
    </h4>
    
    <!-- Product Type -->
    <?php if (!empty($product['product_type_name'])): ?>
    <div style="margin-bottom: 8px;">
        <span style="background: #f0f0f0; color: #666; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">
            <?php echo htmlspecialchars($product['product_type_name']); ?>
        </span>
    </div>
    <?php endif; ?>
    
    <!-- Product Rating -->
    <div class="product-rating" style="margin-bottom: 8px; display: flex; align-items: center; gap: 4px;">
        <?php 
        $rating = isset($product['rating']) ? (int)$product['rating'] : 0;
        for ($i = 0; $i < $rating; $i++): ?>
        <i class="fas fa-star" style="color: #fbbf24; font-size: 12px;"></i>
        <?php endfor; 
        for ($i = $rating; $i < 5; $i++): ?>
        <i class="far fa-star" style="color: #fbbf24; font-size: 12px;"></i>
        <?php endfor; ?>
        <span style="color: #666; font-size: 12px; margin-left: 4px;">(<?php echo isset($product['reviews_count']) ? $product['reviews_count'] : 0; ?>)</span>
    </div>
    
    <!-- Product Price -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <div>
            <span class="price-current" style="font-size: 18px; font-weight: 700; color: #00ff00;">₱<?php echo number_format($product['price'], 2); ?></span>
            <?php if (isset($product['original_price']) && $product['original_price'] > 0 && $product['original_price'] > $product['price']): ?>
            <span class="price-original" style="margin-left: 8px; font-size: 14px; color: #666; text-decoration: line-through;">₱<?php echo number_format($product['original_price'], 2); ?></span>
            <?php endif; ?>
        </div>
        <span style="background: #f0f0f0; color: #666; padding: 2px 6px; border-radius: 4px; font-size: 11px;">
            <?php echo isset($product['stock_quantity']) ? $product['stock_quantity'] : 0; ?> in stock
        </span>
    </div>
    
    <!-- FIXED: SIZE SELECTION SECTION -->
    <?php if (!empty($product['sizes']) && count($product['sizes']) > 0): ?>
    <div class="size-selector-uniqlo" style="margin: 12px 0; padding: 12px; background: #fafafa; border-radius: 8px; border: 1px solid #e0e0e0;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
            <label class="size-label" style="font-size: 13px; font-weight: 600; color: #333; margin: 0;">Size:</label>
            <span class="selected-size-display" style="font-size: 12px; color: #666; font-weight: 500;"></span>
        </div>
        
        <div class="size-options-uniqlo" style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php foreach ($product['sizes'] as $index => $size): ?>
            <button type="button" 
                    class="size-option-uniqlo <?php echo $size['stock'] <= 0 ? 'out-of-stock' : ($size['stock'] <= 5 ? 'low-stock' : ''); ?>"
                    data-size-code="<?php echo htmlspecialchars($size['code']); ?>"
                    data-size-name="<?php echo htmlspecialchars($size['name']); ?>"
                    data-stock="<?php echo $size['stock']; ?>"
                    data-product-id="<?php echo $product['id']; ?>"
                    <?php echo $size['stock'] <= 0 ? 'disabled' : ''; ?>
                    style="
                        min-width: 40px;
                        height: 40px;
                        border: 2px solid #ddd;
                        background: white;
                        border-radius: 4px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 13px;
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
                <span style="position: absolute; top: -2px; right: -2px; width: 8px; height: 8px; background: #ff6b35; border-radius: 50%; border: 1px solid white;"></span>
                <?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>
        
        <?php if (array_filter($product['sizes'], function($s) { return $s['stock'] <= 5 && $s['stock'] > 0; })): ?>
        <div style="margin-top: 6px; font-size: 10px; color: #ff6b35; display: flex; align-items: center; gap: 4px;">
            <span style="width: 6px; height: 6px; background: #ff6b35; border-radius: 50%;"></span>
            Low stock items
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Size Required Warning -->
    <div class="size-required-warning" style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 8px 12px; border-radius: 4px; font-size: 12px; margin: 8px 0; display: none; animation: fadeIn 0.3s ease;">
        <i class="fas fa-exclamation-triangle" style="margin-right: 6px;"></i>
        Please select a size before adding to cart
    </div>
    <?php endif; ?>
    
    <!-- FIXED: Add to Cart Button -->
    <button class="add-to-cart-btn-uniqlo"
            data-product-id="<?php echo $product['id']; ?>"
            style="
                width: 100%; 
                padding: 12px; 
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
                gap: 8px;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            ">
        <i class="fas fa-shopping-cart"></i>
        Add to Cart
    </button>
</div>
<?php endforeach; ?>

<?php endif; ?>
                
                <!-- PAGINATION CONTROLS -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination-container" style="margin-top: 40px; display: flex; justify-content: center; align-items: center; gap: 16px;">
                    
                    <!-- Previous Button -->
                    <?php if ($currentPage > 1): ?>
                    <a href="<?php echo $baseUrl . $paginationSeparator . 'page=' . ($currentPage - 1); ?>" 
                       class="pagination-btn pagination-prev" 
                       style="display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: #1a1a1a; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s;">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                    <?php else: ?>
                    <span class="pagination-btn pagination-prev disabled" 
                          style="display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: #ccc; color: #666; border-radius: 8px; font-weight: 600;">
                        <i class="fas fa-chevron-left"></i> Previous
                    </span>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <div class="pagination-numbers" style="display: flex; gap: 8px;">
                        <?php 
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        // Show first page if not in range
                        if ($startPage > 1): ?>
                            <a href="<?php echo $baseUrl . $paginationSeparator . 'page=1'; ?>" 
                               class="pagination-number" 
                               style="padding: 12px 16px; background: white; color: #333; text-decoration: none; border-radius: 6px; border: 2px solid #e0e0e0; font-weight: 600; transition: all 0.3s;">1</a>
                            <?php if ($startPage > 2): ?>
                                <span style="padding: 12px 8px; color: #666;">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Page range -->
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php if ($i == $currentPage): ?>
                                <span class="pagination-number active" 
                                      style="padding: 12px 16px; background: #00ff00; color: #1a1a1a; border-radius: 6px; border: 2px solid #00ff00; font-weight: 700; transform: scale(1.1);"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo $baseUrl . $paginationSeparator . 'page=' . $i; ?>" 
                                   class="pagination-number" 
                                   style="padding: 12px 16px; background: white; color: #333; text-decoration: none; border-radius: 6px; border: 2px solid #e0e0e0; font-weight: 600; transition: all 0.3s;"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <!-- Show last page if not in range -->
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span style="padding: 12px 8px; color: #666;">...</span>
                            <?php endif; ?>
                            <a href="<?php echo $baseUrl . $paginationSeparator . 'page=' . $totalPages; ?>" 
                               class="pagination-number" 
                               style="padding: 12px 16px; background: white; color: #333; text-decoration: none; border-radius: 6px; border: 2px solid #e0e0e0; font-weight: 600; transition: all 0.3s;"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Next Button -->
                    <?php if ($currentPage < $totalPages): ?>
                    <a href="<?php echo $baseUrl . $paginationSeparator . 'page=' . ($currentPage + 1); ?>" 
                       class="pagination-btn pagination-next" 
                       style="display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: #1a1a1a; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s;">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php else: ?>
                    <span class="pagination-btn pagination-next disabled" 
                          style="display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: #ccc; color: #666; border-radius: 8px; font-weight: 600;">
                        Next <i class="fas fa-chevron-right"></i>
                    </span>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Pagination Info -->
                <div class="pagination-info" style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
                    Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $productsPerPage, $totalProducts); ?> of <?php echo $totalProducts; ?> products
                </div>
                <?php endif; ?>
                
                </div>
            </div>
            <?php else: ?>
            <!-- Default product sections -->
            <div class="sections-grid">
                <!-- New Arrivals -->
                <div class="product-section">
                    <h3 class="section-title">New Arrivals</h3>
                    <div class="product-list">
                        <?php 
                        $newArrivals = array_slice($products, 3, 3);
                        foreach ($newArrivals as $product): 
                        ?>
                        <div class="product-item" onclick="location.href='product.php?id=<?php echo $product['id']; ?>'">
                            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            <div class="product-info">
                                <h4 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h4>
                                <div class="product-rating">
                                    <?php 
                                    $rating = isset($product['rating']) ? (int)$product['rating'] : 0;
                                    for ($i = 0; $i < $rating; $i++): ?>
                                    <i class="fas fa-star star"></i>
                                    <?php endfor; 
                                    for ($i = $rating; $i < 5; $i++): ?>
                                    <i class="far fa-star star"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="product-price">
                                    <span class="price-current">₱<?php echo number_format($product['price'], 2); ?></span>
                                    <?php if (isset($product['original_price']) && $product['original_price'] > 0): ?>
                                    <span class="price-original">₱<?php echo number_format($product['original_price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Trending -->
                <!-- Trending -->
<div class="product-section">
    <h3 class="section-title">Trending</h3>
    <div class="product-list">
        <?php 
        $trending = array_filter($products, function($product) {
    return isset($product['is_trending']) && $product['is_trending'];
});
$trending = array_slice($trending, 0, 3);
        foreach ($trending as $product): 
        ?>
                        <div class="product-item" onclick="location.href='product.php?id=<?php echo $product['id']; ?>'">
                            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            <div class="product-info">
                                <h4 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h4>
                                <div class="product-rating">
                                    <?php 
                                    $rating = isset($product['rating']) ? (int)$product['rating'] : 0;
                                    for ($i = 0; $i < $rating; $i++): ?>
                                    <i class="fas fa-star star"></i>
                                    <?php endfor; 
                                    for ($i = $rating; $i < 5; $i++): ?>
                                    <i class="far fa-star star"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="product-price">
                                    <span class="price-current">₱<?php echo number_format($product['price'], 2); ?></span>
                                    <?php if (isset($product['original_price']) && $product['original_price'] > 0): ?>
                                    <span class="price-original">₱<?php echo number_format($product['original_price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Featured -->
                <div class="product-section">
                    <h3 class="section-title">Featured</h3>
                    <div class="product-list">
                        <?php 
                        $featured = $db->getFeaturedProducts();
                        foreach (array_slice($featured, 0, 3) as $product): 
                        ?>
                        <div class="product-item" onclick="location.href='product.php?id=<?php echo $product['id']; ?>'">
                            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            <div class="product-info">
                                <h4 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h4>
                                <div class="product-rating">
                                    <?php 
                                    $rating = isset($product['rating']) ? (int)$product['rating'] : 0;
                                    for ($i = 0; $i < $rating; $i++): ?>
                                    <i class="fas fa-star star"></i>
                                    <?php endfor; 
                                    for ($i = $rating; $i < 5; $i++): ?>
                                    <i class="far fa-star star"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="product-price">
                                    <span class="price-current">$<?php echo number_format($product['price'], 2); ?></span>
                                    <?php if (isset($product['original_price']) && $product['original_price'] > 0): ?>
                                    <span class="price-original">$<?php echo number_format($product['original_price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <?php if (!isset($_GET['category']) && !isset($_GET['search'])): ?>
    <!-- Deal of the Day -->
    <section class="deal-section">
        <div class="deal-container">
            <div class="deal-header">
                <h2 class="deal-title">Deal Of The Day</h2>
                <div class="deal-rating">
                    <i class="fas fa-star star"></i>
                    <i class="fas fa-star star"></i>
                    <i class="fas fa-star star"></i>
                    <i class="fas fa-star star"></i>
                    <i class="far fa-star star"></i>
                </div>
            </div>
            
            <div class="deal-content">
                <div class="deal-image">
                    <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=400&h=300" 
                         alt="Urban streetwear complete outfit bundle">
                </div>
                <div class="deal-info">
                    <h3 class="deal-product-title">Urban Streetwear Bundle</h3>
                    <p class="deal-description">
                        Complete your street style with this exclusive bundle featuring premium hoodie, 
                        distressed jeans, and urban sneakers. Limited time offer with free shipping.
                    </p>
                    <div class="deal-pricing">
                        <span class="deal-price">890.99</span>
                        <span class="deal-original">₱1499.99</span>
                        <span class="deal-discount">40% OFF</span>
                    </div>
                    <a href="product.php?id=1" class="btn">Shop Deal</a>
                    <div class="deal-timer">
                        <i class="fas fa-clock"></i>
                        <span>Ends in 23:45:12</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

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
                    <img src="<?php echo $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                    <div class="item-details">
                        <h4 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h4>
                        
                        <?php if (!empty($item['selected_size'])): ?>
                        <div class="cart-item-size">Size: <?php echo htmlspecialchars($item['size_name'] ?? $item['selected_size']); ?></div>
                        <?php endif; ?>
                        
                        <div class="item-price">
                            <span class="price-current">₱<?php echo number_format($item['price'] + ($item['price_adjustment'] ?? 0), 2); ?></span>
                            <?php if (isset($item['original_price']) && $item['original_price'] > 0): ?>
                            <span class="price-original">$<?php echo number_format($item['original_price'], 2); ?></span>
                            <?php endif; ?>
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
                            <button class="action-btn btn-danger" onclick="removeFromCartWithSize(<?php echo $item['product_id']; ?>, '<?php echo $item['selected_size'] ?? ''; ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($cartItems)): ?>
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
                    <img src="<?php echo $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                    <div class="item-details">
                        <h4 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h4>
                        <div class="item-price">
                            <span class="price-current">$<?php echo number_format($item['price'], 2); ?></span>
                            <?php if (isset($item['original_price']) && $item['original_price'] > 0): ?>
                            <span class="price-original">$<?php echo number_format($item['original_price'], 2); ?></span>
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

    <!-- FIXED: Enhanced JavaScript -->
    <script src="script.js"></script>
<!-- Debug Information -->
<!-- FIXED: Enhanced JavaScript - Corrected Order -->

    <!-- Search functionality - runs after main script loads -->
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for the main UrbanStitch system to fully initialize
    setTimeout(function() {
        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');
        const categorySuggestions = document.getElementById('categorySuggestions');
        const recentSearches = document.getElementById('recentSearches');
        
        if (!searchInput) return; // Exit if search input not found
        
        // Categories data
        const categories = <?php echo json_encode($categories); ?>;
        
        // Get recent searches from localStorage
        function getRecentSearches() {
            try {
                return JSON.parse(localStorage.getItem('urbanstitch_recent_searches')) || [];
            } catch (e) {
                return [];
            }
        }
        
        // Save search to recent searches
        function saveRecentSearch(term) {
            try {
                let recent = getRecentSearches();
                recent = recent.filter(item => item.toLowerCase() !== term.toLowerCase());
                recent.unshift(term);
                recent = recent.slice(0, 5);
                localStorage.setItem('urbanstitch_recent_searches', JSON.stringify(recent));
            } catch (e) {
                console.log('Could not save recent search');
            }
        }
        
        // Show search suggestions
        function showSuggestions(query) {
            if (!query || query.length < 2) {
                if (searchSuggestions) searchSuggestions.style.display = 'none';
                return;
            }
            
            // Filter categories
            const matchingCategories = categories.filter(cat => 
                cat.name.toLowerCase().includes(query.toLowerCase())
            );
            
            // Clear previous suggestions
            if (categorySuggestions) categorySuggestions.innerHTML = '';
            
            // Add category suggestions
            matchingCategories.slice(0, 4).forEach(category => {
                const suggestionItem = document.createElement('div');
                suggestionItem.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; cursor: pointer; border-radius: 4px; transition: all 0.2s;" 
                         onmouseover="this.style.background='#f0f0f0'" 
                         onmouseout="this.style.background='transparent'"
                         onclick="selectCategory('${category.slug}')">
                        <div class="category-dot dot-${category.color.replace('text-', '')}" style="width: 8px; height: 8px; border-radius: 50%;"></div>
                        <span style="flex: 1; font-size: 14px;">${category.name}</span>
                        <span style="color: #666; font-size: 12px;">${category.product_count || 0} items</span>
                    </div>
                `;
                if (categorySuggestions) categorySuggestions.appendChild(suggestionItem);
            });
            
            // Add recent searches
            const recent = getRecentSearches();
            if (recentSearches) recentSearches.innerHTML = '';
            recent.slice(0, 3).forEach(term => {
                const recentItem = document.createElement('div');
                recentItem.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; cursor: pointer; border-radius: 4px; transition: all 0.2s;"
                         onmouseover="this.style.background='#f0f0f0'" 
                         onmouseout="this.style.background='transparent'"
                         onclick="selectSearch('${term}')">
                        <i class="fas fa-history" style="color: #666; font-size: 12px;"></i>
                        <span style="flex: 1; font-size: 14px;">${term}</span>
                    </div>
                `;
                if (recentSearches) recentSearches.appendChild(recentItem);
            });
            
            if (searchSuggestions) searchSuggestions.style.display = 'block';
        }
        
        // Select category suggestion
        window.selectCategory = function(slug) {
            window.location.href = `index.php?category=${slug}`;
        }
        
        // Select search suggestion  
        window.selectSearch = function(term) {
            searchInput.value = term;
            searchInput.form.submit();
        }
        
        // Search input events
        searchInput.addEventListener('input', function() {
            showSuggestions(this.value);
        });
        
        searchInput.addEventListener('focus', function() {
            if (this.value.length >= 2) {
                showSuggestions(this.value);
            }
        });
        
        searchInput.addEventListener('blur', function() {
            setTimeout(() => {
                if (searchSuggestions) searchSuggestions.style.display = 'none';
            }, 200);
        });
        
        // Save search when form is submitted
        if (searchInput.form) {
            searchInput.form.addEventListener('submit', function() {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    saveRecentSearch(searchTerm);
                }
            });
        }
        
        console.log("✅ Search functionality initialized");
    }, 500); // Wait 500ms for main system to load
});
    </script>

    <!-- Debug Information - Only for development -->
    <script>
// Debug info
console.log("🔍 Debug Info:");
console.log("Current URL:", window.location.href);
console.log("User logged in:", <?php echo isLoggedIn() ? 'true' : 'false'; ?>);
console.log("Cart count:", <?php echo $cartCount; ?>);

// Check if UrbanStitch system loaded properly
setTimeout(function() {
    console.log("🔧 System Check:");
    console.log("UrbanStitch available:", typeof window.UrbanStitch !== 'undefined');
    console.log("Cart manager:", typeof window.UrbanStitch?.cart !== 'undefined');
    console.log("Sidebar manager:", typeof window.UrbanStitch?.sidebar !== 'undefined');
    console.log("Size selector:", typeof window.UrbanStitch?.size !== 'undefined');
    
    const productCount = document.querySelectorAll('[data-product-id]').length;
    const cartButtonCount = document.querySelectorAll('.add-to-cart-btn-uniqlo').length;
    const wishlistButtonCount = document.querySelectorAll('.wishlist-btn-uniqlo').length;
    
    console.log("Products on page:", productCount);
    console.log("Cart buttons:", cartButtonCount);
    console.log("Wishlist buttons:", wishlistButtonCount);
    
    // Test if cart and wishlist buttons are working
    const cartBtn = document.querySelector('.cart-btn');
    const wishlistBtn = document.querySelector('.wishlist-btn');
    const userMenuBtn = document.querySelector('.user-menu-btn');
    
    console.log("Cart button found:", !!cartBtn);
    console.log("Wishlist button found:", !!wishlistBtn);
    console.log("User menu button found:", !!userMenuBtn);
    
    if (window.UrbanStitch && window.UrbanStitch.cart) {
        console.log("✅ All systems operational!");
    } else {
        console.warn("⚠️ Main system not fully loaded");
    }
}, 1000);

// Test function for debugging cart
window.testAddToCart = function(productId) {
    console.log("🧪 Testing add to cart for product:", productId);
    
    if (window.UrbanStitch && window.UrbanStitch.cart) {
        window.UrbanStitch.cart.addToCart(productId);
    } else {
        console.warn("Main cart system not available, using fallback");
        
        const formData = new FormData();
        formData.append("action", "add_to_cart");
        formData.append("product_id", productId);
        formData.append("quantity", "1");
        
        fetch(window.location.pathname, {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log("Fallback response:", text);
            try {
                const data = JSON.parse(text);
                console.log("Parsed response:", data);
            } catch (e) {
                console.log("Response is not JSON");
            }
        })
        .catch(error => {
            console.error("Test error:", error);
        });
    }
}

// Debug helper for sidebar issues
window.debugSidebars = function() {
    console.log("🔧 Sidebar Debug:");
    console.log("Cart sidebar:", document.getElementById('cartSidebar'));
    console.log("Wishlist sidebar:", document.getElementById('wishlistSidebar'));
    console.log("Overlay:", document.getElementById('overlay'));
    
    const cartBtn = document.querySelector('.cart-btn');
    const wishlistBtn = document.querySelector('.wishlist-btn');
    
    if (cartBtn) {
        console.log("Cart button click test:");
        cartBtn.click();
    }
    
    if (wishlistBtn) {
        console.log("Wishlist button click test:");
        wishlistBtn.click();
    }
}


// URGENT FIX: Force login link to work
document.addEventListener('DOMContentLoaded', function() {
    console.log("🔧 Applying login link fix...");
    
    // Wait a bit for other scripts to load
    setTimeout(function() {
        const loginLink = document.querySelector('a.action-btn[href="login.php"]');
        
        if (loginLink) {
            console.log("✅ Login link found:", loginLink);
            
            // Remove all existing event listeners by cloning the element
            const newLoginLink = loginLink.cloneNode(true);
            loginLink.parentNode.replaceChild(newLoginLink, loginLink);
            
            // Apply strong styling to ensure it's clickable
            newLoginLink.style.cssText = `
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                min-width: 44px !important;
                min-height: 44px !important;
                text-decoration: none !important;
                color: inherit !important;
                pointer-events: auto !important;
                z-index: 99999 !important;
                position: relative !important;
                cursor: pointer !important;
                border-radius: 50% !important;
                transition: all 0.3s ease !important;
            `;
            
            // Add hover effect
            newLoginLink.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                this.style.transform = 'scale(1.05)';
            });
            
            newLoginLink.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
                this.style.transform = 'scale(1)';
            });
            
            // Ensure click works
            newLoginLink.addEventListener('click', function(e) {
                console.log("🔗 Login link clicked!");
                // Don't prevent default - let the link work normally
                window.location.href = 'login.php';
            });
            
            console.log("✅ Login link fix applied successfully");
            
            // Test if it's working
            console.log("🧪 Testing login link...");
            console.log("  - href:", newLoginLink.href);
            console.log("  - onclick:", newLoginLink.onclick);
            console.log("  - style.pointerEvents:", newLoginLink.style.pointerEvents);
            console.log("  - style.zIndex:", newLoginLink.style.zIndex);
            
        } else {
            console.error("❌ Login link not found!");
            
            // Let's look for it differently
            const allLinks = document.querySelectorAll('a[href="login.php"]');
            const allUserButtons = document.querySelectorAll('.action-btn');
            
            console.log("🔍 Found links to login.php:", allLinks.length);
            console.log("🔍 Found action buttons:", allUserButtons.length);
            
            allUserButtons.forEach((btn, index) => {
                console.log(`  Button ${index + 1}:`, btn.outerHTML);
            });
            
            // If we find any login link, fix it
            if (allLinks.length > 0) {
                allLinks.forEach((link, index) => {
                    console.log(`🔧 Fixing login link ${index + 1}`);
                    link.style.cssText = `
                        pointer-events: auto !important;
                        z-index: 99999 !important;
                        position: relative !important;
                        cursor: pointer !important;
                    `;
                    
                    // Remove conflicting event listeners
                    const newLink = link.cloneNode(true);
                    link.parentNode.replaceChild(newLink, link);
                    
                    newLink.addEventListener('click', function(e) {
                        console.log("🔗 Alternative login link clicked!");
                        window.location.href = 'login.php';
                    });
                });
            }
        }
    }, 1000); // Wait 1 second for all scripts to load
});

// Additional fallback - create a manual click handler
window.forceLoginRedirect = function() {
    console.log("🚀 Force redirecting to login...");
    window.location.href = 'login.php';
};

// Emergency fallback - if nothing works, you can type this in console: forceLoginRedirect()
console.log("🆘 Emergency: If login still doesn't work, type: forceLoginRedirect()");

    </script>
</body>
</html>
