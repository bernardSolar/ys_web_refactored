/**
 * Product Manager Class
 * Handles all product-related operations like fetching, finding, etc.
 */
class ProductManager {
    constructor() {
        // Base URL for API calls
        this.apiBase = 'api';
    }
    
    /**
     * Fetch all products from the API
     * @returns {Promise} Promise that resolves to the products object
     */
    fetchAllProducts() {
        return fetch(`${this.apiBase}/products.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            });
    }
    
    /**
     * Fetch popular products from the API
     * @param {number} days - Number of days to look back for popularity
     * @param {number} limit - Maximum number of products to return
     * @returns {Promise} Promise that resolves to the popular products array
     */
    fetchPopularProducts(days = 90, limit = 15) {
        return fetch(`${this.apiBase}/popular_products.php?days=${days}&limit=${limit}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            });
    }
    
    /**
     * Find a product by name and category
     * @param {object} products - The products object
     * @param {string} category - The category to search in
     * @param {string} productName - The name of the product to find
     * @returns {array|null} The product array or null if not found
     */
    findProduct(products, category, productName) {
        if (!products[category]) return null;
        
        for (const product of products[category]) {
            if (product[0] === productName) {
                return product;
            }
        }
        
        return null;
    }
    
    /**
     * Get adjusted price based on event pricing
     * @param {number} basePrice - The base price of the product
     * @param {boolean} eventPricingActive - Whether event pricing is active
     * @returns {number} The adjusted price
     */
    getAdjustedPrice(basePrice, eventPricingActive) {
        return eventPricingActive ? basePrice * 1.1 : basePrice;
    }
    
    /**
     * Format price as currency string
     * @param {number} price - The price to format
     * @returns {string} Formatted price with currency symbol
     */
    formatPrice(price) {
        return `£${price.toFixed(2)}`;
    }
}
