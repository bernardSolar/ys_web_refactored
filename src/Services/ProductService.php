<?php
/**
 * Product Service
 * 
 * Handles business logic for products.
 */

namespace POS\Services;

use POS\Database\ProductRepository;
use POS\Models\Product;

class ProductService
{
    /**
     * @var ProductRepository
     */
    private $productRepository;
    
    /**
     * Constructor
     * 
     * @param ProductRepository|null $productRepository Product repository
     */
    public function __construct(?ProductRepository $productRepository = null)
    {
        $this->productRepository = $productRepository ?: new ProductRepository();
    }
    
    /**
     * Get all products grouped by category
     * 
     * @return array
     */
    public function getAllProductsByCategory()
    {
        return $this->productRepository->findAllGroupedByCategory();
    }
    
    /**
     * Get popular products in legacy array format
     * 
     * @param int $days Number of days to look back
     * @param int $limit Maximum number of products to return
     * @return array
     */
    public function getPopularProducts($days = 90, $limit = 15)
    {
        return $this->productRepository->findPopularProducts($days, $limit);
    }
    
    /**
     * Format popular products data for frontend
     * 
     * @param array $popularProducts Popular products array
     * @return array Formatted data for the frontend
     */
    public function formatPopularProductsForFrontend(array $popularProducts)
    {
        // The frontend expects popular products under a 'Home' category
        return ['Home' => $popularProducts];
    }
    
    /**
     * Record sales for products
     * 
     * @param array $items Order items
     * @return bool Success status
     */
    public function recordSales(array $items)
    {
        $success = true;
        
        foreach ($items as $item) {
            if (!isset($item['id']) || !isset($item['count']) && !isset($item['quantity'])) {
                continue;
            }
            
            $quantity = isset($item['count']) ? $item['count'] : $item['quantity'];
            $result = $this->productRepository->recordSale($item['id'], $quantity);
            
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get product by ID
     * 
     * @param int $id Product ID
     * @return Product|null
     */
    public function getProductById($id)
    {
        return $this->productRepository->findById($id);
    }
    
    /**
     * Find product by name and category
     * 
     * @param string $name Product name
     * @param string $category Category name
     * @return Product|null
     */
    public function findProductByNameAndCategory($name, $category)
    {
        return $this->productRepository->findByNameAndCategory($name, $category);
    }
}