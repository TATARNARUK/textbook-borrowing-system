<?php
session_start();
require_once 'config.php';

// เฉพาะ Admin เท่านั้นที่กดคืนได้
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); exit();
}

if (isset($_GET['trans_id']) && isset($_GET['item_id'])) {
    $trans_id = $_GET['trans_id'];
    $item_id = $_GET['item_id'];

    try {
        $pdo->beginTransaction();

        // 1. อัปเดตตาราง transactions (ใส่วันที่คืน)
        $sqlTrans = "UPDATE transactions SET return_date = NOW(), status = 'returned' WHERE id = ?";
        $pdo->prepare($sqlTrans)->execute([$trans_id]);

        // 2. อัปเดตตาราง book_items (เปลี่ยนสถานะกลับเป็นว่าง)
        $sqlItem = "UPDATE book_items SET status = 'available' WHERE id = ?";
        $pdo->prepare($sqlItem)->execute([$item_id]);

        $pdo->commit();
        
        // ส่งกลับไปหน้าประวัติพร้อมแจ้งเตือน
        header("Location: my_history.php?status=returned");

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>