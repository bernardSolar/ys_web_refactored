<?php
/**
 * Database Connection Class
 * 
 * Handles connections to SQLite or MySQL databases.
 * Uses singleton pattern for efficient connection management.
 */

namespace POS\Database;

use PDO;
use PDOException;
use Exception;
use POS\Config\Config;

class Database
{
    /**
     * Database instance (singleton)
     * @var Database
     */
    private static $instance = null;
    
    /**
     * PDO connection
     * @var PDO
     */
    private $connection;
    
    /**
     * Config data
     * @var array
     */
    private $config;
    
    /**
     * Private constructor to prevent direct object creation
     * Establishes database connection based on configuration
     */
    private function __construct()
    {
        // Load configuration
        $this->config = Config::get('database');
        
        try {
            // Check if using SQLite or MySQL
            if ($this->config['type'] === 'sqlite') {
                $this->connectSQLite();
            } else {
                $this->connectMySQL();
            }
            
        } catch(PDOException $e) {
            // Log error and rethrow
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . 
                (Config::get('app.debug') ? $e->getMessage() : "Please check logs for details."));
        }
    }
    
    /**
     * Connect to SQLite database
     */
    private function connectSQLite()
    {
        // Get absolute path to database file
        $dbFile = $this->resolveDatabasePath();
        
        if (!$dbFile) {
            // Database file doesn't exist, create it
            // Using the filename from config
            $filename = basename($this->config['file']);
            $dbFile = ROOT_PATH . '/' . $filename;
            
            // Create directory if it doesn't exist
            $dbDir = dirname($dbFile);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            $dbIsNew = true;
        } else {
            $dbIsNew = false;
        }
        
        // Connect to SQLite database
        $this->connection = new PDO("sqlite:" . $dbFile);
        
        // Set error mode to exceptions
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Enable foreign keys in SQLite
        $this->connection->exec('PRAGMA foreign_keys = ON;');
        
        // Initialize database if it's new
        if ($dbIsNew) {
            $this->initializeDatabase();
            $this->importInitialData();
        }
    }
    
    /**
     * Connect to MySQL database
     */
    private function connectMySQL()
    {
        // Connect to MySQL
        $this->connection = new PDO(
            "mysql:host={$this->config['host']};dbname={$this->config['name']}",
            $this->config['user'],
            $this->config['pass']
        );
        
        // Set error mode to exceptions
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * Resolve database file path
     * 
     * @return string|false The resolved path or false if not found
     */
    private function resolveDatabasePath()
    {
        // Possible database file locations
        $possiblePaths = [
            ROOT_PATH . '/' . basename($this->config['file']),
            ROOT_PATH . '/' . $this->config['file'],
            dirname(ROOT_PATH) . '/' . basename($this->config['file'])
        ];
        
        // Check each possible path
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Try to strip "../" from the path
        $cleanPath = ltrim($this->config['file'], './');
        if (file_exists(ROOT_PATH . '/' . $cleanPath)) {
            return ROOT_PATH . '/' . $cleanPath;
        }
        
        return false;
    }
    
    /**
     * Initialize database schema
     */
    private function initializeDatabase()
    {
        // Import the schema from the original Database class
        // This is directly from the existing implementation
        $this->connection->exec("" .
            "CREATE TABLE IF NOT EXISTS categories (" .
            "    id INTEGER PRIMARY KEY AUTOINCREMENT," .
            "    name TEXT NOT NULL UNIQUE," .
            "    is_custom BOOLEAN DEFAULT FALSE," .
            "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" .
            ");" .
            
            "CREATE TABLE IF NOT EXISTS products (" .
            "    id INTEGER PRIMARY KEY AUTOINCREMENT," .
            "    category_id INTEGER NOT NULL," .
            "    name TEXT NOT NULL," .
            "    price REAL NOT NULL," .
            "    sku TEXT," .
            "    stock INTEGER DEFAULT 0," .
            "    UNIQUE(sku)," .
            "    FOREIGN KEY (category_id) REFERENCES categories (id)," .
            "    UNIQUE(category_id, name)" .
            ");" .
            
            "CREATE TABLE IF NOT EXISTS product_sales (" .
            "    id INTEGER PRIMARY KEY AUTOINCREMENT," .
            "    product_id INTEGER NOT NULL," .
            "    quantity INTEGER NOT NULL," .
            "    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP," .
            "    FOREIGN KEY (product_id) REFERENCES products (id)" .
            ");" .
            
            "CREATE TABLE IF NOT EXISTS users (" .
            "    id INTEGER PRIMARY KEY AUTOINCREMENT," .
            "    organisation TEXT NOT NULL," .
            "    delivery_address TEXT NOT NULL," .
            "    delivery_charge REAL NOT NULL," .
            "    rep_name TEXT NOT NULL," .
            "    email TEXT NOT NULL," .
            "    username TEXT NOT NULL UNIQUE," .
            "    password_hash TEXT NOT NULL," .
            "    role TEXT NOT NULL," .
            "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" .
            ");" .
            
            "CREATE TABLE IF NOT EXISTS order_history (" .
            "    order_id INTEGER PRIMARY KEY AUTOINCREMENT," .
            "    user_id INTEGER," .
            "    organisation TEXT," .
            "    delivery_address TEXT," .
            "    delivery_charge REAL DEFAULT 0," .
            "    order_datetime TEXT NOT NULL," .
            "    order_text TEXT NOT NULL," .
            "    total_amount REAL NOT NULL," .
            "    FOREIGN KEY (user_id) REFERENCES users (id)" .
            ");"   
        );
        
        if (Config::get('app.debug')) {
            error_log("Database tables created successfully");
        }
    }
    
    /**
     * Import initial data from CSV files
     */
    private function importInitialData()
    {
        $this->importProductsFromCSV();
        $this->importUsersFromCSV();
    }
    
    /**
     * Import products from CSV file
     */
    private function importProductsFromCSV()
    {
        try {
            // Check if products table is empty
            $stmt = $this->connection->query("SELECT COUNT(*) FROM products");
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                // Products already exist, no need to import
                return;
            }
            
            // Find the CSV file
            $csvFile = $this->findFile('products.csv');
            
            if (!$csvFile) {
                if (Config::get('app.debug')) {
                    error_log("No products.csv file found for import");
                }
                return;
            }
            
            if (($handle = fopen($csvFile, "r")) !== FALSE) {
                // Skip header line if it exists
                $firstLine = fgetcsv($handle, 0, ",", "\"", "\\");
                // Check if first line is a header by looking for 'category' in it
                if (stripos($firstLine[0], 'category') !== false) {
                    // It's a header, skip to next line
                } else {
                    // Not a header, rewind and use it as data
                    rewind($handle);
                }
                
                while (($data = fgetcsv($handle, 0, ",", "\"", "\\")) !== FALSE) {
                    if (count($data) < 2) continue; // Skip incomplete lines
                    
                    // Assuming CSV format: category,name,price,sku,stock
                    $category = $data[0];
                    $name = $data[1];
                    $price = isset($data[2]) ? floatval($data[2]) : 0;
                    $sku = isset($data[3]) ? $data[3] : null;
                    $stock = isset($data[4]) ? intval($data[4]) : 0;
                    
                    // Insert category if it doesn't exist
                    $stmt = $this->connection->prepare(
                        "INSERT OR IGNORE INTO categories (name) VALUES (?)"  
                    );
                    $stmt->execute([$category]);
                    
                    // Get category ID
                    $stmt = $this->connection->prepare(
                        "SELECT id FROM categories WHERE name = ?"
                    );
                    $stmt->execute([$category]);
                    $categoryId = $stmt->fetchColumn();
                    
                    // Insert product
                    $stmt = $this->connection->prepare(
                        "INSERT OR IGNORE INTO products (category_id, name, price, sku, stock) "
                        . "VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([$categoryId, $name, $price, $sku, $stock]);
                }
                fclose($handle);
                
                if (Config::get('app.debug')) {
                    error_log("Products imported successfully from CSV");
                }
            }
        } catch (Exception $e) {
            error_log("Error importing products from CSV: " . $e->getMessage());
        }
    }
    
    /**
     * Import users from CSV file
     */
    private function importUsersFromCSV()
    {
        try {
            // Check if users table has users
            $stmt = $this->connection->query("SELECT COUNT(*) FROM users");
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                // Users already exist, no need to import
                return;
            }
            
            // Find the CSV file
            $csvFile = $this->findFile('users.csv');
            
            if (!$csvFile) {
                if (Config::get('app.debug')) {
                    error_log("No users.csv file found for import");
                }
                return;
            }
            
            if (($handle = fopen($csvFile, "r")) !== FALSE) {
                // Skip header line
                $firstLine = fgetcsv($handle, 0, ",", "\"", "\\");
                
                while (($data = fgetcsv($handle, 0, ",", "\"", "\\")) !== FALSE) {
                    if (count($data) < 8) continue; // Skip incomplete lines
                    
                    // Clean up the data (trim spaces)
                    $data = array_map('trim', $data);
                    
                    // Parse CSV data: Organisation, Delivery Address, Delivery Charge, Rep Name, Email, Username, Password, Role
                    $organisation = $data[0];
                    $delivery_address = $data[1];
                    $delivery_charge = (float) str_replace(['£', ','], '', $data[2]); // Remove currency symbol and commas
                    $rep_name = $data[3];
                    $email = $data[4];
                    $username = $data[5];
                    $password = $data[6];
                    $role = strtolower($data[7]);
                    
                    // Hash the password for security
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user
                    $stmt = $this->connection->prepare("
                        INSERT INTO users (
                            organisation, delivery_address, delivery_charge, rep_name, 
                            email, username, password_hash, role
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $organisation, $delivery_address, $delivery_charge, $rep_name, 
                        $email, $username, $password_hash, $role
                    ]);
                }
                
                fclose($handle);
                
                if (Config::get('app.debug')) {
                    error_log("Users imported successfully from CSV");
                }
            }
        } catch (Exception $e) {
            error_log("Error importing users from CSV: " . $e->getMessage());
        }
    }
    
    /**
     * Find a file in the project directory
     * 
     * @param string $filename Filename to search for
     * @return string|false Full path to file or false if not found
     */
    private function findFile($filename)
    {
        // Possible file locations
        $possiblePaths = [
            ROOT_PATH . '/' . $filename,
            dirname(ROOT_PATH) . '/' . $filename
        ];
        
        // Check each possible path
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return false;
    }
    
    /**
     * Get singleton instance
     * 
     * @return Database The Database instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     * 
     * @return PDO The PDO connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}