<?php
/**
 * Base Repository Class
 * 
 * Provides base functionality for all repositories.
 */

namespace POS\Database;

use PDO;
use PDOException;
use Exception;

abstract class Repository
{
    /**
     * @var PDO
     */
    protected $db;
    
    /**
     * Constructor
     * 
     * @param PDO|null $db Database connection (optional)
     */
    public function __construct(?PDO $db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            // Get database connection from singleton
            $this->db = Database::getInstance()->getConnection();
        }
    }
    
    /**
     * Begin a database transaction
     * 
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit a database transaction
     * 
     * @return bool
     */
    public function commit()
    {
        return $this->db->commit();
    }
    
    /**
     * Roll back a database transaction
     * 
     * @return bool
     */
    public function rollBack()
    {
        return $this->db->rollBack();
    }
    
    /**
     * Check if a transaction is active
     * 
     * @return bool
     */
    public function inTransaction()
    {
        return $this->db->inTransaction();
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string
     */
    protected function lastInsertId()
    {
        return $this->db->lastInsertId();
    }
    
    /**
     * Execute a query and return the statement
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return \PDOStatement
     * @throws Exception On query error
     */
    protected function executeQuery($sql, array $params = [])
    {
        try {
            // Log the query for debugging
            error_log("Repository::executeQuery SQL: " . $sql);
            error_log("Repository::executeQuery Params: " . json_encode($params));
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            throw new Exception("Database query error: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch all results from a query
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @param int $fetchStyle PDO fetch style
     * @return array
     */
    protected function fetchAll($sql, array $params = [], $fetchStyle = PDO::FETCH_ASSOC)
    {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll($fetchStyle);
    }
    
    /**
     * Fetch a single row from a query
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @param int $fetchStyle PDO fetch style
     * @return array|bool The row or false if not found
     */
    protected function fetchOne($sql, array $params = [], $fetchStyle = PDO::FETCH_ASSOC)
    {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetch($fetchStyle);
    }
    
    /**
     * Fetch a single column from a query
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @param int $column Column index
     * @return mixed
     */
    protected function fetchColumn($sql, array $params = [], $column = 0)
    {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchColumn($column);
    }
    
    /**
     * Execute a query that doesn't return results
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return int Number of affected rows
     */
    protected function execute($sql, array $params = [])
    {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }
}