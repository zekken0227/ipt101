<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/database.php';

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    $_SESSION['error'] = "Unauthorized access";
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['items'] ?? [];
    $purpose = trim($_POST['purpose'] ?? '');

    // Log the received data
    error_log("Received request data: " . print_r($_POST, true));

    // Validate input
    if (empty($items) || empty($purpose)) {
        $_SESSION['error'] = "Please fill in all required fields correctly";
        header('Location: ../request_dashboard.php');
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Validate all items first
        foreach ($items as $item) {
            $item_id = (int)($item['id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);

            if ($item_id <= 0 || $quantity <= 0) {
                throw new Exception("Invalid item or quantity specified");
            }

            // Check if item exists and has sufficient quantity
            $stmt = $pdo->prepare("SELECT quantity FROM inventory WHERE id = ? AND quantity >= ?");
            $stmt->execute([$item_id, $quantity]);
            if (!$stmt->fetch()) {
                throw new Exception("Item not found or insufficient quantity available");
            }
        }

        $requestIds = []; // Store all created request IDs

        // Create the request entries
        foreach ($items as $item) {
            $item_id = (int)$item['id'];
            $quantity = (int)$item['quantity'];

            // Create the request
            $stmt = $pdo->prepare("INSERT INTO requests (user_id, item_id, quantity, purpose, status, created_at) 
                                  VALUES (?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)");
            if (!$stmt->execute([
                $_SESSION['user_id'],
                $item_id,
                $quantity,
                $purpose
            ])) {
                throw new Exception("Failed to insert request");
            }

            // Get the last inserted request ID
            $requestId = $pdo->lastInsertId();
            if (!$requestId) {
                throw new Exception("Failed to get last insert ID");
            }
            $requestIds[] = $requestId;

            // Verify the request was created
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE id = ?");
            $stmt->execute([$requestId]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("Failed to create request");
            }

            // Log successful request creation
            error_log("Created request ID: " . $requestId . " for item ID: " . $item_id);
        }

        $pdo->commit();
        $_SESSION['success'] = "Request submitted successfully";
        
        // Log all created request IDs
        error_log("Successfully created requests: " . implode(', ', $requestIds));
        
        // Clear any existing error messages
        unset($_SESSION['error']);
        
        // Redirect back to dashboard
        header('Location: ../request_dashboard.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in submit_request.php: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Error submitting request: " . $e->getMessage();
        header('Location: ../request_dashboard.php');
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method";
    header('Location: ../request_dashboard.php');
    exit();
} 