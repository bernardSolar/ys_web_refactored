<?php
/**
 * Test Delivery API Endpoints
 * 
 * Simple script to test the delivery API endpoints.
 * Note: This script should be run from the command line.
 */

// Base URL for API requests
$baseUrl = 'http://localhost:8000'; // Adjust to match your development server

// Function to make API requests
function makeRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init($url);
    
    $headers = ['Content-Type: application/json'];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    // You'll need to enable cookies to maintain session
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo "cURL Error: " . curl_error($ch) . "\n";
    }
    
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response ? json_decode($response, true) : null
    ];
}

// First, let's login to get a session
echo "Logging in...\n";
$loginResponse = makeRequest("$baseUrl/api/authenticate.php", 'POST', [
    'username' => 'demo',  // Replace with a valid username
    'password' => 'demo'   // Replace with a valid password
]);

if ($loginResponse['code'] !== 200 || !isset($loginResponse['body']['success']) || !$loginResponse['body']['success']) {
    echo "Login failed. Cannot proceed with tests.\n";
    exit(1);
}

echo "Login successful.\n\n";

// Test 1: Get available dates for current month
echo "Test 1: Get available dates for current month\n";
$currentMonth = date('n');
$currentYear = date('Y');

$datesResponse = makeRequest("$baseUrl/api/delivery.php?action=get_available_dates&month=$currentMonth&year=$currentYear");

if ($datesResponse['code'] === 200 && isset($datesResponse['body']['success']) && $datesResponse['body']['success']) {
    echo "Success! Received calendar data.\n";
    echo "Month: {$datesResponse['body']['calendar']['monthName']} {$datesResponse['body']['calendar']['year']}\n";
    echo "Number of days in response: " . count($datesResponse['body']['calendar']['days']) . "\n";
    
    // Find an available date for the next test
    $availableDate = null;
    foreach ($datesResponse['body']['calendar']['days'] as $day) {
        if ($day['isAvailable'] && $day['isCurrentMonth']) {
            $availableDate = $day['date'];
            echo "Found available date: $availableDate\n";
            break;
        }
    }
} else {
    echo "Failed to get calendar data.\n";
    if (isset($datesResponse['body'])) {
        echo "Response: " . json_encode($datesResponse['body']) . "\n";
    }
}

echo "\n";

// Only proceed if we found an available date
if (!empty($availableDate)) {
    // Test 2: Get available time slots for a specific date
    echo "Test 2: Get available time slots for $availableDate\n";
    $slotsResponse = makeRequest("$baseUrl/api/delivery.php?action=get_available_slots&date=$availableDate");
    
    if ($slotsResponse['code'] === 200 && isset($slotsResponse['body']['success']) && $slotsResponse['body']['success']) {
        echo "Success! Received time slots.\n";
        echo "Number of slots: " . count($slotsResponse['body']['slots']) . "\n";
        
        // Find an available slot for the next test
        $availableSlot = null;
        foreach ($slotsResponse['body']['slots'] as $slot) {
            if ($slot['isAvailable']) {
                $availableSlot = $slot['time'];
                echo "Found available slot: $availableSlot\n";
                break;
            }
        }
        
        // Test 3: Reserve a slot
        if (!empty($availableSlot)) {
            echo "\nTest 3: Reserve slot on $availableDate at $availableSlot\n";
            $reserveResponse = makeRequest("$baseUrl/api/delivery.php?action=reserve_slot", 'POST', [
                'date' => $availableDate,
                'time_slot' => $availableSlot,
                'notes' => 'Test delivery notes from API test'
            ]);
            
            if ($reserveResponse['code'] === 200 && isset($reserveResponse['body']['success']) && $reserveResponse['body']['success']) {
                echo "Success! Slot reserved.\n";
                echo "Reservation ID: {$reserveResponse['body']['reservation']['slot_id']}\n";
                
                // Store the slot ID for the order test
                $slotId = $reserveResponse['body']['reservation']['slot_id'];
                
                // Test 4: Place an order with delivery information
                echo "\nTest 4: Place an order with delivery information\n";
                $orderResponse = makeRequest("$baseUrl/api/place_order.php", 'POST', [
                    'items' => [
                        [
                            'name' => 'Test Product',
                            'price' => 10.00,
                            'count' => 1,
                            'id' => 1,
                            'category' => 'Test'
                        ]
                    ],
                    'total' => 10.00,
                    'delivery_date' => $availableDate,
                    'delivery_time' => $availableSlot,
                    'delivery_notes' => 'Test delivery from API',
                    'slot_id' => $slotId
                ]);
                
                if ($orderResponse['code'] === 200 && isset($orderResponse['body']['success']) && $orderResponse['body']['success']) {
                    echo "Success! Order placed with delivery information.\n";
                    echo "Order ID: {$orderResponse['body']['orderId']}\n";
                    
                    if (isset($orderResponse['body']['orderInfo']['deliveryDate'])) {
                        echo "Delivery Date: {$orderResponse['body']['orderInfo']['deliveryDate']}\n";
                    }
                    
                    if (isset($orderResponse['body']['orderInfo']['deliveryTime'])) {
                        echo "Delivery Time: {$orderResponse['body']['orderInfo']['deliveryTime']}\n";
                    }
                } else {
                    echo "Failed to place order.\n";
                    if (isset($orderResponse['body'])) {
                        echo "Response: " . json_encode($orderResponse['body']) . "\n";
                    }
                }
            } else {
                echo "Failed to reserve slot.\n";
                if (isset($reserveResponse['body'])) {
                    echo "Response: " . json_encode($reserveResponse['body']) . "\n";
                }
            }
        } else {
            echo "No available time slots found.\n";
        }
    } else {
        echo "Failed to get time slots.\n";
        if (isset($slotsResponse['body'])) {
            echo "Response: " . json_encode($slotsResponse['body']) . "\n";
        }
    }
} else {
    echo "No available dates found for testing.\n";
}

echo "\nAPI tests completed.\n";
