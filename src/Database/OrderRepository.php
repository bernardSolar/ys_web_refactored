<?php
/**
 * Order Repository
 * 
 * Handles database operations for orders.
 */

namespace POS\Database;

use POS\Models\Order;
use PDO;

class OrderRepository extends Repository
{
    /**
     * Save an order
     * 
     * @param Order $order The order to save
     * @return int|bool Order ID on success, false on failure
     */
    public function save(Order $order)
    {
        try {
            if ($order->getId()) {
                // Update existing order
                $sql = "
                    UPDATE order_history
                    SET user_id = ?,
                        organisation = ?,
                        delivery_address = ?,
                        delivery_charge = ?,
                        order_datetime = ?,
                        order_text = ?,
                        total_amount = ?,
                        delivery_date = ?,
                        delivery_time = ?,
                        delivery_notes = ?
                    WHERE order_id = ?
                ";
                
                $this->execute($sql, [
                    $order->getUserId(),
                    $order->getOrganisation(),
                    $order->getDeliveryAddress(),
                    $order->getDeliveryCharge(),
                    $order->getOrderDateTime(),
                    $order->getOrderText(),
                    $order->getTotalAmount(),
                    $order->getDeliveryDate(),
                    $order->getDeliveryTime(),
                    $order->getDeliveryNotes(),
                    $order->getId()
                ]);
                
                return $order->getId();
            } else {
                // Insert new order
                $sql = "
                    INSERT INTO order_history (
                        user_id, organisation, delivery_address, delivery_charge,
                        order_datetime, order_text, total_amount,
                        delivery_date, delivery_time, delivery_notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                
                // Debug order data
                error_log("OrderRepository::save - Inserting new order: " . json_encode([
                    'user_id' => $order->getUserId(),
                    'organisation' => $order->getOrganisation(),
                    'delivery_address' => $order->getDeliveryAddress(),
                    'delivery_charge' => $order->getDeliveryCharge(),
                    'datetime' => $order->getOrderDateTime(),
                    'total' => $order->getTotalAmount(),
                    'delivery_date' => $order->getDeliveryDate(),
                    'delivery_time' => $order->getDeliveryTime(),
                    'delivery_notes' => $order->getDeliveryNotes()
                ]));
                
                $params = [
                    $order->getUserId(),
                    $order->getOrganisation() ?: '',
                    $order->getDeliveryAddress() ?: '',
                    $order->getDeliveryCharge() ?: 0,
                    $order->getOrderDateTime(),
                    $order->getOrderText() ?: '{}',
                    $order->getTotalAmount(),
                    $order->getDeliveryDate(),
                    $order->getDeliveryTime(),
                    $order->getDeliveryNotes()
                ];
                
                // Log parameters being passed to query
                error_log('OrderRepository::save - SQL parameters: ' . json_encode($params));
                
                $this->execute($sql, $params);
                
                // Get the inserted ID
                $orderId = $this->lastInsertId();
                $order->setId($orderId);
                
                return $orderId;
            }
        } catch (\Exception $e) {
            error_log("Error saving order: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Find order by ID
     * 
     * @param int $id Order ID
     * @return Order|null
     */
    public function findById($id)
    {
        $sql = "
            SELECT order_id, user_id, organisation, delivery_address,
                   delivery_charge, order_datetime, order_text, total_amount,
                   delivery_date, delivery_time, delivery_notes
            FROM order_history
            WHERE order_id = ?
        ";
        
        $row = $this->fetchOne($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return new Order($row);
    }
    
    /**
     * Get recent orders
     * 
     * @param int $limit Maximum number of orders to return
     * @param int|null $userId Filter by user ID (optional)
     * @return Order[]
     */
    public function findRecent($limit = 10, $userId = null)
    {
        try {
            error_log("OrderRepository::findRecent with limit $limit" . ($userId ? ", userId $userId" : ""));
            
            if ($userId) {
                // Get orders for specific user
                $sql = "
                    SELECT order_id, user_id, organisation, delivery_address, 
                           delivery_charge, order_datetime, order_text, total_amount,
                           delivery_date, delivery_time, delivery_notes
                    FROM order_history
                    WHERE user_id = ?
                    ORDER BY order_datetime DESC
                    LIMIT ?
                ";
                
                $rows = $this->fetchAll($sql, [$userId, $limit]);
            } else {
                // Get all recent orders
                $sql = "
                    SELECT order_id, user_id, organisation, delivery_address, 
                           delivery_charge, order_datetime, order_text, total_amount,
                           delivery_date, delivery_time, delivery_notes
                    FROM order_history
                    ORDER BY order_datetime DESC
                    LIMIT ?
                ";
                
                $rows = $this->fetchAll($sql, [$limit]);
            }
            
            error_log("OrderRepository::findRecent found " . count($rows) . " orders");
            
            $orders = [];
            foreach ($rows as $row) {
                $orders[] = new Order($row);
            }
            
            return $orders;
        } catch (\Exception $e) {
            error_log("Error in OrderRepository::findRecent: " . $e->getMessage());
            // Return empty array instead of throwing to avoid breaking the frontend
            return [];
        }
    }
    
    /**
     * Get orders by user ID
     * 
     * @param int $userId User ID
     * @return Order[]
     */
    public function findByUserId($userId)
    {
        try {
            error_log("OrderRepository::findByUserId for user $userId");
            
            $sql = "
                SELECT order_id, user_id, organisation, delivery_address, 
                       delivery_charge, order_datetime, order_text, total_amount,
                       delivery_date, delivery_time, delivery_notes
                FROM order_history
                WHERE user_id = ?
                ORDER BY order_datetime DESC
            ";
            
            $rows = $this->fetchAll($sql, [$userId]);
            
            error_log("OrderRepository::findByUserId found " . count($rows) . " orders");
            
            $orders = [];
            foreach ($rows as $row) {
                $orders[] = new Order($row);
            }
            
            return $orders;
        } catch (\Exception $e) {
            error_log("Error in OrderRepository::findByUserId: " . $e->getMessage());
            // Return empty array instead of throwing to avoid breaking the frontend
            return [];
        }
    }
    
    /**
     * Find orders by delivery date
     * 
     * @param string $date Delivery date in Y-m-d format
     * @return Order[]
     */
    public function findByDeliveryDate($date)
    {
        try {
            $sql = "
                SELECT order_id, user_id, organisation, delivery_address, 
                       delivery_charge, order_datetime, order_text, total_amount,
                       delivery_date, delivery_time, delivery_notes
                FROM order_history
                WHERE delivery_date = ?
                ORDER BY delivery_time ASC
            ";
            
            $rows = $this->fetchAll($sql, [$date]);
            
            $orders = [];
            foreach ($rows as $row) {
                $orders[] = new Order($row);
            }
            
            return $orders;
        } catch (\Exception $e) {
            error_log("Error in OrderRepository::findByDeliveryDate: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get reserved time slots for a specific date
     *
     * @param string $date Delivery date in Y-m-d format
     * @return array Array of reserved time slots (e.g., ["09:00", "14:00"])
     */
    public function getReservedTimeSlots($date)
    {
        try {
            $sql = "
                SELECT delivery_time
                FROM order_history
                WHERE delivery_date = ?
                AND delivery_time IS NOT NULL
            ";
            
            $rows = $this->fetchAll($sql, [$date]);
            $timeSlots = array_column($rows, 'delivery_time');
            
            return $timeSlots;
        } catch (\Exception $e) {
            error_log("Error in OrderRepository::getReservedTimeSlots: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete an order
     * 
     * @param int $id Order ID
     * @return bool Success status
     */
    public function delete($id)
    {
        try {
            $sql = "DELETE FROM order_history WHERE order_id = ?";
            $affected = $this->execute($sql, [$id]);
            return $affected > 0;
        } catch (\Exception $e) {
            error_log("Error deleting order: " . $e->getMessage());
            return false;
        }
    }
}