<?php
session_start();
require_once 'config.php';

// 1. เช็คว่า Login หรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$book_master_id = $_GET['id'];

try {
    // ---------------------------------------------------------
    // 🛑 Step 2: เช็คว่า "ยืมซ้ำ" หรือไม่? (Logic ใหม่)
    // ---------------------------------------------------------
    // เช็คในตาราง transactions ว่า user คนนี้ ยืมหนังสือรหัส master นี้ และยังไม่คืน (status='borrowed') หรือไม่
    $checkDup = $pdo->prepare("
        SELECT COUNT(*) FROM transactions t
        JOIN book_items bi ON t.book_item_id = bi.id
        WHERE t.user_id = ? 
        AND bi.book_master_id = ? 
        AND t.status = 'borrowed'
    ");
    $checkDup->execute([$user_id, $book_master_id]);
    $is_duplicate = $checkDup->fetchColumn();

    if ($is_duplicate > 0) {
        // ถ้ายืมอยู่แล้ว ให้ดีดกลับไปพร้อม status = duplicate
        header("Location: index.php?status=duplicate");
        exit();
    }

    // ---------------------------------------------------------
    // Step 3: เช็คว่ามีของว่างไหม (หาเล่มที่ available)
    // ---------------------------------------------------------
    $stmt = $pdo->prepare("SELECT id FROM book_items WHERE book_master_id = ? AND status = 'available' LIMIT 1");
    $stmt->execute([$book_master_id]);
    $item = $stmt->fetch();

    if ($item) {
        $book_item_id = $item['id'];
        
        // เริ่ม Transaction (เพื่อให้ทำงานพร้อมกัน ถ้าพลาดให้ rollback)
        $pdo->beginTransaction();

        // 3.1 อัพเดทสถานะเล่มหนังสือเป็น borrowed
        $updateItem = $pdo->prepare("UPDATE book_items SET status = 'borrowed' WHERE id = ?");
        $updateItem->execute([$book_item_id]);

        // 3.2 สร้างรายการยืมใน transactions (กำหนดคืนอีก 7 วัน)
        $return_due = date('Y-m-d', strtotime('+7 days'));
        $insertTrans = $pdo->prepare("INSERT INTO transactions (user_id, book_item_id, borrow_date, due_date, status) VALUES (?, ?, NOW(), ?, 'borrowed')");
        $insertTrans->execute([$user_id, $book_item_id, $return_due]);

        $pdo->commit(); // ยืนยันการทำงาน

        header("Location: index.php?status=success");
    } else {
        // ถ้าหนังสือหมดพอดี
        header("Location: index.php?status=error");
    }

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: index.php?status=error");
}
?>