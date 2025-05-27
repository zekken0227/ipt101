<?php
session_start();
require_once 'config/database.php';

// Initialize variables
$isLoggedIn = isset($_SESSION['user_id']);
$isUser = $isLoggedIn && $_SESSION['role'] === 'user';

// Only fetch data if user is logged in and is a regular user
if ($isUser) {
    // Fetch all user's requests
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.quantity,
            r.purpose,
            r.status,
            r.created_at,
            i.name as item_name,
            i.category,
            i.unit
        FROM requests r 
        LEFT JOIN inventory i ON r.item_id = i.id 
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $requests = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request History - CIMS</title>
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
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem;
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
            padding: 0.75rem;
            vertical-align: middle;
        }
        .search-box {
            position: relative;
            max-width: 300px;
        }
        .search-box .form-control {
            padding-left: 2.5rem;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
        }
        .search-box .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 2rem;
            color: #adb5bd;
            margin-bottom: 0.5rem;
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
            <a class="nav-link" href="request_dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link active" href="request_history.php">
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Request History</h2>
                    <p class="text-muted mb-0">View all your past requests</p>
                </div>
            </div>

            <!-- Request History Table -->
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="btn-group" role="group" aria-label="Filter requests">
                                <button type="button" class="btn btn-outline-secondary active" data-filter="all">All Requests</button>
                                <button type="button" class="btn btn-outline-warning" data-filter="pending">Pending</button>
                                <button type="button" class="btn btn-outline-success" data-filter="approved">Approved</button>
                                <button type="button" class="btn btn-outline-danger" data-filter="rejected">Rejected</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Date Requested</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <h5>No Requests Found</h5>
                                            <p class="text-muted">You haven't made any requests yet.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['item_name'] ?: 'Deleted Item'); ?></td>
                                    <td><?php echo htmlspecialchars($request['category'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($request['quantity']) . ' ' . htmlspecialchars($request['unit'] ?: 'units'); ?></td>
                                    <td><?php echo htmlspecialchars($request['purpose']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar-event me-2 text-muted"></i>
                                            <?php 
                                            $date = new DateTime($request['created_at']);
                                            echo $date->format('M j, Y, g:i A'); 
                                            ?>
                                        </div>
                                    </td>
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
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($isUser): ?>
    <script>
        // Filter functionality
        document.querySelectorAll('.btn-group .btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.btn-group .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const tableRows = document.querySelectorAll('tbody tr');
                
                tableRows.forEach(row => {
                    if (filter === 'all') {
                        row.style.display = '';
                    } else {
                        const status = row.querySelector('.status-badge').textContent.trim().toLowerCase();
                        row.style.display = status === filter ? '' : 'none';
                    }
                });
            });
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
    <?php endif; ?>
</body>
</html> 