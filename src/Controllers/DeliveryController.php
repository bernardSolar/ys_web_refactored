<?php
/**
 * Delivery Controller
 * 
 * Handles delivery scheduling related requests.
 */

namespace POS\Controllers;

use POS\Services\AuthService;
use POS\Database\DeliverySlotRepository;
use POS\Models\DeliverySlot;
use DateTime;
use DateInterval;

class DeliveryController extends BaseController
{
    /**
     * @var DeliverySlotRepository
     */
    private $slotRepository;
    
    /**
     * Constructor
     * 
     * @param AuthService|null $authService Authentication service
     * @param DeliverySlotRepository|null $slotRepository Delivery slot repository
     */
    public function __construct(
        ?AuthService $authService = null,
        ?DeliverySlotRepository $slotRepository = null
    ) {
        parent::__construct($authService);
        $this->slotRepository = $slotRepository ?: new DeliverySlotRepository();
    }
    
    /**
     * Handle request
     */
    public function handleRequest()
    {
        // All delivery endpoints require authentication
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get action from query parameters
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        switch ($action) {
            case 'get_available_dates':
                $this->getAvailableDates();
                break;
                
            case 'get_available_slots':
                $this->getAvailableSlots();
                break;
                
            case 'reserve_slot':
                $this->reserveSlot();
                break;
                
            default:
                $this->errorResponse('Invalid action', 400);
        }
    }
    
    /**
     * Get available dates for delivery
     * Returns a calendar structure for a month, with information about which dates
     * are available, unavailable, or in the past.
     */
    private function getAvailableDates()
    {
        // Get month and year from request (default to current month/year)
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        
        // Validate month and year
        if ($month < 1 || $month > 12) {
            $this->errorResponse('Invalid month', 400);
            return;
        }
        
        if ($year < date('Y') || $year > date('Y') + 2) {
            $this->errorResponse('Invalid year', 400);
            return;
        }
        
        // Calculate start and end dates for the month view (including padding days)
        $startDate = new DateTime("$year-$month-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        
        // Get days from previous month to start the calendar on Sunday
        $firstDayOfWeek = (int)$startDate->format('w'); // 0 (Sunday) to 6 (Saturday)
        if ($firstDayOfWeek > 0) {
            $startDate->modify('-' . $firstDayOfWeek . ' days'); // Adjust to start on Sunday
        }
        
        // Get days from next month to end the calendar on Saturday
        $lastDayOfWeek = (int)$endDate->format('w');
        if ($lastDayOfWeek < 6) {
            $endDate->modify('+' . (6 - $lastDayOfWeek) . ' days'); // Adjust to end on Saturday
        }
        
        // Create calendar structure
        $calendar = [
            'month' => $month,
            'year' => $year,
            'days' => [],
            'weekDays' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'monthName' => date('F', mktime(0, 0, 0, $month, 1, $year)),
            'prevMonth' => $month == 1 ? 12 : $month - 1,
            'prevYear' => $month == 1 ? $year - 1 : $year,
            'nextMonth' => $month == 12 ? 1 : $month + 1,
            'nextYear' => $month == 12 ? $year + 1 : $year,
        ];
        
        // Get today's date and minimum delivery date (2 days ahead)
        $today = new DateTime('today');
        $minDeliveryDate = clone $today;
        $minDeliveryDate->modify('+2 days');
        
        // Generate days data
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $dayOfWeek = (int)$currentDate->format('w'); // 0 (Sunday) to 6 (Saturday)
            
            $dayInfo = [
                'date' => $dateString,
                'day' => (int)$currentDate->format('j'),
                'month' => (int)$currentDate->format('n'),
                'year' => (int)$currentDate->format('Y'),
                'isCurrentMonth' => $currentDate->format('n') == $month,
                'isAvailable' => true,
                'status' => 'available',
                'reason' => null,
            ];
            
            // Check if date is in the past or too soon (< 2 days)
            if ($currentDate < $today) {
                $dayInfo['isAvailable'] = false;
                $dayInfo['status'] = 'past';
                $dayInfo['reason'] = 'Date is in the past';
            } elseif ($currentDate < $minDeliveryDate) {
                $dayInfo['isAvailable'] = false;
                $dayInfo['status'] = 'too_soon';
                $dayInfo['reason'] = 'Must book at least 2 days in advance';
            } elseif ($dayOfWeek === 0) { // Sunday (0)
                $dayInfo['isAvailable'] = false;
                $dayInfo['status'] = 'weekend';
                $dayInfo['reason'] = 'No deliveries on Sundays';
            }
            
            $calendar['days'][] = $dayInfo;
            $currentDate->modify('+1 day');
        }
        
        // Return calendar data
        $this->jsonResponse([
            'success' => true,
            'calendar' => $calendar
        ]);
    }
    
