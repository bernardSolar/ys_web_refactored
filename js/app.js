/**
 * Main POS Application
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the application
    const posApp = new POSApplication();
    posApp.initialize();
});

class POSApplication {
    constructor() {
        this.productManager = new ProductManager();
        this.orderManager = new OrderManager();
        this.uiManager = new UIManager(this);
        this.currentOrder = [];
        this.products = {};
        this.popularProducts = {};
        this.userData = null;
        this.deliveryCharge = 0;
    }
    
    initialize() {
        // Fetch user data from the API first to ensure we have the latest data
        this.fetchUserData().then(() => {
            // Load products and set up event handlers
            this.loadProducts();
            this.setupEventListeners();
            
            // Debug user data
            console.log("User data loaded:", this.userData);
            console.log("Delivery charge:", this.deliveryCharge);
        });
    }
    
    /**
     * Fetch user data from the API
     */
    fetchUserData() {
        return fetch('api/authenticate.php')
            .then(response => {
                if (!response.ok && response.status === 401) {
                    // Not logged in, redirect to login page
                    window.location.href = 'login.php';
                    throw new Error('Not logged in');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.user) {
                    this.userData = data.user;
                    // Explicitly parse delivery charge as float to ensure it's a number
                    this.deliveryCharge = parseFloat(data.user.delivery_charge || 0);
                    console.log("User delivery charge set to:", this.deliveryCharge);
                    return data.user;
                }
                return null;
            })
            .catch(error => {
                console.error('Error getting user data:', error);
                return null;
            });
    }
    
    loadProducts() {
        // First fetch all regular products
        this.productManager.fetchAllProducts()
            .then(products => {
                this.products = products;
                
                // Then fetch popular products for the Home tab
                return this.productManager.fetchPopularProducts();
            })
            .then(popularData => {
                // Store popular products for the Home tab
                this.products['Home'] = popularData.Home;
                
                // Render the UI
                this.uiManager.renderCategoryTabs(this.products);
                this.uiManager.renderProductGrid('Home');
            })
            .catch(error => {
                console.error('Error loading products:', error);
                alert('Failed to load products. Please check the console for details.');
            });
    }
    
    setupEventListeners() {
        // Place order button
        document.getElementById('place-order-button').addEventListener('click', () => {
            this.showOrderConfirmation();
        });
        
        // Modal actions
        const modalProceedButton = document.getElementById('modal-proceed-button');
        if (modalProceedButton) {
            modalProceedButton.addEventListener('click', () => {
                this.processOrder();
            });
        }
    }
    
    showOrderConfirmation() {
        if (this.currentOrder.length === 0) {
            alert('Please add items to your order first.');
            return;
        }
        
        // Calculate total
        const subtotal = this.orderManager.calculateTotal(this.currentOrder);
        const totalWithDelivery = this.orderManager.calculateTotalWithDelivery(subtotal, this.deliveryCharge);
        
        // Make sure we have the latest delivery charge from userData
        const currentDeliveryCharge = this.userData ? parseFloat(this.userData.delivery_charge || 0) : 0;
        
        // Populate the modal - ensure the delivery charge is passed correctly
        this.uiManager.populateOrderModal(
            this.currentOrder, 
            currentDeliveryCharge,
            this.userData
        );
        
        // Show the modal
        const modalElem = document.getElementById('order-confirmation-modal');
        if (modalElem) {
            const modal = new bootstrap.Modal(modalElem);
            modal.show();
        }
    }
    
    processOrder() {
        if (this.currentOrder.length === 0) return;
        
        // Calculate total without delivery charge (backend will add it)
        const subtotal = this.orderManager.calculateTotal(this.currentOrder);
        
        // Prepare order data
        const orderData = {
            items: this.currentOrder,
            total: subtotal
        };
        
        // Add delivery info if available
        if (window.deliveryInfo) {
            orderData.delivery_date = window.deliveryInfo.date;
            orderData.delivery_time = window.deliveryInfo.time;
            orderData.delivery_notes = window.deliveryInfo.notes;
            orderData.slot_id = window.deliveryInfo.slotId;
        }
        
        // Save order to database via API
        this.orderManager.placeOrder(orderData)
            .then(response => {
                if (response.success) {
                    // Close the modal
                    const modalElem = document.getElementById('order-confirmation-modal');
                    if (modalElem) {
                        const modal = bootstrap.Modal.getInstance(modalElem);
                        if (modal) modal.hide();
                    }
                    
                    // Clear the order and any stored delivery info
                    this.currentOrder = [];
                    window.deliveryInfo = null;
                    this.uiManager.updateOrderDisplay(this.currentOrder, this.deliveryCharge);
                    
                    // Show success message
                    let message = 'Order placed successfully!';
                    if (response.orderInfo && response.orderInfo.totalWithDelivery) {
                        message += ` Total: £${response.orderInfo.totalWithDelivery.toFixed(2)}`;
                    }
                    
                    // Add delivery confirmation if available
                    if (response.orderInfo && response.orderInfo.deliveryDate) {
                        const deliveryDate = new Date(response.orderInfo.deliveryDate);
                        message += `\nDelivery scheduled for: ${deliveryDate.toLocaleDateString('en-GB')} at ${response.orderInfo.deliveryTime}`;
                    }
                    
                    alert(message);
                    
                    // Refresh products including popular products
                    this.loadProducts();
                } else {
                    alert('Failed to place order: ' + response.message);
                }
            })
            .catch(error => {
                console.error('Error placing order:', error);
                alert('Error placing order. Please try again.');
            });
    }
    
    addProductToOrder(product) {
        this.currentOrder = this.orderManager.addToOrder(
            this.currentOrder,
            product
        );
        
        this.uiManager.updateOrderDisplay(this.currentOrder, this.deliveryCharge);
    }
    
    removeProductFromOrder(index) {
        this.currentOrder = this.orderManager.removeFromOrder(this.currentOrder, index);
        this.uiManager.updateOrderDisplay(this.currentOrder, this.deliveryCharge);
    }
}