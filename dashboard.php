<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
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
        }
        .brand {
            padding: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h4>IMS</h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link" href="inventory.php">
                <i class="bi bi-box-seam"></i> Inventory
            </a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a class="nav-link" href="users.php">
                <i class="bi bi-people"></i> User Management
            </a>
            <?php endif; ?>
            <a class="nav-link" href="requests.php">
                <i class="bi bi-clipboard-check"></i> Requests
            </a>
        </nav>
        <div class="user-info">
            <div class="d-flex align-items-center">
                <i class="bi bi-person-circle fs-4 me-2"></i>
                <div>
                    <small class="d-block text-muted">Logged in as</small>
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </div>
            </div>
            <a href="auth/logout.php" class="btn btn-light btn-sm w-100 mt-2">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">Dashboard</h2>
            
            <!-- Dashboard Content -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Total Items</h5>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory");
                            $result = $stmt->fetch();
                            ?>
                            <h2 class="mb-0"><?php echo $result['total']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Pending Requests</h5>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as pending FROM requests WHERE status = 'pending'");
                            $result = $stmt->fetch();
                            ?>
                            <h2 class="mb-0"><?php echo $result['pending']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Low Stock Items</h5>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as low FROM inventory WHERE quantity <= 10");
                            $result = $stmt->fetch();
                            ?>
                            <h2 class="mb-0"><?php echo $result['low']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">No recent activity</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 