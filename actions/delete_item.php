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
        
        // Check if there are any pending requests for this item
        $stmt = $pdo->prepare("SELECT COUNT(*) as pending_count FROM requests WHERE item_id = ? AND status = 'pending'");
        $stmt->execute([$item_id]);
        $result = $stmt->fetch();
        
        if ($result['pending_count'] > 0) {
            throw new Exception("Cannot delete item: There are pending requests for this item. Please process all pending requests first.");
        }

        // First, delete related logs
        $stmt = $pdo->prepare("DELETE FROM inventory_logs WHERE item_id = ?");
        $stmt->execute([$item_id]);
        
        // Update completed requests to mark item as deleted
        $stmt = $pdo->prepare("UPDATE requests SET item_deleted = 1 WHERE item_id = ? AND status IN ('approved', 'rejected')");
        $stmt->execute([$item_id]);
        
        // Then delete the item - the ON DELETE SET NULL will handle the foreign key
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->execute([$item_id]);
        
        $pdo->commit();
        $_SESSION['success'] = "Item deleted successfully";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting item: " . $e->getMessage();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request";
}

header('Location: ../inventory.php');
exit(); 