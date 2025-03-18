# York Supplies POS Web Version

Web-based version of the Integral POS system, designed for GoDaddy hosting.

## Overview

This is a web-based version of the Python/Dash POS application, converted to use PHP, JavaScript, HTML, and CSS. It maintains all the same functionality as the original application but can be hosted on standard web hosting platforms like GoDaddy.

## Features

- Product browsing by category
- Order management
- Event pricing toggle
- Order history tracking
- Automatic database creation and product import
- User authentication

## Local Development Setup

### Requirements

- PHP 7.4+ with SQLite support
- Web server (Apache, Nginx, or PHP's built-in server)
- Modern web browser

### Running Locally

1. Clone the repository or download the files
2. Navigate to the `ys_web` directory
3. Start the PHP development server:
   ```
   php -S localhost:8000
   ```
4. Open your browser and navigate to http://localhost:8000

### Database Initialization

The application will automatically:
1. Create a SQLite database if it doesn't exist
2. Initialize the database schema
3. Import products from a CSV file if present

To test this feature, you can:
1. Rename or remove the existing `products.db` file
2. Ensure a `products.csv` file is available in the main directory
3. Restart the application

---
