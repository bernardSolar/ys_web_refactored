<?php
/**
 * Base Controller
 * 
 * Provides common functionality for all controllers.
 */

namespace POS\Controllers;

use POS\Services\AuthService;

abstract class BaseController
{
    /**
     * @var AuthService
     */
    protected $authService;
    
    /**
     * Constructor
     * 
     * @param AuthService|null $authService Authentication service
     */
    public function __construct(?AuthService $authService = null)
    {
        $this->authService = $authService ?: new AuthService();
    }
    
    /**
     * Render JSON response
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Render error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     */
    protected function errorResponse($message, $statusCode = 400)
    {
        $this->jsonResponse([
            'error' => true,
            'message' => $message
        ], $statusCode);
    }
    
    /**
     * Get JSON request data
     * 
     * @return array|null Parsed JSON data or null on error
     */
    protected function getJsonInput()
    {
        $json = file_get_contents('php://input');
        if (!$json) {
            return null;
        }
        
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Check if request is GET
     * 
     * @return bool
     */
    protected function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    /**
     * Check if request is POST
     * 
     * @return bool
     */
    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Check if request is PUT
     * 
     * @return bool
     */
    protected function isPut()
    {
        return $_SERVER['REQUEST_METHOD'] === 'PUT';
    }
    
    /**
     * Check if request is DELETE
     * 
     * @return bool
     */
    protected function isDelete()
    {
        return $_SERVER['REQUEST_METHOD'] === 'DELETE';
    }
    
    /**
     * Require authentication
     * 
     * @return bool True if authenticated
     */
    protected function requireAuth()
    {
        if (!$this->authService->isLoggedIn()) {
            $this->errorResponse('Unauthorized', 401);
            return false;
        }
        
        return true;
    }
    
    /**
     * Require admin role
     * 
     * @return bool True if user is admin
     */
    protected function requireAdmin()
    {
        if (!$this->authService->isLoggedIn()) {
            $this->errorResponse('Unauthorized', 401);
            return false;
        }
        
        if (!$this->authService->isAdmin()) {
            $this->errorResponse('Forbidden - Admin access required', 403);
            return false;
        }
        
        return true;
    }
}