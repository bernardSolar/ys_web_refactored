<?php
/**
 * Database Migration Runner
 * 
 * Runs the delivery fields migration script to update existing databases.
 */

// Define ROOT_PATH constant
define('ROOT_PATH', __DIR__);

// Include the migration script
require_once __DIR__ . '/src/Database/migrations/add_delivery_fields.php';

echo "Migration completed.\n";
