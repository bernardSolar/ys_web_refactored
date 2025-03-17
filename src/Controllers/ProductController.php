<?php
/**
 * Product Controller
 * 
 * Handles product-related requests.
 */

namespace POS\Controllers;

use POS\Services\ProductService;
use POS\Services\AuthService;

class ProductController extends BaseController
{
    /**
     * @var ProductService
     */
    private $productService;
    
    /**
     * Constructor
     * 
     * @param AuthService|null $authService Authentication service
     * @param ProductService|null $productService Product service
     */
    public function __construct(
        ?AuthService $authService = null,
        ?ProductService $productService = null
    ) {
        parent::__construct($authService);
        $this->productService = $productService ?: new ProductService();
    }
    
    /**
     * Handle request
     */
    public function handleRequest()
    {
        // All product endpoints require authentication
        if (!$this->requireAuth()) {
            return;
        }
        
        if ($this->isGet()) {
            // Get all products
            $this->getAllProducts();
        } else {
            $this->errorResponse('Method not allowed', 405);
        }
    }
    
    /**
     * Get all products
     */
    public function getAllProducts()
    {
        $products = $this->productService->getAllProductsByCategory();
        $this->jsonResponse($products);
    }
    
    /**
     * Get popular products
     */
    public function getPopularProducts()
    {
        // Check auth
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get request parameters
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 90;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
        
        // Get popular products
        $popularProducts = $this->productService->getPopularProducts($days, $limit);
        
        // Format for frontend
        $response = $this->productService->formatPopularProductsForFrontend($popularProducts);
        
        $this->jsonResponse($response);
    }
}