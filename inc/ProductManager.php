<?php
/**
 * Product Manager Class
 * Handles all product-related database operations
 */
class ProductManager {
    private $db;
    
    /**
     * Constructor
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all products organized by category
     * @return array Array of products grouped by category
     */
    public function getAllProducts() {
        $products = [];
        
        try {
            // Get all products with their categories
            $query = $this->db->prepare("
                SELECT c.name AS category, p.name, p.price, p.sku, p.stock, p.id
                FROM products p 
                JOIN categories c ON p.category_id = c.id
                ORDER BY c.name, p.name
            ");
            $query->execute();
            
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            
            // Organize products by category
            foreach ($rows as $row) {
                $category = $row['category'];
                $product = [
                    $row['name'],
                    (float)$row['price'],
                    $row['sku'],
                    (int)$row['stock'],
                    (int)$row['id']
                ];
                
                // Add to specific category
                if (!isset($products[$category])) {
                    $products[$category] = [];
                }
                $products[$category][] = $product;
            }
            
            return $products;
            
        } catch (PDOException $e) {
            error_log("Error fetching products: " . $e->getMessage());
            return $products; // Return empty product array on error
        }
    }
    
    /**
     * Get the most popular products based on sales
     * @param int $days Number of days to look back
     * @param int $limit Maximum number of products to return
     * @return array Array of popular products
     */
    public function getPopularProducts($days = 90, $limit = 15) {
        $popularProducts = [];
        
        try {
            // Check if we have any sales data
            $countQuery = $this->db->query("SELECT COUNT(*) FROM product_sales");
            $count = $countQuery->fetchColumn();
            
            if ($count == 0) {
                // No sales data, return some default products
                $query = $this->db->prepare("
                    SELECT p.id, c.name as category, p.name, p.price, p.sku, p.stock
                    FROM products p
                    JOIN categories c ON p.category_id = c.id
                    ORDER BY p.name
                    LIMIT ?
                ");
                $query->execute([$limit]);
            } else {
                // Get popular products based on sales
                $query = $this->db->prepare("
                    SELECT p.id, c.name as category, p.name, p.price, p.sku, p.stock, 
                           SUM(ps.quantity) as total_sold
                    FROM products p
                    JOIN categories c ON p.category_id = c.id
                    JOIN product_sales ps ON p.id = ps.product_id
                    WHERE ps.sale_date > datetime('now', ? || ' days')
                    GROUP BY p.id
                    ORDER BY total_sold DESC
                    LIMIT ?
                ");
                $query->execute(["-{$days}", $limit]);
            }
            
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            
            // Format products for frontend
            foreach ($rows as $row) {
                $popularProducts[] = [
                    $row['name'],
                    (float)$row['price'],
                    $row['sku'],
                    (int)$row['stock'],
                    (int)$row['id']
                ];
            }
            
            return $popularProducts;
            
        } catch (PDOException $e) {
            error_log("Error fetching popular products: " . $e->getMessage());
            return $popularProducts; // Return empty array on error
        }
    }
    
    /**
     * Record a product sale
     * @param int $productId Product ID
     * @param int $quantity Quantity sold
     * @return bool Success status
     */
    public function recordProductSale($productId, $quantity) {
        try {
            $query = $this->db->prepare("
                INSERT INTO product_sales (product_id, quantity)
                VALUES (?, ?)
            ");
            $result = $query->execute([$productId, $quantity]);
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error recording product sale: " . $e->getMessage());
            return false;
        }
    }
}