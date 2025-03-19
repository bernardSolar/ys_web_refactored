# Acceptance Tests for York Supplies

This directory contains acceptance tests written in Gherkin format for Behavior-Driven Development (BDD) testing of the York Supplies application.

## About These Tests

These tests are written in Gherkin syntax (Given-When-Then format) to:

1. Document expected application behavior
2. Serve as acceptance criteria for features
3. Provide a foundation for automated testing

## Running the Tests

These tests are designed to be run with a BDD testing framework such as Cucumber, Behat, or similar tools.

### Prerequisites

- PHP 7.4 or higher
- Composer
- Behat (for PHP-based testing)

### Installation

1. Install Behat and dependencies:
   ```
   composer require --dev behat/behat
   composer require --dev behat/mink
   composer require --dev behat/mink-extension
   composer require --dev behat/mink-selenium2-driver
   ```

2. Initialize Behat:
   ```
   vendor/bin/behat --init
   ```

3. Configure Behat by creating a `behat.yml` file in the project root with appropriate settings.

### Running Tests

Execute all tests:
```
vendor/bin/behat
```

Run a specific feature file:
```
vendor/bin/behat tests/acceptance/delivery_scheduling.feature
```

Run a specific scenario (by line number):
```
vendor/bin/behat tests/acceptance/delivery_scheduling.feature:25
```

## Test Structure

- Each `.feature` file represents a major feature of the application
- The `Background` section sets up common preconditions for all scenarios
- Each scenario tests a specific aspect of functionality
- Scenarios are written from a user's perspective

## Implementing Step Definitions

Step definitions need to be created in the `features/bootstrap` directory to implement the behavior described in the feature files.

## Manual Testing

These scenarios can also be executed manually by following the steps in each scenario. This is helpful for:

1. Initial application testing
2. Verifying specific user flows
3. Exploratory testing to identify edge cases

## Adding New Tests

1. Create a new `.feature` file for each major feature
2. Use descriptive scenario names
3. Keep steps clear and focused on user actions
4. Ensure preconditions are properly set up
5. Verify observable outcomes in "Then" steps