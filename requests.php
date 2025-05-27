<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Handle request status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    
    if (in_array($status, ['approved', 'rejected'])) {
        try {
            $pdo->beginTransaction();
            
            // Update request status
            $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
            $stmt->execute([$status, $request_id]);
            
            // If approved, update inventory quantity
            if ($status === 'approved') {
                $stmt = $pdo->prepare("
                    UPDATE inventory i
                    JOIN requests r ON i.id = r.item_id
                    SET i.quantity = i.quantity - r.quantity
                    WHERE r.id = ?
                ");
                $stmt->execute([$request_id]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Request has been " . $status;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error updating request: " . $e->getMessage();
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all requests with user and item details
$stmt = $pdo->query("
    SELECT 
        r.id,
        r.quantity,
        r.purpose,
        r.status,
        r.created_at,
        r.item_deleted,
        u.username,
        u.full_name,
        i.name as item_name,
        i.category,
        i.unit,
        CASE 
            WHEN i.id IS NULL THEN 1 
            ELSE 0 
        END as is_deleted
    FROM requests r 
    JOIN users u ON r.user_id = u.id 
    LEFT JOIN inventory i ON r.item_id = i.id 
    ORDER BY r.created_at DESC, r.id DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process item names for deleted items
foreach ($requests as &$request) {
    if ($request['is_deleted']) {
        $request['item_name'] = 'Deleted Item';
        $request['category'] = 'N/A';
        $request['unit'] = 'N/A';
    }
}
unset($request); // Break the reference

// Count requests by status
$statusCounts = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

foreach ($requests as $request) {
    $statusCounts[$request['status']]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - CIMS</title>
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
        .stats-card .card-value {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
            color: #4b6cb7;
        }
        .stats-card .card-title {
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
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
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
            margin: 0 0.2rem;
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
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h4>CIMS</h4>
        </div>
        <nav class="nav flex-column mb-auto">
            <a class="nav-link" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link" href="inventory.php">
                <i class="bi bi-box-seam"></i> Inventory
            </a>
            <a class="nav-link" href="users.php">
                <i class="bi bi-people"></i> User Management
            </a>
            <a class="nav-link active" href="requests.php">
                <i class="bi bi-clipboard-check"></i> Requests
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
                    <h2 class="mb-1">Manage Requests</h2>
                    <p class="text-muted mb-0">Review and process user requests</p>
                </div>
            </div>

            <!-- Request Statistics -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h6 class="card-title">Pending Requests</h6>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE status = 'pending'");
                            $pending = $stmt->fetch();
                            ?>
                            <p class="card-value" id="pending-count"><?php echo $pending['count']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h6 class="card-title">Approved Requests</h6>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE status = 'approved'");
                            $approved = $stmt->fetch();
                            ?>
                            <p class="card-value" id="approved-count"><?php echo $approved['count']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h6 class="card-title">Rejected Requests</h6>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE status = 'rejected'");
                            $rejected = $stmt->fetch();
                            ?>
                            <p class="card-value" id="rejected-count"><?php echo $rejected['count']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests Table -->
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
                                    <th>User</th>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <h5>No Requests Found</h5>
                                            <p class="text-muted">There are no requests to display.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td>
                                        <div><?php echo htmlspecialchars($request['username']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($request['full_name']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['category']); ?></td>
                                    <td><?php echo htmlspecialchars($request['quantity']) . ' ' . htmlspecialchars($request['unit']); ?></td>
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
                                    <td>
                                        <?php if ($request['status'] === 'pending'): ?>
                                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" name="status" value="approved" class="btn btn-success btn-action" 
                                                    <?php echo $request['item_deleted'] ? 'disabled' : ''; ?>>
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="submit" name="status" value="rejected" class="btn btn-danger btn-action">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
</body>
</html> 