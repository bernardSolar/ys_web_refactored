<?php
/**
 * User Profile API Endpoint
 * Provides user information and profile management
 */

// Include bootstrap file and initialize application
$init = require_once __DIR__ . '/../src/bootstrap.php';
$init();

// Use the user controller to handle the request
$controller = new \POS\Controllers\UserController();
$controller->handleRequest();