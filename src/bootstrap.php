<?php
/**
 * Bootstrap File
 * 
 * Initializes the application, sets up autoloading, and loads configuration.
 */

// Turn on error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define root path
define('ROOT_PATH', dirname(__DIR__));

// Simple PSR-4 compliant autoloader
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'POS\\';
    
    // Base directory for the namespace prefix
    $base_dir = ROOT_PATH . '/src/';
    
    // Check if class uses our namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Not our namespace, let another autoloader handle it
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators,
    // append .php and build the full path
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If file exists, require it
    if (file_exists($file)) {
        require $file;
    } else {
        // For debugging, output what class is missing
        trigger_error("Class not found: $class in file: $file", E_USER_WARNING);
    }
});

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Return a function to initialize the application
return function () {
    // Load configuration
    try {
        if (!class_exists('\\POS\\Config\\Config')) {
            throw new Exception('Config class not found');
        }
        
        \POS\Config\Config::load();
        
        // Additional initialization can go here
        
        return [
            'initialized' => true,
            'timestamp' => time()
        ];
    } catch (Exception $e) {
        // Log error
        error_log("Bootstrap error: " . $e->getMessage());
        
        // If in development mode, display detailed error
        if (isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
            echo "Bootstrap error: " . $e->getMessage();
            echo "<pre>";
            debug_print_backtrace();
            echo "</pre>";
            exit;
        }
        
        return [
            'initialized' => false,
            'error' => $e->getMessage()
        ];
    }
};