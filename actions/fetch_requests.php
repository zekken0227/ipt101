<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Fetch all requests with user and item details
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.quantity,
            r.purpose,
            r.status,
            r.created_at,
            u.username,
            u.full_name,
            i.name as item_name,
            i.category,
            i.unit
        FROM requests r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN inventory i ON r.item_id = i.id 
        ORDER BY r.created_at DESC, r.id DESC
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process item names for deleted items
    foreach ($requests as &$request) {
        if (!$request['item_name']) {
            $request['item_name'] = 'Deleted Item';
            $request['category'] = 'N/A';
            $request['unit'] = 'N/A';
        }
        // Ensure proper encoding of special characters
        $request = array_map('htmlspecialchars', $request);
    }
    unset($request);

    // Count requests by status
    $statusCounts = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];

    foreach ($requests as $request) {
        $statusCounts[$request['status']]++;
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'requests' => array_values($requests),
        'counts' => $statusCounts
    ]);

} catch (Exception $e) {
    error_log("Error in fetch_requests.php: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 