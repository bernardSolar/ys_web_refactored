<?php
/**
 * Authentication Controller
 * 
 * Handles authentication-related requests.
 */

namespace POS\Controllers;

use POS\Services\AuthService;

class AuthController extends BaseController
{
    /**
     * Handle request
     */
    public function handleRequest()
    {
        if ($this->isGet()) {
            // Get current user info
            $this->getCurrentUser();
        } else if ($this->isPost()) {
            // Login
            $this->login();
        } else if ($this->isDelete()) {
            // Logout
            $this->logout();
        } else {
            $this->errorResponse('Method not allowed', 405);
        }
    }
    
    /**
     * Login
     */
    private function login()
    {
        $data = $this->getJsonInput();
        
        if (!$data || !isset($data['username']) || !isset($data['password'])) {
            $this->errorResponse('Missing username or password');
            return;
        }
        
        $result = $this->authService->login($data['username'], $data['password']);
        
        if (!$result['success']) {
            $this->errorResponse($result['message'], 401);
            return;
        }
        
        $this->jsonResponse($result);
    }
    
    /**
     * Logout
     */
    private function logout()
    {
        $this->authService->logout();
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
    
    /**
     * Get current user
     */
    private function getCurrentUser()
    {
        if (!$this->authService->isLoggedIn()) {
            $this->errorResponse('Not logged in', 401);
            return;
        }
        
        $userData = $this->authService->getCurrentUserData();
        
        $this->jsonResponse([
            'success' => true,
            'user' => $userData
        ]);
    }
}