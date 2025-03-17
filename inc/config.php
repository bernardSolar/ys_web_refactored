<?php
/**
 * Configuration file for POS system
 * Contains database connection settings and other configuration options
 */
return [
    // Database configuration
    'database' => [
        'type' => 'sqlite',  // Currently using SQLite, will change to 'mysql' for production
        'file' => '../products.db',  // Path to SQLite database file (relative to API files)
        
        // MySQL settings (for future use)
        'host' => 'localhost',
        'name' => 'york_pos',
        'user' => 'pos_user',
        'pass' => 'securepassword'
    ],
    
    // Application settings
    'app' => [
        'name' => 'York Supplies POS',
        'version' => '1.0.0',
        'debug' => true  // Set to false in production
    ]
];