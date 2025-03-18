/**
 * Delivery Scheduler
 * Handles calendar display, time slot selection, and delivery scheduling.
 */
class DeliveryScheduler {
    constructor() {
        // State
        this.currentDate = new Date();
        this.currentMonth = this.currentDate.getMonth();
        this.currentYear = this.currentDate.getFullYear();
        this.selectedDate = null;
        this.selectedTimeSlot = null;
        this.availableDates = [];
        this.timeSlots = [];
        this.reservedSlots = [];
        this.deliveryNotes = '';
        this.slotId = null;
        
        // Modal elements
        this.modal = document.getElementById('delivery-scheduler-modal');
        this.calendarMonthYear = document.getElementById('calendar-month-year');
        this.calendarWeekdays = document.getElementById('calendar-weekdays');
        this.calendarDays = document.getElementById('calendar-days');
        this.timeSlotsGrid = document.getElementById('time-slots-grid');
        this.deliveryNotesTextarea = document.getElementById('delivery-notes');
        this.selectedSlotInfo = document.getElementById('selected-slot-info');
        this.selectedDateDisplay = document.getElementById('selected-date-display');
        this.selectedTimeDisplay = document.getElementById('selected-time-display');
        this.reserveButton = document.getElementById('reserve-slot-button');
        
        // Month navigation buttons
        this.prevMonthBtn = document.getElementById('prev-month-btn');
        this.nextMonthBtn = document.getElementById('next-month-btn');
        
        // Minimum delivery date (2 days from now)
        this.minDeliveryDate = new Date();
        this.minDeliveryDate.setDate(this.minDeliveryDate.getDate() + 2);
        
        // Weekday names
        this.weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        
        // Month names
        this.monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        // Initialize
        this.init();
    }
    
    /**
     * Initialize the scheduler
     */
    init() {
        // Render the calendar weekday headers
        this.renderWeekdays();
        
        // Set up event listeners
        this.setupEventListeners();
        
        // When the modal is shown, load the calendar
        const schedulerModal = document.getElementById('delivery-scheduler-modal');
        schedulerModal.addEventListener('show.bs.modal', () => {
            // Reset state
            this.selectedDate = null;
            this.selectedTimeSlot = null;
            this.slotId = null;
            this.deliveryNotes = '';
            
            // Reset UI
            this.deliveryNotesTextarea.value = '';
            this.selectedSlotInfo.style.display = 'none';
            this.timeSlotsGrid.innerHTML = '';
            
            // Set to current month/year and render
            this.currentMonth = new Date().getMonth();
            this.currentYear = new Date().getFullYear();
            this.loadCalendarData();
        });
        
        // Add event listener to Schedule Delivery button in the order confirmation modal
        const scheduleButton = document.getElementById('schedule-delivery-button');
        if (scheduleButton) {
            scheduleButton.addEventListener('click', () => {
                // Hide the confirmation modal
                const confirmModal = bootstrap.Modal.getInstance(document.getElementById('order-confirmation-modal'));
                confirmModal.hide();
                
                // Show the scheduler modal
                const schedulerModal = new bootstrap.Modal(document.getElementById('delivery-scheduler-modal'));
                schedulerModal.show();
            });
        }
    }
    
    /**
     * Set up event listeners
     */
    setupEventListeners() {
        // Month navigation
        this.prevMonthBtn.addEventListener('click', () => {
            this.navigateMonth(-1);
        });
        
        this.nextMonthBtn.addEventListener('click', () => {
            this.navigateMonth(1);
        });
        
        // Reserve button
        this.reserveButton.addEventListener('click', () => {
            this.reserveDeliverySlot();
        });
        
        // Delivery notes textarea
        this.deliveryNotesTextarea.addEventListener('input', (e) => {
            this.deliveryNotes = e.target.value;
        });
    }
    
    /**
     * Navigate to previous/next month
     * @param {number} direction -1 for previous, 1 for next
     */
    navigateMonth(direction) {
        this.currentMonth += direction;
        
        if (this.currentMonth > 11) {
            this.currentMonth = 0;
            this.currentYear += 1;
        } else if (this.currentMonth < 0) {
            this.currentMonth = 11;
            this.currentYear -= 1;
        }
        
        this.loadCalendarData();
    }
    
    /**
     * Render weekday headers
     */
    renderWeekdays() {
        this.calendarWeekdays.innerHTML = '';
        
        for (let i = 0; i < 7; i++) {
            const weekday = document.createElement('div');
            weekday.className = 'calendar-weekday';
            weekday.textContent = this.weekdays[i];
            this.calendarWeekdays.appendChild(weekday);
        }
    }
    
