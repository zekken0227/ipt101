<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Connect to database
    $pdo = new PDO("mysql:host=localhost;dbname=ipt101", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create new password hash
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update admin password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $result = $stmt->execute([$hash]);
    
    if ($result) {
        echo "Admin password has been reset successfully.<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Failed to reset password.";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
} 