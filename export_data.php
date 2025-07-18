<?php
require_once 'config.php';

// Check if user is admin
function requireAdmin()
{
    if (!isLoggedIn() || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

requireAdmin();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'sales_data':
        exportSalesData();
        break;
    case 'stock_data':
        exportStockData();
        break;
    case 'payments_data':
        exportPaymentsData();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function exportSalesData()
{
    global $pdo;
    
    try {
        // Get sales summary
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
            FROM orders
        ");
        $salesSummary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get monthly sales
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as orders_count,
                SUM(total_amount) as revenue
            FROM orders 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ");
        $monthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get sales by category
        $stmt = $pdo->query("
            SELECT 
                c.name as category_name,
                COUNT(oi.id) as items_sold,
                SUM(oi.price * oi.quantity) as category_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'completed'
            GROUP BY c.id, c.name
            ORDER BY category_revenue DESC
        ");
        $categoryStales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get top selling products
        $stmt = $pdo->query("
            SELECT 
                p.name as product_name,
                SUM(oi.quantity) as total_sold,
                SUM(oi.price * oi.quantity) as product_revenue,
                p.price as current_price
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'completed'
            GROUP BY p.id, p.name, p.price
            ORDER BY total_sold DESC
            LIMIT 10
        ");
        $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'summary' => $salesSummary,
                'monthly' => $monthlySales,
                'categories' => $categoryStales,
                'top_products' => $topProducts
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function exportStockData()
{
    global $pdo;
    
    try {
        // Get stock summary
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_products,
                SUM(stock_quantity) as total_stock,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= 10 THEN 1 ELSE 0 END) as low_stock
            FROM products
        ");
        $stockSummary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get products with stock details
        $stmt = $pdo->query("
            SELECT 
                p.id,
                p.name,
                p.price,
                p.stock_quantity as main_stock,
                c.name as category_name,
                pt.name as product_type,
                COUNT(ps.id) as size_variants,
                COALESCE(SUM(ps.stock_quantity), p.stock_quantity) as total_stock
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_types pt ON p.product_type_id = pt.id
            LEFT JOIN product_sizes ps ON p.id = ps.product_id
            GROUP BY p.id
            ORDER BY total_stock ASC
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get size variants stock
        $stmt = $pdo->query("
            SELECT 
                p.name as product_name,
                ps.size_code,
                ps.size_name,
                ps.stock_quantity,
                pt.size_type
            FROM product_sizes ps
            JOIN products p ON ps.product_id = p.id
            LEFT JOIN product_types pt ON p.product_type_id = pt.id
            ORDER BY p.name, ps.size_code
        ");
        $sizeStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'summary' => $stockSummary,
                'products' => $products,
                'size_variants' => $sizeStock
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function exportPaymentsData()
{
    global $pdo;
    
    try {
        // Get payment summary
        $stmt = $pdo->query("
            SELECT 
                payment_method,
                COUNT(*) as order_count,
                SUM(total_amount) as total_amount,
                AVG(total_amount) as avg_amount
            FROM orders
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ");
        $paymentSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent transactions
        $stmt = $pdo->query("
            SELECT 
                o.id,
                o.order_number,
                o.total_amount,
                o.payment_method,
                o.status,
                o.gcash_number,
                o.gcash_reference,
                o.created_at,
                u.username,
                u.email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT 50
        ");
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get GCash specific data
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as gcash_orders,
                SUM(total_amount) as gcash_revenue,
                AVG(total_amount) as gcash_avg
            FROM orders 
            WHERE payment_method = 'gcash'
        ");
        $gcashData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get daily payment totals for last 30 days
        $stmt = $pdo->query("
            SELECT 
                DATE(created_at) as payment_date,
                payment_method,
                COUNT(*) as daily_count,
                SUM(total_amount) as daily_total
            FROM orders
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at), payment_method
            ORDER BY payment_date DESC
        ");
        $dailyPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'summary' => $paymentSummary,
                'transactions' => $transactions,
                'gcash_data' => $gcashData,
                'daily_payments' => $dailyPayments
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>