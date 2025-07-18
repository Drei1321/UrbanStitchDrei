<?php
// NEW FILE: dynamic_xml_operations.php
// This replaces xml_operations.php and provides dynamic XML with database sync

require_once 'config.php';

class DynamicXMLOperations {
    private $pdo;
    private $xmlPath;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->xmlPath = __DIR__ . '/xml/';
        
        // Create XML directory if it doesn't exist
        if (!file_exists($this->xmlPath)) {
            mkdir($this->xmlPath, 0755, true);
        }
        
        // Auto-generate XML files if they don't exist or are outdated
        $this->checkAndUpdateXMLFiles();
    }
    
    // Check if XML files need updating and regenerate them
    private function checkAndUpdateXMLFiles() {
        $productsXMLFile = $this->xmlPath . 'products.xml';
        $categoriesXMLFile = $this->xmlPath . 'categories.xml';
        
        // Check if XML files exist and get their timestamps
        $xmlLastModified = 0;
        if (file_exists($productsXMLFile)) {
            $xmlLastModified = filemtime($productsXMLFile);
        }
        
        // Check database last modified time
        try {
            $stmt = $this->pdo->query("SELECT MAX(updated_at) as last_update FROM products");
            $result = $stmt->fetch();
            $dbLastModified = strtotime($result['last_update'] ?? '1970-01-01');
            
            // Check categories last modified
            $stmt = $this->pdo->query("SELECT MAX(updated_at) as last_update FROM categories");
            $result = $stmt->fetch();
            $categoriesLastModified = strtotime($result['last_update'] ?? '1970-01-01');
            
            $latestDbUpdate = max($dbLastModified, $categoriesLastModified);
            
            // If database is newer than XML, regenerate XML files
            if ($latestDbUpdate > $xmlLastModified || !file_exists($productsXMLFile) || !file_exists($categoriesXMLFile)) {
                $this->generateXMLFromDatabase();
            }
        } catch (Exception $e) {
            error_log("Error checking XML update status: " . $e->getMessage());
            // If there's an error, try to generate XML anyway
            if (!file_exists($productsXMLFile)) {
                $this->generateXMLFromDatabase();
            }
        }
    }
    
    // Generate all XML files from current database
    public function generateXMLFromDatabase() {
        try {
            $this->generateProductsXML();
            $this->generateCategoriesXML();
            $this->generateSiteConfigXML();
            $this->generateUsersOrdersXML();
            return true;
        } catch (Exception $e) {
            error_log("Error generating XML from database: " . $e->getMessage());
            return false;
        }
    }
    
    // Generate products.xml from database
    private function generateProductsXML() {
        $stmt = $this->pdo->query("
            SELECT p.*, 
                   COALESCE(c.name, 'Uncategorized') as category_name, 
                   COALESCE(c.slug, 'uncategorized') as category_slug,
                   COALESCE(p.original_price, 0) as original_price,
                   COALESCE(p.rating, 0) as rating,
                   COALESCE(p.reviews_count, 0) as reviews_count,
                   COALESCE(p.is_featured, 0) as is_featured,
                   COALESCE(p.is_trending, 0) as is_trending,
                   COALESCE(p.stock_quantity, 0) as stock_quantity
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.created_at DESC
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $root = $xml->createElement('urbanstitch_data');
        $root->setAttribute('xmlns', 'http://urbanstitch.com/products');
        $root->setAttribute('version', '1.0');
        $root->setAttribute('generated', date('Y-m-d\TH:i:s\Z'));
        $xml->appendChild($root);
        
        // Add categories
        $categoriesContainer = $xml->createElement('categories');
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($categories as $cat) {
            $categoryNode = $xml->createElement('category');
            $categoryNode->setAttribute('id', $cat['id']);
            $categoryNode->setAttribute('slug', $cat['slug']);
            
            $categoryNode->appendChild($xml->createElement('name', htmlspecialchars($cat['name'])));
            $categoryNode->appendChild($xml->createElement('icon', htmlspecialchars($cat['icon'] ?? 'fas fa-tag')));
            $categoryNode->appendChild($xml->createElement('color', htmlspecialchars($cat['color'] ?? 'text-gray')));
            $categoryNode->appendChild($xml->createElement('description', htmlspecialchars($cat['description'] ?? '')));
            
            // Count products
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $countStmt->execute([$cat['id']]);
            $productCount = $countStmt->fetchColumn();
            $categoryNode->appendChild($xml->createElement('product_count', $productCount));
            
            $categoriesContainer->appendChild($categoryNode);
        }
        $root->appendChild($categoriesContainer);
        
        // Add products
        $productsContainer = $xml->createElement('products');
        $productsContainer->setAttribute('total_count', count($products));
        
        foreach ($products as $product) {
            $productNode = $xml->createElement('product');
            $productNode->setAttribute('id', $product['id']);
            $productNode->setAttribute('featured', $product['is_featured'] ? 'true' : 'false');
            $productNode->setAttribute('trending', $product['is_trending'] ? 'true' : 'false');
            
            // Basic Information
            $basicInfo = $xml->createElement('basic_information');
            $basicInfo->appendChild($xml->createElement('name', htmlspecialchars($product['name'])));
            $basicInfo->appendChild($xml->createElement('slug', htmlspecialchars($product['slug'])));
            $basicInfo->appendChild($xml->createElement('description', htmlspecialchars($product['description'] ?? '')));
            $productNode->appendChild($basicInfo);
            
            // Pricing
            $pricing = $xml->createElement('pricing');
            $pricing->setAttribute('currency', 'USD');
            $pricing->appendChild($xml->createElement('current_price', number_format($product['price'], 2)));
            if ($product['original_price'] > 0) {
                $pricing->appendChild($xml->createElement('original_price', number_format($product['original_price'], 2)));
                $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100, 2);
                $pricing->appendChild($xml->createElement('discount_percentage', $discount));
                $pricing->appendChild($xml->createElement('savings', number_format($product['original_price'] - $product['price'], 2)));
            }
            $productNode->appendChild($pricing);
            
            // Category
            $category = $xml->createElement('category');
            $category->appendChild($xml->createElement('name', htmlspecialchars($product['category_name'])));
            $category->appendChild($xml->createElement('slug', htmlspecialchars($product['category_slug'])));
            $category->appendChild($xml->createElement('id', $product['category_id'] ?? 0));
            $productNode->appendChild($category);
            
            // Inventory
            $inventory = $xml->createElement('inventory');
            $inventory->appendChild($xml->createElement('quantity', $product['stock_quantity']));
            $inventory->appendChild($xml->createElement('status', $product['stock_quantity'] > 0 ? 'in_stock' : 'out_of_stock'));
            $productNode->appendChild($inventory);
            
            // Media
            $media = $xml->createElement('media');
            if (!empty($product['image_url'])) {
                $image = $xml->createElement('image');
                $image->appendChild($xml->createElement('url', htmlspecialchars($product['image_url'])));
                $image->appendChild($xml->createElement('alt_text', htmlspecialchars($product['name'])));
                $media->appendChild($image);
            }
            $productNode->appendChild($media);
            
            // Reviews
            $reviews = $xml->createElement('reviews');
            $reviews->appendChild($xml->createElement('average_rating', number_format($product['rating'], 2)));
            $reviews->appendChild($xml->createElement('total_reviews', $product['reviews_count']));
            $productNode->appendChild($reviews);
            
            // Tags
            if (!empty($product['tags'])) {
                $tags = $xml->createElement('tags');
                $tagArray = explode(',', $product['tags']);
                foreach ($tagArray as $tag) {
                    $tagNode = $xml->createElement('tag', htmlspecialchars(trim($tag)));
                    $tags->appendChild($tagNode);
                }
                $productNode->appendChild($tags);
            }
            
            // Timestamps
            $timestamps = $xml->createElement('timestamps');
            $timestamps->appendChild($xml->createElement('created_at', $product['created_at']));
            $timestamps->appendChild($xml->createElement('updated_at', $product['updated_at'] ?? $product['created_at']));
            $productNode->appendChild($timestamps);
            
            $productsContainer->appendChild($productNode);
        }
        $root->appendChild($productsContainer);
        
        // Statistics
        $statistics = $xml->createElement('statistics');
        $productStats = $xml->createElement('products');
        $productStats->appendChild($xml->createElement('total', count($products)));
        $productStats->appendChild($xml->createElement('featured', count(array_filter($products, function($p) { return $p['is_featured']; }))));
        $productStats->appendChild($xml->createElement('trending', count(array_filter($products, function($p) { return $p['is_trending']; }))));
        $productStats->appendChild($xml->createElement('in_stock', count(array_filter($products, function($p) { return $p['stock_quantity'] > 0; }))));
        $statistics->appendChild($productStats);
        $root->appendChild($statistics);
        
        $xml->save($this->xmlPath . 'products.xml');
    }
    
    // Generate categories.xml from database
    private function generateCategoriesXML() {
        $stmt = $this->pdo->query("
            SELECT c.*, 
                   COALESCE(c.icon, 'fas fa-tag') as icon,
                   COALESCE(c.color, 'text-gray') as color,
                   COUNT(p.id) as product_count
            FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id 
            GROUP BY c.id 
            ORDER BY c.name
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $root = $xml->createElement('categories');
        $root->setAttribute('xmlns', 'http://urbanstitch.com/categories');
        $root->setAttribute('version', '1.0');
        $root->setAttribute('generated', date('Y-m-d\TH:i:s\Z'));
        $xml->appendChild($root);
        
        $metadata = $xml->createElement('metadata');
        $metadata->appendChild($xml->createElement('total_categories', count($categories)));
        $metadata->appendChild($xml->createElement('export_date', date('Y-m-d H:i:s')));
        $metadata->appendChild($xml->createElement('source', 'UrbanStitch E-commerce'));
        $root->appendChild($metadata);
        
        $categoryList = $xml->createElement('category_list');
        
        foreach ($categories as $category) {
            $categoryNode = $xml->createElement('category');
            $categoryNode->setAttribute('id', $category['slug']);
            $categoryNode->setAttribute('database_id', $category['id']);
            
            $categoryNode->appendChild($xml->createElement('name', htmlspecialchars(strtoupper($category['name']))));
            $categoryNode->appendChild($xml->createElement('display_name', htmlspecialchars($category['name'])));
            $categoryNode->appendChild($xml->createElement('icon', htmlspecialchars($category['icon'])));
            $categoryNode->appendChild($xml->createElement('color_class', htmlspecialchars($category['color'])));
            $categoryNode->appendChild($xml->createElement('description', htmlspecialchars($category['description'] ?? '')));
            $categoryNode->appendChild($xml->createElement('product_count', $category['product_count']));
            $categoryNode->appendChild($xml->createElement('status', 'active'));
            
            $categoryList->appendChild($categoryNode);
        }
        $root->appendChild($categoryList);
        
        $xml->save($this->xmlPath . 'categories.xml');
    }
    
    // Generate site_config.xml
    private function generateSiteConfigXML() {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $root = $xml->createElement('urbanstitch_config');
        $root->setAttribute('xmlns', 'http://urbanstitch.com/config');
        $root->setAttribute('version', '1.0');
        $root->setAttribute('generated', date('Y-m-d\TH:i:s\Z'));
        $xml->appendChild($root);
        
        // Site Info
        $siteInfo = $xml->createElement('site_info');
        $siteInfo->appendChild($xml->createElement('name', 'UrbanStitch'));
        $siteInfo->appendChild($xml->createElement('tagline', 'Street Fashion Redefined'));
        $root->appendChild($siteInfo);
        
        // Business Settings
        $businessSettings = $xml->createElement('business_settings');
        $shipping = $xml->createElement('shipping');
        $shipping->appendChild($xml->createElement('free_shipping_threshold', '50.00'));
        $shipping->appendChild($xml->createElement('currency', 'USD'));
        $businessSettings->appendChild($shipping);
        $root->appendChild($businessSettings);
        
        $xml->save($this->xmlPath . 'site_config.xml');
    }
    
    // Generate users_orders.xml (placeholder)
    private function generateUsersOrdersXML() {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $root = $xml->createElement('urbanstitch_users_orders');
        $root->setAttribute('xmlns', 'http://urbanstitch.com/users');
        $root->setAttribute('version', '1.0');
        $root->setAttribute('generated', date('Y-m-d\TH:i:s\Z'));
        $xml->appendChild($root);
        
        $metadata = $xml->createElement('metadata');
        $metadata->appendChild($xml->createElement('export_date', date('Y-m-d H:i:s')));
        $root->appendChild($metadata);
        
        $xml->save($this->xmlPath . 'users_orders.xml');
    }
    
    // Load XML and return data (with fallback to database)
    private function loadXMLFile($filename) {
        $filepath = $this->xmlPath . $filename;
        if (file_exists($filepath)) {
            return simplexml_load_file($filepath);
        }
        return null;
    }
    
    // Product operations - these read from XML but trigger updates when needed
    public function getAllProducts() {
        $xml = $this->loadXMLFile('products.xml');
        if (!$xml) {
            // Fallback to database if XML doesn't exist
            return $this->getProductsFromDatabase();
        }
        
        $products = [];
        foreach ($xml->products->product as $product) {
            $products[] = $this->xmlProductToArray($product);
        }
        return $products;
    }
    
    public function getProductById($id) {
        $xml = $this->loadXMLFile('products.xml');
        if (!$xml) {
            return $this->getProductFromDatabase($id);
        }
        
        foreach ($xml->products->product as $product) {
            if ((string)$product['id'] === (string)$id) {
                return $this->xmlProductToArray($product);
            }
        }
        return null;
    }
    
    public function getProductsByCategory($categorySlug) {
        $xml = $this->loadXMLFile('products.xml');
        if (!$xml) {
            return $this->getProductsByCategoryFromDatabase($categorySlug);
        }
        
        $products = [];
        foreach ($xml->products->product as $product) {
            if ((string)$product->category->slug === $categorySlug) {
                $products[] = $this->xmlProductToArray($product);
            }
        }
        return $products;
    }
    
    public function searchProducts($query) {
        $xml = $this->loadXMLFile('products.xml');
        if (!$xml) {
            return $this->searchProductsInDatabase($query);
        }
        
        $products = [];
        $searchTerm = strtolower($query);
        
        foreach ($xml->products->product as $product) {
            $name = strtolower((string)$product->basic_information->name);
            $description = strtolower((string)$product->basic_information->description);
            
            if (strpos($name, $searchTerm) !== false || strpos($description, $searchTerm) !== false) {
                $products[] = $this->xmlProductToArray($product);
            }
        }
        return $products;
    }
    
    public function getFeaturedProducts() {
        $xml = $this->loadXMLFile('products.xml');
        if (!$xml) {
            return $this->getFeaturedProductsFromDatabase();
        }
        
        $products = [];
        foreach ($xml->products->product as $product) {
            if ((string)$product['featured'] === 'true') {
                $products[] = $this->xmlProductToArray($product);
            }
        }
        return array_slice($products, 0, 6);
    }
    
    public function getTrendingProducts() {
        $xml = $this->loadXMLFile('products.xml');
        if (!$xml) {
            return $this->getTrendingProductsFromDatabase();
        }
        
        $products = [];
        foreach ($xml->products->product as $product) {
            if ((string)$product['trending'] === 'true') {
                $products[] = $this->xmlProductToArray($product);
            }
        }
        return array_slice($products, 0, 6);
    }
    
    // Category operations
    public function getAllCategories() {
        $xml = $this->loadXMLFile('categories.xml');
        if (!$xml) {
            return $this->getCategoriesFromDatabase();
        }
        
        $categories = [];
        foreach ($xml->category_list->category as $category) {
            $categories[] = [
                'id' => (string)$category['database_id'],
                'name' => (string)$category->display_name,
                'slug' => (string)$category['id'],
                'icon' => (string)$category->icon,
                'color' => (string)$category->color_class,
                'description' => (string)$category->description,
                'product_count' => (int)$category->product_count
            ];
        }
        return $categories;
    }
    
    public function getCategoryBySlug($slug) {
        $categories = $this->getAllCategories();
        foreach ($categories as $category) {
            if ($category['slug'] === $slug) {
                return $category;
            }
        }
        return null;
    }
    
    // Helper function to convert XML product to array
    private function xmlProductToArray($product) {
        return [
            'id' => (string)$product['id'],
            'name' => (string)$product->basic_information->name,
            'slug' => (string)$product->basic_information->slug,
            'description' => (string)$product->basic_information->description,
            'price' => (float)$product->pricing->current_price,
            'original_price' => isset($product->pricing->original_price) ? (float)$product->pricing->original_price : 0,
            'image_url' => (string)$product->media->image->url,
            'category_name' => (string)$product->category->name,
            'category_slug' => (string)$product->category->slug,
            'stock_quantity' => (int)$product->inventory->quantity,
            'is_featured' => (string)$product['featured'] === 'true' ? 1 : 0,
            'is_trending' => (string)$product['trending'] === 'true' ? 1 : 0,
            'rating' => isset($product->reviews->average_rating) ? (float)$product->reviews->average_rating : 0,
            'reviews_count' => isset($product->reviews->total_reviews) ? (int)$product->reviews->total_reviews : 0,
            'created_at' => (string)$product->timestamps->created_at,
            'updated_at' => (string)$product->timestamps->updated_at
        ];
    }
    
    // Database fallback methods
    private function getProductsFromDatabase() {
        $stmt = $this->pdo->query("
            SELECT p.*, 
                   COALESCE(c.name, 'Uncategorized') as category_name, 
                   COALESCE(c.slug, 'uncategorized') as category_slug,
                   COALESCE(p.original_price, 0) as original_price,
                   COALESCE(p.rating, 0) as rating,
                   COALESCE(p.reviews_count, 0) as reviews_count,
                   COALESCE(p.is_featured, 0) as is_featured,
                   COALESCE(p.is_trending, 0) as is_trending,
                   COALESCE(p.stock_quantity, 0) as stock_quantity
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getProductFromDatabase($id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, 
                   COALESCE(c.name, 'Uncategorized') as category_name, 
                   COALESCE(c.slug, 'uncategorized') as category_slug,
                   COALESCE(p.original_price, 0) as original_price,
                   COALESCE(p.rating, 0) as rating,
                   COALESCE(p.reviews_count, 0) as reviews_count,
                   COALESCE(p.is_featured, 0) as is_featured,
                   COALESCE(p.is_trending, 0) as is_trending,
                   COALESCE(p.stock_quantity, 0) as stock_quantity
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getCategoriesFromDatabase() {
        $stmt = $this->pdo->query("
            SELECT c.*, 
                   COALESCE(c.icon, 'fas fa-tag') as icon,
                   COALESCE(c.color, 'text-gray') as color,
                   COUNT(p.id) as product_count
            FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id 
            GROUP BY c.id 
            ORDER BY c.name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Cart operations (session-based)
    public function getCartItems($userId) {
        return isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    }
    
    public function addToCart($userId, $productId, $quantity = 1) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        $product = $this->getProductById($productId);
        if (!$product) return false;
        
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = [
                'product_id' => $productId,
                'name' => $product['name'],
                'price' => $product['price'],
                'image_url' => $product['image_url'],
                'quantity' => $quantity
            ];
        }
        return true;
    }
    
    public function removeFromCart($userId, $productId) {
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            return true;
        }
        return false;
    }
    
    public function updateCartQuantity($userId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($userId, $productId);
        }
        
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
            return true;
        }
        return false;
    }
    
    public function getCartItem($userId, $productId) {
        return isset($_SESSION['cart'][$productId]) ? $_SESSION['cart'][$productId] : null;
    }
    
    // Wishlist operations (session-based)
    public function getWishlistItems($userId) {
        return isset($_SESSION['wishlist']) ? $_SESSION['wishlist'] : [];
    }
    
    public function addToWishlist($userId, $productId) {
        if (!isset($_SESSION['wishlist'])) {
            $_SESSION['wishlist'] = [];
        }
        
        if (isset($_SESSION['wishlist'][$productId])) {
            return false; // Already in wishlist
        }
        
        $product = $this->getProductById($productId);
        if (!$product) return false;
        
        $_SESSION['wishlist'][$productId] = [
            'product_id' => $productId,
            'name' => $product['name'],
            'price' => $product['price'],
            'image_url' => $product['image_url']
        ];
        return true;
    }
    
    public function removeFromWishlist($userId, $productId) {
        if (isset($_SESSION['wishlist'][$productId])) {
            unset($_SESSION['wishlist'][$productId]);
            return true;
        }
        return false;
    }
    
    public function isInWishlist($userId, $productId) {
        return isset($_SESSION['wishlist'][$productId]);
    }
    
    // Force XML regeneration (called from admin panel)
    public function forceXMLUpdate() {
        return $this->generateXMLFromDatabase();
    }
    
    // Export functions
    public function exportProductsToXML() {
        $xmlFile = $this->xmlPath . 'products.xml';
        if (file_exists($xmlFile)) {
            return file_get_contents($xmlFile);
        }
        return null;
    }
    
    public function exportUserCartToXML($userId) {
        $cartItems = $this->getCartItems($userId);
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $root = $xml->createElement('cart');
        $root->setAttribute('user_id', $userId);
        $root->setAttribute('export_date', date('Y-m-d\TH:i:s\Z'));
        $xml->appendChild($root);
        
        $total = 0;
        foreach ($cartItems as $item) {
            $itemNode = $xml->createElement('item');
            $itemNode->appendChild($xml->createElement('product_id', $item['product_id']));
            $itemNode->appendChild($xml->createElement('name', htmlspecialchars($item['name'])));
            $itemNode->appendChild($xml->createElement('price', $item['price']));
            $itemNode->appendChild($xml->createElement('quantity', $item['quantity']));
            $itemNode->appendChild($xml->createElement('subtotal', $item['price'] * $item['quantity']));
            $total += $item['price'] * $item['quantity'];
            $root->appendChild($itemNode);
        }
        
        $root->appendChild($xml->createElement('total', $total));
        
        return $xml->saveXML();
    }
}

// Initialize dynamic XML operations
$db = new DynamicXMLOperations($pdo);
?>