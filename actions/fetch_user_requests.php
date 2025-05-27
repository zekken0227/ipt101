<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/database.php';

// Debug log
error_log("User ID: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("User Role: " . ($_SESSION['role'] ?? 'not set'));

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Debug log before query
    error_log("Attempting to fetch requests for user ID: " . $_SESSION['user_id']);

    // Fetch user's requests with item details
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.quantity,
            r.purpose,
            r.status,
            r.created_at,
            r.item_deleted,
            i.name as item_name,
            i.category,
            i.unit
        FROM requests r 
        LEFT JOIN inventory i ON r.item_id = i.id 
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC, r.id DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug log after query
    error_log("Raw requests data: " . print_r($requests, true));

    // Process item names for deleted items
    foreach ($requests as &$request) {
        if ($request['item_deleted'] || !$request['item_name']) {
            $request['item_name'] = 'Deleted Item';
            $request['category'] = 'N/A';
            $request['unit'] = 'N/A';
        }
        // Ensure proper encoding of special characters
        $request = array_map('htmlspecialchars', $request);
    }
    unset($request);

    // Get request counts by status
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

    // Debug log final response
    error_log("Final response data: " . print_r([
        'requests' => array_values($requests),
        'counts' => $statusCounts
    ], true));

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'requests' => array_values($requests),
        'counts' => $statusCounts
    ]);

} catch (Exception $e) {
    error_log("Error in fetch_user_requests.php: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 