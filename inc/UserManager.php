<?php
/**
 * User Manager Class
 * Handles all user-related database operations
 */
class UserManager {
    private $db;
    
    /**
     * Constructor
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get user by username
     * @param string $username Username to look up
     * @return array|bool User data or false if not found
     */
    public function getUserByUsername($username) {
        try {
            $query = $this->db->prepare("
                SELECT id, organisation, delivery_address, delivery_charge, rep_name, 
                       email, username, password_hash, role
                FROM users
                WHERE username = ?
            ");
            $query->execute([$username]);
            
            $user = $query->fetch(PDO::FETCH_ASSOC);
            
            return $user ?: false;
            
        } catch (PDOException $e) {
            error_log("Error retrieving user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by ID
     * @param int $userId User ID to look up
     * @return array|bool User data or false if not found
     */
    public function getUserById($userId) {
        try {
            $query = $this->db->prepare("
                SELECT id, organisation, delivery_address, delivery_charge, rep_name, 
                       email, username, role
                FROM users
                WHERE id = ?
            ");
            $query->execute([$userId]);
            
            $user = $query->fetch(PDO::FETCH_ASSOC);
            
            return $user ?: false;
            
        } catch (PDOException $e) {
            error_log("Error retrieving user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify user credentials
     * @param string $username Username to check
     * @param string $password Password to verify
     * @return array|bool User data if verified, false otherwise
     */
    public function verifyCredentials($username, $password) {
        $user = $this->getUserByUsername($username);
        
        if (!$user) {
            return false;
        }
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Remove password hash before returning
            unset($user['password_hash']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Get all users
     * @return array Array of users
     */
    public function getAllUsers() {
        try {
            $query = $this->db->query("
                SELECT id, organisation, delivery_address, delivery_charge, rep_name, 
                       email, username, role
                FROM users
                ORDER BY organisation
            ");
            
            return $query->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error retrieving users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user orders
     * @param int $userId User ID
     * @return array Array of orders for the user
     */
    public function getUserOrders($userId) {
        try {
            $query = $this->db->prepare("
                SELECT order_id, order_datetime, order_text, total_amount, delivery_charge
                FROM order_history
                WHERE user_id = ?
                ORDER BY order_datetime DESC
            ");
            $query->execute([$userId]);
            
            return $query->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error retrieving user orders: " . $e->getMessage());
            return [];
        }
    }
}