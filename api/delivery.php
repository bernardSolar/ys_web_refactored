<?php
/**
 * Delivery API Endpoint
 * Handles delivery scheduling operations
 */

// Include bootstrap file and initialize application
$init = require_once __DIR__ . '/../src/bootstrap.php';
$init();

// Use the delivery controller to handle the request
$controller = new \POS\Controllers\DeliveryController();
$controller->handleRequest();
