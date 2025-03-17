<?php
/**
 * Products API Endpoint
 * Returns all products organized by category
 */

// Include bootstrap file and initialize application
$init = require_once __DIR__ . '/../src/bootstrap.php';
$init();

// Use controller to handle the request
$controller = new \POS\Controllers\ProductController();
$controller->handleRequest();