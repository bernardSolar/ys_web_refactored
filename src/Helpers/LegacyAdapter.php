<?php
/**
 * Legacy Adapter
 * 
 * Provides compatibility functions to ease the transition from the old code to the new architecture.
 */

namespace POS\Helpers;

use POS\Database\Database;
use POS\Database\ProductRepository;
use POS\Database\UserRepository;
use POS\Database\OrderRepository;
use POS\Services\ProductService;
use POS\Services\OrderService;
use POS\Services\AuthService;

class LegacyAdapter
{
    /**
     * Get a PDO database connection using the new architecture
     * 
     * @return \PDO PDO database connection
     */
    public static function getDatabase()
    {
        return Database::getInstance()->getConnection();
    }
    
    /**
     * Get product manager instance for compatibility with existing code
     * 
     * @param \PDO $db Database connection
     * @return \ProductManager Legacy ProductManager instance
     */
    public static function getProductManager($db)
    {
        if (!class_exists('\\ProductManager')) {
            require_once dirname(dirname(__DIR__)) . '/inc/ProductManager.php';
        }
        
        return new \ProductManager($db);
    }
    
    /**
     * Get order manager instance for compatibility with existing code
     * 
     * @param \PDO $db Database connection
     * @return \OrderManager Legacy OrderManager instance
     */
    public static function getOrderManager($db)
    {
        if (!class_exists('\\OrderManager')) {
            require_once dirname(dirname(__DIR__)) . '/inc/OrderManager.php';
        }
        
        return new \OrderManager($db);
    }
    
    /**
     * Get user manager instance for compatibility with existing code
     * 
     * @param \PDO $db Database connection
     * @return \UserManager Legacy UserManager instance
     */
    public static function getUserManager($db)
    {
        if (!class_exists('\\UserManager')) {
            require_once dirname(dirname(__DIR__)) . '/inc/UserManager.php';
        }
        
        return new \UserManager($db);
    }
    
    /**
     * Get new ProductService
     * 
     * @return ProductService Product service
     */
    public static function getProductService()
    {
        return new ProductService(new ProductRepository());
    }
    
    /**
     * Get new OrderService
     * 
     * @return OrderService Order service
     */
    public static function getOrderService()
    {
        return new OrderService(new OrderRepository(), new ProductRepository());
    }
    
    /**
     * Get new AuthService
     * 
     * @return AuthService Auth service
     */
    public static function getAuthService()
    {
        return new AuthService(new UserRepository());
    }
}