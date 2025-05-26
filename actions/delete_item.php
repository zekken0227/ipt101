<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access";
    header('Location: ../inventory.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = $_POST['item_id'];
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // First, delete related logs
        $stmt = $pdo->prepare("DELETE FROM inventory_logs WHERE item_id = ?");
        $stmt->execute([$item_id]);
        
        // Then delete the item
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->execute([$item_id]);
        
        $pdo->commit();
        $_SESSION['success'] = "Item deleted successfully";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting item: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request";
}

header('Location: ../inventory.php');
exit(); 