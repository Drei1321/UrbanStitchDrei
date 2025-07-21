<?php
// Enhanced UrbanStitch - Admin Dashboard with XML Sync, Image Watermarking, and Size Management
require_once 'config.php';
require_once 'dynamic_xml_operations.php';

// AJAX handlers for order management - ONLY respond to order-related actions
if (isset($_GET['action']) && in_array($_GET['action'], ['get_order_details', 'get_order_status'])) {
    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'get_order_details':
            $orderId = (int)$_GET['order_id'];

            try {
                // Get order details
                $stmt = $pdo->prepare("
                    SELECT o.*, u.username, u.email, u.first_name, u.last_name
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    WHERE o.id = ?
                ");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$order) {
                    echo json_encode(['success' => false, 'message' => 'Order not found']);
                    exit;
                }

                // Get order items
                $stmt = $pdo->prepare("
                    SELECT oi.*, p.name, p.image_url
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$orderId]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Get status history
                $stmt = $pdo->prepare("
                    SELECT osh.*, u.username as admin_username
                    FROM order_status_history osh
                    LEFT JOIN users u ON osh.admin_id = u.id
                    WHERE osh.order_id = ?
                    ORDER BY osh.created_at DESC
                ");
                $stmt->execute([$orderId]);
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'order' => $order,
                    'items' => $items,
                    'history' => $history
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'get_order_status':
            $orderId = (int)$_GET['order_id'];

            try {
                $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    echo json_encode(['success' => true, 'status' => $result['status']]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Order not found']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
    }
}

// Size Management System Classes
class SizeManager
{
    private $pdo;

    private $sizeTypes = [
        'apparel' => [
            'XS' => 'Extra Small',
            'S' => 'Small',
            'M' => 'Medium',
            'L' => 'Large',
            'XL' => 'Extra Large',
            'XXL' => '2X Large',
            'XXXL' => '3X Large'
        ],
        'footwear' => [
            '7' => 'Size 7',
            '7.5' => 'Size 7.5',
            '8' => 'Size 8',
            '8.5' => 'Size 8.5',
            '9' => 'Size 9',
            '9.5' => 'Size 9.5',
            '10' => 'Size 10',
            '10.5' => 'Size 10.5',
            '11' => 'Size 11',
            '11.5' => 'Size 11.5',
            '12' => 'Size 12'
        ]
    ];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->createSizeTables();
    }

    private function createSizeTables()
    {
        // Product sizes table
        $sql1 = "CREATE TABLE IF NOT EXISTS product_sizes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            size_code VARCHAR(10) NOT NULL,
            size_name VARCHAR(50) NOT NULL,
            stock_quantity INT DEFAULT 0,
            price_adjustment DECIMAL(10,2) DEFAULT 0.00,
            is_available BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_product_size (product_id, size_code)
        )";

        // Product types table
        $sql2 = "CREATE TABLE IF NOT EXISTS product_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            size_type ENUM('apparel', 'footwear', 'accessories') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->pdo->exec($sql1);
        $this->pdo->exec($sql2);

        // Add product_type_id column to products table if it doesn't exist
        try {
            $this->pdo->exec("ALTER TABLE products ADD COLUMN product_type_id INT NULL, ADD FOREIGN KEY (product_type_id) REFERENCES product_types(id)");
        } catch (Exception $e) {
            // Column might already exist
        }

        $this->insertDefaultProductTypes();
    }

    private function insertDefaultProductTypes()
    {
        $types = [
            ['T-Shirts', 'apparel'],
            ['Hoodies', 'apparel'],
            ['Jeans', 'apparel'],
            ['Shirts', 'apparel'],
            ['Sneakers', 'footwear'],
            ['Sandals', 'footwear']
        ];

        foreach ($types as $type) {
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO product_types (name, size_type) VALUES (?, ?)");
            $stmt->execute($type);
        }
    }

    public function getProductTypes()
    {
        $stmt = $this->pdo->query("SELECT * FROM product_types ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addProductSizes($productId, $sizes)
    {
        try {
            // Check if we're already in a transaction
            $wasInTransaction = $this->pdo->inTransaction();

            if (!$wasInTransaction) {
                $this->pdo->beginTransaction();
            }

            // Remove existing sizes for this product
            $stmt = $this->pdo->prepare("DELETE FROM product_sizes WHERE product_id = ?");
            if (!$stmt->execute([$productId])) {
                throw new Exception("Failed to delete existing sizes");
            }

            // Add new sizes
            $stmt = $this->pdo->prepare("INSERT INTO product_sizes (product_id, size_code, size_name, stock_quantity, price_adjustment) VALUES (?, ?, ?, ?, ?)");

            foreach ($sizes as $size) {
                if (!$stmt->execute([
                    $productId,
                    $size['code'],
                    $size['name'],
                    $size['stock'] ?? 0,
                    $size['price_adjustment'] ?? 0.00
                ])) {
                    throw new Exception("Failed to insert size: " . $size['code']);
                }
            }

            // Only commit if we started the transaction
            if (!$wasInTransaction) {
                $this->pdo->commit();
            }

            return true;
        } catch (Exception $e) {
            // Only rollback if we started the transaction
            if (!$wasInTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollback();
            }
            error_log("SizeManager Error: " . $e->getMessage());
            return false;
        }
    }

    public function getProductSizes($productId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM product_sizes WHERE product_id = ? ORDER BY 
            CASE 
                WHEN size_code IN ('XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL') THEN 
                    FIELD(size_code, 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL')
                WHEN size_code REGEXP '^[0-9]+(\.[0-9]+)?$' THEN 
                    CAST(size_code AS DECIMAL(4,1))
                ELSE 999
            END");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateSizeStock($productId, $sizeCode, $newStock)
    {
        $stmt = $this->pdo->prepare("UPDATE product_sizes SET stock_quantity = ?, updated_at = NOW() WHERE product_id = ? AND size_code = ?");
        return $stmt->execute([$newStock, $productId, $sizeCode]);
    }

    public function getTotalProductStock($productId)
    {
        $stmt = $this->pdo->prepare("SELECT SUM(stock_quantity) as total_stock FROM product_sizes WHERE product_id = ?");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_stock'] ?? 0;
    }
}

// Image Processing Class (keeping your existing class)
class ImageProcessor
{
    private $uploadDir = 'uploads/products/';
    private $watermarkPath = 'assets/watermark.png';
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private $maxFileSize = 5 * 1024 * 1024;

    public function __construct()
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function processImage($file, $watermarkText = '', $position = 'bottom-right', $opacity = 70)
    {
        try {
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . uniqid() . '_' . time() . '.' . $extension;
            $filepath = $this->uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return ['success' => false, 'error' => 'Failed to upload file'];
            }

            $watermarkedPath = $this->addWatermark($filepath, $watermarkText, $position, $opacity);

            if ($watermarkedPath) {
                if ($watermarkedPath !== $filepath) {
                    unlink($filepath);
                    rename($watermarkedPath, $filepath);
                }

                return [
                    'success' => true,
                    'filepath' => $filepath,
                    'url' => $this->getImageUrl($filepath)
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to add watermark'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function processImageFromUrl($imageUrl, $watermarkText = '', $position = 'bottom-right', $opacity = 70)
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'UrbanStitch/1.0'
                ]
            ]);

            $imageData = file_get_contents($imageUrl, false, $context);
            if ($imageData === false) {
                return ['success' => false, 'error' => 'Failed to download image from URL'];
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'img_') . '.jpg';
            file_put_contents($tempFile, $imageData);

            $imageInfo = getimagesize($tempFile);
            if ($imageInfo === false) {
                unlink($tempFile);
                return ['success' => false, 'error' => 'Invalid image from URL'];
            }

            $filename = 'product_url_' . uniqid() . '_' . time() . '.jpg';
            $filepath = $this->uploadDir . $filename;

            copy($tempFile, $filepath);
            unlink($tempFile);

            $watermarkedPath = $this->addWatermark($filepath, $watermarkText, $position, $opacity);

            if ($watermarkedPath) {
                if ($watermarkedPath !== $filepath) {
                    unlink($filepath);
                    rename($watermarkedPath, $filepath);
                }

                return [
                    'success' => true,
                    'filepath' => $filepath,
                    'url' => $this->getImageUrl($filepath)
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to add watermark'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function validateFile($file)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error: ' . $file['error']];
        }

        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'error' => 'File too large. Maximum size: 5MB'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type. Only JPEG, PNG, and GIF allowed.'];
        }

        return ['success' => true];
    }

    private function addWatermark($imagePath, $watermarkText = '', $position = 'bottom-right', $opacity = 70)
    {
        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) {
            return false;
        }

        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($imagePath);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        if (file_exists($this->watermarkPath)) {
            $this->addImageWatermark($image, $imageWidth, $imageHeight, $position, $opacity);
        }

        if (!empty($watermarkText)) {
            $this->addTextWatermark($image, $imageWidth, $imageHeight, $watermarkText, $position, $opacity);
        } else {
            $this->addTextWatermark($image, $imageWidth, $imageHeight, 'UrbanStitch', $position, $opacity);
        }

        $watermarkedPath = $this->uploadDir . 'wm_' . basename($imagePath);

        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                imagejpeg($image, $watermarkedPath, 90);
                break;
            case 'image/png':
                imagepng($image, $watermarkedPath);
                break;
            case 'image/gif':
                imagegif($image, $watermarkedPath);
                break;
        }

        imagedestroy($image);
        return $watermarkedPath;
    }

    private function addImageWatermark($image, $imageWidth, $imageHeight, $position, $opacity)
    {
        $watermark = imagecreatefrompng($this->watermarkPath);
        if (!$watermark) {
            return;
        }

        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);

        switch ($position) {
            case 'top-left':
                $x = 10;
                $y = 10;
                break;
            case 'top-right':
                $x = $imageWidth - $watermarkWidth - 10;
                $y = 10;
                break;
            case 'bottom-left':
                $x = 10;
                $y = $imageHeight - $watermarkHeight - 10;
                break;
            case 'center':
                $x = ($imageWidth - $watermarkWidth) / 2;
                $y = ($imageHeight - $watermarkHeight) / 2;
                break;
            default:
                $x = $imageWidth - $watermarkWidth - 10;
                $y = $imageHeight - $watermarkHeight - 10;
                break;
        }

        $x = max(0, min($x, $imageWidth - $watermarkWidth));
        $y = max(0, min($y, $imageHeight - $watermarkHeight));

        imagecopymerge($image, $watermark, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight, $opacity);
        imagedestroy($watermark);
    }

    private function addTextWatermark($image, $imageWidth, $imageHeight, $text, $position, $opacity)
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $green = imagecolorallocate($image, 0, 255, 0);

        $fontSize = max(16, min(32, $imageWidth / 25)); // Increased font size
        $fontPath = __DIR__ . '/fonts/arial.ttf';
        $useBuiltInFont = !file_exists($fontPath);

        // Calculate text dimensions
        if ($useBuiltInFont) {
            $font = 5; // Use largest built-in font
            $textWidth = strlen($text) * imagefontwidth($font);
            $textHeight = imagefontheight($font);
        } else {
            $textBox = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = $textBox[4] - $textBox[6];
            $textHeight = $textBox[3] - $textBox[5];
        }

        $padding = 25; // Increased padding for visibility

        // Calculate base coordinates for each position
        switch ($position) {
            case 'top-left':
                $baseX = $padding;
                $baseY = $padding;
                break;

            case 'top-right':
                $baseX = $imageWidth - $textWidth - $padding;
                $baseY = $padding;
                break;

            case 'bottom-left':
                $baseX = $padding;
                $baseY = $imageHeight - $textHeight - $padding;
                break;

            case 'bottom-right':
                $baseX = $imageWidth - $textWidth - $padding;
                $baseY = $imageHeight - $textHeight - $padding;
                break;

            case 'center':
            default:
                $baseX = ($imageWidth - $textWidth) / 2;
                $baseY = ($imageHeight - $textHeight) / 2;
                break;
        }

        // Ensure coordinates are within bounds
        $baseX = max(5, min($baseX, $imageWidth - $textWidth - 5));
        $baseY = max(5, min($baseY, $imageHeight - $textHeight - 5));

        // Different coordinate systems for different font types
        if ($useBuiltInFont) {
            // Built-in fonts: coordinates are top-left of text
            $x = $baseX;
            $y = $baseY;

            // Add shadow
            imagestring($image, $font, $x + 2, $y + 2, $text, $black);
            // Add main text
            imagestring($image, $font, $x, $y, $text, $green);
        } else {
            // TTF fonts: coordinates are baseline of text
            $x = $baseX;
            $y = $baseY + $textHeight; // Add text height to move to baseline

            // Add shadow
            imagettftext($image, $fontSize, 0, $x + 2, $y + 2, $black, $fontPath, $text);
            // Add main text
            imagettftext($image, $fontSize, 0, $x, $y, $green, $fontPath, $text);
        }

        // Enhanced debug logging
        error_log("Watermark Debug - Position: $position, UseBuiltIn: " . ($useBuiltInFont ? 'YES' : 'NO') . ", BaseX: $baseX, BaseY: $baseY, FinalX: $x, FinalY: $y, TextSize: {$textWidth}x{$textHeight}, ImageSize: {$imageWidth}x{$imageHeight}, FontSize: $fontSize");
    }

    private function getImageUrl($filepath)
    {
        return '/' . $filepath;
    }

    public function deleteImage($filepath)
    {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}

// Check if user is admin
function requireAdmin()
{
    if (!isLoggedIn() || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('Location: login.php');
        exit;
    }
}

requireAdmin();

// Initialize size manager
$sizeManager = new SizeManager($pdo);

