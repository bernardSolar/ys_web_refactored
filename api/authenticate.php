<?php
/**
 * User Authentication API Endpoint
 * Handles user login and session management
 */

// Include bootstrap file and initialize application
$init = require_once __DIR__ . '/../src/bootstrap.php';
$init();

// Use the auth controller to handle the request
$controller = new \POS\Controllers\AuthController();
$controller->handleRequest();