<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access";
    header('Location: ../inventory.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validate input
    if (empty($name) || empty($category) || $quantity < 0) {
        $_SESSION['error'] = "Please fill in all required fields correctly";
        header('Location: ../inventory.php');
        exit();
    }

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Get current item details for logging
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->execute([$item_id]);
        $currentItem = $stmt->fetch();

        if ($currentItem) {
            // Update the item
            $stmt = $pdo->prepare("UPDATE inventory SET 
                name = ?, 
                category = ?, 
                quantity = ?,
                unit = ?,
                description = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?");

            $stmt->execute([
                $name,
                $category,
                $quantity,
                $unit,
                $description,
                $item_id
            ]);

            // Log quantity change if any
            if ($currentItem['quantity'] != $quantity) {
                $quantityChange = $quantity - $currentItem['quantity'];
                $logStmt = $pdo->prepare("INSERT INTO inventory_logs 
                    (item_id, user_id, action, quantity_change, old_quantity, new_quantity) 
                    VALUES (?, ?, 'update', ?, ?, ?)");
                $logStmt->execute([
                    $item_id,
                    $_SESSION['user_id'],
                    $quantityChange,
                    $currentItem['quantity'],
                    $quantity
                ]);
            }

            $pdo->commit();
            $_SESSION['success'] = "Item updated successfully";
        } else {
            $_SESSION['error'] = "Item not found";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating item: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request";
}

header('Location: ../inventory.php');
exit(); 