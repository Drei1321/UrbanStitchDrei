<?php
// Determine if we're on localhost or production
$serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($serverName === 'localhost' || $serverName === '127.0.0.1' || strpos($serverName, 'localhost') !== false) {
    // Local development settings
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'urbanstitch_db';
} else {
    // Production settings
    $host = 'localhost';
    $username = 'u801377270_urbanstitch_db'; 
    $password = 'Urbanstitch@2025'; 
    $database = 'u801377270_urbanstitch_db'; 
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in - only declare if not already declared
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Redirect to login if not logged in
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: login.php');
            exit;
        }
    }
}

// Get current user (still from database)
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        global $pdo;
        if (!isLoggedIn()) return null;
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// XML Configuration
if (!function_exists('getXMLConfig')) {
    function getXMLConfig() {
        $configFile = __DIR__ . '/site_config.xml';
        if (file_exists($configFile)) {
            return simplexml_load_file($configFile);
        }
        return null;
    }
}

// Load site configuration from XML
$siteConfig = getXMLConfig();
if ($siteConfig) {
    // Define constants from XML
    if (!defined('SITE_NAME')) {
        define('SITE_NAME', (string)$siteConfig->site_info->name);
    }
    if (!defined('SITE_TAGLINE')) {
        define('SITE_TAGLINE', (string)$siteConfig->site_info->tagline);
    }
    if (!defined('FREE_SHIPPING_THRESHOLD')) {
        define('FREE_SHIPPING_THRESHOLD', (float)$siteConfig->business_settings->shipping->free_shipping_threshold);
    }
    if (!defined('CURRENCY')) {
        define('CURRENCY', (string)$siteConfig->business_settings->shipping->currency);
    }
}
?>