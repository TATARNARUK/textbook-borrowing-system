<?php
// src/config.php

// ตั้งค่าฐานข้อมูล (Database Configuration)
$host     = 'db';        // Server นี้ต้องใช้ localhost
$dbname   = 'vbrs';             // ชื่อฐานข้อมูล
$username = 'vbrs';             // ชื่อผู้ใช้
$password = 'bnccvbrs2026';     // รหัสผ่าน
$charset  = 'utf8mb4';

// เชื่อมต่อฐานข้อมูล (Connection)
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
    
} catch (\PDOException $e) {
    // ถ้าเชื่อมต่อไม่ได้ ให้แสดง Error ออกมาเลย
    die("Database Connection Failed: " . $e->getMessage());
}
?>