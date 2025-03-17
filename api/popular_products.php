<?php
/**
 * Popular Products API Endpoint
 * Returns the most popular products based on sales history
 */

// Include bootstrap file and initialize application
$init = require_once __DIR__ . '/../src/bootstrap.php';
$init();

// Use a product controller 
$controller = new \POS\Controllers\ProductController();
$controller->getPopularProducts();