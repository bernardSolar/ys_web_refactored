<?php
/**
 * Order Service
 * 
 * Handles business logic for orders.
 */

namespace POS\Services;

use POS\Database\OrderRepository;
use POS\Database\ProductRepository;
use POS\Models\Order;
use POS\Models\User;

class OrderService
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    
    /**
     * @var ProductRepository
     */
    private $productRepository;
    
    /**
     * Constructor
     * 
     * @param OrderRepository|null $orderRepository Order repository
     * @param ProductRepository|null $productRepository Product repository
     */
    public function __construct(
        ?OrderRepository $orderRepository = null,
        ?ProductRepository $productRepository = null
    ) {
        $this->orderRepository = $orderRepository ?: new OrderRepository();
        $this->productRepository = $productRepository ?: new ProductRepository();
    }
    
    /**
     * Place an order
     * 
     * @param array $items Order items
     * @param float $subtotal Order subtotal
     * @param User|null $user User placing the order
     * @return array Result with order ID and status
     */
    public function placeOrder(array $items, $subtotal, $user = null)
    {
        // Debug info
        error_log('OrderService::placeOrder called with items: ' . json_encode($items));
        error_log('Subtotal: ' . $subtotal);
        error_log('User: ' . ($user ? $user->getId() : 'null'));
        
        // Create a new order
        $order = new Order();
        
        // Add items to order
        $order->setItems($items);
        
        // Set user data if available and it's a User object
        if ($user && $user instanceof \POS\Models\User) {
            error_log('Setting user data from User object');
            $order->setUserId($user->getId());
            $order->setOrganisation($user->getOrganisation());
            $order->setDeliveryAddress($user->getDeliveryAddress());
            $order->setDeliveryCharge($user->getDeliveryCharge());
        } elseif ($user && is_array($user)) {
            // Support legacy format where user is passed as an array
            error_log('Setting user data from array');
            if (isset($user['id'])) $order->setUserId($user['id']);
            if (isset($user['organisation'])) $order->setOrganisation($user['organisation']);
            if (isset($user['delivery_address'])) $order->setDeliveryAddress($user['delivery_address']);
            if (isset($user['delivery_charge'])) $order->setDeliveryCharge($user['delivery_charge']);
        } else {
            error_log('No valid user data provided');
        }
        
        // Calculate total and generate order text
        $order->updateTotals();
        $order->generateOrderText();
        
        // Begin transaction
        $this->orderRepository->beginTransaction();
        
        try {
            // Save the order
            $orderId = $this->orderRepository->save($order);
            
            if (!$orderId) {
                throw new \Exception('Failed to save order');
            }
            
            // Record product sales
            foreach ($items as $item) {
                if (!isset($item['id']) || (!isset($item['count']) && !isset($item['quantity']))) {
                    continue;
                }
                
                $quantity = isset($item['count']) ? $item['count'] : $item['quantity'];
                $this->productRepository->recordSale($item['id'], $quantity);
            }
            
            // Commit transaction
            $this->orderRepository->commit();
            
            // Return success response
            return [
                'success' => true,
                'orderId' => $orderId,
                'message' => 'Order placed successfully',
                'orderInfo' => [
                    'hasDelivery' => ($user !== null),
                    'deliveryCharge' => $user ? $user->getDeliveryCharge() : 0,
                    'totalWithDelivery' => $order->getTotalAmount()
                ]
            ];
            
        } catch (\Exception $e) {
            // Log detailed error for debugging
            error_log('Error in OrderService::placeOrder: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
            
            // Rollback transaction
            if ($this->orderRepository->inTransaction()) {
                $this->orderRepository->rollBack();
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get recent orders
     * 
     * @param int $limit Maximum number of orders to return
     * @param int|null $userId Filter by user ID (optional)
     * @return array Orders array
     */
    public function getRecentOrders($limit = 10, $userId = null)
    {
        $orders = $this->orderRepository->findRecent($limit, $userId);
        
        // Convert to array format
        $result = [];
        foreach ($orders as $order) {
            $result[] = $order->toArray();
        }
        
        return $result;
    }
    
    /**
     * Get order details
     * 
     * @param int $orderId Order ID
     * @return array|null Order details or null if not found
     */
    public function getOrderDetails($orderId)
    {
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            return null;
        }
        
        return $order->toArray();
    }
    
    /**
     * Calculate total with delivery charge
     * 
     * @param float $orderTotal Base order total
     * @param float $deliveryCharge Delivery charge
     * @return float Final total with delivery charge
     */
    public function calculateTotalWithDelivery($orderTotal, $deliveryCharge)
    {
        return $orderTotal + $deliveryCharge;
    }
}