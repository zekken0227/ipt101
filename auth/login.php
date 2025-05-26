<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Both username and password are required";
        header('Location: ../index.php');
        exit();
    }

    try {
        // Debug information
        $debug = [];
        $debug['username'] = $username;
        
        // Query the user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        $debug['user_found'] = ($user !== false);
        
        if ($user) {
            $debug['password_verify'] = password_verify($password, $user['password']);
            
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: ../dashboard.php');
                exit();
            } else {
                $_SESSION['error'] = "Invalid password";
                $_SESSION['debug'] = $debug; // Only in development
                header('Location: ../index.php');
                exit();
            }
        } else {
            $_SESSION['error'] = "User not found";
            $_SESSION['debug'] = $debug; // Only in development
            header('Location: ../index.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header('Location: ../index.php');
        exit();
    }
}

// If someone tries to access this file directly without POST
header('Location: ../index.php');
exit();
?> 