/**
 * UI Manager Class
 * Handles all UI-related operations like rendering products, updating the order display, etc.
 */
class UIManager {
    constructor(app) {
        this.app = app;
    }
    
    /**
     * Render category tabs from products data
     * @param {object} products - The products object with categories as keys
     */
    renderCategoryTabs(products) {
        const tabsContainer = document.getElementById('categoryTabs');
        const tabContentContainer = document.getElementById('categoryTabContent');
        
        // Clear existing tabs
        tabsContainer.innerHTML = '';
        tabContentContainer.innerHTML = '';
        
        // Debug: Log all category keys and products
        console.log("Available categories:", Object.keys(products));
        Object.keys(products).forEach(cat => {
            console.log(`Category '${cat}' has ${products[cat].length} products`);
        });
        
        // Create tabs for each category
        Object.keys(products).forEach((category, index) => {
            // Skip the Home tab for now; we'll add it first
            if (category === 'Home') return;
            
            // Create tab
            const tab = document.createElement('li');
            tab.className = 'nav-item';
            tab.role = 'presentation';
            
            const button = document.createElement('button');
            button.className = 'nav-link';
            button.id = `${category.toLowerCase().replace(/[^a-z0-9]/g, '-')}-tab`;
            button.setAttribute('data-bs-toggle', 'tab');
            button.setAttribute('data-bs-target', `#${category.toLowerCase().replace(/[^a-z0-9]/g, '-')}`);
            button.type = 'button';
            button.role = 'tab';
            button.setAttribute('aria-controls', category.toLowerCase().replace(/[^a-z0-9]/g, '-'));
            button.setAttribute('aria-selected', 'false');
            button.textContent = category;
            
            tab.appendChild(button);
            
            // Create tab content
            const tabContent = document.createElement('div');
            tabContent.className = 'tab-pane fade';
            tabContent.id = category.toLowerCase().replace(/[^a-z0-9]/g, '-');
            tabContent.role = 'tabpanel';
            tabContent.setAttribute('aria-labelledby', `${category.toLowerCase().replace(/[^a-z0-9]/g, '-')}-tab`);
            
            // Add tab and content to containers
            tabsContainer.appendChild(tab);
            tabContentContainer.appendChild(tabContent);
            
            // Add event listener to load products when tab is clicked
            button.addEventListener('click', () => {
                console.log(`Loading products for category: ${category}`);
                this.renderProductGrid(category);
            });
        });
        
        // Add Home tab first
        const homeTab = document.createElement('li');
        homeTab.className = 'nav-item';
        homeTab.role = 'presentation';
        
        const homeButton = document.createElement('button');
        homeButton.className = 'nav-link active';
        homeButton.id = 'home-tab';
        homeButton.setAttribute('data-bs-toggle', 'tab');
        homeButton.setAttribute('data-bs-target', '#home');
        homeButton.type = 'button';
        homeButton.role = 'tab';
        homeButton.setAttribute('aria-controls', 'home');
        homeButton.setAttribute('aria-selected', 'true');
        homeButton.textContent = 'Home';
        
        homeTab.appendChild(homeButton);
        
        const homeContent = document.createElement('div');
        homeContent.className = 'tab-pane fade show active';
        homeContent.id = 'home';
        homeContent.role = 'tabpanel';
        homeContent.setAttribute('aria-labelledby', 'home-tab');
        
        // Insert at the beginning of the lists
        tabsContainer.insertBefore(homeTab, tabsContainer.firstChild);
        tabContentContainer.insertBefore(homeContent, tabContentContainer.firstChild);
        
        // Add event listener for Home tab
        homeButton.addEventListener('click', () => {
            console.log('Loading Home products');
            this.renderProductGrid('Home');
        });
    }
    
    /**
     * Render the product grid for a category
     * @param {string} category - The category to render
     */
    renderProductGrid(category) {
        const products = this.app.products;
        const safeCategoryId = category.toLowerCase().replace(/[^a-z0-9]/g, '-');
        const tabContent = document.getElementById(safeCategoryId);
        
        console.log(`Rendering products for '${category}' (element ID: ${safeCategoryId})`);
        console.log(`Tab content element:`, tabContent);
        
        if (!tabContent) {
            console.error(`Tab content element not found for category: ${category}`);
            return;
        }
        
        // Check if we have products for this category
        if (!products[category] || products[category].length === 0) {
            console.warn(`No products found for category: ${category}`);
            
            // Try case-insensitive match if no products found
            const caseInsensitiveMatch = Object.keys(products).find(cat => 
                cat.toLowerCase() === category.toLowerCase() && cat !== category
            );
            
            if (caseInsensitiveMatch) {
                console.log(`Found case-insensitive match: '${caseInsensitiveMatch}'`);
                category = caseInsensitiveMatch;
            }
        }
        
        const isHome = category === 'Home';
        
        // Clear existing content
        tabContent.innerHTML = '';

        // Add header only for Home tab
        if (isHome) {
            const header = document.createElement('h5');
            header.textContent = 'Most Popular Products';
            header.style.marginBottom = '10px';
            header.style.marginTop = '10px';
            header.style.paddingLeft = '10px';
            tabContent.appendChild(header);
        }
        
        // Create grid container
        const gridContainer = document.createElement('div');
        gridContainer.className = 'product-grid';
        tabContent.appendChild(gridContainer);
        
        // Add products to grid
        if (products[category] && products[category].length > 0) {
            console.log(`Found ${products[category].length} products for '${category}'`);
            products[category].forEach(product => {
                this.addProductToGrid(gridContainer, product, category);
            });
        } else {
            console.warn(`No products found for '${category}' after checking`);
            const noProducts = document.createElement('div');
            noProducts.textContent = 'No products found in this category.';
            noProducts.style.padding = '20px';
            noProducts.style.textAlign = 'center';
            noProducts.style.width = '100%';
            gridContainer.appendChild(noProducts);
        }
    }
    
