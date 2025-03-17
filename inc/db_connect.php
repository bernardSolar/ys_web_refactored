<?php
/**
 * Database Connection Class
 * Handles connections to SQLite or MySQL databases
 * Includes automatic database initialization and product import from CSV
 */
class Database {
    private static $instance = null;
    private $connection;
    private $config;
    
    /**
     * Private constructor to prevent direct object creation
     * Establishes database connection based on configuration
     */
    private function __construct() {
        // Load configuration
        $this->config = require __DIR__ . '/config.php';
        $db = $this->config['database'];
        
        try {
            // Check if using SQLite or MySQL
            if ($db['type'] === 'sqlite') {
                // Get absolute path to database file
                $dbFile = realpath(__DIR__ . '/' . $db['file']);
                if (!$dbFile && $this->config['app']['debug']) {
                    // If in debug mode and file not found, try one directory up
                    $dbFile = realpath(__DIR__ . '/../' . basename($db['file']));
                }
                
                $dbIsNew = false;
                if (!$dbFile) {
                    // Database file doesn't exist, create it
                    $dbIsNew = true;
                    $dbFile = __DIR__ . '/../' . basename($db['file']);
                    
                    // Create directory if it doesn't exist
                    $dbDir = dirname($dbFile);
                    if (!is_dir($dbDir)) {
                        mkdir($dbDir, 0755, true);
                    }
                }
                
                // Connect to SQLite database (will create it if it doesn't exist)
                $this->connection = new PDO("sqlite:" . $dbFile);
                
                // Set error mode to exceptions
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Enable foreign keys in SQLite
                $this->connection->exec('PRAGMA foreign_keys = ON;');
                
                // If it's a new database, initialize it after connection is established
                if ($dbIsNew) {
                    $this->initializeDatabase();
                    
                    // Check if we should import products
                    $csvFile = realpath(__DIR__ . '/../products.csv');
                    if (!$csvFile) {
                        $csvFile = realpath(__DIR__ . '/../../products.csv');
                    }
                    
                    if ($csvFile) {
                        $this->importProductsFromCSV($csvFile);
                    }
                }
                
                // Check if users table exists and has data
                $this->checkAndCreateUsersTable();
                
            } else {
                // Connect to MySQL
                $this->connection = new PDO(
                    "mysql:host={$db['host']};dbname={$db['name']}",
                    $db['user'],
                    $db['pass']
                );
                // Set error mode to exceptions
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            
        } catch(PDOException $e) {
            // Log error and rethrow
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . ($this->config['app']['debug'] ? $e->getMessage() : "Please check logs for details."));
        }
    }
    
    /**
     * Initialize database with required tables
     */
    private function initializeDatabase() {
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
        
        if ($this->config['app']['debug']) {
            error_log("Database tables created successfully");
        }
    }
    
    /**
     * Import products from CSV file
     * @param string $csvFile Path to the CSV file
     */
    private function importProductsFromCSV($csvFile) {
        try {
            // Check if products table is empty
            $stmt = $this->connection->query("SELECT COUNT(*) FROM products");
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                // Products already exist, no need to import
                return;
            }
            
            // Import products from CSV
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
                
                if ($this->config['app']['debug']) {
                    error_log("Products imported successfully from CSV");
                }
            }
        } catch (Exception $e) {
            error_log("Error importing products from CSV: " . $e->getMessage());
        }
    }
    
    /**
     * Check if users table exists and has users, import from CSV if needed
     */
    private function checkAndCreateUsersTable() {
        try {
            // Check if users table exists
            $tableExists = false;
            try {
                $this->connection->query("SELECT 1 FROM users LIMIT 1");
                $tableExists = true;
            } catch (PDOException $e) {
                // Table doesn't exist, we'll create it
                $this->connection->exec("
                    CREATE TABLE IF NOT EXISTS users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        organisation TEXT NOT NULL,
                        delivery_address TEXT NOT NULL,
                        delivery_charge REAL NOT NULL,
                        rep_name TEXT NOT NULL,
                        email TEXT NOT NULL,
                        username TEXT NOT NULL UNIQUE,
                        password_hash TEXT NOT NULL,
                        role TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                // Alter order_history table if it exists but doesn't have user_id column
                try {
                    $this->connection->query("SELECT user_id FROM order_history LIMIT 1");
                } catch (PDOException $e) {
                    // User_id column doesn't exist, need to alter table
                    $this->connection->exec("
                        CREATE TABLE IF NOT EXISTS order_history_new (
                            order_id INTEGER PRIMARY KEY AUTOINCREMENT,
                            user_id INTEGER,
                            organisation TEXT,
                            delivery_address TEXT,
                            delivery_charge REAL DEFAULT 0,
                            order_datetime TEXT NOT NULL,
                            order_text TEXT NOT NULL,
                            total_amount REAL NOT NULL,
                            FOREIGN KEY (user_id) REFERENCES users (id)
                        );
                        
                        INSERT INTO order_history_new (order_id, order_datetime, order_text, total_amount)
                        SELECT order_id, order_datetime, order_text, total_amount FROM order_history;
                        
                        DROP TABLE order_history;
                        
                        ALTER TABLE order_history_new RENAME TO order_history;
                    ");
                }
            }
            
            // If table exists, check if users exist
            if ($tableExists) {
                $stmt = $this->connection->query("SELECT COUNT(*) FROM users");
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    // Users already exist, no need to import
                    return;
                }
            }
            
            // Import users from CSV
            $csvFile = realpath(__DIR__ . '/../users.csv');
            if (!$csvFile) {
                // Try one directory up
                $csvFile = realpath(__DIR__ . '/../../users.csv');
            }
            
            if (!$csvFile) {
                // No users.csv file found
                if ($this->config['app']['debug']) {
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
                
                if ($this->config['app']['debug']) {
                    error_log("Users imported successfully from CSV");
                }
            }
            
        } catch (Exception $e) {
            error_log("Error setting up users table: " . $e->getMessage());
        }
    }
    
    /**
     * Get singleton instance
     * @return Database The Database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     * @return PDO The PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Get configuration
     * @return array The configuration array
     */
    public function getConfig() {
        return $this->config;
    }
}