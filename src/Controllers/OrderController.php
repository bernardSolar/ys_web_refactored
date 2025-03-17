<?php
/**
 * Order Controller
 * 
 * Handles order-related requests.
 */

namespace POS\Controllers;

use POS\Services\OrderService;
use POS\Services\AuthService;

class OrderController extends BaseController
{
    /**
     * @var OrderService
     */
    private $orderService;
    
    /**
     * Constructor
     * 
     * @param AuthService|null $authService Authentication service
     * @param OrderService|null $orderService Order service
     */
    public function __construct(
        ?AuthService $authService = null,
        ?OrderService $orderService = null
    ) {
        parent::__construct($authService);
        $this->orderService = $orderService ?: new OrderService();
    }
    
    /**
     * Handle request
     */
    public function handleRequest()
    {
        // All order endpoints require authentication
        if (!$this->requireAuth()) {
            return;
        }
        
        if ($this->isPost()) {
            // Place new order
            $this->placeOrder();
        } else if ($this->isGet()) {
            // Get orders
            $this->getOrders();
        } else {
            $this->errorResponse('Method not allowed', 405);
        }
    }
    
    /**
     * Place a new order
     */
    private function placeOrder()
    {
        // Log the raw input
        error_log('OrderController::placeOrder raw input: ' . file_get_contents('php://input'));
        
        $data = $this->getJsonInput();
        error_log('OrderController::placeOrder parsed data: ' . json_encode($data));
        
        if (!$data || !isset($data['items']) || !isset($data['total'])) {
            error_log('OrderController::placeOrder - Missing required fields');
            $this->errorResponse('Missing required fields');
            return;
        }
        
        // Get current user
        $user = $this->authService->getCurrentUser();
        error_log('OrderController::placeOrder user: ' . ($user ? $user->getId() : 'null'));
        
        // Place order
        $result = $this->orderService->placeOrder($data['items'], $data['total'], $user);
        error_log('OrderController::placeOrder result: ' . json_encode($result));
        
        if (!$result['success']) {
            error_log('OrderController::placeOrder error: ' . $result['message']);
            $this->errorResponse($result['message'], 500);
            return;
        }
        
        $this->jsonResponse($result);
    }
    
    /**
     * Get orders
     */
    private function getOrders()
    {
        // Check if specific order requested
        if (isset($_GET['id'])) {
            $orderId = (int)$_GET['id'];
            $order = $this->orderService->getOrderDetails($orderId);
            
            if (!$order) {
                $this->errorResponse('Order not found', 404);
                return;
            }
            
            $this->jsonResponse($order);
            return;
        }
        
        // Get all recent orders
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        // Only show user's own orders unless admin
        $userId = null;
        if (!$this->authService->isAdmin()) {
            $userId = $this->authService->getCurrentUser()->getId();
        }
        
        $orders = $this->orderService->getRecentOrders($limit, $userId);
        
        $this->jsonResponse([
            'success' => true,
            'orders' => $orders
        ]);
    }
}