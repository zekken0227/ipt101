<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Get the return URL or default to index.php
$return_to = isset($_POST['return_to']) ? $_POST['return_to'] : '../index.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Both username and password are required";
        header('Location: ' . $return_to);
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
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: ../dashboard.php');
            } else {
                header('Location: ../request_dashboard.php');
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid username or password";
            $_SESSION['debug'] = $debug; // Only in development
            header('Location: ' . $return_to);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header('Location: ' . $return_to);
        exit();
    }
}

// If someone tries to access this file directly without POST
header('Location: ../index.php');
exit();
?> 