<?php
/**
 * Order Manager Class
 * Handles all order-related database operations
 */
class OrderManager {
    private $db;
    
    /**
     * Constructor
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Save an order to the database
     * @param string $orderText Order text representation
     * @param float $totalAmount Total order amount
     * @param array|null $user User data if available
     * @param float|null $deliveryCharge Optional delivery charge
     * @return int|bool Order ID on success, false on failure
     */
    public function saveOrder($orderText, $totalAmount, $user = null, $deliveryCharge = null) {
        try {
            // Get current timestamp
            $orderDatetime = date('Y-m-d H:i:s');
            
            // Prepare variables for user data
            $userId = null;
            $organisation = null;
            $deliveryAddress = null;
            $actualDeliveryCharge = 0;
            
            // If user data is provided, include it
            if ($user) {
                $userId = $user['id'];
                $organisation = $user['organisation'];
                $deliveryAddress = $user['delivery_address'];
                $actualDeliveryCharge = ($deliveryCharge !== null) ? $deliveryCharge : $user['delivery_charge'];
                
                // Insert order record with user data
                $query = $this->db->prepare("
                    INSERT INTO order_history (
                        user_id, organisation, delivery_address, delivery_charge,
                        order_datetime, order_text, total_amount
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $query->execute([
                    $userId, $organisation, $deliveryAddress, $actualDeliveryCharge,
                    $orderDatetime, $orderText, $totalAmount + $actualDeliveryCharge
                ]);
            } else {
                // Insert order record without user data
                $query = $this->db->prepare("
                    INSERT INTO order_history (order_datetime, order_text, total_amount)
                    VALUES (?, ?, ?)
                ");
                $query->execute([$orderDatetime, $orderText, $totalAmount]);
            }
            
            // Return the order ID
            return $this->db->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Error saving order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent orders from the database
     * @param int $limit Maximum number of orders to return
     * @param int|null $userId Filter by user ID (optional)
     * @return array Array of recent orders
     */
    public function getRecentOrders($limit = 10, $userId = null) {
        $orders = [];
        
        try {
            if ($userId) {
                // Get orders for specific user
                $query = $this->db->prepare("
                    SELECT order_id, user_id, organisation, delivery_address, 
                           delivery_charge, order_datetime, total_amount
                    FROM order_history
                    WHERE user_id = ?
                    ORDER BY order_datetime DESC
                    LIMIT ?
                ");
                $query->execute([$userId, $limit]);
            } else {
                // Get all recent orders
                $query = $this->db->prepare("
                    SELECT order_id, user_id, organisation, delivery_address, 
                           delivery_charge, order_datetime, total_amount
                    FROM order_history
                    ORDER BY order_datetime DESC
                    LIMIT ?
                ");
                $query->execute([$limit]);
            }
            
            $orders = $query->fetchAll(PDO::FETCH_ASSOC);
            
            return $orders;
            
        } catch (PDOException $e) {
            error_log("Error retrieving recent orders: " . $e->getMessage());
            return $orders;
        }
    }
    
    /**
     * Get order details by ID
     * @param int $orderId Order ID
     * @return array|bool Order details or false on failure
     */
    public function getOrderDetails($orderId) {
        try {
            $query = $this->db->prepare("
                SELECT order_id, user_id, organisation, delivery_address,
                       delivery_charge, order_datetime, order_text, total_amount
                FROM order_history
                WHERE order_id = ?
            ");
            $query->execute([$orderId]);
            
            $order = $query->fetch(PDO::FETCH_ASSOC);
            
            return $order ?: false;
            
        } catch (PDOException $e) {
            error_log("Error retrieving order details: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate the total amount including delivery charge
     * @param float $orderTotal Base order total
     * @param float $deliveryCharge Delivery charge
     * @return float Final total with delivery charge
     */
    public function calculateTotalWithDelivery($orderTotal, $deliveryCharge) {
        return $orderTotal + $deliveryCharge;
    }
}