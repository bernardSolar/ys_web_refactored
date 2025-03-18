<?php
/**
 * Delivery Slot Repository
 * 
 * Handles database operations for delivery slots.
 */

namespace POS\Database;

use PDO;

class DeliverySlotRepository extends Repository
{
    /**
     * Reserve a delivery slot
     * 
     * @param string $date Date in Y-m-d format
     * @param string $timeSlot Time slot (e.g. "09:00")
     * @param int|null $orderId Associated order ID (optional)
     * @return int|bool Slot ID on success, false on failure
     */
    public function reserve($date, $timeSlot, $orderId = null)
    {
        try {
            // Check if slot is already reserved
            if ($this->isSlotReserved($date, $timeSlot)) {
                error_log("DeliverySlotRepository::reserve - Slot already reserved: $date $timeSlot");
                return false;
            }
            
            $sql = "
                INSERT INTO delivery_slots (
                    date, time_slot, order_id, status
                ) VALUES (?, ?, ?, ?)
            ";
            
            $this->execute($sql, [
                $date,
                $timeSlot,
                $orderId,
                'reserved'
            ]);
            
            $slotId = $this->lastInsertId();
            error_log("DeliverySlotRepository::reserve - Reserved slot $slotId for $date $timeSlot");
            
            return $slotId;
        } catch (\Exception $e) {
            error_log("Error reserving delivery slot: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a delivery slot
     * 
     * @param int $slotId Slot ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update($slotId, array $data)
    {
        try {
            $allowedFields = ['date', 'time_slot', 'order_id', 'status'];
            $updates = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $sql = "UPDATE delivery_slots SET " . implode(', ', $updates) . " WHERE slot_id = ?";
            $params[] = $slotId;
            
            $affected = $this->execute($sql, $params);
            return $affected > 0;
        } catch (\Exception $e) {
            error_log("Error updating delivery slot: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a slot is already reserved
     * 
     * @param string $date Date in Y-m-d format
     * @param string $timeSlot Time slot (e.g. "09:00")
     * @return bool Whether the slot is reserved
     */
    public function isSlotReserved($date, $timeSlot)
    {
        $sql = "
            SELECT COUNT(*) 
            FROM delivery_slots 
            WHERE date = ? AND time_slot = ? AND status = 'reserved'
        ";
        
        $count = $this->fetchColumn($sql, [$date, $timeSlot]);
        return $count > 0;
    }
    
    /**
     * Get all reserved slots for a date
     * 
     * @param string $date Date in Y-m-d format
     * @return array Array of reserved time slots
     */
    public function getReservedSlots($date)
    {
        $sql = "
            SELECT time_slot 
            FROM delivery_slots 
            WHERE date = ? AND status = 'reserved'
            ORDER BY time_slot ASC
        ";
        
        $rows = $this->fetchAll($sql, [$date]);
        return array_column($rows, 'time_slot');
    }
    
    /**
     * Get delivery slot by order ID
     * 
     * @param int $orderId Order ID
     * @return array|null Slot data or null if not found
     */
    public function findByOrderId($orderId)
    {
        $sql = "
            SELECT slot_id, date, time_slot, order_id, status, created_at
            FROM delivery_slots
            WHERE order_id = ?
        ";
        
        $row = $this->fetchOne($sql, [$orderId]);
        return $row ?: null;
    }
    
    /**
     * Get all slots for a date range
     * 
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array Array of slot data
     */
    public function findByDateRange($startDate, $endDate)
    {
        $sql = "
            SELECT slot_id, date, time_slot, order_id, status, created_at
            FROM delivery_slots
            WHERE date >= ? AND date <= ?
            ORDER BY date ASC, time_slot ASC
        ";
        
        return $this->fetchAll($sql, [$startDate, $endDate]);
    }
    
    /**
     * Cancel a delivery slot
     * 
     * @param int $slotId Slot ID
     * @return bool Success status
     */
    public function cancel($slotId)
    {
        try {
            $sql = "UPDATE delivery_slots SET status = 'cancelled' WHERE slot_id = ?";
            $affected = $this->execute($sql, [$slotId]);
            return $affected > 0;
        } catch (\Exception $e) {
            error_log("Error cancelling delivery slot: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a delivery slot
     * 
     * @param int $slotId Slot ID
     * @return bool Success status
     */
    public function delete($slotId)
    {
        try {
            $sql = "DELETE FROM delivery_slots WHERE slot_id = ?";
            $affected = $this->execute($sql, [$slotId]);
            return $affected > 0;
        } catch (\Exception $e) {
            error_log("Error deleting delivery slot: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark a delivery slot as completed
     * 
     * @param int $slotId Slot ID
     * @return bool Success status
     */
    public function complete($slotId)
    {
        try {
            $sql = "UPDATE delivery_slots SET status = 'completed' WHERE slot_id = ?";
            $affected = $this->execute($sql, [$slotId]);
            return $affected > 0;
        } catch (\Exception $e) {
            error_log("Error completing delivery slot: " . $e->getMessage());
            return false;
        }
    }
}