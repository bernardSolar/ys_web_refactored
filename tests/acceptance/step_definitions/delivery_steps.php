<?php
/**
 * Delivery Scheduling Steps
 * 
 * Step definitions for Behat tests related to delivery scheduling feature
 */

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Features context for Delivery Scheduling feature
 */
class DeliveryFeatureContext extends MinkContext implements Context
{
    /**
     * @Given I am logged in as a registered user
     */
    public function iAmLoggedInAsARegisteredUser()
    {
        // Visit login page
        $this->visit('/login.php');
        
        // Fill in login form
        $this->fillField('username', 'testuser');
        $this->fillField('password', 'password123');
        
        // Submit login form
        $this->pressButton('Login');
        
        // Verify login success
        $this->assertPageContainsText('Order Summary');
    }
    
    /**
     * @Given I have items in my shopping cart
     */
    public function iHaveItemsInMyShoppingCart()
    {
        // Visit main page
        $this->visit('/index.php');
        
        // Click on a product to add to cart
        $this->clickLink('Sylvagrow Ericaceous 40ltr');
        
        // Verify the product is in the cart
        $this->assertElementContainsText('#order-list', 'Sylvagrow Ericaceous 40ltr');
    }
    
    /**
     * @When I click :button
     */
    public function iClick($button)
    {
        $this->pressButton($button);
    }
    
    /**
     * @When I click :linkText in the confirmation modal
     */
    public function iClickInTheConfirmationModal($linkText)
    {
        $this->getSession()->wait(1000);
        $this->pressButton($linkText);
    }
    
    /**
     * @Then I should see the delivery scheduler modal
     */
    public function iShouldSeeTheDeliverySchedulerModal()
    {
        $this->assertElementContainsText('.modal-title', 'Reserve a Delivery Slot');
    }
    
    /**
     * @Then the current month should be displayed in the calendar
     */
    public function theCurrentMonthShouldBeDisplayedInTheCalendar()
    {
        $currentMonth = date('F Y');
        $this->assertElementContainsText('#calendar-month-year', $currentMonth);
    }
    
    /**
     * @When I open the delivery scheduler
     */
    public function iOpenTheDeliveryScheduler()
    {
        $this->visit('/index.php');
        $this->clickLink('Sylvagrow Ericaceous 40ltr');
        $this->pressButton('Place Order');
        $this->getSession()->wait(1000);
        $this->pressButton('Schedule Delivery');
    }
    
    /**
     * @Then all Sundays in the calendar should be disabled
     */
    public function allSundaysInTheCalendarShouldBeDisabled()
    {
        $this->getSession()->evaluateScript(
            "return document.querySelectorAll('.calendar-day')[0].classList.contains('disabled');"
        );
    }
    
    /**
     * @Then Sundays should be displayed in red
     */
    public function sundaysShouldBeDisplayedInRed()
    {
        $this->getSession()->evaluateScript(
            "return document.querySelectorAll('#calendar-weekdays div')[0].style.color === 'rgb(220, 53, 69)';"
        );
    }
    
    /**
     * @When I select a valid date :days days from now
     */
    public function iSelectAValidDateDaysFromNow($days)
    {
        $futureDate = date('j', strtotime("+$days days"));
        
        $this->getSession()->executeScript(
            "Array.from(document.querySelectorAll('.calendar-day')).find(el => !el.classList.contains('disabled') && el.textContent.trim() === '$futureDate').click();"
        );
    }
    
    /**
     * @Then the date should be highlighted
     */
    public function theDateShouldBeHighlighted()
    {
        $this->getSession()->evaluateScript(
            "return document.querySelector('.calendar-day.selected') !== null;"
        );
    }
    
    /**
     * @Then the time slot selection should appear
     */
    public function theTimeSlotSelectionShouldAppear()
    {
        $this->assertElementExists('#time-slots-grid');
        $this->assertElementExists('.time-slot');
    }
    
    /**
     * @When I select the :timeSlot time slot
     */
    public function iSelectTheTimeSlot($timeSlot)
    {
        $this->getSession()->executeScript(
            "Array.from(document.querySelectorAll('.time-slot')).find(el => el.textContent.trim() === '$timeSlot').click();"
        );
    }
    
    /**
     * @Then the time slot should be highlighted
     */
    public function theTimeSlotShouldBeHighlighted()
    {
        $this->getSession()->evaluateScript(
            "return document.querySelector('.time-slot.selected') !== null;"
        );
    }
    
    /**
     * @Then the selected slot information should be displayed
     */
    public function theSelectedSlotInformationShouldBeDisplayed()
    {
        $this->assertElementExists('#selected-slot-info');
        $this->assertElementNotHasClass('#selected-slot-info', 'hidden');
    }
    
    /**
     * @When I enter :notes in the delivery notes field
     */
    public function iEnterInTheDeliveryNotesField($notes)
    {
        $this->fillField('delivery-notes', $notes);
    }
    
    /**
     * @Then I should return to the order confirmation modal
     */
    public function iShouldReturnToTheOrderConfirmationModal()
    {
        $this->assertElementExists('#order-confirmation-modal');
        $this->assertElementContainsText('.modal-title', 'Confirm Your Order');
    }
    
    /**
     * @Then the delivery information should show my selected date and time
     */
    public function theDeliveryInformationShouldShowMySelectedDateAndTime()
    {
        $this->assertElementExists('#modal-delivery-info');
        $this->assertElementContainsText('#modal-delivery-info', 'Delivery Date:');
        $this->assertElementContainsText('#modal-delivery-info', 'Delivery Time:');
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
        $this->getSession()->wait(1000);
        $this->iClick('Proceed with Order');
        $this->assertPageContainsText('Order placed successfully');
    }
}
