<?php
/**
 * Test script for the new architecture
 * This script tests the core components of the refactored system
 */

// Include bootstrap file and initialize
$init = require_once 'src/bootstrap.php';
$init();

header('Content-Type: text/plain');
echo "Testing POS System Refactoring\n";
echo "-----------------------------\n\n";

// Test Product model
try {
    echo "Testing Product model:\n";
    $product = new \POS\Models\Product([
        'id' => 1,
        'name' => 'Test Product',
        'price' => 9.99,
        'sku' => 'TEST123',
        'stock' => 10,
        'category_id' => 1,
        'category' => 'Test Category'
    ]);
    
    echo "- Created product: " . $product->getName() . "\n";
    echo "- Price: £" . $product->getPrice() . "\n";
    echo "- Category: " . $product->getCategoryName() . "\n";
    echo "✓ Product model test passed\n\n";
} catch (Exception $e) {
    echo "✗ Product model test failed: " . $e->getMessage() . "\n\n";
}

// Test Config class
try {
    echo "Testing Config class:\n";
    $dbType = \POS\Config\Config::get('database.type');
    $appName = \POS\Config\Config::get('app.name');
    $debug = \POS\Config\Config::get('app.debug');
    
    echo "- Database type: " . $dbType . "\n";
    echo "- App name: " . $appName . "\n";
    echo "- Debug mode: " . ($debug ? 'true' : 'false') . "\n";
    echo "✓ Config test passed\n\n";
} catch (Exception $e) {
    echo "✗ Config test failed: " . $e->getMessage() . "\n\n";
}

// Test Database connection
try {
    echo "Testing Database connection:\n";
    $db = \POS\Database\Database::getInstance()->getConnection();
    echo "- Connection established\n";
    
    // Test a simple query
    $stmt = $db->query("SELECT COUNT(*) FROM products");
    $count = $stmt->fetchColumn();
    echo "- Product count: " . $count . "\n";
    echo "✓ Database test passed\n\n";
} catch (Exception $e) {
    echo "✗ Database test failed: " . $e->getMessage() . "\n\n";
}

// Test ProductRepository
try {
    echo "Testing ProductRepository:\n";
    $repo = new \POS\Database\ProductRepository();
    $products = $repo->findAll();
    echo "- Found " . count($products) . " products\n";
    
    if (count($products) > 0) {
        $firstProduct = $products[0];
        echo "- First product: " . $firstProduct->getName() . " (£" . $firstProduct->getPrice() . ")\n";
    }
    
    echo "✓ ProductRepository test passed\n\n";
} catch (Exception $e) {
    echo "✗ ProductRepository test failed: " . $e->getMessage() . "\n\n";
}

// Test ProductService
try {
    echo "Testing ProductService:\n";
    $service = new \POS\Services\ProductService();
    $productsByCategory = $service->getAllProductsByCategory();
    echo "- Found " . count($productsByCategory) . " categories\n";
    
    foreach ($productsByCategory as $category => $products) {
        echo "  - Category '$category': " . count($products) . " products\n";
    }
    
    echo "✓ ProductService test passed\n\n";
} catch (Exception $e) {
    echo "✗ ProductService test failed: " . $e->getMessage() . "\n\n";
}

echo "All tests completed.\n";