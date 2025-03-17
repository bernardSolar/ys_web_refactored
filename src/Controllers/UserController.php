<?php
/**
 * User Controller
 * 
 * Handles user profile requests.
 */

namespace POS\Controllers;

use POS\Services\AuthService;
use POS\Database\UserRepository;
use POS\Database\OrderRepository;

class UserController extends BaseController
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    
    /**
     * Constructor
     * 
     * @param AuthService|null $authService Authentication service
     */
    public function __construct(?AuthService $authService = null)
    {
        parent::__construct($authService);
        $this->userRepository = new UserRepository();
        $this->orderRepository = new OrderRepository();
    }
    
    /**
     * Handle request
     */
    public function handleRequest()
    {
        // All user endpoints require authentication
        if (!$this->requireAuth()) {
            return;
        }
        
        if ($this->isGet()) {
            // Get action from query parameters or default to profile
            $action = isset($_GET['action']) ? $_GET['action'] : 'profile';
            
            switch ($action) {
                case 'profile':
                    // Get user profile
                    $this->getUserProfile();
                    break;
                case 'orders':
                    // Get user orders
                    $this->getUserOrders();
                    break;
                case 'order_details':
                    // Get specific order details
                    $this->getOrderDetails();
                    break;
                default:
                    $this->errorResponse('Invalid action', 400);
            }
        } else if ($this->isPost() || $this->isPut()) {
            // Update user profile
            $this->updateUserProfile();
        } else {
            $this->errorResponse('Method not allowed', 405);
        }
    }
    
    /**
     * Get user profile
     */
    private function getUserProfile()
    {
        // Get current user ID
        $userId = isset($_GET['id']) ? (int)$_GET['id'] : $this->authService->getCurrentUser()->getId();
        
        // Admin can view any user profile, regular users can only view their own
        if ($userId !== $this->authService->getCurrentUser()->getId() && !$this->authService->isAdmin()) {
            $this->errorResponse('Forbidden - You can only view your own profile', 403);
            return;
        }
        
        // Get user data
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            $this->errorResponse('User not found', 404);
            return;
        }
        
        $this->jsonResponse([
            'success' => true,
            'user' => $user->toArray()
        ]);
    }
    
    /**
     * Update user profile
     */
    private function updateUserProfile()
    {
        // Get current user ID
        $userId = $this->authService->getCurrentUser()->getId();
        
        // Get request data
        $data = $this->getJsonInput();
        
        if (!$data) {
            $this->errorResponse('Invalid JSON data');
            return;
        }
        
        // Only update allowed fields
        $allowedFields = ['email', 'organisation', 'delivery_address', 
                          'delivery_charge', 'rep_name', 'password'];
        
        // If admin, also allow role changes
        if ($this->authService->isAdmin()) {
            $allowedFields[] = 'role';
        }
        
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        // Update profile
        $success = $this->userRepository->updateProfile($userId, $updateData);
        
        if (!$success) {
            $this->errorResponse('Failed to update profile', 500);
            return;
        }
        
        // Get updated user data
        $user = $this->userRepository->findById($userId);
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user->toArray()
        ]);
    }
    
    /**
     * Get user orders
     */
    private function getUserOrders()
    {
        $currentUser = $this->authService->getCurrentUser();
        
        // Check if we are filtering orders for a specific user or getting all (admin only)
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $currentUser->getId();
        
        // Only admins can view other users' orders
        if ($userId !== $currentUser->getId() && !$this->authService->isAdmin()) {
            $this->errorResponse('Forbidden - You can only view your own orders', 403);
            return;
        }
        
        // Get orders
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100; // Reasonable default
        
        // If admin and no user_id filter, get all recent orders
        if ($this->authService->isAdmin() && !isset($_GET['user_id'])) {
            $orders = $this->orderRepository->findRecent($limit);
        } else {
            // Get specific user's orders
            $orders = $this->orderRepository->findByUserId($userId);
        }
        
        // Convert to array format
        $ordersArray = [];
        foreach ($orders as $order) {
            $ordersArray[] = $order->toArray();
        }
        
        // Ensure we always return an array, even if empty
        error_log("UserController::getUserOrders - returning " . count($ordersArray) . " orders");
        
        $this->jsonResponse([
            'success' => true,
            'orders' => $ordersArray ?: [] // Make sure it's never null
        ]);
    }
    
    /**
     * Get specific order details
     */
    private function getOrderDetails()
    {
        if (!isset($_GET['order_id'])) {
            $this->errorResponse('Order ID is required', 400);
            return;
        }
        
        $orderId = (int)$_GET['order_id'];
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            $this->errorResponse('Order not found', 404);
            return;
        }
        
        // Check if user has access to this order
        $currentUser = $this->authService->getCurrentUser();
        if (!$this->authService->isAdmin() && $order->getUserId() !== $currentUser->getId()) {
            $this->errorResponse('Access denied', 403);
            return;
        }
        
        $orderData = $order->toArray();
        error_log("UserController::getOrderDetails - returning order: " . json_encode($orderData));
        
        $this->jsonResponse([
            'success' => true,
            'order' => $orderData
        ]);
    }
}