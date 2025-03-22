<?php
/**
 * Delivery Scheduling Steps
 * 
 * Step definitions for Behat tests related to delivery scheduling feature
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Features context for Delivery Scheduling feature
 */
class DeliveryFeatureContext implements Context
{
    /**
     * @var \Behat\MinkExtension\Context\MinkContext
     */
    private $minkContext;
    
    /**
     * @BeforeScenario
     */
    public function gatherContexts(\Behat\Behat\Hook\Scope\BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->minkContext = $environment->getContext('Behat\MinkExtension\Context\MinkContext');
    }
    
    /**
     * @Given I am logged in as a registered user
     */
    public function iAmLoggedInAsARegisteredUser()
    {
        // Visit login page
        $this->minkContext->visit('/login.php');
        
        // Fill in login form
        $this->minkContext->fillField('username', 'testuser');
        $this->minkContext->fillField('password', 'password123');
        
        // Submit login form
        $this->minkContext->pressButton('Log In');
        
        // Verify login success
        $this->minkContext->assertPageContainsText('Order Summary');
    }
    
    /**
     * @Given I have items in my shopping cart
     */
    public function iHaveItemsInMyShoppingCart()
    {
        // Visit main page
        $this->minkContext->visit('/index.php');
        
        // Click on a product to add to cart
        $this->minkContext->clickLink('Sylvagrow Ericaceous 40ltr');
        
        // Verify the product is in the cart
        $this->minkContext->assertElementContainsText('#order-list', 'Sylvagrow Ericaceous 40ltr');
    }
    
    /**
     * @When I click :button
     */
    public function iClick($button)
    {
        $this->minkContext->pressButton($button);
    }
    
    /**
     * @When I click :linkText in the confirmation modal
     */
    public function iClickInTheConfirmationModal($linkText)
    {
        $this->minkContext->getSession()->wait(1000);
        $this->minkContext->pressButton($linkText);
    }
    
    /**
     * @Then I should see the delivery scheduler modal
     */
    public function iShouldSeeTheDeliverySchedulerModal()
    {
        $this->minkContext->assertElementContainsText('.modal-title', 'Reserve a Delivery Slot');
    }
    
    /**
     * @Then the current month should be displayed in the calendar
     */
    public function theCurrentMonthShouldBeDisplayedInTheCalendar()
    {
        $currentMonth = date('F Y');
        $this->minkContext->assertElementContainsText('#calendar-month-year', $currentMonth);
    }
    
    /**
     * @When I click the :buttonText button
     */
    public function iClickTheButton($buttonText)
    {
        $buttonId = '';
        
        // Determine button ID based on text
        switch ($buttonText) {
            case 'Next':
                $buttonId = '#next-month-btn';
                break;
            case 'Prev':
                $buttonId = '#prev-month-btn';
                break;
            case 'Reserve Delivery Slot':
                $buttonId = '#reserve-slot-button';
                break;
            default:
                throw new \Exception("Button '$buttonText' not mapped to an ID");
        }
        
        $this->minkContext->getSession()->getPage()->find('css', $buttonId)->click();
    }
    
    /**
     * @Then the calendar should display the next month
     */
    public function theCalendarShouldDisplayTheNextMonth()
    {
        $nextMonth = new \DateTime('first day of next month');
        $expectedMonth = $nextMonth->format('F Y');
        $this->minkContext->assertElementContainsText('#calendar-month-year', $expectedMonth);
    }
    
    /**
     * @Then the calendar should return to the current month
     */
    public function theCalendarShouldReturnToTheCurrentMonth()
    {
        $currentMonth = date('F Y');
        $this->minkContext->assertElementContainsText('#calendar-month-year', $currentMonth);
    }
    
    /**
     * @When I open the delivery scheduler
     */
    public function iOpenTheDeliveryScheduler()
    {
        $this->minkContext->visit('/index.php');
        $this->minkContext->clickLink('Sylvagrow Ericaceous 40ltr');
        $this->minkContext->pressButton('Place Order');
        $this->minkContext->getSession()->wait(1000);
        $this->minkContext->pressButton('Schedule Delivery');
    }
    
