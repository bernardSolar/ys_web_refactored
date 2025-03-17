<?php
/**
 * Product Model
 * 
 * Represents a product in the system.
 */

namespace POS\Models;

class Product
{
    /**
     * @var int
     */
    private $id;
    
    /**
     * @var string
     */
    private $name;
    
    /**
     * @var float
     */
    private $price;
    
    /**
     * @var string|null
     */
    private $sku;
    
    /**
     * @var int
     */
    private $stock;
    
    /**
     * @var int
     */
    private $categoryId;
    
    /**
     * @var string
     */
    private $categoryName;
    
    /**
     * Constructor
     * 
     * @param array $data Product data
     */
    public function __construct(array $data = [])
    {
        // Initialize from data array if provided
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
    /**
     * Hydrate the object from an array of data
     * 
     * @param array $data
     * @return self
     */
    public function hydrate(array $data)
    {
        if (isset($data['id'])) {
            $this->id = (int) $data['id'];
        }
        
        if (isset($data['name'])) {
            $this->name = (string) $data['name'];
        }
        
        if (isset($data['price'])) {
            $this->price = (float) $data['price'];
        }
        
        if (isset($data['sku'])) {
            $this->sku = $data['sku'] !== null ? (string) $data['sku'] : null;
        }
        
        if (isset($data['stock'])) {
            $this->stock = (int) $data['stock'];
        }
        
        if (isset($data['category_id'])) {
            $this->categoryId = (int) $data['category_id'];
        }
        
        if (isset($data['category'])) {
            $this->categoryName = (string) $data['category'];
        }
        
        return $this;
    }
    
    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'sku' => $this->sku,
            'stock' => $this->stock,
            'category_id' => $this->categoryId,
            'category' => $this->categoryName
        ];
    }
    
    /**
     * Convert to legacy array format for backward compatibility
     * The original system used indexed arrays [name, price, sku, stock, id]
     * 
     * @return array
     */
    public function toLegacyArray()
    {
        return [
            $this->name,
            $this->price,
            $this->sku,
            $this->stock,
            $this->id
        ];
    }
    
    // Getters and setters
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }
    
    public function getPrice()
    {
        return $this->price;
    }
    
    public function setPrice($price)
    {
        $this->price = (float) $price;
        return $this;
    }
    
    public function getSku()
    {
        return $this->sku;
    }
    
    public function setSku($sku)
    {
        $this->sku = $sku !== null ? (string) $sku : null;
        return $this;
    }
    
    public function getStock()
    {
        return $this->stock;
    }
    
    public function setStock($stock)
    {
        $this->stock = (int) $stock;
        return $this;
    }
    
    public function getCategoryId()
    {
        return $this->categoryId;
    }
    
    public function setCategoryId($categoryId)
    {
        $this->categoryId = (int) $categoryId;
        return $this;
    }
    
    public function getCategoryName()
    {
        return $this->categoryName;
    }
    
    public function setCategoryName($categoryName)
    {
        $this->categoryName = (string) $categoryName;
        return $this;
    }
}