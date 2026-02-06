<?php
session_start();
require_once 'config.php';

// 1. เช็ค Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. เช็คว่าส่ง ID มาไหม
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$master_id = $_GET['id']; // นี่คือ ID ของปกหนังสือ (Book Master)

try {
    // -------------------------------------------------------------
    // ตรวจสอบความถูกต้องก่อนยืม (Validation)
    // -------------------------------------------------------------

    // A. เช็คว่าผู้ใช้ติด Blacklist (ยืมเกินกำหนด) หรือไม่?
    $stmtBlock = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'borrowed' AND due_date < NOW()");
    $stmtBlock->execute([$user_id]);
    if ($stmtBlock->fetchColumn() > 0) {
        // ถ้าติด Block ให้เด้งออกไปเลย (ป้องกันการยิง URL เข้ามาเอง)
        echo "<script>alert('คุณมีหนังสือค้างส่งเกินกำหนด ไม่สามารถยืมเพิ่มได้'); window.location='index.php';</script>";
        exit();
    }

    // B. เช็คว่ายืมเล่มนี้ซ้ำหรือไม่? (ยืมปกเดิมซ้ำไม่ได้ ถ้ายังไม่คืน)
    $stmtDup = $pdo->prepare("SELECT COUNT(*) FROM transactions t 
                              JOIN book_items bi ON t.book_item_id = bi.id 
                              WHERE t.user_id = ? AND t.status = 'borrowed' AND bi.book_master_id = ?");
    $stmtDup->execute([$user_id, $master_id]);
    if ($stmtDup->fetchColumn() > 0) {
        header("Location: index.php?status=duplicate"); // ส่งสถานะว่ายืมซ้ำ
        exit();
    }

    // -------------------------------------------------------------
    // เริ่มกระบวนการยืม (Transaction)
    // -------------------------------------------------------------
    $pdo->beginTransaction();

    // 1. ค้นหาหนังสือเล่มลูก (Item) ที่ "ว่าง" (available) มา 1 เล่ม
    // ใช้ FOR UPDATE เพื่อล็อคแถวนี้ไว้ กันคนอื่นแย่งยืมเสี้ยววินาทีเดียวกัน
    $stmtFind = $pdo->prepare("SELECT id FROM book_items WHERE book_master_id = ? AND status = 'available' LIMIT 1 FOR UPDATE");
    $stmtFind->execute([$master_id]);
    $bookItem = $stmtFind->fetch();

    if ($bookItem) {
        $item_id = $bookItem['id'];

        // 2. อัปเดตสถานะหนังสือเป็น "ถูกยืม" (borrowed)
        $updateBook = $pdo->prepare("UPDATE book_items SET status = 'borrowed' WHERE id = ?");
        $updateBook->execute([$item_id]);

        // 3. กำหนดวันคืน (อีก 7 วัน)
        $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));

        // 4. บันทึกประวัติการยืม (Transactions)
        $insertTrans = $pdo->prepare("INSERT INTO transactions (user_id, book_item_id, borrow_date, due_date, status) VALUES (?, ?, NOW(), ?, 'borrowed')");
        $insertTrans->execute([$user_id, $item_id, $due_date]);

        // 5. ยืนยันการทำงานทั้งหมด
        $pdo->commit();

        header("Location: index.php?status=success"); // ยืมสำเร็จ!

    } else {
        // กรณีหาหนังสือไม่เจอ (เช่น หมดพอดีในเสี้ยววินาทีนั้น)
        $pdo->rollBack();
        header("Location: index.php?status=error"); // แจ้งว่าของหมด
    }
} catch (Exception $e) {
    $pdo->rollBack();
    // ถ้ามี Error แปลกๆ ให้เด้งไปหน้าแรก (หรือจะ echo ดู error ก็ได้)
    header("Location: index.php?status=error");
}
