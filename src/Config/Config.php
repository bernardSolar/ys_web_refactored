<?php
/**
 * Application Configuration Class
 * 
 * Centralized configuration management for the POS system.
 * Loads configuration from file and provides access to it.
 */

namespace POS\Config;

class Config
{
    /**
     * Configuration data
     * @var array
     */
    private static $config = null;
    
    /**
     * Get configuration value
     * 
     * @param string $key Key in dot notation (e.g. 'database.type')
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get($key, $default = null)
    {
        // Load config if not already loaded
        if (self::$config === null) {
            self::load();
        }
        
        // Handle dot notation in key
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = self::$config;
            
            foreach ($keys as $k) {
                if (!isset($value[$k])) {
                    return $default;
                }
                $value = $value[$k];
            }
            
            return $value;
        }
        
        return isset(self::$config[$key]) ? self::$config[$key] : $default;
    }
    
    /**
     * Load configuration from file
     */
    public static function load()
    {
        // Get root path from constant
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', dirname(dirname(__DIR__)));
        }
        
        // Try possible config file locations
        $possiblePaths = [
            ROOT_PATH . '/inc/config.php',
            ROOT_PATH . '/config.php',
            dirname(ROOT_PATH) . '/inc/config.php'
        ];
        
        foreach ($possiblePaths as $configPath) {
            if (file_exists($configPath)) {
                self::$config = require $configPath;
                return true;
            }
        }
        
        // If no config file found, use fallback config
        self::$config = self::getFallbackConfig();
        return false;
    }
    
    /**
     * Get fallback configuration when no config file is found
     * 
     * @return array Fallback configuration
     */
    private static function getFallbackConfig()
    {
        return [
            'database' => [
                'type' => 'sqlite',
                'file' => 'products.db',
                'host' => 'localhost',
                'name' => 'pos_system',
                'user' => 'root',
                'pass' => ''
            ],
            'app' => [
                'name' => 'POS System',
                'version' => '1.0.0',
                'debug' => true
            ]
        ];
    }
    
    /**
     * Check if config has been loaded
     * 
     * @return bool
     */
    public static function isLoaded()
    {
        return self::$config !== null;
    }
}