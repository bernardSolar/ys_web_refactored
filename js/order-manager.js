/**
 * Order Manager Class
 * Handles all order-related operations like adding items, removing items, calculating totals
 */
class OrderManager {
    constructor() {
        // Base URL for API calls
        this.apiBase = 'api';
    }
    
    /**
     * Add a product to the order
     * @param {array} currentOrder - The current order
     * @param {array} product - The product to add [name, price, sku, stock, id]
     * @returns {array} The updated order
     */
    addToOrder(currentOrder, product) {
        const [name, basePrice, sku, stock, id] = product;
        const category = product[5]; // Category is passed as the 6th element
        
        // Use base price directly
        const price = basePrice;
        
        // Check if product is already in the order
        const existingItem = currentOrder.find(item => item.name === name);
        
        if (existingItem) {
            // Increment count if already in order
            existingItem.count += 1;
        } else {
            // Add new item to order
            currentOrder.push({
                name,
                price,
                sku,
                stock,
                id,
                category,
                count: 1
            });
        }
        
        return currentOrder;
    }
    
    /**
     * Remove an item from the order
     * @param {array} currentOrder - The current order
     * @param {number} index - The index of the item to remove
     * @returns {array} The updated order
     */
    removeFromOrder(currentOrder, index) {
        if (index < 0 || index >= currentOrder.length) {
            return currentOrder;
        }
        
        if (currentOrder[index].count > 1) {
            // Decrease count if more than one
            currentOrder[index].count -= 1;
        } else {
            // Remove item if only one
            currentOrder.splice(index, 1);
        }
        
        return currentOrder;
    }
    
    /**
     * Calculate the total for an order
     * @param {array} orderItems - The items in the order
     * @returns {number} The total price
     */
    calculateTotal(orderItems) {
        return orderItems.reduce((total, item) => {
            return total + (item.price * item.count);
        }, 0);
    }
    
    /**
     * Calculate the total with delivery charge
     * @param {number} subtotal - The order subtotal
     * @param {number} deliveryCharge - The delivery charge
     * @returns {number} The total price with delivery
     */
    calculateTotalWithDelivery(subtotal, deliveryCharge) {
        return subtotal + (deliveryCharge || 0);
    }
    
    /**
     * Place an order via the API
     * @param {array} orderItems - The items in the order
     * @param {number} total - The total price
     * @returns {Promise} Promise that resolves to the API response
     */
    placeOrder(orderItems, total) {
        return fetch(`${this.apiBase}/place_order.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                items: orderItems,
                total: total
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        });
    }
}