    /**
     * Load calendar data from the API
     */
    loadCalendarData() {
        // Update calendar header
        this.calendarMonthYear.textContent = `${this.monthNames[this.currentMonth]} ${this.currentYear}`;
        
        // Get available dates for the current month
        fetch(`api/delivery.php?action=get_available_dates&month=${this.currentMonth + 1}&year=${this.currentYear}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load calendar data');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.availableDates = data.calendar.days;
                    this.renderCalendar(data.calendar);
                } else {
                    console.error('Error loading calendar data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    /**
     * Render the calendar
     * @param {object} calendar Calendar data from API
     */
    renderCalendar(calendar) {
        this.calendarDays.innerHTML = '';
        
        // Render each day
        calendar.days.forEach(day => {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            
            // Add classes based on day properties
            if (!day.isCurrentMonth) dayElement.classList.add('other-month');
            if (!day.isAvailable) dayElement.classList.add('disabled');
            if (day.status === 'weekend') dayElement.classList.add('weekend');
            if (day.status === 'today') dayElement.classList.add('today');
            
            // Disable Sundays (day 0 in JavaScript's getDay())
            const dayDate = new Date(day.date);
            if (dayDate.getDay() === 0) {
                dayElement.classList.add('disabled');
                dayElement.classList.add('weekend');
                day.isAvailable = false;
                day.reason = 'No deliveries on Sundays';
            }
            
            // Day content
            const dayNumber = document.createElement('div');
            dayNumber.textContent = day.day;
            dayElement.appendChild(dayNumber);
            
            // Month abbreviation for non-current month days
            if (!day.isCurrentMonth) {
                const monthAbbr = document.createElement('div');
                monthAbbr.className = 'month-small';
                monthAbbr.textContent = this.monthNames[day.month - 1].substring(0, 3);
                dayElement.appendChild(monthAbbr);
            }
            
            // Make available days clickable
            if (day.isAvailable) {
                dayElement.addEventListener('click', () => {
                    this.selectDate(day.date, dayElement);
                });
            } else {
                // Add tooltip with reason
                if (day.reason) {
                    dayElement.title = day.reason;
                }
            }
            
            this.calendarDays.appendChild(dayElement);
        });
    }
    
    /**
     * Handle date selection
     * @param {string} dateStr Selected date in YYYY-MM-DD format
     * @param {HTMLElement} element The clicked day element
     */
    selectDate(dateStr, element) {
        // Update selection UI
        const prevSelected = this.calendarDays.querySelector('.selected');
        if (prevSelected) prevSelected.classList.remove('selected');
        element.classList.add('selected');
        
        // Update state
        this.selectedDate = dateStr;
        
        // Clear time slot selection
        this.selectedTimeSlot = null;
        
        // Load time slots for this date
        this.loadTimeSlots(dateStr);
        
        // Update selected slot info display
        this.updateSelectedSlotInfo();
    }
    
    /**
     * Load time slots for a specific date
     * @param {string} dateStr Date in YYYY-MM-DD format
     */
    loadTimeSlots(dateStr) {
        fetch(`api/delivery.php?action=get_available_slots&date=${dateStr}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load time slots');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.timeSlots = data.slots;
                    this.renderTimeSlots(data.slots);
                } else {
                    console.error('Error loading time slots:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    /**
     * Render time slots
     * @param {array} slots Array of time slot objects
     */
    renderTimeSlots(slots) {
        this.timeSlotsGrid.innerHTML = '';
        
        slots.forEach(slot => {
            const timeSlot = document.createElement('div');
            timeSlot.className = 'time-slot';
            timeSlot.textContent = slot.time;
            
            // Mark as disabled if not available
            if (!slot.isAvailable) {
                timeSlot.classList.add('disabled');
                timeSlot.title = 'Already reserved';
            } else {
                // Make available slots clickable
                timeSlot.addEventListener('click', () => {
                    this.selectTimeSlot(slot.time, timeSlot);
                });
            }
            
            this.timeSlotsGrid.appendChild(timeSlot);
        });
    }
    
    /**
     * Handle time slot selection
     * @param {string} timeStr Selected time in HH:MM format
     * @param {HTMLElement} element The clicked time slot element
     */
    selectTimeSlot(timeStr, element) {
        // Update selection UI
        const prevSelected = this.timeSlotsGrid.querySelector('.selected');
        if (prevSelected) prevSelected.classList.remove('selected');
        element.classList.add('selected');
        
        // Update state
        this.selectedTimeSlot = timeStr;
        
        // Update selected slot info display
        this.updateSelectedSlotInfo();
    }
    
    /**
     * Update the selected slot info display
     */
    updateSelectedSlotInfo() {
        if (this.selectedDate && this.selectedTimeSlot) {
            // Format date for display
            const dateObj = new Date(this.selectedDate);
            const formattedDate = `${dateObj.toLocaleDateString('en-GB', { weekday: 'long' })}, ${dateObj.getDate()} ${this.monthNames[dateObj.getMonth()]} ${dateObj.getFullYear()}`;
            
            // Format time for display (convert 24h to 12h format)
            let [hours, minutes] = this.selectedTimeSlot.split(':');
            hours = parseInt(hours);
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            const formattedTime = `${hours}:${minutes} ${ampm}`;
            
            // Update display
            this.selectedDateDisplay.textContent = formattedDate;
            this.selectedTimeDisplay.textContent = formattedTime;
            this.selectedSlotInfo.style.display = 'block';
            
            // Enable the reserve button
            this.reserveButton.disabled = false;
        } else {
            // Hide the info display if no complete selection
            this.selectedSlotInfo.style.display = 'none';
            
            // Disable the reserve button
            this.reserveButton.disabled = true;
        }
    }
    
    /**
     * Reserve a delivery slot
     */
    reserveDeliverySlot() {
        if (!this.selectedDate || !this.selectedTimeSlot) {
            alert('Please select a date and time for delivery');
            return;
        }
        
        // Prepare reservation data
        const reservationData = {
            date: this.selectedDate,
            time_slot: this.selectedTimeSlot,
            notes: this.deliveryNotes
        };
        
        // Send reservation request
        fetch('api/delivery.php?action=reserve_slot', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(reservationData)
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 409) {
                    throw new Error('This time slot is already reserved');
                } else {
                    throw new Error('Failed to reserve delivery slot');
                }
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Store the reservation details
                this.slotId = data.reservation.slot_id;
                
                // Close the scheduler modal
                const schedulerModal = bootstrap.Modal.getInstance(document.getElementById('delivery-scheduler-modal'));
                schedulerModal.hide();
                
                // Show the confirmation modal again with updated info
                const confirmModal = new bootstrap.Modal(document.getElementById('order-confirmation-modal'));
                confirmModal.show();
                
                // Update the delivery info in the confirmation modal
                this.updateOrderConfirmationModal();
                
                // Show success message
                alert('Delivery slot reserved successfully!');
            } else {
                console.error('Error reserving slot:', data.message);
                alert(`Failed to reserve slot: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message);
        });
    }
    
    /**
     * Update the order confirmation modal with delivery info
     */
    updateOrderConfirmationModal() {
        if (!this.selectedDate || !this.selectedTimeSlot) return;
        
        // Get the delivery information container
        const deliveryInfoContainer = document.getElementById('modal-delivery-info');
        if (!deliveryInfoContainer) return;
        
        // Format date for display
        const dateObj = new Date(this.selectedDate);
        const formattedDate = `${dateObj.toLocaleDateString('en-GB', { weekday: 'long' })}, ${dateObj.getDate()} ${this.monthNames[dateObj.getMonth()]} ${dateObj.getFullYear()}`;
        
        // Format time for display (convert 24h to 12h format)
        let [hours, minutes] = this.selectedTimeSlot.split(':');
        hours = parseInt(hours);
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        const formattedTime = `${hours}:${minutes} ${ampm}`;
        
        // Create delivery info content
        deliveryInfoContainer.innerHTML = `
            <h6 class="mt-3">Delivery Information:</h6>
            <div><strong>Delivery Date:</strong> ${formattedDate}</div>
            <div><strong>Delivery Time:</strong> ${formattedTime}</div>
        `;
        
        if (window.userData) {
            deliveryInfoContainer.innerHTML += `
                <div><strong>Organisation:</strong> ${window.userData.organisation || 'Not specified'}</div>
                <div><strong>Delivery Address:</strong> ${window.userData.delivery_address || 'Not specified'}</div>
            `;
        }
        
        if (this.deliveryNotes) {
            deliveryInfoContainer.innerHTML += `
                <div class="mt-2"><strong>Delivery Notes:</strong> ${this.deliveryNotes}</div>
            `;
        }
        
        // Store delivery info in the window object for order placement
        window.deliveryInfo = {
            date: this.selectedDate,
            time: this.selectedTimeSlot,
            notes: this.deliveryNotes,
            slotId: this.slotId
        };
    }
}

// Initialize the delivery scheduler
document.addEventListener('DOMContentLoaded', function() {
    window.deliveryScheduler = new DeliveryScheduler();
});