    /**
     * Add a product to the grid
     * @param {HTMLElement} container - The container to add to
     * @param {array} product - The product data [name, price, sku, stock, id]
     * @param {string} category - The product's category
     */
    addProductToGrid(container, product, category) {
        const [name, price, sku, stock, id] = product;
        
        // Create grid item
        const gridItem = document.createElement('div');
        gridItem.className = 'product-grid-item';
        
        // Create button
        const button = document.createElement('button');
        button.className = 'btn btn-outline-primary product-button';
        button.dataset.category = category;
        button.dataset.name = name;
        button.dataset.price = price;
        button.dataset.sku = sku;
        button.dataset.stock = stock;
        button.dataset.id = id;
        
        // Format button content
        const productName = document.createElement('span');
        productName.style.textAlign = 'left';
        productName.style.width = '100%'; // This ensures the full width is available for alignment
        productName.textContent = name;
        
        const productPrice = document.createElement('span');
        productPrice.className = 'product-price';
        productPrice.textContent = `£${parseFloat(price).toFixed(2)}`;
        
        button.appendChild(productName);
        button.appendChild(productPrice);
        
        // Add event listener
        button.addEventListener('click', () => {
            // Add product to order
            const productData = [name, parseFloat(price), sku, parseInt(stock), id, category];
            this.app.addProductToOrder(productData);
        });
        
        gridItem.appendChild(button);
        container.appendChild(gridItem);
    }
    
    /**
     * Update the order display
     * @param {array} orderItems - The items in the order
     * @param {number} deliveryCharge - The delivery charge (optional)
     */
    updateOrderDisplay(orderItems, deliveryCharge = 0) {
        // Get elements
        const orderList = document.getElementById('order-list');
        const orderTotalElem = document.getElementById('order-total');
        const orderTotalTopElem = document.getElementById('order-total-top');
        const deliveryChargeInfoElem = document.getElementById('delivery-charge-info');
        
        // Hide the delivery charge info element since we're moving it to the modal
        if (deliveryChargeInfoElem) {
            deliveryChargeInfoElem.style.display = 'none';
        }
        
        // Clear existing items
        orderList.innerHTML = '';
        
        if (orderItems.length === 0) {
            // Show no items message
            const noItems = document.createElement('div');
            noItems.className = 'no-items';
            noItems.textContent = 'No items selected.';
            orderList.appendChild(noItems);
            
            // Update totals
            orderTotalElem.textContent = 'Total: £0.00';
            orderTotalTopElem.textContent = 'Total: £0.00';
            return;
        }
        
        // Calculate subtotal
        let subtotal = 0;
        
        // Add each item to the display
        orderItems.forEach((item, index) => {
            const itemElem = document.createElement('div');
            itemElem.className = 'order-item';
            
            // Item row with item details and remove button
            const itemRow = document.createElement('div');
            itemRow.className = 'row align-items-center';
            
            // Item details column
            const detailsCol = document.createElement('div');
            detailsCol.className = 'col-8';
            
            const itemName = document.createElement('div');
            itemName.innerHTML = `<span style="font-size: 20px;">${index + 1}. ${item.name} (x${item.count})</span>`;
            
            const itemPrice = document.createElement('div');
            itemPrice.innerHTML = `<span style="font-size: 16px;">£${item.price.toFixed(2)} each</span>`;
            
            const itemSubtotal = document.createElement('div');
            const lineSubtotal = item.price * item.count;
            itemSubtotal.innerHTML = `<span style="font-size: 16px;">Subtotal: £${lineSubtotal.toFixed(2)}</span>`;
            
            detailsCol.appendChild(itemName);
            detailsCol.appendChild(itemPrice);
            detailsCol.appendChild(itemSubtotal);
            
            // Remove button column
            const buttonCol = document.createElement('div');
            buttonCol.className = 'col-4 text-end';
            
            const removeButton = document.createElement('button');
            removeButton.className = 'btn btn-danger btn-sm';
            removeButton.textContent = 'Remove';
            removeButton.addEventListener('click', () => {
                this.app.removeProductFromOrder(index);
            });
            
            buttonCol.appendChild(removeButton);
            
            // Add columns to row
            itemRow.appendChild(detailsCol);
            itemRow.appendChild(buttonCol);
            
            // Add row to item element
            itemElem.appendChild(itemRow);
            
            // Add item to order list
            orderList.appendChild(itemElem);
            
            // Add to subtotal
            subtotal += lineSubtotal;
        });
        
        // Calculate final total with delivery
        const totalWithDelivery = subtotal + deliveryCharge;
        
        // Update totals - only show the subtotal on the main screen
        // Delivery charge info will be shown in the modal
        orderTotalElem.textContent = `Total: £${subtotal.toFixed(2)}`;
        orderTotalTopElem.textContent = `Total: £${subtotal.toFixed(2)}`;
    }
    
