<?php

function getDBConnection() {
    $host = 'localhost';
    $db   = 'moderntech_hr';
    $user = 'root';
    $pass = 'CubanaKing@2016';
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
    return $conn;
}

// PDO connection for Employee_Directory.php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=moderntech_hr;charset=utf8mb4",
        'root',
        'CubanaKing@2016',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>