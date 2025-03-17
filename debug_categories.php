<?php
/**
 * Debug script to check category and product data in the database
 * Run this script directly to see database contents
 */
header('Content-Type: text/plain');

// Include database connection
require_once 'inc/db_connect.php';

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    // Output database path for reference
    $path = $db->query("PRAGMA database_list")->fetchAll(PDO::FETCH_ASSOC);
    echo "Database path: " . print_r($path, true) . "\n\n";
    
    // 1. List all categories
    echo "\n=== CATEGORIES ===\n";
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total categories: " . count($categories) . "\n";
    foreach ($categories as $cat) {
        echo "ID: {$cat['id']}, Name: '{$cat['name']}'\n";
    }
    
    // 2. Count products per category
    echo "\n\n=== PRODUCTS PER CATEGORY ===\n";
    $stmt = $db->query("
        SELECT c.id, c.name, COUNT(p.id) as product_count 
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id
        ORDER BY c.name
    ");
    
    $productCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($productCounts as $pc) {
        echo "Category: '{$pc['name']}', Products: {$pc['product_count']}\n";
    }
    
    // 3. List some sample products from each category
    echo "\n\n=== SAMPLE PRODUCTS ===\n";
    foreach ($categories as $cat) {
        echo "\nCategory: '{$cat['name']}'\n";
        $stmt = $db->prepare("
            SELECT name, price, sku, stock 
            FROM products 
            WHERE category_id = ? 
            LIMIT 5
        ");
        $stmt->execute([$cat['id']]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($products) > 0) {
            foreach ($products as $i => $p) {
                echo "  {$i}. {$p['name']} (£{$p['price']})\n";
            }
        } else {
            echo "  No products found\n";
        }
    }
    
    // 4. Check for any category name issues (case sensitivity, special chars)
    echo "\n\n=== CATEGORY NAME CHECK ===\n";
    $categoryNames = array_column($categories, 'name');
    $tabNames = ["Home", "Feeds", "Pest Control", "Soil & Compost", "Special Offers", "Sundries", "Vitax Range", "Weedkillers"];
    
    echo "UI Tab Names: " . implode(", ", $tabNames) . "\n";
    echo "\nDatabase Category Names: " . implode(", ", $categoryNames) . "\n";
    
    echo "\nMatching Check:\n";
    foreach ($tabNames as $tab) {
        $found = in_array($tab, $categoryNames);
        echo "Tab '$tab': " . ($found ? "✓ Found" : "✗ NOT FOUND") . "\n";
        
        // If not found, check for case-insensitive match
        if (!$found) {
            $matches = array_filter($categoryNames, function($cat) use ($tab) {
                return strtolower($cat) === strtolower($tab);
            });
            
            if (count($matches) > 0) {
                echo "  - Case-insensitive match found: '" . implode("', '", $matches) . "'\n";
            } else {
                // Check for close matches (similar names)
                $closeMatches = array_filter($categoryNames, function($cat) use ($tab) {
                    return similar_text($cat, $tab) > (strlen($tab) * 0.7);
                });
                
                if (count($closeMatches) > 0) {
                    echo "  - Similar names found: '" . implode("', '", $closeMatches) . "'\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
