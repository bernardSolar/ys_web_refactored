/**
 * Profile Page JavaScript
 * Handles user profile data, order history, and order details display
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the profile page
    const profilePage = new ProfilePage();
    profilePage.initialize();
});

class ProfilePage {
    constructor() {
        this.userData = null;
        this.userOrders = [];
        this.isAdmin = false;
        this.selectedOrderId = null;
    }
    
    initialize() {
        // Set up event listeners
        this.setupEventListeners();
        
        // Load user profile data
        this.loadUserProfile();
        
        // Load order history
        this.loadOrderHistory();
    }
    
    setupEventListeners() {
        // Logout button
        document.getElementById('logout-button').addEventListener('click', () => {
            this.logout();
        });
        
        // Admin search functionality
        const searchButton = document.getElementById('search-button');
        if (searchButton) {
            searchButton.addEventListener('click', () => {
                this.filterOrders();
            });
        }
        
        // Admin reset button
        const resetButton = document.getElementById('reset-button');
        if (resetButton) {
            resetButton.addEventListener('click', () => {
                document.getElementById('search-input').value = '';
                this.loadOrderHistory();
            });
        }
    }
    
    loadUserProfile() {
        fetch('api/user_profile.php?action=profile')
            .then(response => {
                if (!response.ok) {
                    if (response.status === 401) {
                        // Not logged in, redirect to login page
                        window.location.href = 'login.php';
                        throw new Error('Not logged in');
                    }
                    return response.json().then(err => { throw new Error(err.message || 'Unknown error'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.user) {
                    this.userData = data.user;
                    this.isAdmin = data.user.role === 'admin';
                    
                    // Display user information
                    this.displayUserInfo(data.user);
                    
                    // Show admin controls if user is admin
                    if (this.isAdmin) {
                        document.getElementById('admin-controls').classList.remove('hidden');
                    }
                }
            })
            .catch(error => {
                console.error('Error loading profile:', error);
                alert('Error loading profile data: ' + error.message);
            })
            .finally(() => {
                // Hide loading indicator
                document.getElementById('loading-indicator').classList.add('hidden');
                document.getElementById('user-info-section').classList.remove('hidden');
                document.getElementById('order-history-section').classList.remove('hidden');
            });
    }
    
    displayUserInfo(user) {
        // Set user information in the UI
        document.getElementById('user-name').textContent = user.rep_name;
        document.getElementById('user-organisation').textContent = user.organisation;
        document.getElementById('user-email').textContent = user.email;
        document.getElementById('user-address').textContent = user.delivery_address;
        document.getElementById('user-delivery-charge').textContent = parseFloat(user.delivery_charge).toFixed(2);
    }
    
    loadOrderHistory() {
        // Construct URL based on admin status
        let url = 'api/user_profile.php?action=orders';
        if (this.isAdmin && document.getElementById('search-input').value) {
            // If admin is searching, we'll filter on the client side
        }
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Failed to load orders'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.userOrders = data.orders;
                    this.displayOrderHistory(this.userOrders);
                }
            })
            .catch(error => {
                console.error('Error loading orders:', error);
                alert('Error loading order history: ' + error.message);
            });
    }
    
    displayOrderHistory(orders) {
        const tableBody = document.getElementById('order-history-body');
        const noOrdersMessage = document.getElementById('no-orders-message');
        
        // Clear existing content
        tableBody.innerHTML = '';
        
        // Check if orders exist
        if (orders.length === 0) {
            noOrdersMessage.classList.remove('hidden');
            return;
        }
        
        // Hide no orders message
        noOrdersMessage.classList.add('hidden');
        
        // Add orders to table
        orders.forEach(order => {
            const row = document.createElement('tr');
            row.dataset.orderId = order.order_id;
            
            // Format the date
            const orderDate = new Date(order.order_datetime.replace(' ', 'T'));
            const formattedDate = orderDate.toLocaleString();
            
            // Total amount with delivery charge included
            const totalAmount = parseFloat(order.total_amount).toFixed(2);
            
            row.innerHTML = `
                <td>${order.order_id}</td>
                <td>${formattedDate}</td>
                <td>${order.organisation || 'N/A'}</td>
                <td>£${totalAmount}</td>
            `;
            
            // Add click event to show order details
            row.addEventListener('click', () => {
                this.showOrderDetails(order.order_id);
                
                // Highlight the selected row
                const allRows = tableBody.querySelectorAll('tr');
                allRows.forEach(r => r.classList.remove('active'));
                row.classList.add('active');
            });
            
            tableBody.appendChild(row);
        });
    }
    
    showOrderDetails(orderId) {
        // Save selected order ID
        this.selectedOrderId = orderId;
        
        fetch(`api/user_profile.php?action=order_details&order_id=${orderId}`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Failed to load order details'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.order) {
                    const order = data.order;
                    
                    // Format the date
                    const orderDate = new Date(order.order_datetime.replace(' ', 'T'));
                    const formattedDate = orderDate.toLocaleString();
                    
                    // Update the order details section
                    document.getElementById('detail-order-id').textContent = order.order_id;
                    document.getElementById('detail-date').textContent = formattedDate;
                    document.getElementById('detail-total').textContent = parseFloat(order.total_amount).toFixed(2);
                    
                    // Handle order_text content - try to parse as JSON
                    try {
                        // Try to parse as JSON
                        const orderDetails = JSON.parse(order.order_text);
                        this.displayFormattedOrderDetails(orderDetails);
                    } catch (e) {
                        // If not valid JSON, display as is (legacy format)
                        document.getElementById('order-details-text').textContent = order.order_text;
                    }
                    
                    // Show the order details section
                    document.getElementById('order-details-section').classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error loading order details:', error);
                alert('Error loading order details: ' + error.message);
            });
    }
    
    displayFormattedOrderDetails(orderDetails) {
        const detailsContainer = document.getElementById('order-details-text');
        
        // Clear previous content
        detailsContainer.innerHTML = '';
        
        if (!orderDetails || !orderDetails.items) {
            detailsContainer.textContent = 'No details available';
            return;
        }
        
        // Format and display items
        let formattedText = '';
        
        // Items section
        orderDetails.items.forEach((item, index) => {
            const quantity = item.count || item.quantity || 1;
            const itemTotal = item.price * quantity;
            formattedText += `${index + 1}. ${item.name} (x${quantity})    £${parseFloat(item.price).toFixed(2)} each\n`;
            formattedText += `   Subtotal: £${itemTotal.toFixed(2)}\n`;
        });
        
        formattedText += '\n';
        
        // Subtotals by category if present
        if (orderDetails.subtotals && Object.keys(orderDetails.subtotals).length > 0) {
            formattedText += 'Category Subtotals:\n';
            for (const [category, amount] of Object.entries(orderDetails.subtotals)) {
                formattedText += `   ${category}: £${parseFloat(amount).toFixed(2)}\n`;
            }
            formattedText += '\n';
        }
        
        // Summary section
        if (orderDetails.summary) {
            formattedText += `Subtotal: £${parseFloat(orderDetails.summary.subtotal).toFixed(2)}\n`;
            
            // Add delivery charge from summary if present
            if (orderDetails.summary.delivery_charge !== undefined) {
                formattedText += `Delivery Charge: £${parseFloat(orderDetails.summary.delivery_charge).toFixed(2)}\n`;
            }
            
            // Total from summary
            formattedText += `\nTotal: £${parseFloat(orderDetails.summary.total).toFixed(2)}\n`;
        }
        
        // Add delivery info if present
        if (orderDetails.delivery_info) {
            formattedText += '\nDelivery Information:\n';
            formattedText += `   Delivery Charge: £${parseFloat(orderDetails.delivery_info.delivery_charge).toFixed(2)}\n`;
            
            if (orderDetails.delivery_info.delivery_address) {
                formattedText += `   Delivery Address: ${orderDetails.delivery_info.delivery_address}\n`;
            }
            
            if (orderDetails.delivery_info.organisation) {
                formattedText += `   Organisation: ${orderDetails.delivery_info.organisation}\n`;
            }
        }
        
        // Add contact info if present
        if (orderDetails.contact_info) {
            formattedText += '\nContact Information:\n';
            if (orderDetails.contact_info.name) {
                formattedText += `   Name: ${orderDetails.contact_info.name}\n`;
            }
            if (orderDetails.contact_info.email) {
                formattedText += `   Email: ${orderDetails.contact_info.email}\n`;
            }
        }
        
        formattedText += '\nThank you for your order!';
        
        detailsContainer.textContent = formattedText;
    }
    
    filterOrders() {
        if (!this.isAdmin) return;
        
        const searchText = document.getElementById('search-input').value.toLowerCase();
        
        if (!searchText) {
            this.loadOrderHistory();
            return;
        }
        
        // Filter orders by organisation name
        const filteredOrders = this.userOrders.filter(order => {
            return order.organisation && order.organisation.toLowerCase().includes(searchText);
        });
        
        this.displayOrderHistory(filteredOrders);
    }
    
    logout() {
        fetch('api/authenticate.php', {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'login.php';
            }
        })
        .catch(error => {
            console.error('Logout error:', error);
            alert('Error logging out. Please try again.');
        });
    }
}