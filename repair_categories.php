<?php
// Category Database Repair Tool
// Save this as 'repair_categories.php' and run it to fix your categories

require_once 'config.php';

echo "<h1>🔧 UrbanStitch Category Repair Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
    .section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
    .duplicate { background: #ffebee; border-left: 4px solid #f44336; }
    .missing { background: #fff3e0; border-left: 4px solid #ff9800; }
    .correct { background: #e8f5e8; border-left: 4px solid #4caf50; }
    .action-btn { background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
    .danger-btn { background: #dc3545; }
    .success-btn { background: #28a745; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #f2f2f2; font-weight: bold; }
    .actions { margin: 20px 0; }
</style>";

echo "<div class='container'>";

try {
    // 1. ANALYZE CURRENT CATEGORIES
    echo "<div class='section'>";
    echo "<h2>📊 Current Categories Analysis</h2>";
    
    $categoriesQuery = "SELECT * FROM categories ORDER BY name, id";
    $categories = $pdo->query($categoriesQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Slug</th><th>Icon</th><th>Color</th><th>Products</th><th>Status</th></tr>";
    
    // Track duplicates
    $nameCount = [];
    $slugCount = [];
    
    foreach ($categories as $category) {
        // Count products in this category
        $countQuery = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute([$category['id']]);
        $productCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Track for duplicates
        $nameCount[$category['name']] = ($nameCount[$category['name']] ?? 0) + 1;
        $slugCount[$category['slug']] = ($slugCount[$category['slug']] ?? 0) + 1;
        
        $status = "✅ OK";
        $rowClass = "correct";
        
        if ($nameCount[$category['name']] > 1) {
            $status = "🔄 DUPLICATE NAME";
            $rowClass = "duplicate";
        }
        if ($slugCount[$category['slug']] > 1) {
            $status = "🔄 DUPLICATE SLUG";
            $rowClass = "duplicate";
        }
        if ($productCount == 0) {
            $status = "⚠️ NO PRODUCTS";
            $rowClass = "missing";
        }
        
        echo "<tr class='{$rowClass}'>";
        echo "<td>{$category['id']}</td>";
        echo "<td>" . htmlspecialchars($category['name']) . "</td>";
        echo "<td>" . htmlspecialchars($category['slug']) . "</td>";
        echo "<td>" . htmlspecialchars($category['icon']) . "</td>";
        echo "<td>" . htmlspecialchars($category['color']) . "</td>";
        echo "<td>{$productCount}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 2. IDENTIFY SPECIFIC ISSUES
    echo "<div class='section'>";
    echo "<h2>🚨 Issues Found</h2>";
    
    $issues = [];
    
    // Check for duplicates
    foreach ($nameCount as $name => $count) {
        if ($count > 1) {
            $issues[] = "Duplicate category name: '{$name}' appears {$count} times";
        }
    }
    
    foreach ($slugCount as $slug => $count) {
        if ($count > 1) {
            $issues[] = "Duplicate category slug: '{$slug}' appears {$count} times";
        }
    }
    
    // Check for missing winter wear
    $hasWinterWear = false;
    foreach ($categories as $category) {
        if (stripos($category['name'], 'winter') !== false || stripos($category['slug'], 'winter') !== false) {
            $hasWinterWear = true;
            break;
        }
    }
    
    if (!$hasWinterWear) {
        $issues[] = "Missing 'Winter Wear' category";
    }
    
    if (empty($issues)) {
        echo "<div class='correct'><p>✅ No major issues found!</p></div>";
    } else {
        foreach ($issues as $issue) {
            echo "<div class='duplicate'><p>❌ {$issue}</p></div>";
        }
    }
    echo "</div>";
    
    // 3. REPAIR ACTIONS
    echo "<div class='section'>";
    echo "<h2>🔧 Repair Actions</h2>";
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'remove_duplicates':
                echo "<h3>Removing Duplicate Categories...</h3>";
                
                // Find duplicates and keep the one with the most products
                $duplicateNames = array_filter($nameCount, function($count) { return $count > 1; });
                
                foreach ($duplicateNames as $name => $count) {
                    echo "<p>Processing duplicates for: <strong>{$name}</strong></p>";
                    
                    // Get all categories with this name, ordered by product count
                    $duplicateQuery = "
                        SELECT c.*, COUNT(p.id) as product_count 
                        FROM categories c 
                        LEFT JOIN products p ON c.id = p.category_id 
                        WHERE c.name = ? 
                        GROUP BY c.id 
                        ORDER BY product_count DESC, c.id ASC
                    ";
                    $duplicateStmt = $pdo->prepare($duplicateQuery);
                    $duplicateStmt->execute([$name]);
                    $duplicates = $duplicateStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Keep the first one (most products), delete the rest
                    $keepCategory = array_shift($duplicates);
                    echo "<p>✅ Keeping category ID {$keepCategory['id']} with {$keepCategory['product_count']} products</p>";
                    
                    foreach ($duplicates as $duplicate) {
                        // Move products to the category we're keeping
                        if ($duplicate['product_count'] > 0) {
                            $moveQuery = "UPDATE products SET category_id = ? WHERE category_id = ?";
                            $moveStmt = $pdo->prepare($moveQuery);
                            $moveStmt->execute([$keepCategory['id'], $duplicate['id']]);
                            echo "<p>📦 Moved {$duplicate['product_count']} products from category {$duplicate['id']} to {$keepCategory['id']}</p>";
                        }
                        
                        // Delete the duplicate category
                        $deleteQuery = "DELETE FROM categories WHERE id = ?";
                        $deleteStmt = $pdo->prepare($deleteQuery);
                        $deleteStmt->execute([$duplicate['id']]);
                        echo "<p>🗑️ Deleted duplicate category ID {$duplicate['id']}</p>";
                    }
                }
                
                echo "<div class='correct'><p>✅ Duplicate removal completed!</p></div>";
                break;
                
            case 'add_winter_wear':
                echo "<h3>Adding Winter Wear Category...</h3>";
                
                $insertQuery = "INSERT INTO categories (name, slug, description, icon, color) VALUES (?, ?, ?, ?, ?)";
                $insertStmt = $pdo->prepare($insertQuery);
                
                $result = $insertStmt->execute([
                    'Winter Wear',
                    'winter-wear',
                    'Cozy and stylish winter clothing for the urban lifestyle',
                    'fas fa-snowflake',
                    'text-blue'
                ]);
                
                if ($result) {
                    echo "<div class='correct'><p>✅ Winter Wear category added successfully!</p></div>";
                } else {
                    echo "<div class='duplicate'><p>❌ Failed to add Winter Wear category</p></div>";
                }
                break;
                
            case 'fix_slugs':
                echo "<h3>Fixing Category Slugs...</h3>";
                
                foreach ($categories as $category) {
                    $expectedSlug = strtolower(str_replace([' ', '&'], ['-', '-'], $category['name']));
                    $expectedSlug = preg_replace('/[^a-z0-9\-]/', '', $expectedSlug);
                    $expectedSlug = preg_replace('/\-+/', '-', $expectedSlug);
                    $expectedSlug = trim($expectedSlug, '-');
                    
                    if ($category['slug'] !== $expectedSlug) {
                        $updateQuery = "UPDATE categories SET slug = ? WHERE id = ?";
                        $updateStmt = $pdo->prepare($updateQuery);
                        $updateStmt->execute([$expectedSlug, $category['id']]);
                        
                        echo "<p>🔧 Fixed slug for '{$category['name']}': '{$category['slug']}' → '{$expectedSlug}'</p>";
                    }
                }
                
                echo "<div class='correct'><p>✅ Slug fixing completed!</p></div>";
                break;
        }
        
        // Refresh the page to show updated data
        echo "<script>setTimeout(function(){ window.location.reload(); }, 3000);</script>";
    }
    
    echo "<div class='actions'>";
    echo "<h3>Available Repair Actions:</h3>";
    
    if (!empty(array_filter($nameCount, function($count) { return $count > 1; }))) {
        echo "<form method='POST' style='display: inline;'>";
        echo "<input type='hidden' name='action' value='remove_duplicates'>";
        echo "<button type='submit' class='action-btn danger-btn' onclick='return confirm(\"Are you sure you want to remove duplicate categories? This will merge products.\")'>🗑️ Remove Duplicates</button>";
        echo "</form>";
    }
    
    if (!$hasWinterWear) {
        echo "<form method='POST' style='display: inline;'>";
        echo "<input type='hidden' name='action' value='add_winter_wear'>";
        echo "<button type='submit' class='action-btn success-btn'>❄️ Add Winter Wear Category</button>";
        echo "</form>";
    }
    
    echo "<form method='POST' style='display: inline;'>";
    echo "<input type='hidden' name='action' value='fix_slugs'>";
    echo "<button type='submit' class='action-btn'>🔧 Fix All Slugs</button>";
    echo "</form>";
    
    echo "</div>";
    echo "</div>";
    
    // 4. EXPECTED CATEGORIES
    echo "<div class='section'>";
    echo "<h2>📋 Recommended Category Structure</h2>";
    
    $recommendedCategories = [
        ['name' => 'Streetwear', 'slug' => 'streetwear', 'icon' => 'fas fa-tshirt', 'color' => 'text-neon-green'],
        ['name' => 'Footwear', 'slug' => 'footwear', 'icon' => 'fas fa-shoe-prints', 'color' => 'text-orange'],
        ['name' => 'Accessories', 'slug' => 'accessories', 'icon' => 'fas fa-glasses', 'color' => 'text-purple'],
        ['name' => 'Winter Wear', 'slug' => 'winter-wear', 'icon' => 'fas fa-snowflake', 'color' => 'text-blue'],
        ['name' => 'Shorts & Jeans', 'slug' => 'shorts-jeans', 'icon' => 'fas fa-user-circle', 'color' => 'text-indigo'],
        ['name' => 'Urban Tees', 'slug' => 'urban-tees', 'icon' => 'fas fa-tshirt', 'color' => 'text-neon-green']
    ];
    
    echo "<table>";
    echo "<tr><th>Name</th><th>Slug</th><th>Icon</th><th>Color</th><th>Status</th><th>Action</th></tr>";
    
    foreach ($recommendedCategories as $recommended) {
        $exists = false;
        $existingCategory = null;
        
        foreach ($categories as $category) {
            if (strtolower($category['name']) === strtolower($recommended['name']) || 
                $category['slug'] === $recommended['slug']) {
                $exists = true;
                $existingCategory = $category;
                break;
            }
        }
        
        if ($exists) {
            echo "<tr class='correct'>";
            echo "<td>" . htmlspecialchars($recommended['name']) . "</td>";
            echo "<td>" . htmlspecialchars($recommended['slug']) . "</td>";
            echo "<td>" . htmlspecialchars($recommended['icon']) . "</td>";
            echo "<td>" . htmlspecialchars($recommended['color']) . "</td>";
            echo "<td>✅ EXISTS (ID: {$existingCategory['id']})</td>";
            echo "<td>-</td>";
        } else {
            echo "<tr class='missing'>";
            echo "<td>" . htmlspecialchars($recommended['name']) . "</td>";
            echo "<td>" . htmlspecialchars($recommended['slug']) . "</td>";
            echo "<td>" . htmlspecialchars($recommended['icon']) . "</td>";
            echo "<td>" . htmlspecialchars($recommended['color']) . "</td>";
            echo "<td>❌ MISSING</td>";
            echo "<td>";
            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='action' value='add_category'>";
            echo "<input type='hidden' name='cat_name' value='" . htmlspecialchars($recommended['name']) . "'>";
            echo "<input type='hidden' name='cat_slug' value='" . htmlspecialchars($recommended['slug']) . "'>";
            echo "<input type='hidden' name='cat_icon' value='" . htmlspecialchars($recommended['icon']) . "'>";
            echo "<input type='hidden' name='cat_color' value='" . htmlspecialchars($recommended['color']) . "'>";
            echo "<button type='submit' class='action-btn success-btn' style='font-size: 12px; padding: 4px 8px;'>Add</button>";
            echo "</form>";
            echo "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Handle adding individual categories
    if (isset($_POST['action']) && $_POST['action'] === 'add_category') {
        $insertQuery = "INSERT INTO categories (name, slug, description, icon, color) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $pdo->prepare($insertQuery);
        
        $result = $insertStmt->execute([
            $_POST['cat_name'],
            $_POST['cat_slug'],
            "Discover amazing " . strtolower($_POST['cat_name']) . " products",
            $_POST['cat_icon'],
            $_POST['cat_color']
        ]);
        
        if ($result) {
            echo "<div class='correct'><p>✅ Category '{$_POST['cat_name']}' added successfully!</p></div>";
            echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='duplicate'>";
    echo "<h3>❌ Error occurred:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>";

echo "<div style='margin: 20px; padding: 20px; background: #e3f2fd; border-radius: 8px;'>";
echo "<h3>💡 Instructions:</h3>";
echo "<ol>";
echo "<li>Review the analysis above to understand the issues</li>";
echo "<li>Use the repair actions to fix duplicates and add missing categories</li>";
echo "<li>Check your categories page after running repairs</li>";
echo "<li>If you have products assigned to wrong categories, use the admin panel to reassign them</li>";
echo "</ol>";
echo "<p><strong>⚠️ Warning:</strong> Always backup your database before running repair actions!</p>";
echo "</div>";
?>