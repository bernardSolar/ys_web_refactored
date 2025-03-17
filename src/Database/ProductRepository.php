<?php
/**
 * Product Repository
 * 
 * Handles database operations for products.
 */

namespace POS\Database;

use POS\Models\Product;
use PDO;

class ProductRepository extends Repository
{
    /**
     * Get all products
     * 
     * @return Product[]
     */
    public function findAll()
    {
        $sql = "
            SELECT p.id, p.name, p.price, p.sku, p.stock, p.category_id, c.name AS category
            FROM products p 
            JOIN categories c ON p.category_id = c.id
            ORDER BY c.name, p.name
        ";
        
        $rows = $this->fetchAll($sql);
        
        $products = [];
        foreach ($rows as $row) {
            $products[] = new Product($row);
        }
        
        return $products;
    }
    
    /**
     * Get products grouped by category
     * 
     * @return array Products grouped by category
     */
    public function findAllGroupedByCategory()
    {
        $products = [];
        
        $sql = "
            SELECT c.name AS category, p.name, p.price, p.sku, p.stock, p.id
            FROM products p 
            JOIN categories c ON p.category_id = c.id
            ORDER BY c.name, p.name
        ";
        
        $rows = $this->fetchAll($sql);
        
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
    }
    
    /**
     * Find product by ID
     * 
     * @param int $id Product ID
     * @return Product|null
     */
    public function findById($id)
    {
        $sql = "
            SELECT p.id, p.name, p.price, p.sku, p.stock, p.category_id, c.name AS category
            FROM products p 
            JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?
        ";
        
        $row = $this->fetchOne($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return new Product($row);
    }
    
    /**
     * Find product by name and category
     * 
     * @param string $name Product name
     * @param string $categoryName Category name
     * @return Product|null
     */
    public function findByNameAndCategory($name, $categoryName)
    {
        $sql = "
            SELECT p.id, p.name, p.price, p.sku, p.stock, p.category_id, c.name AS category
            FROM products p 
            JOIN categories c ON p.category_id = c.id
            WHERE p.name = ? AND c.name = ?
        ";
        
        $row = $this->fetchOne($sql, [$name, $categoryName]);
        
        if (!$row) {
            return null;
        }
        
        return new Product($row);
    }
    
    /**
     * Get the most popular products based on sales
     * 
     * @param int $days Number of days to look back
     * @param int $limit Maximum number of products to return
     * @return array Popular products in legacy format
     */
    public function findPopularProducts($days = 90, $limit = 15)
    {
        $popularProducts = [];
        
        try {
            // Check if we have any sales data
            $count = $this->fetchColumn("SELECT COUNT(*) FROM product_sales");
            
            if ($count == 0) {
                // No sales data, return some default products
                $sql = "
                    SELECT p.id, c.name as category, p.name, p.price, p.sku, p.stock
                    FROM products p
                    JOIN categories c ON p.category_id = c.id
                    ORDER BY p.name
                    LIMIT ?
                ";
                $rows = $this->fetchAll($sql, [$limit]);
            } else {
                // Get popular products based on sales
                $sql = "
                    SELECT p.id, c.name as category, p.name, p.price, p.sku, p.stock, 
                           SUM(ps.quantity) as total_sold
                    FROM products p
                    JOIN categories c ON p.category_id = c.id
                    JOIN product_sales ps ON p.id = ps.product_id
                    WHERE ps.sale_date > datetime('now', ? || ' days')
                    GROUP BY p.id
                    ORDER BY total_sold DESC
                    LIMIT ?
                ";
                $rows = $this->fetchAll($sql, ["-{$days}", $limit]);
            }
            
            // Format products for frontend (legacy format)
            foreach ($rows as $row) {
                $popularProducts[] = [
                    $row['name'],
                    (float)$row['price'],
                    $row['sku'],
                    (int)$row['stock'],
                    (int)$row['id']
                ];
            }
            
        } catch (\Exception $e) {
            error_log("Error fetching popular products: " . $e->getMessage());
            // Return empty array on error
        }
        
        return $popularProducts;
    }
    
    /**
     * Record a product sale
     * 
     * @param int $productId Product ID
     * @param int $quantity Quantity sold
     * @return bool Success status
     */
    public function recordSale($productId, $quantity)
    {
        try {
            $sql = "
                INSERT INTO product_sales (product_id, quantity)
                VALUES (?, ?)
            ";
            
            $this->execute($sql, [$productId, $quantity]);
            return true;
        } catch (\Exception $e) {
            error_log("Error recording product sale: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update product stock
     * 
     * @param int $id Product ID
     * @param int $stock New stock level
     * @return bool Success status
     */
    public function updateStock($id, $stock)
    {
        try {
            $sql = "
                UPDATE products
                SET stock = ?
                WHERE id = ?
            ";
            
            $affected = $this->execute($sql, [$stock, $id]);
            return $affected > 0;
        } catch (\Exception $e) {
            error_log("Error updating product stock: " . $e->getMessage());
            return false;
        }
    }
}