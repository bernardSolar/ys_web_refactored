<?php
/**
 * User Model
 * 
 * Represents a user in the system.
 */

namespace POS\Models;

class User
{
    /**
     * @var int
     */
    private $id;
    
    /**
     * @var string
     */
    private $username;
    
    /**
     * @var string
     */
    private $passwordHash;
    
    /**
     * @var string
     */
    private $email;
    
    /**
     * @var string
     */
    private $organisation;
    
    /**
     * @var string
     */
    private $deliveryAddress;
    
    /**
     * @var float
     */
    private $deliveryCharge;
    
    /**
     * @var string
     */
    private $repName;
    
    /**
     * @var string
     */
    private $role;
    
    /**
     * @var string
     */
    private $createdAt;
    
    /**
     * Constructor
     * 
     * @param array $data User data
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
        
        if (isset($data['username'])) {
            $this->username = (string) $data['username'];
        }
        
        if (isset($data['password_hash'])) {
            $this->passwordHash = (string) $data['password_hash'];
        }
        
        if (isset($data['email'])) {
            $this->email = (string) $data['email'];
        }
        
        if (isset($data['organisation'])) {
            $this->organisation = (string) $data['organisation'];
        }
        
        if (isset($data['delivery_address'])) {
            $this->deliveryAddress = (string) $data['delivery_address'];
        }
        
        if (isset($data['delivery_charge'])) {
            $this->deliveryCharge = (float) $data['delivery_charge'];
        }
        
        if (isset($data['rep_name'])) {
            $this->repName = (string) $data['rep_name'];
        }
        
        if (isset($data['role'])) {
            $this->role = (string) $data['role'];
        }
        
        if (isset($data['created_at'])) {
            $this->createdAt = (string) $data['created_at'];
        }
        
        return $this;
    }
    
    /**
     * Convert to array
     * 
     * @param bool $includePassword Whether to include the password hash
     * @return array
     */
    public function toArray($includePassword = false)
    {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'organisation' => $this->organisation,
            'delivery_address' => $this->deliveryAddress,
            'delivery_charge' => $this->deliveryCharge,
            'rep_name' => $this->repName,
            'role' => $this->role,
            'created_at' => $this->createdAt
        ];
        
        if ($includePassword) {
            $data['password_hash'] = $this->passwordHash;
        }
        
        return $data;
    }
    
    /**
     * Verify password
     * 
     * @param string $password The password to verify
     * @return bool Whether the password is valid
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->passwordHash);
    }
    
    /**
     * Set password (hashed)
     * 
     * @param string $password The plain-text password
     * @return self
     */
    public function setPassword($password)
    {
        $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
        return $this;
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
    
    public function getUsername()
    {
        return $this->username;
    }
    
    public function setUsername($username)
    {
        $this->username = (string) $username;
        return $this;
    }
    
    public function getEmail()
    {
        return $this->email;
    }
    
    public function setEmail($email)
    {
        $this->email = (string) $email;
        return $this;
    }
    
    public function getOrganisation()
    {
        return $this->organisation;
    }
    
    public function setOrganisation($organisation)
    {
        $this->organisation = (string) $organisation;
        return $this;
    }
    
    public function getDeliveryAddress()
    {
        return $this->deliveryAddress;
    }
    
    public function setDeliveryAddress($deliveryAddress)
    {
        $this->deliveryAddress = (string) $deliveryAddress;
        return $this;
    }
    
    public function getDeliveryCharge()
    {
        return $this->deliveryCharge;
    }
    
    public function setDeliveryCharge($deliveryCharge)
    {
        $this->deliveryCharge = (float) $deliveryCharge;
        return $this;
    }
    
    public function getRepName()
    {
        return $this->repName;
    }
    
    public function setRepName($repName)
    {
        $this->repName = (string) $repName;
        return $this;
    }
    
    public function getRole()
    {
        return $this->role;
    }
    
    public function setRole($role)
    {
        $this->role = (string) $role;
        return $this;
    }
    
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = (string) $createdAt;
        return $this;
    }
    
    /**
     * Check if user is an admin
     * 
     * @return bool
     */
    public function isAdmin()
    {
        return strtolower($this->role) === 'admin';
    }
}