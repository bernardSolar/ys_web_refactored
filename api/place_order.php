<?php
/**
 * Place Order API Endpoint
 * Processes an order and records it in the database
 */

// Include bootstrap file and initialize application
$init = require_once __DIR__ . '/../src/bootstrap.php';
$init();

// Use the order controller to handle the request
$controller = new \POS\Controllers\OrderController();
$controller->handleRequest();