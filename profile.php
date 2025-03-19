<?php
// Include session check
require_once 'inc/session_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link href="css/profile.css" rel="stylesheet">
    <style>
        .order-details-text {
            white-space: pre-line;
            font-family: inherit;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
            border: 1px solid #dee2e6;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <h1>User Profile</h1>
            <div>
                <a href="index.php" class="btn btn-primary">Back</a>
                <button id="logout-button" class="btn btn-outline-secondary ms-2">Logout</button>
            </div>
        </div>
        
        <!-- Loading Indicator -->
        <div id="loading-indicator" class="text-center p-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading profile data...</p>
        </div>
        
        <!-- User Information Card -->
        <div id="user-info-section" class="user-info-card hidden">
            <div class="card">
                <div class="card-header">
                    <h2 id="user-name" class="h4 mb-0">User Information</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Organisation:</strong> <span id="user-organisation"></span></p>
                            <p><strong>Email:</strong> <span id="user-email"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Delivery Address:</strong> <span id="user-address"></span></p>
                            <p><strong>Delivery Charge:</strong> £<span id="user-delivery-charge"></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order History Section -->
        <div id="order-history-section" class="hidden">
            <h2>Order History</h2>
            
            <!-- Admin Filter Controls (only visible for admins) -->
            <div id="admin-controls" class="mb-3 hidden">
                <div class="input-group mb-3">
                    <input type="text" id="search-input" class="form-control" placeholder="Search by organisation...">
                    <button class="btn btn-outline-secondary" type="button" id="search-button">Search</button>
                    <button class="btn btn-outline-secondary" type="button" id="reset-button">Reset</button>
                </div>
            </div>
            
            <!-- Order History Table -->
            <div class="order-history-table">
                <table class="table table-striped order-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Organisation</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody id="order-history-body">
                        <!-- Order rows will be populated here -->
                    </tbody>
                </table>
            </div>
            
            <!-- No Orders Message -->
            <div id="no-orders-message" class="alert alert-info hidden">
                No orders found.
            </div>
            
            <!-- Order Details Section -->
            <div id="order-details-section" class="order-details hidden">
                <h3>Order Details</h3>
                <div class="mb-3">
                    <p><strong>Order ID:</strong> <span id="detail-order-id"></span></p>
                    <p><strong>Order Date:</strong> <span id="detail-date"></span></p>
                    <p><strong>Total Amount:</strong> £<span id="detail-total"></span></p>
                    <!-- New delivery slot info -->
                    <div id="delivery-slot-info" class="hidden">
                        <p><strong>Preferred Delivery Slot:</strong> <span id="detail-delivery-slot"></span></p>
                    </div>
                </div>
                <h4>Items:</h4>
                <div id="order-details-text" class="order-details-text"></div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/profile.js"></script>
</body>
</html>