    /**
     * Populate the order confirmation modal
     * @param {array} orderItems - The items in the order
     * @param {number} deliveryCharge - The delivery charge
     * @param {object} userData - User data for delivery information
     */
    populateOrderModal(orderItems, deliveryCharge = 0, userData = null) {
        const modalSummary = document.getElementById('modal-order-summary');
        const modalTotal = document.getElementById('modal-order-total');
        const modalDeliveryInfo = document.getElementById('modal-delivery-info');
        
        // Debug info to help track the issue
        console.log("Populating modal with delivery charge:", deliveryCharge);
        console.log("User data:", userData);
        
        // Clear existing content
        modalSummary.innerHTML = '';
        if (modalDeliveryInfo) modalDeliveryInfo.innerHTML = '';
        
        // Calculate subtotal
        let subtotal = 0;
        
        // Add each item to the modal
        orderItems.forEach((item, index) => {
            const itemElem = document.createElement('div');
            itemElem.className = 'modal-item';
            
            const itemName = document.createElement('div');
            itemName.className = 'modal-item-name';
            itemName.textContent = `${index + 1}. ${item.name} (x${item.count})`;
            
            const itemDetails = document.createElement('div');
            itemDetails.className = 'modal-item-details';
            const lineSubtotal = item.price * item.count;
            itemDetails.textContent = `£${item.price.toFixed(2)} each | Subtotal: £${lineSubtotal.toFixed(2)}`;
            
            itemElem.appendChild(itemName);
            itemElem.appendChild(itemDetails);
            modalSummary.appendChild(itemElem);
            
            // Add to subtotal
            subtotal += lineSubtotal;
        });
        
        // Add delivery information if available
        if (modalDeliveryInfo && userData) {
            // Create delivery info content
            const deliveryTitle = document.createElement('h6');
            deliveryTitle.className = 'mt-3';
            deliveryTitle.textContent = 'Delivery Information:';
            
            const organisationInfo = document.createElement('div');
            organisationInfo.textContent = `Organisation: ${userData.organisation || 'Not specified'}`;
            
            const addressInfo = document.createElement('div');
            addressInfo.textContent = `Delivery Address: ${userData.delivery_address || 'Not specified'}`;
            
            modalDeliveryInfo.appendChild(deliveryTitle);
            modalDeliveryInfo.appendChild(organisationInfo);
            modalDeliveryInfo.appendChild(addressInfo);
        }
        
        // CRITICAL FIX: Always use userData.delivery_charge directly if available
        let userDeliveryCharge = deliveryCharge;
        
        // Check if userData has delivery_charge
        if (userData && userData.delivery_charge !== undefined) {
            userDeliveryCharge = parseFloat(userData.delivery_charge);
            console.log("Using delivery charge from userData:", userDeliveryCharge);
        } else {
            console.log("No delivery charge in userData, using passed value:", deliveryCharge);
        }
        
        // Calculate final total with delivery
        const totalWithDelivery = subtotal + userDeliveryCharge;
        
        // Create delivery charge and total display elements directly
        modalTotal.innerHTML = '';

        // Create and add subtotal element
        const subtotalElem = document.createElement('div');
        subtotalElem.textContent = `Subtotal: £${subtotal.toFixed(2)}`;
        modalTotal.appendChild(subtotalElem);
        
        // Create and add delivery charge element
        const deliveryElem = document.createElement('div');
        deliveryElem.textContent = `Delivery Charge: £${userDeliveryCharge.toFixed(2)}`;
        modalTotal.appendChild(deliveryElem);
        
        // Create and add total element (in bold)
        const totalElem = document.createElement('div');
        totalElem.style.marginTop = '10px';
        totalElem.innerHTML = `<strong>Total: £${totalWithDelivery.toFixed(2)}</strong>`;
        modalTotal.appendChild(totalElem);
    }
}