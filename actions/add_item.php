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
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $minimum_stock = (int)($_POST['minimum_stock'] ?? 10);
    $description = trim($_POST['description'] ?? '');

    // Validate input
    if (empty($name) || empty($category) || $quantity < 0 || empty($unit)) {
        $_SESSION['error'] = "Please fill in all required fields correctly";
        header('Location: ../inventory.php');
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Insert into inventory
        $stmt = $pdo->prepare("INSERT INTO inventory (name, category, quantity, unit, minimum_stock, description) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name,
            $category,
            $quantity,
            $unit,
            $minimum_stock,
            $description
        ]);
        
        $itemId = $pdo->lastInsertId();

        // Log the addition
        $stmt = $pdo->prepare("INSERT INTO inventory_logs (item_id, user_id, action, quantity_change, old_quantity, new_quantity) 
                              VALUES (?, ?, 'add', ?, 0, ?)");
        $stmt->execute([
            $itemId,
            $_SESSION['user_id'],
            $quantity,
            $quantity
        ]);

        $pdo->commit();
        $_SESSION['success'] = "Item added successfully";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error adding item: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request method";
}

header('Location: ../inventory.php');
exit(); 