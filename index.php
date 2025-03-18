<?php
// Include session check
require_once 'inc/session_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link href="css/delivery-scheduler.css" rel="stylesheet">
    <style>
        .organisation-name {
            font-size: 1.5rem;
            font-weight: 500;
            text-align: center;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .user-menu {
            text-align: right;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header with logo and organization name -->
        <div class="row mb-3">
            <div class="col-md-4">
                <img src="assets/logo.jpg" class="img-fluid" style="height: 60px; margin: 15px 0;">
            </div>
            <div class="col-md-4">
                <div class="organisation-name" id="organisation-name">
                    <!-- Organization name will be populated here -->
                </div>
            </div>
            <div class="col-md-4">
                <div class="user-menu">
                    <a href="profile.php" class="text-decoration-none" id="profile-link">
                        <span id="rep-name-display"></span>
                    </a>
                    <button id="logout-button" class="btn btn-outline-secondary btn-sm ms-2">Logout</button>
                </div>
            </div>
        </div>
        
        <!-- Main content area -->
        <div class="row">
            <!-- Product navigation and display -->
            <div class="col-md-8 pe-1">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                    <!-- Tabs will be populated by JavaScript -->
                </ul>
                
                <!-- Tab content -->
                <div class="tab-content" id="categoryTabContent">
                    <!-- Product grids will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Order panel -->
            <div class="col-md-4 ps-1">
                <div class="order-panel">
                    <!-- Order header -->
                    <div class="order-header">
                        <div class="row align-items-center g-0">
                            <div class="col-6">
                                <h5 class="m-0 pt-1">Order Summary</h5>
                            </div>
                            <div class="col-6">
                                <h5 id="order-total-top" class="m-0 pt-1 text-end">Total: £0.00</h5>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order list -->
                    <div id="order-list" class="order-items-container">
                        <div class="no-items">No items selected.</div>
                    </div>
                    
                    <!-- Delivery charge info - kept in place but will be hidden -->
                    <div id="delivery-charge-info" class="delivery-charge-info" style="display: none;">
                        <!-- Delivery charge will be shown in modal instead -->
                    </div>
                    
                    <!-- Order total and action -->
                    <h4 id="order-total" class="mt-3">Total: £0.00</h4>
                    <button id="place-order-button" class="btn btn-success w-100 mt-2">Place Order</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order confirmation modal -->
    <div class="modal fade" id="order-confirmation-modal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Your Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modal-order-summary" style="max-height: 400px; overflow-y: auto; padding-right: 5px;">
                        <!-- Order details will be populated here -->
                    </div>
                    <div id="modal-delivery-info" class="mt-3">
                        <!-- Delivery information will be shown here -->
                    </div>
                    <h5 id="modal-order-total" class="mt-3"></h5>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="schedule-delivery-button">Schedule Delivery</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="modal-go-back-button">Go Back</button>
                    <button type="button" class="btn btn-success" id="modal-proceed-button">Proceed with Order</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delivery scheduler modal -->
    <div class="modal fade" id="delivery-scheduler-modal" tabindex="-1" aria-labelledby="schedulerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="schedulerModalLabel">Reserve a Delivery Slot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Step 1: Date Selection -->
                    <div class="scheduler-step">
                        <div class="step-number">1</div>
                        <div class="step-title">Select a Date</div>
                    </div>
                    
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button class="btn btn-sm btn-outline-secondary" id="prev-month-btn">
                                <span aria-hidden="true">&laquo;</span> Prev
                            </button>
                            <h3 class="calendar-title" id="calendar-month-year">March 2025</h3>
                            <button class="btn btn-sm btn-outline-secondary" id="next-month-btn">
                                Next <span aria-hidden="true">&raquo;</span>
                            </button>
                        </div>
                        
                        <div class="calendar-grid" id="calendar-weekdays">
                            <!-- Weekday headers will be added by JavaScript -->
                        </div>
                        
                        <div class="calendar-grid" id="calendar-days">
                            <!-- Calendar days will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Step 2: Time Selection -->
                    <div class="scheduler-step">
                        <div class="step-number">2</div>
                        <div class="step-title">Select a Time</div>
                    </div>
                    
                    <div class="time-slots-container">
                        <div class="time-slots-grid" id="time-slots-grid">
                            <!-- Time slots will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Step 3: Delivery Notes -->
                    <div class="scheduler-step">
                        <div class="step-number">3</div>
                        <div class="step-title">Add Delivery Notes (Optional)</div>
                    </div>
                    
                    <div class="delivery-notes-container">
                        <textarea 
                            class="delivery-notes-textarea" 
                            id="delivery-notes" 
                            placeholder="e.g. Notes for driver, feedback or product suggestions..."
                        ></textarea>
                    </div>
                    
                    <!-- Selected delivery slot summary -->
                    <div class="alert alert-primary mt-3" id="selected-slot-info" style="display: none;">
                        <strong>Selected Delivery Slot:</strong> <span id="selected-date-display"></span> at <span id="selected-time-display"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="reserve-slot-button">Reserve Delivery Slot</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/product-manager.js"></script>
    <script src="js/order-manager.js"></script>
    <script src="js/ui-manager.js"></script>
    <script src="js/delivery-scheduler.js"></script>
    <script src="js/app.js"></script>
    
    <script>
        // User authentication and session management
        document.addEventListener('DOMContentLoaded', function() {
            // Add logout functionality
            document.getElementById('logout-button').addEventListener('click', function() {
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
            });
            
            // Get current user info
            fetch('api/authenticate.php')
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
                        // Set organization name
                        document.getElementById('organisation-name').textContent = data.user.organisation;
                        
                        // Set rep name display instead of username
                        const repNameElem = document.getElementById('rep-name-display');
                        if (repNameElem && data.user.rep_name) {
                            repNameElem.textContent = data.user.rep_name;
                        } else if (repNameElem) {
                            // Fallback to username if rep_name is not available
                            repNameElem.textContent = data.user.username;
                        }
                        
                        // Style the profile link
                        const profileLink = document.getElementById('profile-link');
                        if (profileLink) {
                            profileLink.title = 'View profile';
                            // Add an icon to indicate it's clickable
                            repNameElem.innerHTML += ' 👤';
                        }
                        
                        // Hide the delivery charge on the main page
                        // The delivery charge will be shown only in the confirmation modal
                        const deliveryChargeInfo = document.getElementById('delivery-charge-info');
                        if (deliveryChargeInfo) {
                            deliveryChargeInfo.style.display = 'none';
                        }
                        
                        // Store user data in a global variable for use in the app
                        window.userData = data.user;
                    }
                })
                .catch(error => {
                    console.error('Error getting user data:', error);
                });
        });
    </script>
</body>
</html>