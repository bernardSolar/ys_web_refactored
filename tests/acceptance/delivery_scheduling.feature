Feature: Delivery Scheduling
  As a customer
  I want to schedule delivery of my order
  So that I receive my gardening supplies at a convenient time

  Background:
    Given I am logged in as a registered user
    And I have items in my shopping cart

  Scenario: Navigate through calendar months
    When I click "Place Order"
    And I click "Schedule Delivery" in the confirmation modal
    Then I should see the delivery scheduler modal
    And the current month should be displayed in the calendar

    When I click the "Next" button
    Then the calendar should display the next month
    
    When I click the "Prev" button
    Then the calendar should return to the current month

  Scenario: Sundays are disabled for delivery
    When I open the delivery scheduler
    Then all Sundays in the calendar should be disabled
    And Sundays should be displayed in red
    And all other days should be enabled (except those in the past or < 2 days ahead)

  Scenario: Cannot select dates less than 2 days in advance
    When I open the delivery scheduler
    Then today's date should be disabled
    And tomorrow's date should be disabled
    And dates 2 or more days ahead should be enabled (except Sundays)

  Scenario: Select a valid delivery date
    When I open the delivery scheduler
    And I select a valid date 3 days from now
    Then the date should be highlighted
    And the time slot selection should appear

  Scenario: Select a delivery time slot
    When I open the delivery scheduler
    And I select a valid date
    Then I should see available time slots between 8:00 and 20:00
    And the 13:00 slot should not be available (lunch break)

    When I select the "10:00" time slot
    Then the time slot should be highlighted
    And the selected slot information should be displayed
    And the "Reserve Delivery Slot" button should be enabled

  Scenario: Add delivery notes
    When I have selected a date and time slot
    And I enter "Please leave at the back door" in the delivery notes field
    And I click "Reserve Delivery Slot"
    Then the delivery notes should be saved with my order

  Scenario: Complete end-to-end delivery scheduling flow
    When I click "Place Order" with items in my cart
    And I click "Schedule Delivery" in the confirmation modal
    And I select a valid delivery date
    And I select the "15:00" time slot
    And I add delivery notes "Call when you arrive"
    And I click "Reserve Delivery Slot"
    
    Then I should return to the order confirmation modal
    And the delivery information should show my selected date and time
    And my delivery notes should be displayed
    
    When I click "Proceed with Order"
    Then the order should be placed successfully
    And I should see a confirmation message

  Scenario: View delivery information in order history
    Given I have placed an order with scheduled delivery
    When I navigate to my profile page
    And I click on my order in the order history
    Then I should see the order details
    And the details should include my selected delivery slot
    And my delivery notes should be displayed

  Scenario: Previously reserved time slots are unavailable
    Given another customer has reserved "10:00" on a specific date
    When I try to reserve the same time slot
    Then the "10:00" slot should be displayed as unavailable
    And I should not be able to select it

  Scenario: Handle month boundary edge cases
    When I navigate to the last day of the current month in the calendar
    And I select that date
    Then I should see available time slots for that date
    
    When I navigate to the first day of the next month
    And I select that date
    Then I should see available time slots for that date