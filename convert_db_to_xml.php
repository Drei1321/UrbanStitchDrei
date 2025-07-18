<?php
// Create this file as: convert_db_to_xml.php
// Run it ONCE to convert your database to XML

require_once 'config.php';

// Create XML directory if it doesn't exist
if (!file_exists('xml')) {
    mkdir('xml', 0755, true);
}

// Function to convert database products to XML
function convertProductsToXML($pdo) {
    // Get all products from database
    $stmt = $pdo->query("
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
    
    // Create XML
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    
    $root = $xml->createElement('urbanstitch_data');
    $root->setAttribute('xmlns', 'http://urbanstitch.com/products');
    $root->setAttribute('version', '1.0');
    $root->setAttribute('generated', date('Y-m-d\TH:i:s\Z'));
    $xml->appendChild($root);
    
    // Add categories first
    $categoriesContainer = $xml->createElement('categories');
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categories as $cat) {
        $categoryNode = $xml->createElement('category');
        $categoryNode->setAttribute('id', $cat['id']);
        $categoryNode->setAttribute('slug', $cat['slug']);
        
        $categoryNode->appendChild($xml->createElement('name', htmlspecialchars($cat['name'])));
        $categoryNode->appendChild($xml->createElement('icon', htmlspecialchars($cat['icon'] ?? 'fas fa-tag')));
        $categoryNode->appendChild($xml->createElement('color', htmlspecialchars($cat['color'] ?? 'text-gray')));
        $categoryNode->appendChild($xml->createElement('description', htmlspecialchars($cat['description'] ?? '')));
        
        // Count products in this category
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
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
        
        // Pricing Information
        $pricing = $xml->createElement('pricing');
        $pricing->setAttribute('currency', 'USD');
        $pricing->appendChild($xml->createElement('current_price', number_format($product['price'], 2)));
        if ($product['original_price'] > 0) {
            $pricing->appendChild($xml->createElement('original_price', number_format($product['original_price'], 2)));
            $discount_percent = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100, 2);
            $pricing->appendChild($xml->createElement('discount_percentage', $discount_percent));
            $pricing->appendChild($xml->createElement('savings', number_format($product['original_price'] - $product['price'], 2)));
        }
        $productNode->appendChild($pricing);
        
        // Category Information
        $category = $xml->createElement('category');
        $category->appendChild($xml->createElement('name', htmlspecialchars($product['category_name'])));
        $category->appendChild($xml->createElement('slug', htmlspecialchars($product['category_slug'])));
        $category->appendChild($xml->createElement('id', $product['category_id'] ?? 0));
        $productNode->appendChild($category);
        
        // Stock Information
        $stock = $xml->createElement('inventory');
        $stock->appendChild($xml->createElement('quantity', $product['stock_quantity']));
        $stock->appendChild($xml->createElement('status', $product['stock_quantity'] > 0 ? 'in_stock' : 'out_of_stock'));
        $productNode->appendChild($stock);
        
        // Media Information
        $media = $xml->createElement('media');
        if (!empty($product['image_url'])) {
            $image = $xml->createElement('image');
            $image->appendChild($xml->createElement('url', htmlspecialchars($product['image_url'])));
            $image->appendChild($xml->createElement('alt_text', htmlspecialchars($product['name'])));
            $media->appendChild($image);
        }
        $productNode->appendChild($media);
        
        // Rating and Reviews
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
    
    // Add statistics
    $statistics = $xml->createElement('statistics');
    $productStats = $xml->createElement('products');
    $productStats->appendChild($xml->createElement('total', count($products)));
    $productStats->appendChild($xml->createElement('featured', count(array_filter($products, function($p) { return $p['is_featured']; }))));
    $productStats->appendChild($xml->createElement('trending', count(array_filter($products, function($p) { return $p['is_trending']; }))));
    $productStats->appendChild($xml->createElement('in_stock', count(array_filter($products, function($p) { return $p['stock_quantity'] > 0; }))));
    $statistics->appendChild($productStats);
    $root->appendChild($statistics);
    
    // Save to file
    $xml->save('xml/products.xml');
    
    return count($products);
}

// Function to convert categories to XML
function convertCategoriesToXML($pdo) {
    $stmt = $pdo->query("
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
        
        $navigation = $xml->createElement('navigation');
        $navigation->appendChild($xml->createElement('url', '/index.php?category=' . $category['slug']));
        $navigation->appendChild($xml->createElement('breadcrumb', 'Home > Categories > ' . htmlspecialchars($category['name'])));
        $categoryNode->appendChild($navigation);
        
        $categoryList->appendChild($categoryNode);
    }
    $root->appendChild($categoryList);
    
    $xml->save('xml/categories.xml');
    
    return count($categories);
}

// Create a simple site config XML
function createSiteConfigXML() {
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
    $siteInfo->appendChild($xml->createElement('description', 'UrbanStitch is your destination for premium street fashion, urban wear, and lifestyle accessories that define modern street culture.'));
    $root->appendChild($siteInfo);
    
    // Business Settings
    $businessSettings = $xml->createElement('business_settings');
    $shipping = $xml->createElement('shipping');
    $shipping->appendChild($xml->createElement('free_shipping_threshold', '50.00'));
    $shipping->appendChild($xml->createElement('currency', 'USD'));
    $businessSettings->appendChild($shipping);
    $root->appendChild($businessSettings);
    
    $xml->save('xml/site_config.xml');
}

// Create empty users_orders.xml
function createEmptyUsersOrdersXML() {
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    
    $root = $xml->createElement('urbanstitch_users_orders');
    $root->setAttribute('xmlns', 'http://urbanstitch.com/users');
    $root->setAttribute('version', '1.0');
    $root->setAttribute('generated', date('Y-m-d\TH:i:s\Z'));
    $xml->appendChild($root);
    
    $metadata = $xml->createElement('metadata');
    $metadata->appendChild($xml->createElement('export_date', date('Y-m-d H:i:s')));
    $metadata->appendChild($xml->createElement('source', 'UrbanStitch E-commerce'));
    $root->appendChild($metadata);
    
    $users = $xml->createElement('users');
    $root->appendChild($users);
    
    $orders = $xml->createElement('orders');
    $root->appendChild($orders);
    
    $xml->save('xml/users_orders.xml');
}

// Run the conversion
try {
    echo "<h1>Converting UrbanStitch Database to XML</h1>";
    
    $productCount = convertProductsToXML($pdo);
    echo "<p>✅ Converted {$productCount} products to XML</p>";
    
    $categoryCount = convertCategoriesToXML($pdo);
    echo "<p>✅ Converted {$categoryCount} categories to XML</p>";
    
    createSiteConfigXML();
    echo "<p>✅ Created site configuration XML</p>";
    
    createEmptyUsersOrdersXML();
    echo "<p>✅ Created users/orders XML template</p>";
    
    echo "<h2>✅ Conversion Complete!</h2>";
    echo "<p>Your XML files are now ready in the /xml/ folder:</p>";
    echo "<ul>";
    echo "<li>xml/products.xml - {$productCount} products</li>";
    echo "<li>xml/categories.xml - {$categoryCount} categories</li>";
    echo "<li>xml/site_config.xml - Site configuration</li>";
    echo "<li>xml/users_orders.xml - Template for users/orders</li>";
    echo "</ul>";
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Create the xml_operations.php file</li>";
    echo "<li>Make sure index.php includes xml_operations.php</li>";
    echo "<li>Test your website - products should now be visible!</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>