<?php
/**
 * Authentication Service
 * 
 * Handles user authentication and session management.
 */

namespace POS\Services;

use POS\Database\UserRepository;
use POS\Models\User;

class AuthService
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    
    /**
     * Constructor
     * 
     * @param UserRepository|null $userRepository User repository
     */
    public function __construct(?UserRepository $userRepository = null)
    {
        $this->userRepository = $userRepository ?: new UserRepository();
    }
    
    /**
     * Attempt to log in a user
     * 
     * @param string $username Username
     * @param string $password Password
     * @return array Login result and user data if successful
     */
    public function login($username, $password)
    {
        $user = $this->userRepository->findByUsername($username);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
        if (!$user->verifyPassword($password)) {
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['organisation'] = $user->getOrganisation();
        $_SESSION['role'] = $user->getRole();
        
        return [
            'success' => true,
            'user' => $user->toArray()
        ];
    }
    
    /**
     * Log out the current user
     * 
     * @return bool Success status
     */
    public function logout()
    {
        // Unset all session variables
        $_SESSION = [];
        
        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        return true;
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user
     * 
     * @return User|null
     */
    public function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->userRepository->findById($_SESSION['user_id']);
    }
    
    /**
     * Get current user data for API responses
     * 
     * @return array|null
     */
    public function getCurrentUserData()
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return null;
        }
        
        return $user->toArray();
    }
    
    /**
     * Check if current user is admin
     * 
     * @return bool
     */
    public function isAdmin()
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return strtolower($_SESSION['role']) === 'admin';
    }
    
    /**
     * Require authentication
     * 
     * Redirects to login page if not authenticated
     * 
     * @param string $loginUrl URL to redirect to if not authenticated
     * @return bool True if authenticated, redirects otherwise
     */
    public function requireAuth($loginUrl = 'login.php')
    {
        if (!$this->isLoggedIn()) {
            header("Location: $loginUrl");
            exit;
        }
        
        return true;
    }
}