<?php
/**
 * User Repository
 * 
 * Handles database operations for users.
 */

namespace POS\Database;

use POS\Models\User;
use PDO;

class UserRepository extends Repository
{
    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return User|null
     */
    public function findById($id)
    {
        $sql = "
            SELECT id, username, password_hash, email, organisation, 
                   delivery_address, delivery_charge, rep_name, role, created_at
            FROM users
            WHERE id = ?
        ";
        
        $row = $this->fetchOne($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return new User($row);
    }
    
    /**
     * Find user by username
     * 
     * @param string $username Username
     * @return User|null
     */
    public function findByUsername($username)
    {
        $sql = "
            SELECT id, username, password_hash, email, organisation, 
                   delivery_address, delivery_charge, rep_name, role, created_at
            FROM users
            WHERE username = ?
        ";
        
        $row = $this->fetchOne($sql, [$username]);
        
        if (!$row) {
            return null;
        }
        
        return new User($row);
    }
    
    /**
     * Get all users
     * 
     * @return User[]
     */
    public function findAll()
    {
        $sql = "
            SELECT id, username, password_hash, email, organisation, 
                   delivery_address, delivery_charge, rep_name, role, created_at
            FROM users
            ORDER BY organisation, username
        ";
        
        $rows = $this->fetchAll($sql);
        
        $users = [];
        foreach ($rows as $row) {
            $users[] = new User($row);
        }
        
        return $users;
    }
    
    /**
     * Save user
     * 
     * @param User $user User to save
     * @return bool Success status
     */
    public function save(User $user)
    {
        try {
            if ($user->getId()) {
                // Update existing user
                $sql = "
                    UPDATE users
                    SET username = ?, 
                        password_hash = ?, 
                        email = ?, 
                        organisation = ?, 
                        delivery_address = ?, 
                        delivery_charge = ?, 
                        rep_name = ?, 
                        role = ?
                    WHERE id = ?
                ";
                
                $this->execute($sql, [
                    $user->getUsername(),
                    $user->toArray(true)['password_hash'],
                    $user->getEmail(),
                    $user->getOrganisation(),
                    $user->getDeliveryAddress(),
                    $user->getDeliveryCharge(),
                    $user->getRepName(),
                    $user->getRole(),
                    $user->getId()
                ]);
                
                return true;
            } else {
                // Insert new user
                $sql = "
                    INSERT INTO users (
                        username, password_hash, email, organisation, 
                        delivery_address, delivery_charge, rep_name, role
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ";
                
                $this->execute($sql, [
                    $user->getUsername(),
                    $user->toArray(true)['password_hash'],
                    $user->getEmail(),
                    $user->getOrganisation(),
                    $user->getDeliveryAddress(),
                    $user->getDeliveryCharge(),
                    $user->getRepName(),
                    $user->getRole()
                ]);
                
                // Set the ID from the insert
                $user->setId($this->lastInsertId());
                
                return true;
            }
        } catch (\Exception $e) {
            error_log("Error saving user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete user
     * 
     * @param int $id User ID
     * @return bool Success status
     */
    public function delete($id)
    {
        try {
            $sql = "DELETE FROM users WHERE id = ?";
            $affected = $this->execute($sql, [$id]);
            return $affected > 0;
        } catch (\Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user profile
     * 
     * @param int $id User ID
     * @param array $data Profile data to update
     * @return bool Success status
     */
    public function updateProfile($id, array $data)
    {
        try {
            // Get current user
            $user = $this->findById($id);
            if (!$user) {
                return false;
            }
            
            // Update fields that were provided
            if (isset($data['email'])) {
                $user->setEmail($data['email']);
            }
            
            if (isset($data['organisation'])) {
                $user->setOrganisation($data['organisation']);
            }
            
            if (isset($data['delivery_address'])) {
                $user->setDeliveryAddress($data['delivery_address']);
            }
            
            if (isset($data['delivery_charge'])) {
                $user->setDeliveryCharge($data['delivery_charge']);
            }
            
            if (isset($data['rep_name'])) {
                $user->setRepName($data['rep_name']);
            }
            
            // Only admins can change role
            if (isset($data['role']) && $user->isAdmin()) {
                $user->setRole($data['role']);
            }
            
            // Update password if provided
            if (isset($data['password']) && !empty($data['password'])) {
                $user->setPassword($data['password']);
            }
            
            // Save changes
            return $this->save($user);
            
        } catch (\Exception $e) {
            error_log("Error updating user profile: " . $e->getMessage());
            return false;
        }
    }
}