// Handle admin actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_category':
            $name = trim($_POST['name']);
            $slug = strtolower(str_replace(' ', '-', $name));
            $icon = $_POST['icon'];
            $color = $_POST['color'];
            $description = trim($_POST['description']);

            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon, color, description, updated_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$name, $slug, $icon, $color, $description])) {
                $db->forceXMLUpdate();
                $message = 'Category added successfully and XML files updated!';
                $messageType = 'success';
            } else {
                $message = 'Failed to add category.';
                $messageType = 'error';
            }
            break;
        case 'delete_category':
            $category_id = (int)$_POST['category_id'];
            $force_delete = isset($_POST['force_delete']) && $_POST['force_delete'] === '1';

            try {
                // Get category info before deletion
                $stmt = $pdo->prepare("SELECT id, name, slug FROM categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$category) {
                    $message = 'Category not found.';
                    $messageType = 'error';
                    break;
                }

                // Check if category has products
                $stmt = $pdo->prepare("SELECT COUNT(*) as product_count FROM products WHERE category_id = ?");
                $stmt->execute([$category_id]);
                $product_info = $stmt->fetch(PDO::FETCH_ASSOC);

                // If category has products and force delete is not requested, show warning
                if ($product_info['product_count'] > 0 && !$force_delete) {
                    $message = "Cannot delete category \"{$category['name']}\" because it contains {$product_info['product_count']} product(s). Use 'Force Delete' to remove the category and move products to 'Uncategorized'.";
                    $messageType = 'error';
                    break;
                }

                // Start transaction for safe deletion
                $pdo->beginTransaction();

                try {
                    // If force delete, move products to uncategorized or delete them
                    if ($force_delete && $product_info['product_count'] > 0) {
                        // Option 1: Move products to uncategorized (category_id = NULL)
                        $stmt = $pdo->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?");
                        $stmt->execute([$category_id]);

                        // Option 2: Or delete all products in this category (uncomment if preferred)
                        // $stmt = $pdo->prepare("DELETE FROM products WHERE category_id = ?");
                        // $stmt->execute([$category_id]);
                    }

                    // Delete the category
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    if (!$stmt->execute([$category_id])) {
                        throw new Exception("Failed to delete category from database");
                    }

                    // Check if any rows were affected
                    if ($stmt->rowCount() === 0) {
                        throw new Exception("No category found with the specified ID");
                    }

                    // Commit the transaction
                    $pdo->commit();

                    // Update XML files
                    $db->forceXMLUpdate();

                    $message = 'Category "' . htmlspecialchars($category['name']) . '" deleted successfully!';
                    if ($force_delete && $product_info['product_count'] > 0) {
                        $message .= " {$product_info['product_count']} product(s) moved to uncategorized.";
                    }
                    $message .= ' XML files updated.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    // Rollback transaction on error
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $message = 'Failed to delete category: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                error_log("Delete category error: " . $e->getMessage());
                $message = 'An error occurred while processing the request: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;

        case 'check_category_dependencies':
            $category_id = (int)$_POST['category_id'];

            try {
                // Get category info
                $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$category) {
                    echo json_encode(['error' => 'Category not found']);
                    exit;
                }

                // Check products
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                $stmt->execute([$category_id]);
                $products = $stmt->fetch(PDO::FETCH_ASSOC);

                echo json_encode([
                    'category_name' => $category['name'],
                    'product_count' => $products['count'],
                    'can_delete_simple' => $products['count'] == 0,
                    'requires_force' => $products['count'] > 0
                ]);
                exit;
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
                exit;
            }
            break;

        case 'add_product_with_sizes':
            $imageProcessor = new ImageProcessor();
            $imageResult = null;

            $addWatermark = isset($_POST['add_watermark']);
            $watermarkText = $_POST['watermark_text'] ?? 'UrbanStitch';
            $watermarkPosition = $_POST['watermark_position'] ?? 'bottom-right';
            $watermarkOpacity = (int)($_POST['watermark_opacity'] ?? 70);

            // Handle image processing first
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                if ($addWatermark) {
                    $imageResult = $imageProcessor->processImage($_FILES['product_image'], $watermarkText, $watermarkPosition, $watermarkOpacity);
                } else {
                    $extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                    $filename = 'product_' . uniqid() . '_' . time() . '.' . $extension;
                    $filepath = 'uploads/products/' . $filename;

                    if (!is_dir('uploads/products/')) {
                        mkdir('uploads/products/', 0755, true);
                    }

                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $filepath)) {
                        $imageResult = ['success' => true, 'url' => '/' . $filepath];
                    }
                }
            } elseif (!empty($_POST['image_url'])) {
                if ($addWatermark) {
                    $imageResult = $imageProcessor->processImageFromUrl($_POST['image_url'], $watermarkText, $watermarkPosition, $watermarkOpacity);
                } else {
                    $imageResult = ['success' => true, 'url' => $_POST['image_url']];
                }
            }

            // Only proceed if image processing was successful
            if ($imageResult && $imageResult['success']) {
                try {
                    // Clear any existing transaction state
                    if ($pdo->inTransaction()) {
                        error_log("Warning: Transaction already active, rolling back");
                        $pdo->rollBack();
                    }

                    // Start fresh transaction
                    $pdo->beginTransaction();

                    // Get form data
                    $image_url = $imageResult['url'];
                    $name = trim($_POST['name']);
                    $slug = strtolower(str_replace(' ', '-', $name));
                    $description = trim($_POST['description']);
                    $price = (float)$_POST['price'];
                    $original_price = (float)$_POST['original_price'];
                    $category_id = (int)$_POST['category_id'];
                    $product_type_id = !empty($_POST['product_type_id']) ? (int)$_POST['product_type_id'] : null;
                    $tags = trim($_POST['tags']);
                    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                    $is_trending = isset($_POST['is_trending']) ? 1 : 0;

                    // Insert the product
                    $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, price, original_price, category_id, image_url, tags, is_featured, is_trending, product_type_id, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                    if (!$stmt->execute([$name, $slug, $description, $price, $original_price, $category_id, $image_url, $tags, $is_featured, $is_trending, $product_type_id])) {
                        throw new Exception("Failed to insert product");
                    }

                    $productId = $pdo->lastInsertId();

                    // Handle sizes if provided
                    $sizes = [];
                    $totalStock = 0;

                    if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
                        foreach ($_POST['sizes'] as $sizeData) {
                            if (!empty($sizeData['code']) && !empty($sizeData['name'])) {
                                $sizes[] = [
                                    'code' => $sizeData['code'],
                                    'name' => $sizeData['name'],
                                    'stock' => (int)($sizeData['stock'] ?? 0),
                                    'price_adjustment' => (float)($sizeData['price_adjustment'] ?? 0.00)
                                ];
                                $totalStock += (int)($sizeData['stock'] ?? 0);
                            }
                        }

                        // Add sizes if any were provided
                        if (!empty($sizes)) {
                            if (!$sizeManager->addProductSizes($productId, $sizes)) {
                                throw new Exception("Failed to add product sizes");
                            }
                        }
                    } else {
                        // Use fallback stock quantity if no sizes provided
                        $totalStock = (int)($_POST['stock_quantity'] ?? 0);
                    }

                    // Update product with total stock
                    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
                    if (!$stmt->execute([$totalStock, $productId])) {
                        throw new Exception("Failed to update product stock");
                    }

                    // Commit the transaction
                    $pdo->commit();

                    // Force XML update after successful database commit
                    $db->forceXMLUpdate();

                    $sizeMessage = !empty($sizes) ? ' with ' . count($sizes) . ' sizes' : '';
                    $watermarkMsg = $addWatermark ? ' and watermark' : '';
                    $message = 'Product added successfully' . $sizeMessage . $watermarkMsg . '!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    // Rollback transaction if still active
                    try {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                    } catch (Exception $rollbackException) {
                        error_log("Rollback failed: " . $rollbackException->getMessage());
                    }
                    $message = 'Failed to add product: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = 'Failed to process image: ' . ($imageResult['error'] ?? 'Unknown error');
                $messageType = 'error';
            }
            break;

        case 'update_size_stock':
            $productId = (int)$_POST['product_id'];
            $sizeCode = $_POST['size_code'];
            $newStock = (int)$_POST['new_stock'];

            if ($sizeManager->updateSizeStock($productId, $sizeCode, $newStock)) {
                $totalStock = $sizeManager->getTotalProductStock($productId);
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$totalStock, $productId]);

                $db->forceXMLUpdate();
                $message = 'Size stock updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to update size stock.';
                $messageType = 'error';
            }
            break;

        case 'add_product_with_image':
            $imageProcessor = new ImageProcessor();
            $imageResult = null;

            $addWatermark = isset($_POST['add_watermark']);
            $watermarkText = $_POST['watermark_text'] ?? 'UrbanStitch';
            $watermarkPosition = $_POST['watermark_position'] ?? 'bottom-right';
            $watermarkOpacity = (int)($_POST['watermark_opacity'] ?? 70);

            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                if ($addWatermark) {
                    $imageResult = $imageProcessor->processImage($_FILES['product_image'], $watermarkText, $watermarkPosition, $watermarkOpacity);
                } else {
                    $extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                    $filename = 'product_' . uniqid() . '_' . time() . '.' . $extension;
                    $filepath = 'uploads/products/' . $filename;

                    if (!is_dir('uploads/products/')) {
                        mkdir('uploads/products/', 0755, true);
                    }

                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $filepath)) {
                        $imageResult = ['success' => true, 'url' => '/' . $filepath];
                    }
                }
            } elseif (!empty($_POST['image_url'])) {
                if ($addWatermark) {
                    $imageResult = $imageProcessor->processImageFromUrl($_POST['image_url'], $watermarkText, $watermarkPosition, $watermarkOpacity);
                } else {
                    $imageResult = ['success' => true, 'url' => $_POST['image_url']];
                }
            }

            if ($imageResult && $imageResult['success']) {
                $image_url = $imageResult['url'];
                $name = trim($_POST['name']);
                $slug = strtolower(str_replace(' ', '-', $name));
                $description = trim($_POST['description']);
                $price = (float)$_POST['price'];
                $original_price = (float)$_POST['original_price'];
                $category_id = (int)$_POST['category_id'];
                $stock_quantity = (int)$_POST['stock_quantity'];
                $tags = trim($_POST['tags']);
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $is_trending = isset($_POST['is_trending']) ? 1 : 0;

                $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, price, original_price, category_id, image_url, stock_quantity, tags, is_featured, is_trending, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                if ($stmt->execute([$name, $slug, $description, $price, $original_price, $category_id, $image_url, $stock_quantity, $tags, $is_featured, $is_trending])) {
                    $db->forceXMLUpdate();
                    $message = 'Product added successfully and XML files updated!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add product.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Failed to process image: ' . ($imageResult['error'] ?? 'Unknown error');
                $messageType = 'error';
            }
            break;

        case 'update_stock':
            $product_id = (int)$_POST['product_id'];
            $new_stock = (int)$_POST['new_stock'];

            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$new_stock, $product_id])) {
                $db->forceXMLUpdate();
                $message = 'Stock updated successfully and XML files updated!';
                $messageType = 'success';
            }
            break;
        case 'delete_category':
            $category_id = (int)$_POST['category_id'];
            $force_delete = isset($_POST['force_delete']) && $_POST['force_delete'] === '1';

            try {
                // Get category info before deletion
                $stmt = $pdo->prepare("SELECT id, name, slug FROM categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$category) {
                    $message = 'Category not found.';
                    $messageType = 'error';
                    break;
                }

                // Check if category has products
                $stmt = $pdo->prepare("SELECT COUNT(*) as product_count FROM products WHERE category_id = ?");
                $stmt->execute([$category_id]);
                $product_info = $stmt->fetch(PDO::FETCH_ASSOC);

                // If category has products and force delete is not requested, show warning
                if ($product_info['product_count'] > 0 && !$force_delete) {
                    $message = "Cannot delete category \"{$category['name']}\" because it contains {$product_info['product_count']} product(s). Use 'Force Delete' to remove the category and move products to 'Uncategorized'.";
                    $messageType = 'error';
                    break;
                }

                // Start transaction for safe deletion
                $pdo->beginTransaction();

                try {
                    // If force delete, move products to uncategorized or delete them
                    if ($force_delete && $product_info['product_count'] > 0) {
                        // Option 1: Move products to uncategorized (category_id = NULL)
                        $stmt = $pdo->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?");
                        $stmt->execute([$category_id]);

                        // Option 2: Or delete all products in this category (uncomment if preferred)
                        // $stmt = $pdo->prepare("DELETE FROM products WHERE category_id = ?");
                        // $stmt->execute([$category_id]);
                    }

                    // Delete the category
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    if (!$stmt->execute([$category_id])) {
                        throw new Exception("Failed to delete category from database");
                    }

                    // Check if any rows were affected
                    if ($stmt->rowCount() === 0) {
                        throw new Exception("No category found with the specified ID");
                    }

                    // Commit the transaction
                    $pdo->commit();

                    // Update XML files
                    $db->forceXMLUpdate();

                    $message = 'Category "' . htmlspecialchars($category['name']) . '" deleted successfully!';
                    if ($force_delete && $product_info['product_count'] > 0) {
                        $message .= " {$product_info['product_count']} product(s) moved to uncategorized.";
                    }
                    $message .= ' XML files updated.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    // Rollback transaction on error
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $message = 'Failed to delete category: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                error_log("Delete category error: " . $e->getMessage());
                $message = 'An error occurred while processing the request: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;

        case 'check_category_dependencies':
            $category_id = (int)$_POST['category_id'];

            try {
                // Get category info
                $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$category) {
                    echo json_encode(['error' => 'Category not found']);
                    exit;
                }

                // Check products
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                $stmt->execute([$category_id]);
                $products = $stmt->fetch(PDO::FETCH_ASSOC);

                echo json_encode([
                    'category_name' => $category['name'],
                    'product_count' => $products['count'],
                    'can_delete_simple' => $products['count'] == 0,
                    'requires_force' => $products['count'] > 0
                ]);
                exit;
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
                exit;
            }
            break;
        case 'delete_product':
            $product_id = (int)$_POST['product_id'];
            $force_delete = isset($_POST['force_delete']) && $_POST['force_delete'] === '1';

            try {
                // Get product info before deletion
                $stmt = $pdo->prepare("SELECT id, name, image_url FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    $message = 'Product not found.';
                    $messageType = 'error';
                    break;
                }

                // Check if product is referenced in orders
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as order_count, 
                           COUNT(DISTINCT o.id) as unique_orders,
                           MIN(o.created_at) as first_order,
                           MAX(o.created_at) as last_order
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE oi.product_id = ?
                ");
                $stmt->execute([$product_id]);
                $order_info = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check if product is in any carts
                $stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM cart_items WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $cart_info = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check if product has reviews (if table exists)
                $review_info = ['review_count' => 0];
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as review_count FROM product_reviews WHERE product_id = ?");
                    $stmt->execute([$product_id]);
                    $review_info = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    // Table might not exist, ignore
                }

                // If product has orders and force delete is not requested, show options
                if ($order_info['order_count'] > 0 && !$force_delete) {
                    $message = "Cannot delete product \"{$product['name']}\" because it has been ordered {$order_info['order_count']} times in {$order_info['unique_orders']} different orders. ";
                    $message .= "First ordered: " . date('M j, Y', strtotime($order_info['first_order'])) . ", ";
                    $message .= "Last ordered: " . date('M j, Y', strtotime($order_info['last_order'])) . ". ";

                    if ($cart_info['cart_count'] > 0) {
                        $message .= "It's also in {$cart_info['cart_count']} shopping cart(s). ";
                    }

                    if ($review_info['review_count'] > 0) {
                        $message .= "It has {$review_info['review_count']} review(s). ";
                    }

                    $message .= "Use 'Force Delete' option if you really want to remove it (this will also remove it from all orders).";
                    $messageType = 'error';
                    break;
                }

                // Start transaction for safe deletion
                $pdo->beginTransaction();

                try {
                    $deleted_details = [];

                    // Always clean up related data first

                    // 1. Delete from cart_items
                    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE product_id = ?");
                    $stmt->execute([$product_id]);
                    $deleted_cart_items = $stmt->rowCount();
                    if ($deleted_cart_items > 0) {
                        $deleted_details[] = "Removed from {$deleted_cart_items} shopping cart(s)";
                    }

                    // 2. Delete from wishlist_items (if table exists)
                    try {
                        $stmt = $pdo->prepare("DELETE FROM wishlist_items WHERE product_id = ?");
                        $stmt->execute([$product_id]);
                        $deleted_wishlist_items = $stmt->rowCount();
                        if ($deleted_wishlist_items > 0) {
                            $deleted_details[] = "Removed from {$deleted_wishlist_items} wishlist(s)";
                        }
                    } catch (Exception $e) {
                        // Table might not exist, continue
                    }

                    // 3. Delete product reviews (if table exists)
                    try {
                        $stmt = $pdo->prepare("DELETE FROM product_reviews WHERE product_id = ?");
                        $stmt->execute([$product_id]);
                        $deleted_reviews = $stmt->rowCount();
                        if ($deleted_reviews > 0) {
                            $deleted_details[] = "Deleted {$deleted_reviews} review(s)";
                        }
                    } catch (Exception $e) {
                        // Table might not exist, continue
                    }

                    // 4. Handle order_items - ONLY if force delete is requested
                    if ($force_delete && $order_info['order_count'] > 0) {
                        // Delete order items (this will affect order history)
                        $stmt = $pdo->prepare("DELETE FROM order_items WHERE product_id = ?");
                        $stmt->execute([$product_id]);
                        $deleted_order_items = $stmt->rowCount();
                        if ($deleted_order_items > 0) {
                            $deleted_details[] = "Removed from {$deleted_order_items} order item(s) - ORDER HISTORY AFFECTED";
                        }
                    }

                    // 5. Delete product sizes
                    $stmt = $pdo->prepare("DELETE FROM product_sizes WHERE product_id = ?");
                    $stmt->execute([$product_id]);
                    $deleted_sizes = $stmt->rowCount();
                    if ($deleted_sizes > 0) {
                        $deleted_details[] = "Deleted {$deleted_sizes} size variant(s)";
                    }

                    // 6. Finally delete the product
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    if (!$stmt->execute([$product_id])) {
                        throw new Exception("Failed to delete product from database");
                    }

                    // Check if any rows were affected
                    if ($stmt->rowCount() === 0) {
                        throw new Exception("No product found with the specified ID");
                    }

                    // Commit the transaction
                    $pdo->commit();

                    // Clean up image file if it's a local upload
                    if ($product['image_url'] && strpos($product['image_url'], '/uploads/') === 0) {
                        $imageProcessor = new ImageProcessor();
                        $imagePath = ltrim($product['image_url'], '/');
                        if (file_exists($imagePath)) {
                            $imageProcessor->deleteImage($imagePath);
                        }
                    }

                    // Update XML files
                    if (isset($db)) {
                        $db->forceXMLUpdate();
                    }

                    // Build success message with details
                    $message = 'Product "' . htmlspecialchars($product['name']) . '" deleted successfully!';
                    if (!empty($deleted_details)) {
                        $message .= ' Additional actions: ' . implode(', ', $deleted_details) . '.';
                    }
                    $message .= ' XML files updated.';
                    $messageType = 'success';

                    // Warning if order history was affected
                    if ($force_delete && $order_info['order_count'] > 0) {
                        $message .= ' WARNING: Order history has been modified - some past orders may show incomplete information.';
                    }
                } catch (Exception $e) {
                    // Rollback transaction on error
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $message = 'Failed to delete product: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                error_log("Delete product error: " . $e->getMessage());
                $message = 'An error occurred while processing the request: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;

            // Add a case to check product dependencies (for AJAX calls)
        case 'check_product_dependencies':
            $product_id = (int)$_POST['product_id'];

            try {
                // Get product info
                $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    echo json_encode(['error' => 'Product not found']);
                    exit;
                }

                // Check dependencies
                $dependencies = [];

                // Check orders
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count, MIN(o.created_at) as first_order, MAX(o.created_at) as last_order
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE oi.product_id = ?
                ");
                $stmt->execute([$product_id]);
                $orders = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($orders['count'] > 0) {
                    $dependencies['orders'] = $orders;
                }

                // Check cart items
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart_items WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $carts = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($carts['count'] > 0) {
                    $dependencies['carts'] = $carts;
                }

                // Check reviews
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM product_reviews WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $reviews = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($reviews['count'] > 0) {
                    $dependencies['reviews'] = $reviews;
                }

                // Check sizes
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM product_sizes WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $sizes = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($sizes['count'] > 0) {
                    $dependencies['sizes'] = $sizes;
                }

                echo json_encode([
                    'product_name' => $product['name'],
                    'dependencies' => $dependencies,
                    'can_delete_simple' => empty($dependencies['orders']),
                    'requires_force' => !empty($dependencies['orders'])
                ]);
                exit;
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
                exit;
            }
            break;
        case 'edit_product':
            $product_id = (int)$_POST['product_id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);

            // Get price data with defaults
            $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
            $original_price = isset($_POST['original_price']) ? (float)$_POST['original_price'] : 0;

            // Handle image update
            $image_url = $_POST['current_image_url']; // Default to current image
            $imageProcessor = new ImageProcessor();

            $edit_image_method = $_POST['edit_image_method'] ?? 'keep';

            // Handle image update
            $image_url = $_POST['current_image_url']; // Default to current image
            $imageProcessor = new ImageProcessor();

            $edit_image_method = $_POST['edit_image_method'] ?? 'keep';

            if ($edit_image_method === 'upload' && isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                // Upload new file
                $addWatermark = isset($_POST['add_watermark']);
                $watermarkText = $_POST['watermark_text'] ?? 'UrbanStitch';
                $watermarkPosition = $_POST['watermark_position'] ?? 'bottom-right';

                if ($addWatermark) {
                    $imageResult = $imageProcessor->processImage($_FILES['product_image'], $watermarkText, $watermarkPosition);
                } else {
                    $extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                    $filename = 'product_' . uniqid() . '_' . time() . '.' . $extension;
                    $filepath = 'uploads/products/' . $filename;

                    if (!is_dir('uploads/products/')) {
                        mkdir('uploads/products/', 0755, true);
                    }

                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $filepath)) {
                        $imageResult = ['success' => true, 'url' => '/' . $filepath];
                    }
                }

                if ($imageResult && $imageResult['success']) {
                    $image_url = $imageResult['url'];
                }
            } elseif ($edit_image_method === 'url' && !empty($_POST['image_url'])) {
                // Use new URL
                $addWatermark = isset($_POST['add_watermark']);
                $watermarkText = $_POST['watermark_text'] ?? 'UrbanStitch';
                $watermarkPosition = $_POST['watermark_position'] ?? 'bottom-right';

                if ($addWatermark) {
                    $imageResult = $imageProcessor->processImageFromUrl($_POST['image_url'], $watermarkText, $watermarkPosition);
                    if ($imageResult && $imageResult['success']) {
                        $image_url = $imageResult['url'];
                    }
                } else {
                    $image_url = $_POST['image_url'];
                }
            }
            // If 'keep', $image_url remains as current_image_url

            // Get price data
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, original_price = ?, image_url = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$name, $description, $price, $original_price, $image_url, $product_id])) {
                $db->forceXMLUpdate();
                $message = 'Product updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to update product.';
                $messageType = 'error';
            }
            break;

        case 'force_xml_update':
            if ($db->forceXMLUpdate()) {
                $message = 'XML files regenerated successfully from database!';
                $messageType = 'success';
            } else {
                $message = 'Failed to regenerate XML files.';
                $messageType = 'error';
            }
            break;
        case 'update_order_status':
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $notify_customer = isset($_POST['notify_customer']);
    $admin_id = $_SESSION['user_id'];

    try {
        // Validate status
        $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
        if (!in_array($new_status, $validStatuses)) {
            throw new Exception('Invalid status');
        }

        // Get current order info with customer details (SIMPLIFIED VERSION)
        $stmt = $pdo->prepare("
            SELECT o.*, u.email, u.first_name, u.last_name, u.username
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception('Order not found');
        }

        $old_status = $order['status'];

        // Don't update if status is the same
        if ($old_status === $new_status) {
            $message = 'Order status is already ' . ucfirst($new_status);
            $messageType = 'warning';
            break;
        }

        // Get admin info for email
        $stmt = $pdo->prepare("SELECT username, first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin_info = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->beginTransaction();

        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = ?, admin_notes = ?, status_updated_at = NOW(), updated_by_admin = ?
            WHERE id = ?
        ");
        if (!$stmt->execute([$new_status, $admin_notes, $admin_id, $order_id])) {
            throw new Exception('Failed to update order status');
        }

        // Insert into history table with auto_increment fix
        try {
            $stmt = $pdo->prepare("
                INSERT INTO order_status_history (order_id, old_status, new_status, admin_id, admin_notes) 
                VALUES (?, ?, ?, ?, ?)
            ");
            if (!$stmt->execute([$order_id, $old_status, $new_status, $admin_id, $admin_notes])) {
                throw new Exception('Failed to log status change');
            }
        } catch (Exception $historyError) {
            // If we get a duplicate entry error, fix auto_increment and retry
            if (strpos($historyError->getMessage(), 'Duplicate entry') !== false || 
                strpos($historyError->getMessage(), '1062') !== false) {
                
                error_log("Fixing auto_increment and retrying history insert");
                
                // Fix the auto_increment
                $maxIdResult = $pdo->query("SELECT MAX(id) as max_id FROM order_status_history");
                $maxId = $maxIdResult ? $maxIdResult->fetch(PDO::FETCH_ASSOC)['max_id'] ?? 0 : 0;
                $newAutoIncrement = $maxId + 1;
                
                $pdo->exec("ALTER TABLE order_status_history AUTO_INCREMENT = $newAutoIncrement");
                
                // Retry the insert
                $stmt = $pdo->prepare("
                    INSERT INTO order_status_history (order_id, old_status, new_status, admin_id, admin_notes) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                if (!$stmt->execute([$order_id, $old_status, $new_status, $admin_id, $admin_notes])) {
                    throw new Exception('Failed to log status change even after auto_increment fix');
                }
            } else {
                throw $historyError;
            }
        }

        $pdo->commit();

        // Send email notification if requested
        $email_result = null;
        if ($notify_customer && !empty($order['email'])) {
            // Include the EmailService if not already included
            if (!class_exists('EmailService')) {
                require_once 'email_service.php';
            }
            
            $emailService = new EmailService();
            
            // Prepare order data for email
            $orderData = [
                'order_number' => $order['order_number'] ?? '#' . str_pad($order['id'], 6, '0', STR_PAD_LEFT),
                'customer_name' => trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')),
                'email' => $order['email'],
                'total_amount' => $order['total_amount'],
                'payment_method' => $order['payment_method'],
                'created_at' => $order['created_at'],
                'items' => [] // You can add order items here if needed
            ];

            // Send the email using your existing EmailService
            $email_result = $emailService->sendOrderStatusUpdate($orderData, $new_status, $admin_notes, $admin_info);
        }

        // Build success message
        $message = "Order status updated from '" . ucfirst($old_status) . "' to '" . ucfirst($new_status) . "' successfully!";

        if ($notify_customer) {
            if ($email_result && $email_result['success']) {
                if ($email_result['mode'] === 'development') {
                    $message .= " Email logged (Development Mode).";
                } else {
                    $message .= " Customer notified via email.";
                }
            } elseif ($email_result) {
                $message .= " Warning: " . $email_result['message'];
            } else {
                $message .= " Note: Email notification was requested but not processed.";
            }
        }

        $messageType = 'success';
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Order status update error: " . $e->getMessage());
        $message = 'Failed to update order status: ' . $e->getMessage();
        $messageType = 'error';
    }
    break;

        case 'verify_admin_password':
            // Set JSON header immediately
            header('Content-Type: application/json');

            $password = $_POST['password'] ?? '';
            $admin_username = $_SESSION['username'];

            // Debug logging
            error_log("Admin verification attempt for: " . $admin_username);

            try {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ? AND is_admin = 1");
                $stmt->execute([$admin_username]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($admin && password_verify($password, $admin['password'])) {
                    error_log("Password verification successful");
                    echo json_encode(['success' => true]);
                } else {
                    error_log("Password verification failed");
                    echo json_encode(['success' => false, 'error' => 'Invalid password']);
                }
            } catch (Exception $e) {
                error_log("Password verification error: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Verification failed']);
            }
            exit(); // This is crucial - prevents any other output
            break;
        case 'delete_user':
            $user_id = (int)$_POST['user_id'];

            try {
                // Get user info before deletion
                $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ? AND is_admin = 0");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $message = 'User not found or cannot delete admin users.';
                    $messageType = 'error';
                    break;
                }

                // Delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
                if ($stmt->execute([$user_id]) && $stmt->rowCount() > 0) {
                    $message = 'User "' . htmlspecialchars($user['username']) . '" deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to delete user or user not found.';
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                error_log("Delete user error: " . $e->getMessage());
                $message = 'An error occurred: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;
        case 'get_product_sizes':  // ← ADD THE NEW CASE HERE
            $product_id = (int)$_POST['product_id'];

            try {
                // Get product info
                $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    echo json_encode(['success' => false, 'error' => 'Product not found']);
                    exit;
                }

                // Get product sizes
                $sizes = $sizeManager->getProductSizes($product_id);

                echo json_encode([
                    'success' => true,
                    'product' => $product,
                    'sizes' => $sizes
                ]);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
            break;
    }
}


// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE is_admin = 0");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $stmt->fetch()['total_products'];

$stmt = $pdo->query("SELECT COUNT(*) as total_categories FROM categories");
$total_categories = $stmt->fetch()['total_categories'];

$stmt = $pdo->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE status = 'completed'");
$total_revenue = $stmt->fetch()['total_revenue'] ?? 0;

// Get data for tables with size information
$categories = $db->getAllCategories();

// Enhanced products query with size information
$productsQuery = "
    SELECT p.*, c.name as category_name, pt.name as product_type_name, pt.size_type,
           COUNT(ps.id) as size_count,
           COALESCE(SUM(ps.stock_quantity), p.stock_quantity) as total_stock
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_types pt ON p.product_type_id = pt.id
    LEFT JOIN product_sizes ps ON p.id = ps.product_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
";
$stmt = $pdo->query($productsQuery);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$productTypes = $sizeManager->getProductTypes();

$stmt = $pdo->query("SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT o.*, u.username as user_name, u.email as user_email, u.first_name, u.last_name,
           COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC LIMIT 50
");
$recent_orders = $stmt->fetchAll();

// Check XML file status
$xmlStatus = [
    'products' => file_exists(__DIR__ . '../xml/products.xml'),
    'categories' => file_exists(__DIR__ . '../xml/categories.xml'),
    'site_config' => file_exists(__DIR__ . '../xml/site_config.xml'),
    'users_orders' => file_exists(__DIR__ . '../xml/users_orders.xml')
];

$xmlLastUpdate = '';
if ($xmlStatus['products']) {
    $xmlLastUpdate = date('Y-m-d H:i:s', filemtime(__DIR__ . '../xml/products.xml'));
    // AJAX handlers for order management
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');

        switch ($_GET['action']) {
            case 'get_order_details':
                $orderId = (int)$_GET['order_id'];

                try {
                    // Get order details
                    $stmt = $pdo->prepare("
                    SELECT o.*, u.username, u.email, u.first_name, u.last_name
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    WHERE o.id = ?
                ");
                    $stmt->execute([$orderId]);
                    $order = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$order) {
                        echo json_encode(['success' => false, 'message' => 'Order not found']);
                        exit;
                    }

                    // Get order items
                    $stmt = $pdo->prepare("
                    SELECT oi.*, p.name, p.image_url
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?
                ");
                    $stmt->execute([$orderId]);
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Get status history
                    $stmt = $pdo->prepare("
                    SELECT osh.*, u.username as admin_username
                    FROM order_status_history osh
                    LEFT JOIN users u ON osh.admin_id = u.id
                    WHERE osh.order_id = ?
                    ORDER BY osh.created_at DESC
                ");
                    $stmt->execute([$orderId]);
                    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode([
                        'success' => true,
                        'order' => $order,
                        'items' => $items,
                        'history' => $history
                    ]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                exit;

            case 'get_order_status':
                $orderId = (int)$_GET['order_id'];

                try {
                    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
                    $stmt->execute([$orderId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($result) {
                        echo json_encode(['success' => true, 'status' => $result['status']]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Order not found']);
                    }
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UrbanStitch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css">
    <style>
        #adminPasswordModal .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        #adminPasswordModal .form-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .password-strength {
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</head>

<body>

    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p style="color: #ccc; font-size: 14px;">UrbanStitch</p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="#dashboard" class="nav-link active" onclick="showSection('dashboard')">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a></li>
                <li><a href="#xml-status" class="nav-link" onclick="showSection('xml-status')">
                        <i class="fas fa-file-code"></i> XML Status
                    </a></li>
                <li><a href="#categories" class="nav-link" onclick="showSection('categories')">
                        <i class="fas fa-tags"></i> Categories
                    </a></li>
                <li><a href="#products" class="nav-link" onclick="showSection('products')">
                        <i class="fas fa-box"></i> Products with Sizes
                    </a></li>
                <li><a href="#users" class="nav-link" onclick="showSection('users')">
                        <i class="fas fa-users"></i> Users
                    </a></li>
                <li><a href="#orders" class="nav-link" onclick="showSection('orders')">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a></li>
                <li><a href="#reports" class="nav-link" onclick="showSection('reports')">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a></li>
                <li><a href="index.php">
                        <i class="fas fa-globe"></i> View Website
                    </a></li>
                <li><a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Admin Dashboard with Analytics & Size Management</h1>
                <div>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <button class="btn btn-info" onclick="generatePDFReport()">
                        <i class="fas fa-file-pdf"></i> Generate PDF Report
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Enhanced Dashboard Section with Analytics -->
            <div id="dashboard" class="content-section active">
                <!-- KPI Cards with Dynamic Data -->
                <div class="kpi-grid">
                    <div class="kpi-card revenue">
                        <div class="kpi-header">
                            <div class="kpi-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="kpi-trend <?php echo isset($revenue_trend) && $revenue_trend >= 0 ? 'up' : 'down'; ?>">
                                <i class="fas fa-arrow-<?php echo isset($revenue_trend) && $revenue_trend >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo isset($revenue_trend) ? ($revenue_trend >= 0 ? '+' : '') . round($revenue_trend, 1) . '%' : 'N/A'; ?>
                            </div>
                        </div>
                        <div class="kpi-value">₱<?php echo number_format($total_revenue, 0); ?></div>
                        <div class="kpi-label">Total Revenue</div>
                        <div class="kpi-subtitle">vs last month</div>
                    </div>

                    <div class="kpi-card orders">
                        <div class="kpi-header">
                            <div class="kpi-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="kpi-trend <?php echo isset($orders_trend) && $orders_trend >= 0 ? 'up' : 'down'; ?>">
                                <i class="fas fa-arrow-<?php echo isset($orders_trend) && $orders_trend >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo isset($orders_trend) ? ($orders_trend >= 0 ? '+' : '') . round($orders_trend, 1) . '%' : 'New'; ?>
                            </div>
                        </div>
                        <div class="kpi-value"><?php echo isset($total_orders) ? $total_orders : 0; ?></div>
                        <div class="kpi-label">Total Orders</div>
                        <div class="kpi-subtitle"><?php echo isset($completed_orders) ? $completed_orders : 0; ?> completed</div>
                    </div>

                    <div class="kpi-card products">
                        <div class="kpi-header">
                            <div class="kpi-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="kpi-trend up">
                                <i class="fas fa-info-circle"></i> Live
                            </div>
                        </div>
                        <div class="kpi-value"><?php echo $total_products; ?></div>
                        <div class="kpi-label">Active Products</div>
                        <div class="kpi-subtitle">with size variants</div>
                    </div>

                    <div class="kpi-card customers">
                        <div class="kpi-header">
                            <div class="kpi-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="kpi-trend up">
                                <i class="fas fa-user-plus"></i> Active
                            </div>
                        </div>
                        <div class="kpi-value"><?php echo $total_users; ?></div>
                        <div class="kpi-label">Customers</div>
                        <div class="kpi-subtitle">registered users</div>
                    </div>
                </div>

                <!-- Analytics Section -->
                <div class="analytics-section">
                    <div class="analytics-tabs">
                        <div class="analytics-tab active" onclick="switchAnalyticsTab('overview')">
                            <i class="fas fa-chart-line"></i> Overview
                        </div>
                        <div class="analytics-tab" onclick="switchAnalyticsTab('sales')">
                            <i class="fas fa-chart-bar"></i> Sales
                        </div>
                        <div class="analytics-tab" onclick="switchAnalyticsTab('products')">
                            <i class="fas fa-boxes"></i> Products
                        </div>
                        <div class="analytics-tab" onclick="switchAnalyticsTab('sizes')">
                            <i class="fas fa-ruler"></i> Size Analytics
                        </div>
                    </div>

                    <!-- Analytics Filters -->
                    <div class="analytics-filters">
                        <div class="filter-group">
                            <label class="filter-label">Time Period</label>
                            <select class="filter-select" id="timePeriodFilter" onchange="updateCharts()">
                                <option value="7">Last 7 days</option>
                                <option value="30" selected>Last 30 days</option>
                                <option value="90">Last 3 months</option>
                                <option value="365">Last year</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Category</label>
                            <select class="filter-select" id="categoryFilter" onchange="updateCharts()">
                                <option value="all">All Categories</option>
                                <?php if (isset($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="export-options">
                            <button class="export-btn" onclick="exportSalesReport()">
                                <i class="fas fa-file-pdf"></i> Sales Report PDF
                            </button>
                            <button class="export-btn" onclick="exportStockReport()">
                                <i class="fas fa-file-pdf"></i> Stock Report PDF
                            </button>
                            <button class="export-btn" onclick="exportPaymentsReport()">
                                <i class="fas fa-file-pdf"></i> Payments Report PDF
                            </button>
                            <button class="export-btn" onclick="exportCompleteReport()">
                                <i class="fas fa-file-pdf"></i> Complete Report PDF
                            </button>
                        </div>
                    </div>

                    <!-- Overview Tab -->
                    <div id="overview-tab" class="analytics-content">
                        <div class="analytics-grid">
                            <!-- Revenue Chart -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title">
                                            <i class="fas fa-chart-line"></i>
                                            Revenue Trend
                                        </h3>
                                        <p class="chart-subtitle">Daily revenue over the last 30 days</p>
                                    </div>
                                    <div class="chart-actions">
                                        <button class="chart-action-btn" onclick="refreshChart('revenue')">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>

                            <!-- Orders Chart -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title">
                                            <i class="fas fa-shopping-cart"></i>
                                            Orders Overview
                                        </h3>
                                        <p class="chart-subtitle">Order count and status distribution</p>
                                    </div>
                                    <div class="chart-actions">
                                        <button class="chart-action-btn" onclick="refreshChart('orders')">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="ordersChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Tab -->
                    <div id="sales-tab" class="analytics-content" style="display: none;">
                        <div class="analytics-grid">
                            <!-- Sales by Category -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title">
                                            <i class="fas fa-chart-pie"></i>
                                            Sales by Category
                                        </h3>
                                        <p class="chart-subtitle">Revenue distribution across product categories</p>
                                    </div>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="salesCategoryChart"></canvas>
                                </div>
                            </div>

                            <!-- Monthly Sales -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title">
                                            <i class="fas fa-chart-bar"></i>
                                            Monthly Sales Comparison
                                        </h3>
                                        <p class="chart-subtitle">Current vs previous year</p>
                                    </div>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="monthlySalesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Tab -->
                    <div id="products-tab" class="analytics-content" style="display: none;">
                        <div class="analytics-grid">
                            <!-- Top Products -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title">
                                            <i class="fas fa-star"></i>
                                            Top Performing Products
                                        </h3>
                                        <p class="chart-subtitle">Best sellers by revenue</p>
                                    </div>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="topProductsChart"></canvas>
                                </div>
                            </div>

                            <!-- Stock Levels -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title">
                                            <i class="fas fa-warehouse"></i>
                                            Stock Levels Overview
                                        </h3>
                                        <p class="chart-subtitle">Inventory status across all products</p>
                                    </div>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="stockLevelsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Size Analytics Tab -->
                    <div id="sizes-tab" class="analytics-content" style="display: none;">
                        <div class="analytics-grid">
                            <!-- Size Distribution -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title">
                                            <i class="fas fa-ruler"></i>
                                            Size Sales Distribution
                                        </h3>
                                        <p class="chart-subtitle">Most popular sizes across all products</p>
                                    </div>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="sizeDistributionChart"></canvas>
                                </div>
                            </div>

                            <!-- Size Performance -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title">
                                            <i class="fas fa-chart-line"></i>
                                            Size Performance Trends
                                        </h3>
                                        <p class="chart-subtitle">Size sales over time</p>
                                    </div>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="sizePerformanceChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Size Breakdown -->
                        <div class="size-analytics-grid">
                            <div class="size-breakdown">
                                <h4><i class="fas fa-tshirt"></i> Apparel Sizes</h4>
                                <div class="size-item-analytics">
                                    <span class="size-name-analytics">Small (S)</span>
                                    <div class="size-stats">
                                        <span class="size-stock-analytics">Stock: 145</span>
                                        <span class="size-sales-analytics">Sold: 89</span>
                                    </div>
                                </div>
                                <div class="size-item-analytics">
                                    <span class="size-name-analytics">Medium (M)</span>
                                    <div class="size-stats">
                                        <span class="size-stock-analytics">Stock: 203</span>
                                        <span class="size-sales-analytics">Sold: 156</span>
                                    </div>
                                </div>
                                <div class="size-item-analytics">
                                    <span class="size-name-analytics">Large (L)</span>
                                    <div class="size-stats">
                                        <span class="size-stock-analytics">Stock: 187</span>
                                        <span class="size-sales-analytics">Sold: 134</span>
                                    </div>
                                </div>
                                <div class="size-item-analytics">
                                    <span class="size-name-analytics">Extra Large (XL)</span>
                                    <div class="size-stats">
                                        <span class="size-stock-analytics">Stock: 98</span>
                                        <span class="size-sales-analytics">Sold: 67</span>
                                    </div>
                                </div>
                            </div>

                            <div class="size-breakdown">
                                <h4><i class="fas fa-shoe-prints"></i> Footwear Sizes</h4>
                                <div class="size-item-analytics">
                                    <span class="size-name-analytics">Size 9</span>
                                    <div class="size-stats">
                                        <span class="size-stock-analytics">Stock: 45</span>
                                        <span class="size-sales-analytics">Sold: 32</span>
                                    </div>
                                </div>
                                <div class="size-item-analytics">
                                    <span class="size-name-analytics">Size 10</span>
                                    <div class="size-stats">
                                        <span class="size-stock-analytics">Stock: 52</span>
                                        <span class="size-sales-analytics">Sold: 38</span>
                                    </div>
                                </div>
                                <div class="size-item-analytics">
                                    <span class="size-name-analytics">Size 11</span>
                                    <div class="size-stats">
                                        <span class="size-stock-analytics">Stock: 38</span>
                                        <span class="size-sales-analytics">Sold: 29</span>
                                    </div>
                                </div>
                                <div class="size-item-analytics">
                                    <span class="size-name-analytics">Size 8</span>
                                    <div class="size-stats">
                                        <span class="size-stock-analytics">Stock: 41</span>
                                        <span class="size-sales-analytics">Sold: 25</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Insights with Real Data -->
                <div class="quick-insights">
                    <h3><i class="fas fa-lightbulb"></i> Quick Insights</h3>
                    <div class="insights-grid">
                        <div class="insight-item">
                            <div class="insight-label">Average Order Value</div>
                            <div class="insight-value">₱<?php echo isset($avg_order_value) ? number_format($avg_order_value, 2) : '0.00'; ?></div>
                            <div class="insight-change <?php echo isset($avg_order_value) && $avg_order_value > 50 ? 'positive' : 'negative'; ?>">
                                <?php
                                if (isset($total_orders) && $total_orders > 0) {
                                    echo 'Based on ' . (isset($completed_orders) ? $completed_orders : 0) . ' orders';
                                } else {
                                    echo 'No orders yet';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="insight-item">
                            <div class="insight-label">Order Status</div>
                            <div class="insight-value">
                                <?php echo isset($completed_orders) ? $completed_orders : 0; ?> / <?php echo isset($total_orders) ? $total_orders : 0; ?>
                            </div>
                            <div class="insight-change <?php echo isset($total_orders) && $total_orders > 0 ? 'positive' : 'negative'; ?>">
                                <?php
                                if (isset($total_orders) && $total_orders > 0) {
                                    echo round(((isset($completed_orders) ? $completed_orders : 0) / $total_orders) * 100, 1) . '% completed';
                                } else {
                                    echo 'No orders yet';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="insight-item">
                            <div class="insight-label">Low Stock Alert</div>
                            <div class="insight-value">
                                <?php echo isset($stock_levels) ? ($stock_levels['low_stock'] + $stock_levels['out_of_stock']) : 0; ?> Products
                            </div>
                            <div class="insight-change <?php echo isset($stock_levels) && ($stock_levels['low_stock'] + $stock_levels['out_of_stock']) > 0 ? 'negative' : 'positive'; ?>">
                                <?php
                                if (isset($stock_levels)) {
                                    echo $stock_levels['out_of_stock'] . ' out of stock, ' . $stock_levels['low_stock'] . ' low stock';
                                } else {
                                    echo 'Stock data loading...';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="insight-item">
                            <div class="insight-label">This Month</div>
                            <div class="insight-value">
                                <?php echo isset($current_month) ? $current_month['orders_count'] : 0; ?> Orders
                            </div>
                            <div class="insight-change <?php echo isset($current_month) && $current_month['orders_count'] > 0 ? 'positive' : 'negative'; ?>">
                                $<?php echo isset($current_month) ? number_format($current_month['revenue_sum'], 2) : '0.00'; ?> revenue
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-section active">
                    <div class="section-header">
                        <h3>System Status</h3>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="force_xml_update">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-sync"></i> Force XML Update
                            </button>
                        </form>
                    </div>
                    <div class="section-content">
                        <p>Welcome to the UrbanStitch Admin Dashboard with comprehensive analytics and size management.</p>
                        <p><strong>Analytics:</strong> Real-time charts and insights for sales, products, and size performance tracking.</p>
                        <p><strong>Size Management:</strong> Multi-variant inventory with individual stock tracking for apparel (XS-XXXL) and footwear (sizes 7-12).</p>
                        <p><strong>XML System:</strong> Your products and categories are automatically synced to XML files whenever you make changes.</p>
                        <p><strong>Image Processing:</strong> All product images can be uploaded directly or from URLs and are automatically watermarked.</p>
                    </div>
                </div>
            </div>

            <!-- XML Status Section -->
            <div id="xml-status" class="content-section">
                <div class="section-header">
                    <h3>XML Files Status</h3>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="force_xml_update">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-sync"></i> Regenerate All XML Files
                        </button>
                    </form>
                </div>
                <div class="section-content">
                    <div class="xml-files-grid">
                        <div class="xml-file-status <?php echo isset($xmlStatus['products']) && $xmlStatus['products'] ? 'xml-file-exists' : 'xml-file-missing'; ?>">
                            <i class="fas fa-file-code"></i><br>
                            <strong>products.xml</strong><br>
                            <?php echo isset($xmlStatus['products']) && $xmlStatus['products'] ? 'EXISTS' : 'MISSING'; ?>
                        </div>
                        <div class="xml-file-status <?php echo isset($xmlStatus['categories']) && $xmlStatus['categories'] ? 'xml-file-exists' : 'xml-file-missing'; ?>">
                            <i class="fas fa-file-code"></i><br>
                            <strong>categories.xml</strong><br>
                            <?php echo isset($xmlStatus['categories']) && $xmlStatus['categories'] ? 'EXISTS' : 'MISSING'; ?>
                        </div>
                        <div class="xml-file-status <?php echo isset($xmlStatus['site_config']) && $xmlStatus['site_config'] ? 'xml-file-exists' : 'xml-file-missing'; ?>">
                            <i class="fas fa-file-code"></i><br>
                            <strong>site_config.xml</strong><br>
                            <?php echo isset($xmlStatus['site_config']) && $xmlStatus['site_config'] ? 'EXISTS' : 'MISSING'; ?>
                        </div>
                        <div class="xml-file-status <?php echo isset($xmlStatus['users_orders']) && $xmlStatus['users_orders'] ? 'xml-file-exists' : 'xml-file-missing'; ?>">
                            <i class="fas fa-file-code"></i><br>
                            <strong>users_orders.xml</strong><br>
                            <?php echo isset($xmlStatus['users_orders']) && $xmlStatus['users_orders'] ? 'EXISTS' : 'MISSING'; ?>
                        </div>
                    </div>

                    <h4>How XML Sync Works:</h4>
                    <ul>
                        <li><strong>Automatic Updates:</strong> XML files are automatically regenerated whenever you add, edit, or delete products/categories</li>
                        <li><strong>Performance:</strong> Frontend loads products from XML files for faster performance</li>
                        <li><strong>Size Support:</strong> XML files now include size variant data for enhanced inventory management</li>
                        <li><strong>Manual Sync:</strong> Use "Force XML Update" if files become out of sync</li>
                    </ul>
                </div>
            </div>

            <!-- Categories Section -->
            <div id="categories" class="content-section">
                <div class="section-header">
                    <h3>Manage Categories</h3>
                    <button class="btn" onclick="showModal('categoryModal')">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
                <div class="section-content">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td>
                                            <i class="<?php echo $category['icon']; ?>" style="margin-right: 8px;"></i>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                        <td><?php echo $category['product_count']; ?></td>
                                        <td>
                                            <button class="btn btn-sm" onclick="editCategory(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Enhanced Products Section with Size Management -->
            <div id="products" class="content-section">
                <div class="section-header">
                    <h3>Manage Products with Size Variants</h3>
                    <button class="btn" onclick="showModal('productModal')">
                        <i class="fas fa-plus"></i> Add Product with Sizes
                    </button>
                </div>
                <div class="section-content">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Sizes</th>
                                <th>Total Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($products)): ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <img src="<?php echo $product['image_url']; ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                            <?php if (strpos($product['image_url'], '/uploads/') === 0): ?>
                                                <span class="badge badge-success" style="display: block; margin-top: 2px; font-size: 10px;">WATERMARKED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($product['name']); ?>
                                            <?php if (isset($product['is_featured']) && $product['is_featured']): ?>
                                                <span class="badge badge-success">FEATURED</span>
                                            <?php endif; ?>
                                            <?php if (isset($product['is_trending']) && $product['is_trending']): ?>
                                                <span class="badge badge-warning">TRENDING</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($product['product_type_name']) && $product['product_type_name']): ?>
                                                <span class="size-badge <?php echo isset($product['size_type']) ? $product['size_type'] : 'accessories'; ?>">
                                                    <?php echo htmlspecialchars($product['product_type_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">No Type</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Unknown'); ?></td>
                                        <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                        <td>
                                            <?php if (isset($product['size_count']) && $product['size_count'] > 0): ?>
                                                <span class="badge badge-success"><?php echo $product['size_count']; ?> sizes</span>
                                                <button class="btn btn-sm btn-info" onclick="showSizeManagement(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-cog"></i> Manage
                                                </button>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">No sizes</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo isset($product['total_stock']) ? $product['total_stock'] : $product['stock_quantity']; ?></strong>
                                            <?php if (!isset($product['size_count']) || $product['size_count'] == 0): ?>
                                                <form method="POST" style="display: inline-flex; gap: 5px; margin-top: 5px;" onsubmit="return confirm('Update stock for this product?')">
                                                    <input type="hidden" name="action" value="update_stock">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <input type="number" name="new_stock" value="<?php echo $product['stock_quantity']; ?>" style="width: 70px; padding: 4px; border: 1px solid #ccc; border-radius: 3px;" min="0" required>
                                                    <button type="submit" class="btn btn-sm btn-primary" style="padding: 4px 8px;">
                                                        <i class="fas fa-save"></i> Update
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $stockValue = isset($product['total_stock']) ? $product['total_stock'] : $product['stock_quantity'];
                                            if ($stockValue > 0):
                                            ?>
                                                <span class="badge badge-success">In Stock</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Out of Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm" onclick="editProduct(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 20px;">
                                        <i class="fas fa-box-open" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i><br>
                                        No products found. <a href="#" onclick="showModal('productModal')">Add your first product</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Users Section -->
            <div id="users" class="content-section">
                <div class="section-header">
                    <h3>Manage Users</h3>
                </div>
                <div class="section-content">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($users) && !empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px;">
                                        <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i><br>
                                        No users found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Enhanced Orders Section with GCash Management -->
            <div id="orders" class="content-section">
                <div class="section-header">
                    <h3>Order Management & GCash Payments</h3>
                    <div style="display: flex; gap: 10px;">
                        <select id="orderStatusFilter" class="form-select" style="width: 150px;" onchange="filterOrders()">
                            <option value="all">All Orders</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button class="btn btn-info" onclick="exportOrders()">
                            <i class="fas fa-download"></i> Export Orders
                        </button>
                    </div>
                </div>
                <div class="section-content">
                    <div class="orders-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                        <div class="stat-card" style="background: #fff3cd; padding: 16px; border-radius: 8px; border-left: 4px solid #ffc107;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-clock" style="color: #856404;"></i>
                                <div>
                                    <div style="font-size: 24px; font-weight: 700; color: #856404;" id="pendingCount">
                                        <?php
                                        $pendingCount = 0;
                                        foreach ($recent_orders as $order) {
                                            if ($order['status'] === 'pending') $pendingCount++;
                                        }
                                        echo $pendingCount;
                                        ?>
                                    </div>
                                    <div style="font-size: 12px; color: #856404;">Pending Orders</div>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card" style="background: #d1ecf1; padding: 16px; border-radius: 8px; border-left: 4px solid #17a2b8;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-cog" style="color: #0c5460;"></i>
                                <div>
                                    <div style="font-size: 24px; font-weight: 700; color: #0c5460;" id="processingCount">
                                        <?php
                                        $processingCount = 0;
                                        foreach ($recent_orders as $order) {
                                            if (in_array($order['status'], ['confirmed', 'processing', 'shipped'])) $processingCount++;
                                        }
                                        echo $processingCount;
                                        ?>
                                    </div>
                                    <div style="font-size: 12px; color: #0c5460;">Processing</div>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card" style="background: #d4edda; padding: 16px; border-radius: 8px; border-left: 4px solid #28a745;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-check-circle" style="color: #155724;"></i>
                                <div>
                                    <div style="font-size: 24px; font-weight: 700; color: #155724;" id="completedCount">
                                        <?php
                                        $completedCount = 0;
                                        foreach ($recent_orders as $order) {
                                            if (in_array($order['status'], ['completed', 'delivered'])) $completedCount++;
                                        }
                                        echo $completedCount;
                                        ?>
                                    </div>
                                    <div style="font-size: 12px; color: #155724;">Completed</div>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card" style="background: #f8d7da; padding: 16px; border-radius: 8px; border-left: 4px solid #dc3545;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-times-circle" style="color: #721c24;"></i>
                                <div>
                                    <div style="font-size: 24px; font-weight: 700; color: #721c24;" id="cancelledCount">
                                        <?php
                                        $cancelledCount = 0;
                                        foreach ($recent_orders as $order) {
                                            if ($order['status'] === 'cancelled') $cancelledCount++;
                                        }
                                        echo $cancelledCount;
                                        ?>
                                    </div>
                                    <div style="font-size: 12px; color: #721c24;">Cancelled</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Customer</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>GCash Info</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <?php if (isset($recent_orders) && !empty($recent_orders)): ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr data-status="<?php echo $order['status']; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['order_number'] ?? '#' . str_pad($order['id'], 6, '0', STR_PAD_LEFT)); ?></strong>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($order['user_name'] ?? 'N/A'); ?></div>
                                            <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($order['user_email'] ?? ''); ?></div>
                                        </td>
                                        <td>
                                            <strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                                                        echo match ($order['status']) {
                                                                            'pending' => 'warning',
                                                                            'confirmed' => 'info',
                                                                            'processing' => 'primary',
                                                                            'shipped' => 'secondary',
                                                                            'delivered', 'completed' => 'success',
                                                                            'cancelled' => 'danger',
                                                                            default => 'secondary'
                                                                        };
                                                                        ?>">
                                                <i class="fas fa-<?php
                                                                    echo match ($order['status']) {
                                                                        'pending' => 'clock',
                                                                        'confirmed' => 'check',
                                                                        'processing' => 'cog',
                                                                        'shipped' => 'truck',
                                                                        'delivered' => 'home',
                                                                        'completed' => 'check-circle',
                                                                        'cancelled' => 'times-circle',
                                                                        default => 'question'
                                                                    };
                                                                    ?>"></i>
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($order['gcash_number']) && $order['gcash_number']): ?>
                                                <div style="font-size: 12px;">
                                                    <div><strong>Number:</strong> <?php echo htmlspecialchars($order['gcash_number']); ?></div>
                                                    <div><strong>Ref:</strong> <?php echo htmlspecialchars($order['gcash_reference'] ?? 'N/A'); ?></div>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #666; font-style: italic;">PayPal/Other</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                            <div style="font-size: 12px; color: #666;"><?php echo date('g:i A', strtotime($order['created_at'])); ?></div>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                                <button class="btn btn-sm btn-info" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-edit"></i> Update
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-shopping-cart" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i><br>
                                        No orders found. Orders will appear here when customers make purchases.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Reports Section -->
            <div id="reports" class="content-section">
                <div class="section-header">
                    <h3>Data Reports with Size Analytics</h3>
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div>
                            <h4>XML Data Export</h4>
                            <a href="xml/products.xml" target="_blank" class="btn btn-info">
                                <i class="fas fa-download"></i> Download Products XML
                            </a>
                            <a href="xml/categories.xml" target="_blank" class="btn btn-info">
                                <i class="fas fa-download"></i> Download Categories XML
                            </a>
                        </div>
                        <div>
                            <h4>Size Management Reports</h4>
                            <button class="btn" onclick="exportSizeData()">
                                <i class="fas fa-file-csv"></i> Export Size Data CSV
                            </button>
                            <button class="btn btn-warning" onclick="importSizeData()">
                                <i class="fas fa-file-import"></i> Import Size Data CSV
                            </button>
                        </div>
                        <div>
                            <h4>PDF Reports</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                                <button class="btn" onclick="exportSalesReport()">
                                    <i class="fas fa-chart-line"></i> Sales Report PDF
                                </button>
                                <button class="btn" onclick="exportStockReport()">
                                    <i class="fas fa-boxes"></i> Stock Report PDF
                                </button>
                                <button class="btn" onclick="exportPaymentsReport()">
                                    <i class="fas fa-credit-card"></i> Payments Report PDF
                                </button>
                                <button class="btn btn-success" onclick="exportCompleteReport()">
                                    <i class="fas fa-file-pdf"></i> Complete Report PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('categoryModal')">&times;</span>
            <h3>Add New Category</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Icon Class</label>
                    <input type="text" name="icon" class="form-input" placeholder="fas fa-tshirt" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Color Class</label>
                    <select name="color" class="form-select" required>
                        <option value="text-neon-green">Neon Green</option>
                        <option value="text-urban-orange">Urban Orange</option>
                        <option value="text-blue">Blue</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" required></textarea>
                </div>
                <button type="submit" class="btn">Add Category</button>
            </form>
        </div>
    </div>

    <!-- Enhanced Product Modal with Size Management -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('productModal')">&times;</span>
            <h3 id="productModalTitle">Add New Product with Size Management</h3>

            <form method="POST" id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product_with_sizes" id="productAction">
                <input type="hidden" name="product_id" id="productId">

                <div class="form-grid">
                    <div>
                        <!-- Basic Product Information -->
                        <div class="form-group">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" id="productName" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="productCategory" class="form-select" required>
                                <?php if (isset($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>


                        <!-- Product Type Selection for Size Management -->
                        <div class="product-type-selector">
                            <label class="form-label">Product Type (for Size Management)</label>
                            <select name="product_type_id" id="productType" class="form-select" required onchange="updateSizeOptions()">
                                <option value="">Select Product Type</option>
                                <?php if (isset($productTypes)): ?>
                                    <?php foreach ($productTypes as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" data-size-type="<?php echo $type['size_type']; ?>">
                                            <?php echo htmlspecialchars($type['name']); ?>
                                            (<?php echo strtoupper($type['size_type']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                <i class="fas fa-info-circle"></i> This determines available sizes for your product
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" id="productPrice" step="0.01" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Original Price</label>
                            <input type="number" name="original_price" id="productOriginalPrice" step="0.01" class="form-input">
                        </div>
                    </div>

                    <div>
                        <!-- Enhanced Image Section -->
                        <div class="form-group">
                            <label class="form-label">Product Image</label>
                            <div class="image-upload-section">
                                <!-- Image Upload Method Selector -->
                                <div class="upload-method-selector">
                                    <label>
                                        <input type="radio" name="image_method" value="upload" checked onchange="toggleImageMethod()">
                                        <span>📁 Upload Image File</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="image_method" value="url" onchange="toggleImageMethod()">
                                        <span>🌐 Use Image URL</span>
                                    </label>
                                </div>

                                <!-- File Upload Option -->
                                <div id="fileUploadSection">
                                    <div class="drag-drop-zone">
                                        <i class="fas fa-cloud-upload-alt" style="font-size: 2em; color: #ccc; margin-bottom: 10px;"></i>
                                        <p style="margin-bottom: 10px;">Drag & drop your image here or click to browse</p>
                                        <input type="file" name="product_image" id="productImageFile" accept="image/*" class="form-input" style="margin-bottom: 10px;">
                                        <p style="font-size: 12px; color: #666;">Supported: JPEG, PNG, GIF • Max size: 5MB</p>
                                    </div>
                                    <div class="upload-preview" id="uploadPreview" style="display: none;">
                                        <h5 style="margin-bottom: 10px;">Preview:</h5>
                                        <img id="previewImage">
                                    </div>
                                </div>

                                <!-- URL Option -->
                                <div id="urlUploadSection" style="display: none;">
                                    <input type="url" name="image_url" id="productImageUrl" class="form-input" placeholder="https://example.com/image.jpg" style="margin-bottom: 10px;">
                                    <button type="button" onclick="previewUrlImage()" class="btn btn-sm" style="margin-bottom: 10px;">
                                        <i class="fas fa-eye"></i> Preview Image
                                    </button>
                                    <div class="url-preview" id="urlPreview" style="display: none;">
                                        <h5 style="margin-bottom: 10px;">Preview:</h5>
                                        <img id="previewUrlImage">
                                    </div>
                                </div>

                                <!-- Watermark Options -->
                                <div class="watermark-options">
                                    <h4><i class="fas fa-stamp"></i> Watermark Options</h4>

                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label class="form-checkbox">
                                            <input type="checkbox" name="add_watermark" id="addWatermark" checked onchange="toggleWatermarkOptions()">
                                            <span>Add Watermark to Image</span>
                                        </label>
                                    </div>

                                    <div id="watermarkSettings">
                                        <div class="form-group" style="margin-bottom: 10px;">
                                            <label class="form-label">Watermark Text</label>
                                            <input type="text" name="watermark_text" id="watermarkText" value="UrbanStitch" class="form-input" placeholder="Enter watermark text">
                                        </div>

                                        <div class="form-group" style="margin-bottom: 10px;">
                                            <label class="form-label">Watermark Position</label>
                                            <select name="watermark_position" id="watermarkPosition" class="form-select">
                                                <option value="bottom-right">Bottom Right</option>
                                                <option value="bottom-left">Bottom Left</option>
                                                <option value="top-right">Top Right</option>
                                                <option value="top-left">Top Left</option>
                                                <option value="center">Center</option>
                                            </select>
                                        </div>

                                        <div class="form-group" style="margin-bottom: 10px;">
                                            <label class="form-label">Watermark Opacity</label>
                                            <select name="watermark_opacity" id="watermarkOpacity" class="form-select">
                                                <option value="30">30% (Light)</option>
                                                <option value="50">50% (Medium)</option>
                                                <option value="70" selected>70% (Bold)</option>
                                                <option value="90">90% (Very Bold)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tags (comma separated)</label>
                            <input type="text" name="tags" id="productTags" class="form-input" placeholder="streetwear, trendy, urban">
                        </div>

                        <div class="form-group">
                            <div class="form-checkbox">
                                <input type="checkbox" name="is_featured" id="productFeatured">
                                <label for="productFeatured">Featured Product</label>
                            </div>
                            <div class="form-checkbox">
                                <input type="checkbox" name="is_trending" id="productTrending">
                                <label for="productTrending">Trending Product</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Size Management Section -->
                <div class="size-management-section">
                    <h4><i class="fas fa-ruler"></i> Size & Stock Management</h4>

                    <div id="noSizeTypeSelected" class="no-sizes-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Please select a product type above to configure sizes and stock levels.
                    </div>

                    <div id="sizeManagementContent" style="display: none;">
                        <div class="size-quick-actions">
                            <button type="button" class="quick-action-btn" onclick="selectAllSizes()">
                                <i class="fas fa-check-double"></i> Select All
                            </button>
                            <button type="button" class="quick-action-btn" onclick="deselectAllSizes()">
                                <i class="fas fa-times"></i> Deselect All
                            </button>
                            <button type="button" class="quick-action-btn" onclick="setUniformStock()">
                                <i class="fas fa-balance-scale"></i> Set Uniform Stock
                            </button>
                            <button type="button" class="quick-action-btn" onclick="autoFillSizes()">
                                <i class="fas fa-magic"></i> Auto Fill Common Sizes
                            </button>
                        </div>

                        <div id="sizeGrid" class="size-grid">
                            <!-- Size options will be populated by JavaScript -->
                        </div>

                        <div class="size-summary" id="sizeSummary" style="display: none;">
                            <h5><i class="fas fa-chart-bar"></i> Stock Summary</h5>
                            <div id="summaryContent">
                                <!-- Summary will be updated by JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Fallback for products without sizes -->
                    <div id="fallbackStockSection" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Total Stock Quantity</label>
                            <input type="number" name="stock_quantity" id="productStock" class="form-input" value="0">
                            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                <i class="fas fa-info-circle"></i> Use this for products that don't require size variants (accessories, one-size items)
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="productDescription" class="form-textarea" required placeholder="Describe your product..."></textarea>
                </div>

                <!-- Processing Indicator -->
                <div id="processingIndicator" style="display: none;">
                    <div>
                        <i class="fas fa-spinner fa-spin"></i> Processing product with sizes and watermark...
                    </div>
                    <div style="font-size: 12px; margin-top: 5px;">Please wait while we optimize your product</div>
                </div>

                <button type="submit" class="btn" id="productSubmitBtn">
                    <i class="fas fa-plus"></i> Add Product with Sizes & Watermark
                </button>
            </form>
        </div>
    </div>
    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('editProductModal')">&times;</span>
            <h3>Edit Product</h3>

            <form method="POST" id="editProductForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" name="product_id" id="editProductId">

                <div class="form-grid">
                    <div>
                        <div class="form-group">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" id="editProductName" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" id="editProductPrice" step="0.01" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Original Price</label>
                            <input type="number" name="original_price" id="editProductOriginalPrice" step="0.01" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editProductDescription" class="form-textarea" required rows="4"></textarea>
                        </div>
                    </div>

                    <div>
                        <div class="form-group">
                            <label class="form-label">Product Image</label>

                            <!-- Current Image Preview -->
                            <div id="currentImagePreview" style="margin-bottom: 15px;">
                                <h5>Current Image:</h5>
                                <img id="currentProductImage" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                            </div>

                            <!-- New Image Upload Options -->
                            <div class="image-upload-section">
                                <div class="upload-method-selector">
                                    <label>
                                        <input type="radio" name="edit_image_method" value="keep" checked onchange="toggleEditImageMethod()">
                                        <span>Keep Current Image</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="edit_image_method" value="upload" onchange="toggleEditImageMethod()">
                                        <span>📁 Upload New Image</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="edit_image_method" value="url" onchange="toggleEditImageMethod()">
                                        <span>🌐 Use New Image URL</span>
                                    </label>
                                </div>

                                <!-- Keep current (hidden input for current URL) -->
                                <input type="hidden" name="current_image_url" id="currentImageUrl">

                                <!-- File Upload Option -->
                                <div id="editFileUploadSection" style="display: none;">
                                    <input type="file" name="product_image" id="editProductImageFile" accept="image/*" class="form-input">
                                    <p style="font-size: 12px; color: #666;">Supported: JPEG, PNG, GIF • Max size: 5MB</p>
                                </div>

                                <!-- URL Option -->
                                <div id="editUrlUploadSection" style="display: none;">
                                    <input type="url" name="image_url" id="editProductImageUrl" class="form-input" placeholder="https://example.com/image.jpg">
                                    <button type="button" onclick="previewEditUrlImage()" class="btn btn-sm">
                                        <i class="fas fa-eye"></i> Preview
                                    </button>
                                </div>
                            </div>

                            <!-- Watermark Options for Edit -->
                            <div class="watermark-options" id="editWatermarkOptions" style="display: none;">
                                <h5><i class="fas fa-stamp"></i> Watermark New Image</h5>
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" name="add_watermark" id="editAddWatermark" checked>
                                        <span>Add Watermark to New Image</span>
                                    </label>
                                </div>
                                <div id="editWatermarkSettings">
                                    <div class="form-group">
                                        <label class="form-label">Watermark Text</label>
                                        <input type="text" name="watermark_text" value="UrbanStitch" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Position</label>
                                        <select name="watermark_position" class="form-select">
                                            <option value="bottom-right">Bottom Right</option>
                                            <option value="bottom-left">Bottom Left</option>
                                            <option value="top-right">Top Right</option>
                                            <option value="top-left">Top Left</option>
                                            <option value="center">Center</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Update Product
                </button>
            </form>
        </div>
    </div>

    <!-- Size Management Modal -->
    <div id="sizeManagementModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="hideModal('sizeManagementModal')">&times;</span>
            <h3 id="sizeManagementTitle">Manage Product Sizes & Stock</h3>

            <div id="sizeManagementTableContainer">
                <!-- Size management table will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Uniform Stock Input Modal -->
    <div id="uniformStockModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <span class="close" onclick="hideModal('uniformStockModal')">&times;</span>
            <h3>Set Uniform Stock</h3>
            <div class="form-group">
                <label class="form-label">Stock Quantity for All Selected Sizes</label>
                <input type="number" id="uniformStockValue" class="form-input" value="10" min="0">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn" onclick="applyUniformStock()">Apply to All</button>
                <button type="button" class="btn" onclick="hideModal('uniformStockModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div id="deleteProductModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="hideModal('deleteProductModal')">&times;</span>
            <h3>Delete Product</h3>

            <div id="deleteProductInfo" style="background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 4px;">
                <div id="loadingDependencies">
                    <i class="fas fa-spinner fa-spin"></i> Checking product dependencies...
                </div>
                <div id="dependencyResults" style="display: none;"></div>
            </div>

            <form method="POST" id="deleteProductForm">
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="product_id" id="deleteProductId">
                <input type="hidden" name="force_delete" id="forceDeleteFlag" value="0">

                <div id="deleteButtons" style="display: none;">
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn" onclick="hideModal('deleteProductModal')">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="simpleDeleteBtn" style="display: none;">
                            <i class="fas fa-trash"></i> Delete Product
                        </button>
                        <button type="button" class="btn btn-warning" id="forceDeleteBtn" style="display: none;"
                            onclick="confirmForceDelete()">
                            <i class="fas fa-exclamation-triangle"></i> Force Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <span class="close" onclick="hideModal('orderDetailsModal')">&times;</span>
            <h3 id="orderDetailsTitle">Order Details</h3>
            <div id="orderDetailsContent">
                <!-- Dynamic content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Update Order Status Modal -->
    <div id="updateOrderStatusModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="hideModal('updateOrderStatusModal')">&times;</span>
            <h3>Update Order Status</h3>
            <form method="POST" id="updateOrderStatusForm">
                <input type="hidden" name="action" value="update_order_status">
                <input type="hidden" name="order_id" id="updateOrderId">

                <div class="form-group">
                    <label class="form-label">Current Status</label>
                    <div id="currentOrderStatus" style="padding: 8px; background: #f8f9fa; border-radius: 4px; margin-bottom: 10px;">
                        <!-- Current status will be displayed here -->
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">New Status</label>
                    <select name="new_status" id="newOrderStatus" class="form-select" required>
                        <option value="">Select new status...</option>
                        <option value="confirmed">Confirmed - Payment Verified</option>
                        <option value="processing">Processing - Preparing Order</option>
                        <option value="shipped">Shipped - Order Sent</option>
                        <option value="delivered">Delivered - Order Arrived</option>
                        <option value="completed">Completed - Order Finished</option>
                        <option value="cancelled">Cancelled - Order Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Admin Notes</label>
                    <textarea name="admin_notes" id="adminNotesField" class="form-textarea"
                        placeholder="Add notes about this status update (optional)..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="notify_customer" id="notifyCustomer" checked>
                        <span>Send email notification to customer</span>
                    </label>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="hideModal('updateOrderStatusModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Delete Category Modal -->
    <div id="deleteCategoryModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="hideModal('deleteCategoryModal')">&times;</span>
            <h3>Delete Category</h3>

            <div id="deleteCategoryInfo" style="background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 4px;">
                <div id="loadingCategoryDependencies">
                    <i class="fas fa-spinner fa-spin"></i> Checking category dependencies...
                </div>
                <div id="categoryDependencyResults" style="display: none;"></div>
            </div>

            <form method="POST" id="deleteCategoryForm">
                <input type="hidden" name="action" value="delete_category">
                <input type="hidden" name="category_id" id="deleteCategoryId">
                <input type="hidden" name="force_delete" id="forceCategoryDeleteFlag" value="0">

                <div id="deleteCategoryButtons" style="display: none;">
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn" onclick="hideModal('deleteCategoryModal')">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="simpleCategoryDeleteBtn" style="display: none;">
                            <i class="fas fa-trash"></i> Delete Category
                        </button>
                        <button type="button" class="btn btn-warning" id="forceCategoryDeleteBtn" style="display: none;"
                            onclick="confirmForceCategoryDelete()">
                            <i class="fas fa-exclamation-triangle"></i> Force Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('deleteUserModal')">&times;</span>
            <h3>Delete User</h3>
            <p style="color: #721c24; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i>
                Are you sure you want to delete this user? This action cannot be undone.
            </p>
            <div id="deleteUserInfo" style="background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 4px;">
                <!-- User info will be populated by JavaScript -->
            </div>
            <form method="POST" onsubmit="return confirm('Are you absolutely sure? This will permanently delete the user account.')">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="hideModal('deleteUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Yes, Delete User
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Admin Password Verification Modal -->
    <div id="adminPasswordModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <span class="close" onclick="hideModal('adminPasswordModal')">&times;</span>
            <h3>Admin Verification Required</h3>
            <p style="color: #721c24; margin-bottom: 20px;">
                <i class="fas fa-shield-alt"></i>
                Please enter your admin password to confirm user deletion.
            </p>
            <form id="adminPasswordForm">
                <div class="form-group">
                    <label class="form-label">Admin Password</label>
                    <input type="password" id="adminPasswordInput" class="form-input" placeholder="Enter your password" required>
                    <div id="passwordError" style="color: #dc3545; font-size: 12px; margin-top: 5px; display: none;">
                        <i class="fas fa-exclamation-triangle"></i> Invalid password
                    </div>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="hideModal('adminPasswordModal')">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="verifyAdminPassword()">
                        <i class="fas fa-check"></i> Verify & Delete
                    </button>
                </div>
            </form>
        </div>
    </div>


    <script src="admins-functions.js"></script>

    <script>
        // PDF Export Functions
        async function exportSalesReport() {
            try {
                showLoadingIndicator('Generating Sales Report...');

                const response = await fetch('export_data.php?action=sales_data');
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Failed to fetch sales data');
                }

                generateSalesPDF(result.data);
                hideLoadingIndicator();

            } catch (error) {
                console.error('Error exporting sales report:', error);
                alert('Error generating sales report: ' + error.message);
                hideLoadingIndicator();
            }
        }

        async function exportStockReport() {
            try {
                showLoadingIndicator('Generating Stock Report...');

                const response = await fetch('export_data.php?action=stock_data');
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Failed to fetch stock data');
                }

                generateStockPDF(result.data);
                hideLoadingIndicator();

            } catch (error) {
                console.error('Error exporting stock report:', error);
                alert('Error generating stock report: ' + error.message);
                hideLoadingIndicator();
            }
        }

        async function exportPaymentsReport() {
            try {
                showLoadingIndicator('Generating Payments Report...');

                const response = await fetch('export_data.php?action=payments_data');
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Failed to fetch payments data');
                }

                generatePaymentsPDF(result.data);
                hideLoadingIndicator();

            } catch (error) {
                console.error('Error exporting payments report:', error);
                alert('Error generating payments report: ' + error.message);
                hideLoadingIndicator();
            }
        }

        async function exportCompleteReport() {
            try {
                showLoadingIndicator('Generating Complete Report...');

                // Fetch all data
                const [salesResponse, stockResponse, paymentsResponse] = await Promise.all([
                    fetch('export_data.php?action=sales_data'),
                    fetch('export_data.php?action=stock_data'),
                    fetch('export_data.php?action=payments_data')
                ]);

                const [salesData, stockData, paymentsData] = await Promise.all([
                    salesResponse.json(),
                    stockResponse.json(),
                    paymentsResponse.json()
                ]);

                if (!salesData.success || !stockData.success || !paymentsData.success) {
                    throw new Error('Failed to fetch complete data');
                }

                generateCompletePDF(salesData.data, stockData.data, paymentsData.data);
                hideLoadingIndicator();

            } catch (error) {
                console.error('Error exporting complete report:', error);
                alert('Error generating complete report: ' + error.message);
                hideLoadingIndicator();
            }
        }

        function generateSalesPDF(data) {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            // Header
            doc.setFontSize(20);
            doc.setTextColor(40, 40, 40);
            doc.text('UrbanStitch - Sales Report', 20, 20);

            doc.setFontSize(12);
            doc.setTextColor(100, 100, 100);
            doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 20, 30);

            let yPosition = 50;

            // Sales Summary
            doc.setFontSize(16);
            doc.setTextColor(40, 40, 40);
            doc.text('Sales Summary', 20, yPosition);
            yPosition += 10;

            const summaryData = [
                ['Total Orders', data.summary.total_orders || 0],
                ['Total Revenue', `₱${parseFloat(data.summary.total_revenue || 0).toLocaleString()}`],
                ['Average Order Value', `₱${parseFloat(data.summary.avg_order_value || 0).toFixed(2)}`],
                ['Completed Orders', data.summary.completed_orders || 0]
            ];

            doc.autoTable({
                startY: yPosition,
                head: [
                    ['Metric', 'Value']
                ],
                body: summaryData,
                theme: 'grid',
                styles: {
                    fontSize: 10
                }
            });

            yPosition = doc.lastAutoTable.finalY + 20;

            // Monthly Sales
            if (data.monthly && data.monthly.length > 0) {
                doc.setFontSize(16);
                doc.text('Monthly Sales (Last 12 Months)', 20, yPosition);
                yPosition += 10;

                const monthlyData = data.monthly.map(row => [
                    row.month,
                    row.orders_count,
                    `₱${parseFloat(row.revenue).toLocaleString()}`
                ]);

                doc.autoTable({
                    startY: yPosition,
                    head: [
                        ['Month', 'Orders', 'Revenue']
                    ],
                    body: monthlyData,
                    theme: 'grid',
                    styles: {
                        fontSize: 10
                    }
                });

                yPosition = doc.lastAutoTable.finalY + 20;
            }

            // Add new page if needed
            if (yPosition > 250) {
                doc.addPage();
                yPosition = 20;
            }

            // Top Products
            if (data.top_products && data.top_products.length > 0) {
                doc.setFontSize(16);
                doc.text('Top Selling Products', 20, yPosition);
                yPosition += 10;

                const productData = data.top_products.map(row => [
                    row.product_name,
                    row.total_sold,
                    `₱${parseFloat(row.product_revenue).toLocaleString()}`,
                    `₱${parseFloat(row.current_price).toFixed(2)}`
                ]);

                doc.autoTable({
                    startY: yPosition,
                    head: [
                        ['Product Name', 'Units Sold', 'Revenue', 'Current Price']
                    ],
                    body: productData,
                    theme: 'grid',
                    styles: {
                        fontSize: 10
                    }
                });
            }

            doc.save(`UrbanStitch_Sales_Report_${new Date().toISOString().split('T')[0]}.pdf`);
        }

        function generateStockPDF(data) {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            // Header
            doc.setFontSize(20);
            doc.setTextColor(40, 40, 40);
            doc.text('UrbanStitch - Stock Report', 20, 20);

            doc.setFontSize(12);
            doc.setTextColor(100, 100, 100);
            doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 20, 30);

            let yPosition = 50;

            // Stock Summary
            doc.setFontSize(16);
            doc.setTextColor(40, 40, 40);
            doc.text('Stock Summary', 20, yPosition);
            yPosition += 10;

            const summaryData = [
                ['Total Products', data.summary.total_products || 0],
                ['Total Stock Units', data.summary.total_stock || 0],
                ['Out of Stock Products', data.summary.out_of_stock || 0],
                ['Low Stock Products (≤10)', data.summary.low_stock || 0]
            ];

            doc.autoTable({
                startY: yPosition,
                head: [
                    ['Metric', 'Value']
                ],
                body: summaryData,
                theme: 'grid',
                styles: {
                    fontSize: 10
                }
            });

            yPosition = doc.lastAutoTable.finalY + 20;

            // Products Stock Details
            if (data.products && data.products.length > 0) {
                doc.setFontSize(16);
                doc.text('Product Stock Details', 20, yPosition);
                yPosition += 10;

                const productData = data.products.slice(0, 20).map(product => [
                    product.name.substring(0, 30),
                    product.category_name || 'N/A',
                    product.size_variants || 0,
                    product.total_stock,
                    product.total_stock === 0 ? 'Out of Stock' :
                    product.total_stock <= 10 ? 'Low Stock' : 'In Stock'
                ]);

                doc.autoTable({
                    startY: yPosition,
                    head: [
                        ['Product Name', 'Category', 'Size Variants', 'Total Stock', 'Status']
                    ],
                    body: productData,
                    theme: 'grid',
                    styles: {
                        fontSize: 9
                    }
                });

                yPosition = doc.lastAutoTable.finalY + 20;
            }

            // Size Variants Stock (if exists)
            if (data.size_variants && data.size_variants.length > 0) {
                // Add new page for size variants
                doc.addPage();
                yPosition = 20;

                doc.setFontSize(16);
                doc.text('Size Variants Stock', 20, yPosition);
                yPosition += 10;

                const sizeData = data.size_variants.slice(0, 30).map(size => [
                    size.product_name.substring(0, 25),
                    size.size_code,
                    size.size_name,
                    size.stock_quantity,
                    size.size_type || 'N/A'
                ]);

                doc.autoTable({
                    startY: yPosition,
                    head: [
                        ['Product', 'Size Code', 'Size Name', 'Stock', 'Type']
                    ],
                    body: sizeData,
                    theme: 'grid',
                    styles: {
                        fontSize: 9
                    }
                });
            }

            doc.save(`UrbanStitch_Stock_Report_${new Date().toISOString().split('T')[0]}.pdf`);
        }

        function generatePaymentsPDF(data) {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            // Header
            doc.setFontSize(20);
            doc.setTextColor(40, 40, 40);
            doc.text('UrbanStitch - Payments Report', 20, 20);

            doc.setFontSize(12);
            doc.setTextColor(100, 100, 100);
            doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 20, 30);

            let yPosition = 50;

            // Payment Method Summary
            doc.setFontSize(16);
            doc.setTextColor(40, 40, 40);
            doc.text('Payment Methods Summary', 20, yPosition);
            yPosition += 10;

            const paymentSummaryData = data.summary.map(payment => [
                payment.payment_method.toUpperCase(),
                payment.order_count,
                `₱${parseFloat(payment.total_amount).toLocaleString()}`,
                `₱${parseFloat(payment.avg_amount).toFixed(2)}`
            ]);

            doc.autoTable({
                startY: yPosition,
                head: [
                    ['Payment Method', 'Orders', 'Total Amount', 'Average Amount']
                ],
                body: paymentSummaryData,
                theme: 'grid',
                styles: {
                    fontSize: 10
                }
            });

            yPosition = doc.lastAutoTable.finalY + 20;

            // GCash Summary
            if (data.gcash_data) {
                doc.setFontSize(16);
                doc.text('GCash Payment Details', 20, yPosition);
                yPosition += 10;

                const gcashData = [
                    ['Total GCash Orders', data.gcash_data.gcash_orders || 0],
                    ['Total GCash Revenue', `₱${parseFloat(data.gcash_data.gcash_revenue || 0).toLocaleString()}`],
                    ['Average GCash Order', `₱${parseFloat(data.gcash_data.gcash_avg || 0).toFixed(2)}`]
                ];

                doc.autoTable({
                    startY: yPosition,
                    head: [
                        ['Metric', 'Value']
                    ],
                    body: gcashData,
                    theme: 'grid',
                    styles: {
                        fontSize: 10
                    }
                });

                yPosition = doc.lastAutoTable.finalY + 20;
            }

            // Recent Transactions
            if (data.transactions && data.transactions.length > 0) {
                if (yPosition > 200) {
                    doc.addPage();
                    yPosition = 20;
                }

                doc.setFontSize(16);
                doc.text('Recent Transactions', 20, yPosition);
                yPosition += 10;

                const transactionData = data.transactions.slice(0, 15).map(transaction => [
                    transaction.order_number,
                    `₱${parseFloat(transaction.total_amount).toFixed(2)}`,
                    transaction.payment_method.toUpperCase(),
                    transaction.status.toUpperCase(),
                    new Date(transaction.created_at).toLocaleDateString()
                ]);

                doc.autoTable({
                    startY: yPosition,
                    head: [
                        ['Order #', 'Amount', 'Method', 'Status', 'Date']
                    ],
                    body: transactionData,
                    theme: 'grid',
                    styles: {
                        fontSize: 9
                    }
                });
            }

            doc.save(`UrbanStitch_Payments_Report_${new Date().toISOString().split('T')[0]}.pdf`);
        }

        function generateCompletePDF(salesData, stockData, paymentsData) {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            // Header
            doc.setFontSize(20);
            doc.setTextColor(40, 40, 40);
            doc.text('UrbanStitch - Complete Business Report', 20, 20);

            doc.setFontSize(12);
            doc.setTextColor(100, 100, 100);
            doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 20, 30);

            let yPosition = 50;

            // Executive Summary
            doc.setFontSize(16);
            doc.setTextColor(40, 40, 40);
            doc.text('Executive Summary', 20, yPosition);
            yPosition += 10;

            const executiveSummary = [
                ['Total Orders', salesData.summary.total_orders || 0],
                ['Total Revenue', `₱${parseFloat(salesData.summary.total_revenue || 0).toLocaleString()}`],
                ['Total Products', stockData.summary.total_products || 0],
                ['Total Stock Units', stockData.summary.total_stock || 0],
                ['Payment Methods', paymentsData.summary.length || 0]
            ];

            doc.autoTable({
                startY: yPosition,
                head: [
                    ['Key Metric', 'Value']
                ],
                body: executiveSummary,
                theme: 'grid',
                styles: {
                    fontSize: 10
                }
            });

            // Add sales summary on new page
            doc.addPage();
            yPosition = 20;

            doc.setFontSize(16);
            doc.text('Sales Performance', 20, yPosition);
            yPosition += 10;

            const salesSummaryData = [
                ['Completed Orders', salesData.summary.completed_orders || 0],
                ['Average Order Value', `₱${parseFloat(salesData.summary.avg_order_value || 0).toFixed(2)}`],
                ['Top Category Revenue', salesData.categories.length > 0 ?
                    `₱${parseFloat(salesData.categories[0].category_revenue || 0).toLocaleString()}` : 'N/A'
                ]
            ];

            doc.autoTable({
                startY: yPosition,
                head: [
                    ['Sales Metric', 'Value']
                ],
                body: salesSummaryData,
                theme: 'grid',
                styles: {
                    fontSize: 10
                }
            });

            yPosition = doc.lastAutoTable.finalY + 20;

            // Stock Status
            doc.setFontSize(16);
            doc.text('Inventory Status', 20, yPosition);
            yPosition += 10;

            const stockSummaryData = [
                ['Out of Stock Products', stockData.summary.out_of_stock || 0],
                ['Low Stock Products', stockData.summary.low_stock || 0],
                ['Stock Coverage', `${Math.round(((stockData.summary.total_products - stockData.summary.out_of_stock) / stockData.summary.total_products) * 100)}%`]
            ];

            doc.autoTable({
                startY: yPosition,
                head: [
                    ['Stock Metric', 'Value']
                ],
                body: stockSummaryData,
                theme: 'grid',
                styles: {
                    fontSize: 10
                }
            });

            yPosition = doc.lastAutoTable.finalY + 20;

            // Payment Performance
            doc.setFontSize(16);
            doc.text('Payment Performance', 20, yPosition);
            yPosition += 10;

            const paymentPerformanceData = paymentsData.summary.map(payment => [
                payment.payment_method.toUpperCase(),
                `₱${parseFloat(payment.total_amount).toLocaleString()}`,
                `${Math.round((payment.total_amount / paymentsData.summary.reduce((sum, p) => sum + parseFloat(p.total_amount), 0)) * 100)}%`
            ]);

            doc.autoTable({
                startY: yPosition,
                head: [
                    ['Payment Method', 'Revenue', 'Share %']
                ],
                body: paymentPerformanceData,
                theme: 'grid',
                styles: {
                    fontSize: 10
                }
            });

            doc.save(`UrbanStitch_Complete_Report_${new Date().toISOString().split('T')[0]}.pdf`);
        }

        // Loading indicator functions
        function showLoadingIndicator(message) {
            const indicator = document.createElement('div');
            indicator.id = 'pdfLoadingIndicator';
            indicator.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        color: white;
        font-size: 18px;
    `;
            indicator.innerHTML = `
        <div style="text-align: center;">
            <i class="fas fa-spinner fa-spin" style="font-size: 32px; margin-bottom: 10px;"></i><br>
            ${message}
        </div>
    `;
            document.body.appendChild(indicator);
        }

        function hideLoadingIndicator() {
            const indicator = document.getElementById('pdfLoadingIndicator');
            if (indicator) {
                indicator.remove();
            }
        }

        // Update existing generatePDFReport function
        function generatePDFReport(type) {
            switch (type) {
                case 'sales':
                    exportSalesReport();
                    break;
                case 'products':
                    exportStockReport();
                    break;
                case 'payments':
                    exportPaymentsReport();
                    break;
                default:
                    exportCompleteReport();
                    break;
            }
        }




        // Edit Product Functions
        function editProduct(productId) {
            // Get product data from the table row or make an AJAX call
            fetch(`get_product.php?id=${productId}`)
                .then(response => response.json())
                .then(product => {
                    if (product.success) {
                        populateEditForm(product.data);
                        showModal('editProductModal');
                    } else {
                        alert('Failed to load product data');
                    }
                })
                .catch(error => {
                    console.error('Error loading product:', error);
                    alert('Error loading product data');
                });
        }

        function populateEditForm(product) {
            document.getElementById('editProductId').value = product.id;
            document.getElementById('editProductName').value = product.name;
            document.getElementById('editProductDescription').value = product.description;
            document.getElementById('editProductPrice').value = product.price;
            document.getElementById('editProductOriginalPrice').value = product.original_price || '';
            document.getElementById('currentImageUrl').value = product.image_url;
            document.getElementById('currentProductImage').src = product.image_url;
        }

        function toggleEditImageMethod() {
            const method = document.querySelector('input[name="edit_image_method"]:checked').value;
            const fileSection = document.getElementById('editFileUploadSection');
            const urlSection = document.getElementById('editUrlUploadSection');
            const watermarkOptions = document.getElementById('editWatermarkOptions');

            fileSection.style.display = 'none';
            urlSection.style.display = 'none';
            watermarkOptions.style.display = 'none';

            if (method === 'upload') {
                fileSection.style.display = 'block';
                watermarkOptions.style.display = 'block';
            } else if (method === 'url') {
                urlSection.style.display = 'block';
                watermarkOptions.style.display = 'block';
            }
        }

        function previewEditUrlImage() {
            const url = document.getElementById('editProductImageUrl').value;
            if (url) {
                const img = document.getElementById('currentProductImage');
                img.src = url;
            }
        }
        // Order Management Functions


        // View order details
        async function viewOrderDetails(orderId) {
            const modal = document.getElementById('orderDetailsModal');
            const title = document.getElementById('orderDetailsTitle');
            const content = document.getElementById('orderDetailsContent');

            title.textContent = 'Loading Order Details...';
            content.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            showModal('orderDetailsModal');

            try {
                const response = await fetch(`adminDashboard.php?action=get_order_details&order_id=${orderId}`);
                const data = await response.json();

                if (data.success) {
                    title.textContent = `Order Details - ${data.order.order_number}`;
                    content.innerHTML = generateOrderDetailsHTML(data.order, data.items, data.history);
                } else {
                    content.innerHTML = '<div style="color: #ff4444; text-align: center; padding: 40px;">Failed to load order details</div>';
                }
            } catch (error) {
                console.error('Error loading order details:', error);
                content.innerHTML = '<div style="color: #ff4444; text-align: center; padding: 40px;">Error loading order details</div>';
            }
        }

        // Generate order details HTML
        function generateOrderDetailsHTML(order, items, history) {
            const billingInfo = order.billing_info ? JSON.parse(order.billing_info) : {};
            const statusBadgeClass = getStatusBadgeClass(order.status);

            let html = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <div>
                        <h4>Order Information</h4>
                        <div style="background: #f8f9fa; padding: 16px; border-radius: 8px;">
                            <div style="margin-bottom: 8px;"><strong>Order Number:</strong> ${order.order_number}</div>
                            <div style="margin-bottom: 8px;"><strong>Date:</strong> ${new Date(order.created_at).toLocaleDateString()}</div>
                            <div style="margin-bottom: 8px;"><strong>Status:</strong> <span class="badge ${statusBadgeClass}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></div>
                            <div style="margin-bottom: 8px;"><strong>Total:</strong> ₱${parseFloat(order.total_amount).toFixed(2)}</div>
                            <div style="margin-bottom: 8px;"><strong>Payment:</strong> ${order.payment_method.toUpperCase()}</div>
                        </div>
                    </div>
                    
                    <div>
                        <h4>Customer Information</h4>
                        <div style="background: #f8f9fa; padding: 16px; border-radius: 8px;">
                            <div style="margin-bottom: 8px;"><strong>Name:</strong> ${billingInfo.first_name || ''} ${billingInfo.last_name || ''}</div>
                            <div style="margin-bottom: 8px;"><strong>Email:</strong> ${billingInfo.email || ''}</div>
                            <div style="margin-bottom: 8px;"><strong>Phone:</strong> ${billingInfo.phone || ''}</div>
                            <div style="margin-bottom: 8px;"><strong>Address:</strong> ${billingInfo.address || ''}, ${billingInfo.city || ''}, ${billingInfo.province || ''} ${billingInfo.postal_code || ''}</div>
                        </div>
                    </div>
                </div>
            `;

            if (order.gcash_number) {
                html += `
                    <div style="margin-bottom: 24px;">
                        <h4>GCash Payment Information</h4>
                        <div style="background: #e3f2fd; padding: 16px; border-radius: 8px; border-left: 4px solid #2196f3;">
                            <div style="margin-bottom: 8px;"><strong>GCash Number:</strong> ${order.gcash_number}</div>
                            <div style="margin-bottom: 8px;"><strong>Reference Number:</strong> ${order.gcash_reference || 'N/A'}</div>
                        </div>
                    </div>
                `;
            }

            return html;
        }

        // Update order status
        function updateOrderStatus(orderId) {
            // First get current order info
            fetch(`adminDashboard.php?action=get_order_status&order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('updateOrderId').value = orderId;
                        document.getElementById('currentOrderStatus').innerHTML = `
                            <span class="badge ${getStatusBadgeClass(data.status)}">
                                ${data.status.charAt(0).toUpperCase() + data.status.slice(1)}
                            </span>
                        `;

                        showModal('updateOrderStatusModal');
                    }
                })
                .catch(error => {
                    console.error('Error getting order status:', error);
                    alert('Failed to load order information');
                });
        }

        // Get status badge class
        function getStatusBadgeClass(status) {
            const statusClasses = {
                'pending': 'badge-warning',
                'confirmed': 'badge-info',
                'processing': 'badge-primary',
                'shipped': 'badge-secondary',
                'delivered': 'badge-success',
                'completed': 'badge-success',
                'cancelled': 'badge-danger'
            };
            return statusClasses[status] || 'badge-secondary';
        }

        // Filter orders by status
        function filterOrders() {
            const filter = document.getElementById('orderStatusFilter').value;
            const rows = document.querySelectorAll('#ordersTableBody tr');

            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (filter === 'all' || filter === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        // Fixed saveOrderStatus function for admin_notes column
        // CORRECTED saveOrderStatus function for the actual database structure
        function saveOrderStatus() {
            const orderId = document.getElementById('updateOrderId').value;
            const newStatus = document.getElementById('newOrderStatus').value;

            console.log('Updating order:', orderId, 'to status:', newStatus);

            if (!orderId || !newStatus || newStatus === 'Select new status...') {
                alert('Please select a valid status');
                return;
            }

            // Map dropdown values to database enum values
            const statusMapping = {
                'Confirmed - Payment Verified': 'confirmed',
                'Processing - Preparing Order': 'processing',
                'Shipped - Order Sent': 'shipped',
                'Delivered - Order Arrived': 'delivered',
                'Completed - Order Finished': 'completed',
                'Cancelled - Order Cancelled': 'cancelled'
            };

            // Get the database status value
            const dbStatus = statusMapping[newStatus] || newStatus.toLowerCase();

            console.log('Mapped status:', dbStatus);

            // Show loading state
            const submitBtn = event.target;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;

            // Create form data - using CORRECT column names
            const formData = new FormData();
            formData.append('action', 'update_order_status');
            formData.append('order_id', orderId);
            formData.append('status', dbStatus); // Using 'status' column, not 'admin_notes'

            console.log('Sending form data:', {
                action: 'update_order_status',
                order_id: orderId,
                status: dbStatus
            });

            // Send AJAX request
            fetch('adminDashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);

                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            console.log('Response text:', text);
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                // If response isn't JSON but status is OK, assume success
                                return {
                                    success: response.ok,
                                    message: 'Status updated successfully'
                                };
                            }
                        });
                    }
                })
                .then(data => {
                    console.log('Parsed response:', data);

                    if (data.success !== false) {
                        // Close modal
                        hideModal('updateOrderStatusModal');

                        // Update the status in the table
                        updateOrderStatusInTable(orderId, newStatus, dbStatus);

                        // Show success message
                        showAdminNotification(`Order #${orderId} status updated to ${newStatus}`, 'success');

                        // Optionally refresh the page to show all changes
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showAdminNotification(data.message || 'Failed to update order status', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error updating order status:', error);
                    showAdminNotification('Network error. Please try again.', 'error');
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        }

        // Updated table update function
        function updateOrderStatusInTable(orderId, displayStatus, dbStatus) {
            // Find the order row by order ID
            const orderRows = document.querySelectorAll('tr');
            let orderRow = null;

            orderRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0 && cells[0].textContent.trim() === orderId.toString()) {
                    orderRow = row;
                }
            });

            if (orderRow) {
                // Find the status column (usually after order number, total, etc.)
                const cells = orderRow.querySelectorAll('td');

                // Look for existing status badge or the status column
                let statusCell = null;
                cells.forEach(cell => {
                    if (cell.querySelector('.badge') || cell.textContent.toLowerCase().includes('pending') ||
                        cell.textContent.toLowerCase().includes('confirmed') || cell.textContent.toLowerCase().includes('processing') ||
                        cell.textContent.toLowerCase().includes('shipped') || cell.textContent.toLowerCase().includes('delivered') ||
                        cell.textContent.toLowerCase().includes('completed') || cell.textContent.toLowerCase().includes('cancelled')) {
                        statusCell = cell;
                    }
                });

                // If no status cell found, assume it's the second to last column
                if (!statusCell && cells.length > 1) {
                    statusCell = cells[cells.length - 2];
                }

                if (statusCell) {
                    // Update with the new status badge
                    statusCell.innerHTML = `<span class="badge ${getStatusBadgeClass(dbStatus)}">${displayStatus}</span>`;

                    // Update the row's data attribute if it exists
                    orderRow.setAttribute('data-status', dbStatus);
                }

                console.log('Updated order row for ID:', orderId, 'with status:', dbStatus);
            } else {
                console.log('Could not find order row for ID:', orderId);
            }
        }

        // Update the badge class function to match your database enum
        function getStatusBadgeClass(status) {
            const statusClasses = {
                'pending': 'badge-warning',
                'confirmed': 'badge-info',
                'processing': 'badge-primary',
                'shipped': 'badge-secondary',
                'delivered': 'badge-success',
                'completed': 'badge-success',
                'cancelled': 'badge-danger'
            };
            return statusClasses[status.toLowerCase()] || 'badge-secondary';
        }

        // Enhanced notification function for admin
        function showAdminNotification(message, type = 'info') {
            // Remove any existing notifications first
            const existingNotifications = document.querySelectorAll('.admin-notification');
            existingNotifications.forEach(notif => notif.remove());

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `admin-notification alert alert-${type}`;
            notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        max-width: 350px;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease;
        font-weight: 600;
    `;

            const bgColors = {
                success: '#d4edda',
                error: '#f8d7da',
                warning: '#fff3cd',
                info: '#d1ecf1'
            };

            const textColors = {
                success: '#155724',
                error: '#721c24',
                warning: '#856404',
                info: '#0c5460'
            };

            notification.style.backgroundColor = bgColors[type] || bgColors.info;
            notification.style.color = textColors[type] || textColors.info;
            notification.style.border = `1px solid ${textColors[type] || textColors.info}`;

            notification.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>${message}</span>
            <button type="button" onclick="this.parentElement.parentElement.remove()" 
                    style="background: none; border: none; font-size: 18px; cursor: pointer; margin-left: 10px; color: inherit;">
                &times;
            </button>
        </div>
    `;

            document.body.appendChild(notification);

            // Auto remove after 4 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 4000);
        }

        // Alternative function if your Update Status button has a different onclick
        function updateOrderStatusSubmit() {
            saveOrderStatus();
        }
        // Delete Category Functions
        function deleteCategory(categoryId) {
            document.getElementById('deleteCategoryId').value = categoryId;
            document.getElementById('loadingCategoryDependencies').style.display = 'block';
            document.getElementById('categoryDependencyResults').style.display = 'none';
            document.getElementById('deleteCategoryButtons').style.display = 'none';
            document.getElementById('forceCategoryDeleteFlag').value = '0';

            showModal('deleteCategoryModal');

            // Check dependencies
            const formData = new FormData();
            formData.append('action', 'check_category_dependencies');
            formData.append('category_id', categoryId);

            fetch('adminDashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loadingCategoryDependencies').style.display = 'none';
                    document.getElementById('categoryDependencyResults').style.display = 'block';
                    document.getElementById('deleteCategoryButtons').style.display = 'block';

                    if (data.error) {
                        document.getElementById('categoryDependencyResults').innerHTML = `
                <div style="color: #ff4444;">
                    <i class="fas fa-exclamation-triangle"></i> Error: ${data.error}
                </div>
            `;
                        return;
                    }

                    let html = `<h4>Category: "${data.category_name}"</h4>`;

                    if (data.can_delete_simple) {
                        html += `
                <div style="color: #28a745; margin-bottom: 15px;">
                    <i class="fas fa-check-circle"></i> This category has no products and can be safely deleted.
                </div>
            `;
                        document.getElementById('simpleCategoryDeleteBtn').style.display = 'inline-block';
                        document.getElementById('forceCategoryDeleteBtn').style.display = 'none';
                    } else {
                        html += `
                <div style="color: #dc3545; margin-bottom: 15px;">
                    <i class="fas fa-exclamation-triangle"></i> 
                    This category contains <strong>${data.product_count} product(s)</strong>.
                </div>
                <div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #ffc107;">
                    <strong>Force Delete Options:</strong><br>
                    • Products in this category will be moved to "Uncategorized"<br>
                    • This action cannot be undone
                </div>
            `;
                        document.getElementById('simpleCategoryDeleteBtn').style.display = 'none';
                        document.getElementById('forceCategoryDeleteBtn').style.display = 'inline-block';
                    }

                    document.getElementById('categoryDependencyResults').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error checking category dependencies:', error);
                    document.getElementById('loadingCategoryDependencies').style.display = 'none';
                    document.getElementById('categoryDependencyResults').style.display = 'block';
                    document.getElementById('categoryDependencyResults').innerHTML = `
            <div style="color: #ff4444;">
                <i class="fas fa-exclamation-triangle"></i> Error checking dependencies
            </div>
        `;
                });
        }

        function confirmForceCategoryDelete() {
            if (confirm('Are you sure you want to force delete this category? All products in this category will be moved to uncategorized. This action cannot be undone.')) {
                document.getElementById('forceCategoryDeleteFlag').value = '1';
                document.getElementById('deleteCategoryForm').submit();
            }
        }

        function editCategory(categoryId) {
            // Placeholder for edit category function
            alert('Edit category functionality - to be implemented');
        }

        // Export orders (placeholder)
        function exportOrders() {
            alert('Export feature - this would generate a CSV/PDF of orders');
        }
    </script>
</body>

</html>