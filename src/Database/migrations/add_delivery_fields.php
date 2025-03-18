<?php
/**
 * Migration: Add Delivery Fields to Order History Table
 * 
 * This script adds delivery_date, delivery_time, and delivery_notes fields
 * to the order_history table to support the delivery slot scheduling feature.
 * It also creates the delivery_slots table if it doesn't exist.
 */

// Include bootstrap file to initialize application
require_once __DIR__ . '/../../bootstrap.php';

use POS\Database\Database;

// Get database connection
$db = Database::getInstance()->getConnection();

try {
    // Begin transaction
    $db->beginTransaction();
    
    // Check if columns already exist in order_history table
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
    
    // Check if delivery_slots table exists
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='delivery_slots'");
    $tableExists = (bool) $stmt->fetch(PDO::FETCH_COLUMN);
    
    // Create delivery_slots table if it doesn't exist
    if (!$tableExists) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS delivery_slots (
                slot_id INTEGER PRIMARY KEY AUTOINCREMENT,
                date DATE NOT NULL,
                time_slot VARCHAR(10) NOT NULL,
                order_id INTEGER,
                status VARCHAR(20) DEFAULT 'reserved',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES order_history (order_id),
                UNIQUE(date, time_slot)
            );
        ");
        echo "Created delivery_slots table\n";
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