    /**
     * Get available time slots for a specific date
     */
    private function getAvailableSlots()
    {
        // Get date from request
        $date = isset($_GET['date']) ? $_GET['date'] : null;
        
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->errorResponse('Invalid date format (required: YYYY-MM-DD)', 400);
            return;
        }
        
        // Validate that the date is in the future and at least 2 days ahead
        $requestedDate = new DateTime($date);
        $minDate = new DateTime('+2 days');
        
        if ($requestedDate < $minDate) {
            $this->errorResponse('Date must be at least 2 days in the future', 400);
            return;
        }
        
        // Check if the date is a Sunday (0)
        $dayOfWeek = (int)$requestedDate->format('w');
        if ($dayOfWeek === 0) {
            $this->errorResponse('No deliveries on Sundays', 400);
            return;
        }
        
        // Define all possible time slots (8:00 to 20:00, hourly)
        $allTimeSlots = [];
        for ($hour = 8; $hour <= 20; $hour++) {
            // Skip lunch break at 13:00
            if ($hour !== 13) {
                $allTimeSlots[] = sprintf("%02d:00", $hour);
            }
        }
        
        // Get reserved slots for the date
        $reservedSlots = $this->slotRepository->getReservedSlots($date);
        
        // Build response with available and reserved slots
        $slots = [];
        foreach ($allTimeSlots as $slot) {
            $slots[] = [
                'time' => $slot,
                'isAvailable' => !in_array($slot, $reservedSlots),
                'status' => in_array($slot, $reservedSlots) ? 'reserved' : 'available'
            ];
        }
        
        $this->jsonResponse([
            'success' => true,
            'date' => $date,
            'slots' => $slots
        ]);
    }
    
    /**
     * Reserve a delivery slot
     */
    private function reserveSlot()
    {
        // This endpoint requires POST method
        if (!$this->isPost()) {
            $this->errorResponse('Method not allowed', 405);
            return;
        }
        
        // Get request data
        $data = $this->getJsonInput();
        
        if (!$data) {
            $this->errorResponse('Invalid JSON data', 400);
            return;
        }
        
        // Validate required fields
        if (!isset($data['date']) || !isset($data['time_slot'])) {
            $this->errorResponse('Date and time_slot are required', 400);
            return;
        }
        
        $date = $data['date'];
        $timeSlot = $data['time_slot'];
        $notes = isset($data['notes']) ? $data['notes'] : null;
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->errorResponse('Invalid date format (required: YYYY-MM-DD)', 400);
            return;
        }
        
        // Validate time slot format
        if (!preg_match('/^\d{2}:\d{2}$/', $timeSlot)) {
            $this->errorResponse('Invalid time slot format (required: HH:MM)', 400);
            return;
        }
        
        // Check if slot is available
        if ($this->slotRepository->isSlotReserved($date, $timeSlot)) {
            $this->errorResponse('This time slot is already reserved', 409);
            return;
        }
        
        // Check if the date is a Sunday
        $requestedDate = new DateTime($date);
        $dayOfWeek = (int)$requestedDate->format('w');
        if ($dayOfWeek === 0) { // Sunday = 0
            $this->errorResponse('No deliveries on Sundays', 400);
            return;
        }
        
        // Get current user ID for tracking
        $userId = $this->authService->getCurrentUser()->getId();
        
        // Reserve the slot (temporarily without an order ID)
        $slotId = $this->slotRepository->reserve($date, $timeSlot, null);
        
        if (!$slotId) {
            $this->errorResponse('Failed to reserve slot', 500);
            return;
        }
        
        // Return success response with reservation details
        $this->jsonResponse([
            'success' => true,
            'message' => 'Slot reserved successfully',
            'reservation' => [
                'slot_id' => $slotId,
                'date' => $date,
                'time_slot' => $timeSlot,
                'notes' => $notes
            ]
        ]);
    }
}