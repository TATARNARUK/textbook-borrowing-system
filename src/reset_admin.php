<?php
require_once 'config.php'; // เรียกไฟล์เชื่อมต่อฐานข้อมูลของเซิร์ฟเวอร์

// ใส่รหัสนักเรียน (หรือไอดี) ของแอดมินที่คุณใช้ล็อกอิน
$admin_id = 'admin'; // <--- ถ้าไอดีแอดมินของคุณไม่ใช่คำว่า admin ให้เปลี่ยนตรงนี้นะครับ
$new_password = 'ใส่รหัสผ่านใหม่ที่ต้องการตรงนี้'; 

$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // 🔥 แก้ไขตรงนี้: เปลี่ยน username เป็น student_id
    $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE student_id = :user"); 
    
    $stmt->execute([
        ':pass' => $hashed_password,
        ':user' => $admin_id
    ]);
    
    echo "อัปเดตรหัสผ่านบนเซิร์ฟเวอร์สำเร็จแล้ว! ลองไปหน้า Login ดูครับ";
    
} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>