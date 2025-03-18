<?php
/**
 * Test Delivery Slots
 * 
 * Simple script to test the delivery slots functionality.
 */

// Define ROOT_PATH constant
define('ROOT_PATH', __DIR__);

// Include the bootstrap file to initialize the application
require_once __DIR__ . '/src/bootstrap.php';

use POS\Database\DeliverySlotRepository;
use POS\Models\DeliverySlot;

// Create a delivery slot repository
$slotRepo = new DeliverySlotRepository();

// Create test data
$date = date('Y-m-d', strtotime('+3 days')); // 3 days from now
$timeSlot = "10:00";

echo "Testing delivery slot functionality...\n";

// Check if slot is available
$isReserved = $slotRepo->isSlotReserved($date, $timeSlot);
echo "Is slot reserved ($date $timeSlot)? " . ($isReserved ? "Yes" : "No") . "\n";

// Reserve the slot
if (!$isReserved) {
    $slotId = $slotRepo->reserve($date, $timeSlot, null);
    
    if ($slotId) {
        echo "Successfully reserved slot: $slotId\n";
        
        // Get reserved slots for the date
        $reservedSlots = $slotRepo->getReservedSlots($date);
        echo "Reserved slots for $date: " . implode(", ", $reservedSlots) . "\n";
        
        // Cancel the reservation
        $cancelled = $slotRepo->cancel($slotId);
        echo "Cancelled reservation: " . ($cancelled ? "Yes" : "No") . "\n";
        
        // Delete the test slot
        $deleted = $slotRepo->delete($slotId);
        echo "Deleted test slot: " . ($deleted ? "Yes" : "No") . "\n";
    } else {
        echo "Failed to reserve slot.\n";
    }
} else {
    echo "Slot is already reserved.\n";
}

echo "Done testing delivery slots.\n";
