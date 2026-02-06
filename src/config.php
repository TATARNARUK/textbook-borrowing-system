<?php
// ฟังก์ชันสำหรับอ่านไฟล์ .env (แบบไม่ต้องลง Composer)
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // ข้ามบรรทัดที่เป็น Comment (#)
        if (strpos(trim($line), '#') === 0) continue;
        
        // แยกชื่อตัวแปรกับค่า
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // ลบ quote รอบๆ ออก (ถ้ามี)
            $value = trim($value, "\"'");
            
            // เก็บค่าลงใน Environment Variable
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// เรียกใช้ฟังก์ชันโหลดไฟล์ .env
loadEnv(__DIR__ . '/.env');

// ดึงค่าจาก Environment Variable (ถ้าไม่มีให้ใช้ค่า Default หลัง ??)
$host     = getenv('DB_HOST') ?: 'db';
$dbname   = getenv('DB_NAME') ?: 'vbrs';
$username = getenv('DB_USER') ?: 'vbrs';
$password = getenv('DB_PASS') ?: 'bnccvbrs2026';
$charset  = getenv('DB_CHARSET') ?: 'utf8mb4';

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