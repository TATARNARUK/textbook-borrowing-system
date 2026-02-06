<?php
// 1. ฟังก์ชันสำหรับอ่านไฟล์ .env (แบบไม่ง้อ Composer)
function loadEnv($path) {
    if (!file_exists($path)) {
        // ถ้าหาไฟล์ไม่เจอ ให้แจ้งเตือน หรือปล่อยผ่าน
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // ข้ามบรรทัดที่เป็น Comment
        if (strpos(trim($line), '#') === 0) continue;
        
        // แยกชื่อตัวแปรกับค่า
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // ลบ quote รอบๆ ออก (ถ้ามี)
            $value = trim($value, "\"'");
            
            // เก็บลงตัวแปร Environment
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// 2. เรียกใช้ฟังก์ชันเพื่อโหลดไฟล์ .env
loadEnv(__DIR__ . '/.env');

// 3. กำหนดค่าตัวแปรจาก .env (ถ้าไม่มีจะใช้ค่า Default หลัง ??)
$host    = $_ENV['DB_HOST'] ?? 'db';
$dbname  = $_ENV['DB_NAME'] ?? 'vbrs'; // จะดึงชื่อ DB ใหม่จากไฟล์ .env
$username= $_ENV['DB_USER'] ?? 'vbrs';
$password= $_ENV['DB_PASS'] ?? 'bnccvbrs2026';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

// 4. เชื่อมต่อฐานข้อมูล PDO
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // กรณีเชื่อมต่อไม่ได้ ให้แสดง Error
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>