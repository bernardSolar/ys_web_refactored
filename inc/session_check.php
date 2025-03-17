<?php
/**
 * Session Check
 * Verifies that the user is logged in, redirects to login page if not
 */

// Include bootstrap file
$init = require_once __DIR__ . '/../src/bootstrap.php';
$init();

// Use auth service to check if user is logged in
$authService = new \POS\Services\AuthService();
$authService->requireAuth('login.php');

// User is now guaranteed to be logged in
// $_SESSION contains the user data:
// $_SESSION['user_id'] - User ID
// $_SESSION['username'] - Username
// $_SESSION['organisation'] - Organization name
// $_SESSION['role'] - User role (admin or user)