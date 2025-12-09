<?php
session_start();
require_once 'config.php';

// 1. ตรวจสอบว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = $_SESSION['user_id'];
    $master_id = $_GET['id']; // รับ ID หนังสือแม่แบบที่ต้องการยืม

    try {
        // เริ่มต้น Transaction (เพื่อให้การทำงานเชื่อมโยงกัน พลาดจุดหนึ่งให้ยกเลิกทั้งหมด)
        $pdo->beginTransaction();

        // 2. ค้นหาหนังสือเล่มที่ "ว่าง" (Available) มา 1 เล่ม (เรียงตาม id น้อยสุดก่อน)
        $stmt = $pdo->prepare("SELECT id, book_code FROM book_items WHERE book_master_id = ? AND status = 'available' LIMIT 1");
        $stmt->execute([$master_id]);
        $item = $stmt->fetch();

        if ($item) {
            $item_id = $item['id'];

            // 3. บันทึกลงตาราง transactions (ประวัติการยืม)
            // กำหนดคืน 7 วัน (INTERVAL 7 DAY)
            $sqlTrans = "INSERT INTO transactions (user_id, book_item_id, borrow_date, due_date, status) 
                         VALUES (:uid, :bid, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'borrowed')";
            $stmtTrans = $pdo->prepare($sqlTrans);
            $stmtTrans->execute(['uid' => $user_id, 'bid' => $item_id]);

            // 4. อัปเดตสถานะหนังสือเล่มนั้นเป็น "ถูกยืม" (borrowed)
            $sqlUpdate = $pdo->prepare("UPDATE book_items SET status = 'borrowed' WHERE id = ?");
            $sqlUpdate->execute([$item_id]);

            // ยืนยันการทำงานทั้งหมด
            $pdo->commit();
            
            // ส่งค่ากลับไปบอกหน้าแรกว่าสำเร็จ
            header("Location: index.php?status=success");

        } else {
            // กรณีหนังสือหมดพอดี (อาจมีคนกดตัดหน้า)
            $pdo->rollBack();
            header("Location: index.php?status=error");
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: index.php");
}
?>