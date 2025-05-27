<?php
session_start();
require_once 'config/database.php';

// Initialize variables
$isLoggedIn = isset($_SESSION['user_id']);
$isUser = $isLoggedIn && $_SESSION['role'] === 'user';

// Only fetch data if user is logged in and is a regular user
if ($isUser) {
    // Fetch available inventory items
    $stmt = $pdo->query("SELECT * FROM inventory ORDER BY name");
    $items = $stmt->fetchAll();

    // Get request counts
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM requests 
        WHERE user_id = ? 
        GROUP BY status
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $statusCounts = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];
    while ($row = $stmt->fetch()) {
        $statusCounts[$row['status']] = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Dashboard - CIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #ffffff;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: #ffffff;
            border-right: 1px solid #dee2e6;
            padding-top: 1rem;
        }
        .sidebar .nav-link {
            color: #6c757d;
            padding: 0.8rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #343a40;
            background-color: #f8f9fa;
        }
        .sidebar .nav-link i {
            width: 24px;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            background-color: #ffffff;
        }
        .brand {
            padding: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
            background-color: #ffffff;
        }
        .brand h4 {
            color: #343a40;
            margin: 0;
        }
        .user-info {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            position: absolute;
            bottom: 0;
            width: 100%;
            background-color: #ffffff;
        }
        .card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            margin-bottom: 1.5rem;
            background-color: #ffffff;
        }
        .stats-card {
            background: #ffffff;
            color: #343a40;
            border: 1px solid #dee2e6;
        }
        .stats-card .card-title {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stats-card .card-value {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
            color: #4b6cb7;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #ffffff;
            border-bottom: 2px solid #dee2e6;
            color: #6c757d;
            font-weight: 500;
        }
        .table td {
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
        }
        .modal-content {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .modal-header {
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
        }
        .modal-footer {
            background-color: #ffffff;
            border-top: 1px solid #dee2e6;
        }
        .request-item {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php if (!$isLoggedIn): ?>
    <!-- Login Form -->
    <div class="container">
        <div class="card mx-auto mt-5" style="max-width: 400px;">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">CIMS Request System</h2>
                <p class="text-center text-muted mb-4">Please login to continue</p>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo htmlspecialchars($_SESSION['error']); 
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                <form action="auth/login.php" method="POST">
                    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
    <?php elseif (!$isUser): ?>
    <!-- Access Denied Message -->
    <div class="container">
        <div class="card mx-auto mt-5" style="max-width: 400px;">
            <div class="card-body p-4 text-center">
                <h2 class="mb-4">Access Denied</h2>
                <p class="text-muted mb-4">This area is for regular users only.</p>
                <a href="dashboard.php" class="btn btn-primary">Go to Admin Dashboard</a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h4>CIMS</h4>
        </div>
        <nav class="nav flex-column mb-auto">
            <a class="nav-link active" href="request_dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link" href="request_history.php">
                <i class="bi bi-clock-history"></i> Request History
            </a>
        </nav>
        <div class="user-info">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-person-circle fs-4 me-2"></i>
                <div>
                    <small class="d-block text-muted">Logged in as</small>
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </div>
            </div>
            <a href="auth/logout.php" class="btn btn-light btn-sm w-100">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Request Dashboard</h2>
                    <p class="text-muted mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                    <i class="bi bi-plus-lg"></i> New Request
                </button>
            </div>

            <!-- Request Statistics -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h6 class="card-title">Pending Requests</h6>
                            <p class="card-value" id="pending-count"><?php echo $statusCounts['pending']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h6 class="card-title">Approved Requests</h6>
                            <p class="card-value" id="approved-count"><?php echo $statusCounts['approved']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h6 class="card-title">Rejected Requests</h6>
                            <p class="card-value" id="rejected-count"><?php echo $statusCounts['rejected']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Available Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Available Stock</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No items available</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                <?php
                                    $status = '';
                                    $statusClass = '';
                                    if ($item['quantity'] <= 0) {
                                        $status = 'Out of Stock';
                                        $statusClass = 'bg-danger text-white';
                                    } elseif ($item['quantity'] <= $item['minimum_stock']) {
                                        $status = 'Low Stock';
                                        $statusClass = 'bg-warning text-dark';
                                    } else {
                                        $status = 'In Stock';
                                        $statusClass = 'bg-success text-white';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Request Modal -->
    <div class="modal fade" id="newRequestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="actions/submit_request.php" method="POST" id="requestForm" onsubmit="return validateForm()">
                    <div class="modal-body">
                        <div id="request-items">
                            <!-- Template for request item -->
                            <div class="request-item mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Item #<span class="item-number">1</span></h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-item" onclick="removeRequestItem(this)" style="display: none;">
                                        <i class="bi bi-trash"></i> Remove
                                    </button>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Item</label>
                                        <select class="form-select item-select" name="items[0][id]" required onchange="updateAvailableQuantity(this)">
                                            <option value="">Select an item</option>
                                            <?php foreach ($items as $item): ?>
                                            <?php if ($item['quantity'] > 0): ?>
                                            <option value="<?php echo $item['id']; ?>" 
                                                    data-available="<?php echo $item['quantity']; ?>"
                                                    data-unit="<?php echo htmlspecialchars($item['unit']); ?>">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </option>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Quantity</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control quantity-input" name="items[0][quantity]" min="1" required>
                                            <span class="input-group-text unit-label">units</span>
                                        </div>
                                        <small class="text-muted">Available: <span class="available-quantity">0</span></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary" onclick="addRequestItem()">
                                <i class="bi bi-plus-lg"></i> Add Another Item
                            </button>
                        </div>

                        <div class="mb-3">
                            <label for="purpose" class="form-label">Purpose</label>
                            <textarea class="form-control" id="purpose" name="purpose" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($isUser): ?>
    <script>
        let itemCounter = 1;

        // Function to validate form before submission
        function validateForm() {
            const items = document.querySelectorAll('.request-item');
            const purpose = document.getElementById('purpose').value.trim();
            
            if (items.length === 0) {
                alert('Please add at least one item to your request');
                return false;
            }
            
            if (!purpose) {
                alert('Please specify the purpose of your request');
                return false;
            }
            
            // Validate each item
            for (let item of items) {
                const select = item.querySelector('.item-select');
                const quantity = item.querySelector('.quantity-input');
                
                if (!select.value) {
                    alert('Please select an item');
                    select.focus();
                    return false;
                }
                
                if (!quantity.value || quantity.value <= 0) {
                    alert('Please enter a valid quantity');
                    quantity.focus();
                    return false;
                }
                
                const availableQuantity = parseInt(select.options[select.selectedIndex].dataset.available);
                if (parseInt(quantity.value) > availableQuantity) {
                    alert('Requested quantity exceeds available stock');
                    quantity.focus();
                    return false;
                }
            }
            
            return true;
        }

        // Function to pre-fill request modal
        function prepareRequest(itemId, itemName) {
            const firstItemSelect = document.querySelector('.item-select');
            firstItemSelect.value = itemId;
            // Trigger change event to update available quantity
            firstItemSelect.dispatchEvent(new Event('change'));
        }

        // Function to update available quantity and unit
        function updateAvailableQuantity(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const requestItem = selectElement.closest('.request-item');
            const availableSpan = requestItem.querySelector('.available-quantity');
            const unitSpan = requestItem.querySelector('.unit-label');
            const quantityInput = requestItem.querySelector('.quantity-input');
            
            if (selectedOption.value) {
                const availableQuantity = selectedOption.dataset.available;
                const unit = selectedOption.dataset.unit;
                
                availableSpan.textContent = availableQuantity;
                unitSpan.textContent = unit;
                quantityInput.max = availableQuantity;
                quantityInput.value = ''; // Reset quantity when item changes
            } else {
                availableSpan.textContent = '0';
                unitSpan.textContent = 'units';
                quantityInput.value = '';
            }
        }

        // Function to add new request item
        function addRequestItem() {
            itemCounter++;
            const template = document.querySelector('.request-item').cloneNode(true);
            
            // Update item number
            template.querySelector('.item-number').textContent = itemCounter;
            
            // Update form field names
            const select = template.querySelector('.item-select');
            const quantity = template.querySelector('.quantity-input');
            select.name = `items[${itemCounter-1}][id]`;
            quantity.name = `items[${itemCounter-1}][quantity]`;
            
            // Reset values
            select.value = '';
            quantity.value = '';
            template.querySelector('.available-quantity').textContent = '0';
            template.querySelector('.unit-label').textContent = 'units';
            
            // Show remove button
            template.querySelector('.remove-item').style.display = 'block';
            
            // Add to form
            document.getElementById('request-items').appendChild(template);
            
            // Show all remove buttons if more than one item
            if (itemCounter > 1) {
                document.querySelectorAll('.remove-item').forEach(btn => btn.style.display = 'block');
            }
        }

        // Function to remove request item
        function removeRequestItem(button) {
            const item = button.closest('.request-item');
            item.remove();
            itemCounter--;
            
            // Update item numbers
            document.querySelectorAll('.item-number').forEach((span, index) => {
                span.textContent = index + 1;
            });
            
            // Hide remove buttons if only one item left
            if (itemCounter === 1) {
                document.querySelector('.remove-item').style.display = 'none';
            }
            
            // Update form field names
            document.querySelectorAll('.request-item').forEach((item, index) => {
                const select = item.querySelector('.item-select');
                const quantity = item.querySelector('.quantity-input');
                select.name = `items[${index}][id]`;
                quantity.name = `items[${index}][quantity]`;
            });
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    </script>
    <?php endif; ?>
</body>
</html> 