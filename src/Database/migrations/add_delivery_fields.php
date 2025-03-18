<?php
/**
 * Migration: Add Delivery Fields to Order History Table
 * 
 * This script adds delivery_date, delivery_time, and delivery_notes fields
 * to the order_history table to support the delivery slot scheduling feature.
 */

// Include bootstrap file to initialize application
require_once __DIR__ . '/../../bootstrap.php';

use POS\Database\Database;

// Get database connection
$db = Database::getInstance()->getConnection();

try {
    // Begin transaction
    $db->beginTransaction();
    
    // Check if columns already exist
    $stmt = $db->query("PRAGMA table_info(order_history)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existingColumns = array_column($columns, 'name');
    
    // Add delivery_date column if it doesn't exist
    if (!in_array('delivery_date', $existingColumns)) {
        $db->exec("ALTER TABLE order_history ADD COLUMN delivery_date DATE NULL");
        echo "Added delivery_date column to order_history table\n";
    }
    
    // Add delivery_time column if it doesn't exist
    if (!in_array('delivery_time', $existingColumns)) {
        $db->exec("ALTER TABLE order_history ADD COLUMN delivery_time VARCHAR(10) NULL");
        echo "Added delivery_time column to order_history table\n";
    }
    
    // Add delivery_notes column if it doesn't exist
    if (!in_array('delivery_notes', $existingColumns)) {
        $db->exec("ALTER TABLE order_history ADD COLUMN delivery_notes TEXT NULL");
        echo "Added delivery_notes column to order_history table\n";
    }
    
    // Commit transaction
    $db->commit();
    
    echo "Migration completed successfully\n";
} catch (Exception $e) {
    // Roll back transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
