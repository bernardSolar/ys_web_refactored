<?php
/**
 * Order Model
 * 
 * Represents an order in the system.
 */

namespace POS\Models;

class Order
{
    /**
     * @var int
     */
    private $id;
    
    /**
     * @var int|null
     */
    private $userId;
    
    /**
     * @var string|null
     */
    private $organisation;
    
    /**
     * @var string|null
     */
    private $deliveryAddress;
    
    /**
     * @var float
     */
    private $deliveryCharge = 0.0;
    
    /**
     * @var string
     */
    private $orderDateTime;
    
    /**
     * @var string
     */
    private $orderText;
    
    /**
     * @var float
     */
    private $totalAmount;
    
    /**
     * @var array
     */
    private $items = [];
    
    /**
     * Constructor
     * 
     * @param array $data Order data
     */
    public function __construct(array $data = [])
    {
        // Initialize from data array if provided
        if (!empty($data)) {
            $this->hydrate($data);
        }
        
        // Set default order date if not provided
        if (empty($this->orderDateTime)) {
            $this->orderDateTime = date('Y-m-d H:i:s');
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
        if (isset($data['order_id'])) {
            $this->id = (int) $data['order_id'];
        }
        
        if (isset($data['user_id'])) {
            $this->userId = $data['user_id'] !== null ? (int) $data['user_id'] : null;
        }
        
        if (isset($data['organisation'])) {
            $this->organisation = $data['organisation'];
        }
        
        if (isset($data['delivery_address'])) {
            $this->deliveryAddress = $data['delivery_address'];
        }
        
        if (isset($data['delivery_charge'])) {
            $this->deliveryCharge = (float) $data['delivery_charge'];
        }
        
        if (isset($data['order_datetime'])) {
            $this->orderDateTime = $data['order_datetime'];
        }
        
        if (isset($data['order_text'])) {
            $this->orderText = $data['order_text'];
            
            // Parse order text to extract items if it's JSON
            if (!empty($this->orderText) && $this->isJson($this->orderText)) {
                $orderData = json_decode($this->orderText, true);
                if (isset($orderData['items']) && is_array($orderData['items'])) {
                    $this->items = $orderData['items'];
                }
            }
        }
        
        if (isset($data['total_amount'])) {
            $this->totalAmount = (float) $data['total_amount'];
        }
        
        if (isset($data['items']) && is_array($data['items'])) {
            $this->items = $data['items'];
        }
        
        return $this;
    }
    
    /**
     * Check if a string is valid JSON
     * 
     * @param string $string The string to check
     * @return bool Whether the string is valid JSON
     */
    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
    
    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'order_id' => $this->id,
            'user_id' => $this->userId,
            'organisation' => $this->organisation,
            'delivery_address' => $this->deliveryAddress,
            'delivery_charge' => $this->deliveryCharge,
            'order_datetime' => $this->orderDateTime,
            'order_text' => $this->orderText,
            'total_amount' => $this->totalAmount,
            'items' => $this->items
        ];
    }
    
    /**
     * Get the order text as JSON
     * This serializes the current order data
     * 
     * @return string
     */
    public function generateOrderText()
    {
        error_log('Order::generateOrderText called');
        
        // Create order details object 
        $orderDetails = [
            'items' => $this->items,
            'subtotals' => $this->calculateSubtotals(),
            'summary' => [
                'subtotal' => $this->calculateSubtotal(),
                'delivery_charge' => $this->deliveryCharge,
                'total' => $this->totalAmount
            ],
            'delivery_info' => [
                'delivery_charge' => $this->deliveryCharge,
                'delivery_address' => $this->deliveryAddress ?: '',
                'organisation' => $this->organisation ?: ''
            ],
            'contact_info' => null
        ];
        
        // Debug the details before encoding
        error_log('Order::generateOrderText details: ' . print_r($orderDetails, true));
        
        try {
            $this->orderText = json_encode($orderDetails, JSON_PRETTY_PRINT);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('JSON encoding error: ' . json_last_error_msg());
                // Fallback if encoding fails
                $this->orderText = '{"error":"Failed to encode order details"}';
            }
        } catch (\Exception $e) {
            error_log('Error generating order text: ' . $e->getMessage());
            $this->orderText = '{"error":"Exception while encoding order"}';
        }
        
        return $this->orderText;
    }
    
    /**
     * Calculate subtotals by category
     * 
     * @return array
     */
    private function calculateSubtotals()
    {
        error_log('Order::calculateSubtotals called');
        $subtotals = [];
        
        // Check if items is an array
        if (!is_array($this->items)) {
            error_log('Order::calculateSubtotals - Items is not an array: ' . gettype($this->items));
            return $subtotals;
        }
        
        error_log('Order::calculateSubtotals - Processing ' . count($this->items) . ' items');
        
        foreach ($this->items as $index => $item) {
            // Debug the item
            error_log('Order::calculateSubtotals - Processing item ' . $index . ': ' . json_encode($item));
            
            // Skip items without category
            if (!isset($item['category'])) {
                error_log('Order::calculateSubtotals - Item has no category, skipping');
                continue;
            }
            
            $category = $item['category'];
            
            // Calculate subtotal, handling different field names
            $price = isset($item['price']) ? $item['price'] : 0;
            $quantity = isset($item['quantity']) ? $item['quantity'] : 
                       (isset($item['count']) ? $item['count'] : 1);
            
            $subtotal = $price * $quantity;
            
            if (!isset($subtotals[$category])) {
                $subtotals[$category] = 0;
            }
            
            $subtotals[$category] += $subtotal;
        }
        
        error_log('Order::calculateSubtotals - Final subtotals: ' . json_encode($subtotals));
        return $subtotals;
    }
    
    /**
     * Calculate order subtotal (without delivery)
     * 
     * @return float
     */
    public function calculateSubtotal()
    {
        error_log('Order::calculateSubtotal called');
        $subtotal = 0;
        
        // Check if items is an array
        if (!is_array($this->items)) {
            error_log('Order::calculateSubtotal - Items is not an array: ' . gettype($this->items));
            return $subtotal;
        }
        
        foreach ($this->items as $index => $item) {
            if (!isset($item['price'])) {
                error_log('Order::calculateSubtotal - Item ' . $index . ' has no price');
                continue;
            }
            
            $price = (float)$item['price'];
            $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 
                       (isset($item['count']) ? (int)$item['count'] : 1);
            
            $lineTotal = $price * $quantity;
            $subtotal += $lineTotal;
            
            error_log("Order::calculateSubtotal - Item $index: price=$price, quantity=$quantity, lineTotal=$lineTotal");
        }
        
        error_log('Order::calculateSubtotal - Final subtotal: ' . $subtotal);
        return $subtotal;
    }
    
    /**
     * Calculate total with delivery charge
     * 
     * @return float
     */
    public function calculateTotal()
    {
        return $this->calculateSubtotal() + $this->deliveryCharge;
    }
    
    /**
     * Add item to order
     * 
     * @param array $item Item data
     * @return self
     */
    public function addItem(array $item)
    {
        // Check if item exists
        foreach ($this->items as &$existingItem) {
            if ($existingItem['name'] === $item['name']) {
                // Increment count/quantity
                if (isset($existingItem['count'])) {
                    $existingItem['count']++;
                } else if (isset($existingItem['quantity'])) {
                    $existingItem['quantity']++;
                } else {
                    $existingItem['quantity'] = 2; // First increment
                }
                
                return $this;
            }
        }
        
        // Standardize item format
        $standardItem = [
            'name' => $item['name'],
            'price' => (float) $item['price'],
            'quantity' => 1
        ];
        
        // Add other fields if available
        if (isset($item['id'])) $standardItem['id'] = (int) $item['id'];
        if (isset($item['sku'])) $standardItem['sku'] = $item['sku'];
        if (isset($item['category'])) $standardItem['category'] = $item['category'];
        
        // Copy count field if using that format
        if (isset($item['count'])) {
            $standardItem['count'] = (int) $item['count'];
            $standardItem['quantity'] = (int) $item['count'];
        }
        
        $this->items[] = $standardItem;
        
        // Update totals
        $this->updateTotals();
        
        return $this;
    }
    
    /**
     * Remove item from order
     * 
     * @param int $index Item index
     * @return self
     */
    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            // Decrease count if more than 1
            if (isset($this->items[$index]['count']) && $this->items[$index]['count'] > 1) {
                $this->items[$index]['count']--;
            } else if (isset($this->items[$index]['quantity']) && $this->items[$index]['quantity'] > 1) {
                $this->items[$index]['quantity']--;
            } else {
                // Remove item
                array_splice($this->items, $index, 1);
            }
            
            // Update totals
            $this->updateTotals();
        }
        
        return $this;
    }
    
    /**
     * Update order totals
     * 
     * @return self
     */
    public function updateTotals()
    {
        error_log('Order::updateTotals called');
        
        $subtotal = $this->calculateSubtotal();
        $this->totalAmount = $subtotal + (float)$this->deliveryCharge;
        
        error_log('Order::updateTotals - subtotal=' . $subtotal . ', deliveryCharge=' . $this->deliveryCharge . ', total=' . $this->totalAmount);
        
        return $this;
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
    
    public function getUserId()
    {
        return $this->userId;
    }
    
    public function setUserId($userId)
    {
        $this->userId = $userId !== null ? (int) $userId : null;
        return $this;
    }
    
    public function getOrganisation()
    {
        return $this->organisation;
    }
    
    public function setOrganisation($organisation)
    {
        $this->organisation = $organisation;
        return $this;
    }
    
    public function getDeliveryAddress()
    {
        return $this->deliveryAddress;
    }
    
    public function setDeliveryAddress($deliveryAddress)
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }
    
    public function getDeliveryCharge()
    {
        return $this->deliveryCharge;
    }
    
    public function setDeliveryCharge($deliveryCharge)
    {
        $this->deliveryCharge = (float) $deliveryCharge;
        $this->updateTotals(); // Recalculate totals when delivery charge changes
        return $this;
    }
    
    public function getOrderDateTime()
    {
        return $this->orderDateTime;
    }
    
    public function setOrderDateTime($orderDateTime)
    {
        $this->orderDateTime = $orderDateTime;
        return $this;
    }
    
    public function getOrderText()
    {
        return $this->orderText;
    }
    
    public function setOrderText($orderText)
    {
        $this->orderText = $orderText;
        return $this;
    }
    
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }
    
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = (float) $totalAmount;
        return $this;
    }
    
    public function getItems()
    {
        return $this->items;
    }
    
    public function setItems(array $items)
    {
        error_log('Order::setItems called with: ' . json_encode($items));
        $this->items = $items;
        $this->updateTotals(); // Recalculate totals when items change
        return $this;
    }
}