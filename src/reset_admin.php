<?php
require_once 'config.php'; // เรียกไฟล์เชื่อมต่อฐานข้อมูลของเซิร์ฟเวอร์

$username_admin = 'admin'; // ใส่ username ของแอดมิน
$new_password = 'ใส่รหัสผ่านใหม่ที่ต้องการตรงนี้'; 

$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE username = :user"); 
    // หมายเหตุ: ถ้าในฐานข้อมูลคุณใช้ฟิลด์ student_id แทน username ให้แก้ตรง WHERE เป็น student_id = :user นะครับ

    $stmt->execute([
        ':pass' => $hashed_password,
        ':user' => $username_admin
    ]);

    echo "อัปเดตรหัสผ่านบนเซิร์ฟเวอร์สำเร็จแล้ว! ลองไปหน้า Login ดูครับ";

} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>