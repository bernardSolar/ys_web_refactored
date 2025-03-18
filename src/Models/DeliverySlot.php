<?php
/**
 * Delivery Slot Model
 * 
 * Represents a delivery time slot in the system.
 */

namespace POS\Models;

class DeliverySlot
{
    /**
     * @var int
     */
    private $id;
    
    /**
     * @var string
     */
    private $date;
    
    /**
     * @var string
     */
    private $timeSlot;
    
    /**
     * @var int|null
     */
    private $orderId;
    
    /**
     * @var string
     */
    private $status = 'reserved';
    
    /**
     * @var string
     */
    private $createdAt;
    
    /**
     * Constructor
     * 
     * @param array $data Slot data
     */
    public function __construct(array $data = [])
    {
        // Initialize from data array if provided
        if (!empty($data)) {
            $this->hydrate($data);
        }
        
        // Set default creation date if not provided
        if (empty($this->createdAt)) {
            $this->createdAt = date('Y-m-d H:i:s');
        }
    }
    
    /**
     * Hydrate the object from an array of data
     * 
     * @param array $data
     * @return self
     */
    public function hydrate(array $data)
    {
        if (isset($data['slot_id'])) {
            $this->id = (int) $data['slot_id'];
        }
        
        if (isset($data['date'])) {
            $this->date = $data['date'];
        }
        
        if (isset($data['time_slot'])) {
            $this->timeSlot = $data['time_slot'];
        }
        
        if (isset($data['order_id'])) {
            $this->orderId = $data['order_id'] !== null ? (int) $data['order_id'] : null;
        }
        
        if (isset($data['status'])) {
            $this->status = $data['status'];
        }
        
        if (isset($data['created_at'])) {
            $this->createdAt = $data['created_at'];
        }
        
        return $this;
    }
    
    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'slot_id' => $this->id,
            'date' => $this->date,
            'time_slot' => $this->timeSlot,
            'order_id' => $this->orderId,
            'status' => $this->status,
            'created_at' => $this->createdAt
        ];
    }
    
    /**
     * Check if the slot is reserved
     * 
     * @return bool
     */
    public function isReserved()
    {
        return $this->status === 'reserved';
    }
    
    /**
     * Check if the slot is completed
     * 
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }
    
    /**
     * Check if the slot is cancelled
     * 
     * @return bool
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }
    
    /**
     * Get formatted time slot (for display)
     * 
     * @return string
     */
    public function getFormattedTimeSlot()
    {
        // Convert 24-hour format to 12-hour format with AM/PM
        if (preg_match('/^(\d{1,2}):00$/', $this->timeSlot, $matches)) {
            $hour = (int) $matches[1];
            
            if ($hour === 0) {
                return '12:00 AM';
            } elseif ($hour === 12) {
                return '12:00 PM';
            } elseif ($hour < 12) {
                return $hour . ':00 AM';
            } else {
                return ($hour - 12) . ':00 PM';
            }
        }
        
        // If not in expected format, return as is
        return $this->timeSlot;
    }
    
    /**
     * Get formatted date (for display)
     * 
     * @param string $format Date format (default: d F Y)
     * @return string
     */
    public function getFormattedDate($format = 'd F Y')
    {
        $date = \DateTime::createFromFormat('Y-m-d', $this->date);
        
        if ($date) {
            return $date->format($format);
        }
        
        // If not in expected format, return as is
        return $this->date;
    }
    
    // Getters and setters
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }
    
    public function getDate()
    {
        return $this->date;
    }
    
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }
    
    public function getTimeSlot()
    {
        return $this->timeSlot;
    }
    
    public function setTimeSlot($timeSlot)
    {
        $this->timeSlot = $timeSlot;
        return $this;
    }
    
    public function getOrderId()
    {
        return $this->orderId;
    }
    
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId !== null ? (int) $orderId : null;
        return $this;
    }
    
    public function getStatus()
    {
        return $this->status;
    }
    
    public function setStatus($status)
    {
        $validStatuses = ['reserved', 'completed', 'cancelled'];
        
        if (in_array($status, $validStatuses)) {
            $this->status = $status;
        }
        
        return $this;
    }
    
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}