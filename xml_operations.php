<?php
// NEW FILE: xml_operations.php - Replace database_operations.php with this

class XMLOperations {
    private $productsXML;
    private $categoriesXML;
    private $usersXML;
    private $configXML;
    
    public function __construct() {
        $this->loadXMLFiles();
    }
    
    private function loadXMLFiles() {
        // Load the XML files I created
        $this->productsXML = $this->loadXML('products.xml');
        $this->categoriesXML = $this->loadXML('categories.xml');
        $this->usersXML = $this->loadXML('users_orders.xml');
        $this->configXML = $this->loadXML('site_config.xml');
    }
    
    private function loadXML($filename) {
        $filepath = __DIR__ . '/xml/' . $filename;
        if (file_exists($filepath)) {
            return simplexml_load_file($filepath);
        }
        return null;
    }
    
    // Product operations using XML
    public function getAllProducts() {
        if (!$this->productsXML) return [];
        
        $products = [];
        foreach ($this->productsXML->products->product as $product) {
            $products[] = $this->xmlProductToArray($product);
        }
        return $products;
    }
    
    public function getProductById($id) {
        if (!$this->productsXML) return null;
        
        foreach ($this->productsXML->products->product as $product) {
            if ((string)$product['id'] === (string)$id) {
                return $this->xmlProductToArray($product);
            }
        }
        return null;
    }
    
    public function getProductsByCategory($categorySlug) {
        if (!$this->productsXML) return [];
        
        $products = [];
        foreach ($this->productsXML->products->product as $product) {
            if ((string)$product->category->slug === $categorySlug) {
                $products[] = $this->xmlProductToArray($product);
            }
        }
        return $products;
    }
    
    public function searchProducts($query) {
        if (!$this->productsXML) return [];
        
        $products = [];
        $searchTerm = strtolower($query);
        
        foreach ($this->productsXML->products->product as $product) {
            $name = strtolower((string)$product->basic_information->name);
            $description = strtolower((string)$product->basic_information->description);
            
            if (strpos($name, $searchTerm) !== false || strpos($description, $searchTerm) !== false) {
                $products[] = $this->xmlProductToArray($product);
            }
        }
        return $products;
    }
    
    public function getFeaturedProducts() {
        if (!$this->productsXML) return [];
        
        $products = [];
        foreach ($this->productsXML->products->product as $product) {
            if ((string)$product['featured'] === 'true') {
                $products[] = $this->xmlProductToArray($product);
            }
        }
        return array_slice($products, 0, 6);
    }
    
    public function getTrendingProducts() {
        if (!$this->productsXML) return [];
        
        $products = [];
        foreach ($this->productsXML->products->product as $product) {
            if ((string)$product['trending'] === 'true') {
                $products[] = $this->xmlProductToArray($product);
            }
        }
        return array_slice($products, 0, 6);
    }
    
    // Category operations using XML
    public function getAllCategories() {
        if (!$this->categoriesXML) return [];
        
        $categories = [];
        foreach ($this->categoriesXML->category_list->category as $category) {
            $categories[] = [
                'id' => (string)$category['database_id'],
                'name' => (string)$category->name,
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
        if (!$this->categoriesXML) return null;
        
        foreach ($this->categoriesXML->category_list->category as $category) {
            if ((string)$category['id'] === $slug) {
                return [
                    'id' => (string)$category['database_id'],
                    'name' => (string)$category->name,
                    'slug' => (string)$category['id'],
                    'icon' => (string)$category->icon,
                    'color' => (string)$category->color_class,
                    'description' => (string)$category->description,
                    'product_count' => (int)$category->product_count
                ];
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
    
    // Cart operations (keep using sessions for now)
    public function getCartItems($userId) {
        // Keep using session-based cart for now
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
    
    // Wishlist operations (keep using sessions)
    public function getWishlistItems($userId) {
        return isset($_SESSION['wishlist']) ? $_SESSION['wishlist'] : [];
    }
    
    public function addToWishlist($userId, $productId) {
        if (!isset($_SESSION['wishlist'])) {
            $_SESSION['wishlist'] = [];
        }
        
        if (in_array($productId, $_SESSION['wishlist'])) {
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
    
    // XML Export functions
    public function exportProductsToXML() {
        return file_get_contents(__DIR__ . '/xml/products.xml');
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

// Initialize XML operations
$db = new XMLOperations();
?>