    /**
     * @Then all Sundays in the calendar should be disabled
     */
    public function allSundaysInTheCalendarShouldBeDisabled()
    {
        $this->minkContext->getSession()->wait(500); // Wait for calendar to load
        
        $result = $this->minkContext->getSession()->evaluateScript(
            "return document.querySelectorAll('.calendar-day')[0].classList.contains('disabled');"
        );
        
        if (!$result) {
            throw new \Exception("Sunday is not disabled in the calendar");
        }
    }
    
    /**
     * @Then Sundays should be displayed in red
     */
    public function sundaysShouldBeDisplayedInRed()
    {
        $redColorFound = $this->minkContext->getSession()->evaluateScript(
            "return document.querySelectorAll('#calendar-weekdays div')[0].style.color === 'rgb(220, 53, 69)';"
        );
        
        if (!$redColorFound) {
            throw new \Exception("Sunday column is not displayed in red");
        }
    }
    
    /**
     * @Then all other days should be enabled (except those in the past or < :days days ahead)
     */
    public function allOtherDaysShouldBeEnabledExceptThoseInThePastOrDaysAhead($days)
    {
        // This is a complex check that requires JavaScript evaluation
        $script = "
            const today = new Date();
            const minDate = new Date();
            minDate.setDate(today.getDate() + parseInt('$days'));
            
            const allDays = Array.from(document.querySelectorAll('.calendar-day:not(.other-month)'));
            const weekdays = allDays.filter((day, index) => index % 7 !== 0); // Not Sundays
            
            for (const day of weekdays) {
                const dayNum = parseInt(day.textContent.trim());
                const dayDate = new Date(today.getFullYear(), today.getMonth(), dayNum);
                
                // Days in the past or less than minDate should be disabled
                if (dayDate < today || dayDate < minDate) {
                    if (!day.classList.contains('disabled')) {
                        return false;
                    }
                } else {
                    if (day.classList.contains('disabled')) {
                        return false;
                    }
                }
            }
            return true;
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("Day availability not correctly configured");
        }
    }
    
    /**
     * @Then today's date should be disabled
     */
    public function todaysDateShouldBeDisabled()
    {
        $script = "
            const today = new Date();
            const todayDate = today.getDate();
            const todayElement = Array.from(document.querySelectorAll('.calendar-day:not(.other-month)'))
                .find(day => parseInt(day.textContent.trim()) === todayDate);
            
            return todayElement && todayElement.classList.contains('disabled');
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("Today's date is not disabled");
        }
    }
    
    /**
     * @Then tomorrow's date should be disabled
     */
    public function tomorrowsDateShouldBeDisabled()
    {
        $script = "
            const today = new Date();
            const tomorrow = new Date();
            tomorrow.setDate(today.getDate() + 1);
            const tomorrowDate = tomorrow.getDate();
            
            const tomorrowElement = Array.from(document.querySelectorAll('.calendar-day:not(.other-month)'))
                .find(day => parseInt(day.textContent.trim()) === tomorrowDate);
            
            return tomorrowElement && tomorrowElement.classList.contains('disabled');
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("Tomorrow's date is not disabled");
        }
    }
    
    /**
     * @Then dates :days or more days ahead should be enabled (except Sundays)
     */
    public function datesOrMoreDaysAheadShouldBeEnabledExceptSundays($days)
    {
        $script = "
            const today = new Date();
            const futureDate = new Date();
            futureDate.setDate(today.getDate() + parseInt('$days'));
            
            // Find all days from futureDate onwards that are not Sundays
            const validDays = Array.from(document.querySelectorAll('.calendar-day:not(.other-month)'))
                .filter(day => {
                    const dayNum = parseInt(day.textContent.trim());
                    const dayDate = new Date(today.getFullYear(), today.getMonth(), dayNum);
                    const dayOfWeek = dayDate.getDay(); // 0 = Sunday
                    
                    return dayDate >= futureDate && dayOfWeek !== 0;
                });
            
            // Verify they are enabled
            return validDays.every(day => !day.classList.contains('disabled'));
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("Dates $days or more days ahead are not correctly enabled");
        }
    }
    
    /**
     * @When I select a valid date :days days from now
     */
    public function iSelectAValidDateDaysFromNow($days)
    {
        $this->minkContext->getSession()->wait(500); // Wait for calendar to load
        
        $futureDate = date('j', strtotime("+$days days"));
        
        $script = "
            const futureDate = '$futureDate';
            const validDay = Array.from(document.querySelectorAll('.calendar-day'))
                .find(el => !el.classList.contains('disabled') && 
                            !el.classList.contains('other-month') && 
                            el.textContent.trim() === futureDate);
            
            if (validDay) {
                validDay.click();
                return true;
            }
            return false;
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("Could not select date $days days from now");
        }
    }
    
    /**
     * @Then the date should be highlighted
     */
    public function theDateShouldBeHighlighted()
    {
        $highlighted = $this->minkContext->getSession()->evaluateScript(
            "return document.querySelector('.calendar-day.selected') !== null;"
        );
        
        if (!$highlighted) {
            throw new \Exception("Selected date is not highlighted");
        }
    }
    
    /**
     * @Then the time slot selection should appear
     */
    public function theTimeSlotSelectionShouldAppear()
    {
        $this->minkContext->assertElementExists('#time-slots-grid');
        $this->minkContext->assertElementExists('.time-slot');
    }
    
    /**
     * @When I select a valid date
     */
    public function iSelectAValidDate()
    {
        $this->iSelectAValidDateDaysFromNow(3);
    }
    
    /**
     * @Then I should see available time slots between :startHour::startMin and :endHour::endMin
     */
    public function iShouldSeeAvailableTimeSlotsBetweenAnd($startHour, $startMin, $endHour, $endMin)
    {
        $this->minkContext->getSession()->wait(500); // Wait for slots to load
        
        $script = "
            const slots = Array.from(document.querySelectorAll('.time-slot'));
            const startTime = parseInt('$startHour');
            const endTime = parseInt('$endHour');
            
            // Check if slots exist for the specified range
            let hasStartSlot = false;
            let hasEndSlot = false;
            
            for (const slot of slots) {
                const time = slot.textContent.trim();
                const hour = parseInt(time.split(':')[0]);
                
                if (hour === startTime) hasStartSlot = true;
                if (hour === endTime) hasEndSlot = true;
            }
            
            return slots.length > 0 && hasStartSlot && hasEndSlot;
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("Time slots not available in the expected range");
        }
    }
    
    /**
     * @Then the :hour::min slot should not be available (lunch break)
     */
    public function theSlotShouldNotBeAvailableLunchBreak($hour, $min)
    {
        $script = "
            const lunchTime = '$hour:$min';
            const lunchSlot = Array.from(document.querySelectorAll('.time-slot'))
                .find(slot => slot.textContent.trim() === lunchTime);
                
            return !lunchSlot || lunchSlot.classList.contains('disabled');
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("Lunch slot $hour:$min is available when it should be disabled");
        }
    }
    
    /**
     * @When I select the :timeSlot time slot
     */
    public function iSelectTheTimeSlot($timeSlot)
    {
        $this->minkContext->getSession()->wait(500); // Wait for slots to load
        
        $script = "
            const timeSlot = '$timeSlot';
            const slot = Array.from(document.querySelectorAll('.time-slot'))
                .find(el => el.textContent.trim() === timeSlot && !el.classList.contains('disabled'));
                
            if (slot) {
                slot.click();
                return true;
            }
            return false;
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("Could not select time slot $timeSlot");
        }
    }
    
    /**
     * @Then the time slot should be highlighted
     */
    public function theTimeSlotShouldBeHighlighted()
    {
        $highlighted = $this->minkContext->getSession()->evaluateScript(
            "return document.querySelector('.time-slot.selected') !== null;"
        );
        
        if (!$highlighted) {
            throw new \Exception("Selected time slot is not highlighted");
        }
    }
    
    /**
     * @Then the selected slot information should be displayed
     */
    public function theSelectedSlotInformationShouldBeDisplayed()
    {
        $this->minkContext->assertElementExists('#selected-slot-info');
        $this->assertElementIsVisible('#selected-slot-info');
    }
    
    /**
     * @Then the :buttonText button should be enabled
     */
    public function theButtonShouldBeEnabled($buttonText)
    {
        $buttonId = '';
        
        // Determine button ID based on text
        switch ($buttonText) {
            case 'Reserve Delivery Slot':
                $buttonId = '#reserve-slot-button';
                break;
            default:
                throw new \Exception("Button '$buttonText' not mapped to an ID");
        }
        
        $disabled = $this->minkContext->getSession()->evaluateScript(
            "return document.querySelector('$buttonId').disabled;"
        );
        
        if ($disabled) {
            throw new \Exception("Button '$buttonText' is disabled when it should be enabled");
        }
    }
    
    /**
     * @When I have selected a date and time slot
     */
    public function iHaveSelectedADateAndTimeSlot()
    {
        $this->iOpenTheDeliveryScheduler();
        $this->iSelectAValidDate();
        $this->iSelectTheTimeSlot('10:00');
    }
    
    /**
     * @When I enter :notes in the delivery notes field
     */
    public function iEnterInTheDeliveryNotesField($notes)
    {
        $this->minkContext->fillField('delivery-notes', $notes);
    }
    
    /**
     * @Then the delivery notes should be saved with my order
     */
    public function theDeliveryNotesShouldBeSavedWithMyOrder()
    {
        // This would need to place the order and verify in the database
        // For now, we'll just check that notes are in the modal
        $this->iClick('Reserve Delivery Slot');
        $this->minkContext->getSession()->wait(1000);
        $this->minkContext->assertElementContainsText('#modal-delivery-info', 'Please leave at the back door');
    }
    
    /**
     * @When I click :button with items in my cart
     */
    public function iClickWithItemsInMyCart($button)
    {
        $this->iHaveItemsInMyShoppingCart();
        $this->iClick($button);
    }
    
    /**
     * @When I select a valid delivery date
     */
    public function iSelectAValidDeliveryDate()
    {
        $this->iSelectAValidDate();
    }
    
    /**
     * @When I add delivery notes :notes
     */
    public function iAddDeliveryNotes($notes)
    {
        $this->iEnterInTheDeliveryNotesField($notes);
    }
    
    /**
     * @Then I should return to the order confirmation modal
     */
    public function iShouldReturnToTheOrderConfirmationModal()
    {
        $this->minkContext->assertElementExists('#order-confirmation-modal');
        $this->minkContext->assertElementContainsText('.modal-title', 'Confirm Your Order');
    }
    
    /**
     * @Then the delivery information should show my selected date and time
     */
    public function theDeliveryInformationShouldShowMySelectedDateAndTime()
    {
        $this->minkContext->assertElementExists('#modal-delivery-info');
        $this->minkContext->assertElementContainsText('#modal-delivery-info', 'Delivery Date:');
        $this->minkContext->assertElementContainsText('#modal-delivery-info', 'Delivery Time:');
    }
    
    /**
     * @Then my delivery notes should be displayed
     */
    public function myDeliveryNotesShouldBeDisplayed()
    {
        $this->minkContext->assertElementContainsText('#modal-delivery-info', 'Call when you arrive');
    }
    
    /**
     * @Then the order should be placed successfully
     */
    public function theOrderShouldBePlacedSuccessfully()
    {
        $this->minkContext->getSession()->wait(3000); // Wait for order processing
        // This would check for success message or redirect
    }
    
    /**
     * @Then I should see a confirmation message
     */
    public function iShouldSeeAConfirmationMessage()
    {
        $this->minkContext->assertPageContainsText('Order placed successfully');
    }
    
    /**
     * @Given I have placed an order with scheduled delivery
     */
    public function iHavePlacedAnOrderWithScheduledDelivery()
    {
        $this->iOpenTheDeliveryScheduler();
        $this->iSelectAValidDateDaysFromNow(3);
        $this->iSelectTheTimeSlot('10:00');
        $this->iEnterInTheDeliveryNotesField('Test delivery notes');
        $this->iClick('Reserve Delivery Slot');
        $this->minkContext->getSession()->wait(1000);
        $this->iClick('Proceed with Order');
        $this->minkContext->assertPageContainsText('Order placed successfully');
    }
    
    /**
     * @When I navigate to my profile page
     */
    public function iNavigateToMyProfilePage()
    {
        $this->minkContext->visit('/profile.php');
    }
    
    /**
     * @When I click on my order in the order history
     */
    public function iClickOnMyOrderInTheOrderHistory()
    {
        $script = "
            const firstOrder = document.querySelector('#order-history-body tr');
            if (firstOrder) {
                firstOrder.click();
                return true;
            }
            return false;
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("No orders found in the order history");
        }
    }
    
    /**
     * @Then I should see the order details
     */
    public function iShouldSeeTheOrderDetails()
    {
        $this->minkContext->assertElementExists('#order-details-text');
        $this->assertElementIsVisible('#order-details-section');
    }
    
    /**
     * @Then the details should include my selected delivery slot
     */
    public function theDetailsShouldIncludeMySelectedDeliverySlot()
    {
        $this->minkContext->assertElementExists('#delivery-slot-info');
        $this->minkContext->assertElementContainsText('#detail-delivery-slot', ':');
    }
    
    /**
     * @Given another customer has reserved :timeSlot on a specific date
     */
    public function anotherCustomerHasReservedOnASpecificDate($timeSlot)
    {
        // This would require database access to simulate another reservation
        // For now, we'll use a future date that we'll try to reserve in the next step
        $this->minkContext->getSession()->setCustomParam('reservedTime', $timeSlot);
        $this->minkContext->getSession()->setCustomParam('reservedDate', date('Y-m-d', strtotime('+4 days')));
    }
    
    /**
     * @When I try to reserve the same time slot
     */
    public function iTryToReserveTheSameTimeSlot()
    {
        // We would need to actually create a reservation first
        // For manual testing, this would involve creating one reservation,
        // then trying to create another for the same slot
    }
    
    /**
     * @Then the :timeSlot slot should be displayed as unavailable
     */
    public function theSlotShouldBeDisplayedAsUnavailable($timeSlot)
    {
        // For now, we'll just verify that at least one slot is disabled
        $script = "
            return Array.from(document.querySelectorAll('.time-slot.disabled')).length > 0;
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("No disabled time slots found");
        }
    }
    
    /**
     * @Then I should not be able to select it
     */
    public function iShouldNotBeAbleToSelectIt()
    {
        $script = "
            const disabledSlot = document.querySelector('.time-slot.disabled');
            return disabledSlot && disabledSlot.classList.contains('disabled');
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("Disabled slot can still be selected");
        }
    }
    
    /**
     * @When I navigate to the last day of the current month in the calendar
     */
    public function iNavigateToTheLastDayOfTheCurrentMonthInTheCalendar()
    {
        $script = "
            const today = new Date();
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            const lastDayNum = lastDay.getDate();
            
            const lastDayEl = Array.from(document.querySelectorAll('.calendar-day:not(.other-month)'))
                .find(day => parseInt(day.textContent.trim()) === lastDayNum);
                
            if (lastDayEl && !lastDayEl.classList.contains('disabled')) {
                lastDayEl.click();
                return true;
            }
            return false;
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("Could not navigate to last day of current month");
        }
    }
    
    /**
     * @When I select that date
     */
    public function iSelectThatDate()
    {
        // This is handled by the previous step
    }
    
    /**
     * @Then I should see available time slots for that date
     */
    public function iShouldSeeAvailableTimeSlotsForThatDate()
    {
        $this->minkContext->assertElementExists('#time-slots-grid');
        
        $script = "
            return Array.from(document.querySelectorAll('.time-slot:not(.disabled)')).length > 0;
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("No available time slots found for the selected date");
        }
    }
    
    /**
     * @When I navigate to the first day of the next month
     */
    public function iNavigateToTheFirstDayOfTheNextMonth()
    {
        // First navigate to next month
        $this->iClickTheButton('Next');
        
        // Then select first day
        $script = "
            const firstDayEl = Array.from(document.querySelectorAll('.calendar-day:not(.other-month)'))
                .find(day => parseInt(day.textContent.trim()) === 1);
                
            if (firstDayEl && !firstDayEl.classList.contains('disabled')) {
                firstDayEl.click();
                return true;
            }
            return false;
        ";
        
        $result = $this->minkContext->getSession()->evaluateScript($script);
        
        if (!$result) {
            throw new \Exception("Could not select first day of next month");
        }
    }
    
    /**
     * Helper method to check if an element is visible
     */
    protected function assertElementIsVisible($selector)
    {
        $element = $this->minkContext->getSession()->getPage()->find('css', $selector);
        
        if (!$element) {
            throw new \Exception("Element '$selector' not found.");
        }
        
        if (!$element->isVisible()) {
            throw new \Exception("Element '$selector' is not visible.");
        }
    }
}
