<?php
session_start();
require_once 'config.php';
// ดึงฟังก์ชันมาจากไฟล์ Helper แค่ครั้งเดียว (ไม่ต้องเขียนฟังก์ชัน sendLinePush ในไฟล์นี้อีก)
require_once 'line_helper.php'; 

// 1. เช็คว่าล็อกอินหรือยัง?
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. เช็คว่ามีการส่งรหัสหนังสือมาหรือไม่?
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$master_id = $_GET['id'];

try {
    // 3. เช็คติด Block หรือไม่
    $stmtBlock = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'borrowed' AND due_date < NOW()");
    $stmtBlock->execute([$user_id]);
    if ($stmtBlock->fetchColumn() > 0) {
        echo "<script>alert('คุณมีหนังสือค้างส่งเกินกำหนด ไม่สามารถยืมเพิ่มได้'); window.location='index.php';</script>";
        exit();
    }

    // 4. เช็คยืมซ้ำ
    $stmtDup = $pdo->prepare("SELECT COUNT(*) FROM transactions t 
                              JOIN book_items bi ON t.book_item_id = bi.id 
                              WHERE t.user_id = ? AND t.status = 'borrowed' AND bi.book_master_id = ?");
    $stmtDup->execute([$user_id, $master_id]);
    if ($stmtDup->fetchColumn() > 0) {
        header("Location: index.php?status=duplicate");
        exit();
    }

    // เริ่มต้น Transaction ฐานข้อมูล
    $pdo->beginTransaction();

    // 5. หาเล่มที่ว่าง
    $stmtFind = $pdo->prepare("SELECT id FROM book_items WHERE book_master_id = ? AND status = 'available' LIMIT 1 FOR UPDATE");
    $stmtFind->execute([$master_id]);
    $bookItem = $stmtFind->fetch();

    if ($bookItem) {
        $item_id = $bookItem['id'];
        
        // อัปเดตสถานะหนังสือเป็น 'ถูกยืม'
        $updateBook = $pdo->prepare("UPDATE book_items SET status = 'borrowed' WHERE id = ?");
        $updateBook->execute([$item_id]);

        // สร้างรายการยืม (บวกเวลาไป 7 วัน)
        $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));
        $insertTrans = $pdo->prepare("INSERT INTO transactions (user_id, book_item_id, borrow_date, due_date, status) VALUES (?, ?, NOW(), ?, 'borrowed')");
        $insertTrans->execute([$user_id, $item_id, $due_date]);

        // ยืนยันการบันทึก
        $pdo->commit();

        // -------------------------------------------------------------
        // 🟢 ส่งแจ้งเตือนเข้า LINE หลังยืมสำเร็จ
        // -------------------------------------------------------------
        try {
            // หาชื่อหนังสือ
            $stmtBook = $pdo->prepare("SELECT title FROM book_masters WHERE id = ?");
            $stmtBook->execute([$master_id]);
            $bookData = $stmtBook->fetch();
            $book_title = $bookData ? $bookData['title'] : "ไม่ทราบชื่อหนังสือ";

            // หาชื่อนักเรียน และ Line ID
            $stmtUser = $pdo->prepare("SELECT fullname, line_user_id FROM users WHERE id = ?"); 
            $stmtUser->execute([$user_id]);
            $userData = $stmtUser->fetch();
            
            $student_name = $userData ? $userData['fullname'] : "นักเรียน";
            $student_line_id = $userData ? $userData['line_user_id'] : null;

            // ส่งหานักเรียน (ถ้าผูกไลน์ไว้)
            if (!empty($student_line_id)) {
                $msg = "📖 แจ้งเตือนการยืมหนังสือสำเร็จ\n";
                $msg .= "👤 สวัสดีคุณ: " . $student_name . "\n";
                $msg .= "📚 คุณได้ยืม: " . $book_title . "\n";
                $msg .= "📅 กำหนดส่งคืน: " . date("d/m/Y", strtotime($due_date)) . "\n";
                $msg .= "⚠️ โปรดคืนหนังสือภายในกำหนดเพื่อรักษาสิทธิ์การยืมนะครับ";
                
                sendLinePush($student_line_id, $msg);
            }
            
            // ส่งหา Admin (แอดมินรับทราบทุกรายการ)
            $admin_user_id = 'Ua019e53e001e2e7288fa2ef981c0921a';
            $admin_msg = "🔔 มีการยืมหนังสือใหม่ในระบบ!\nผู้ยืม: $student_name\nหนังสือ: $book_title";
            sendLinePush($admin_user_id, $admin_msg);

        } catch (Exception $lineError) {
            // กรณีส่ง LINE ไม่สำเร็จ ไม่ต้องให้เว็บพัง แค่บันทึก Log
            error_log("Line Notification Error: " . $lineError->getMessage());
        }
        // -------------------------------------------------------------

        // เด้งกลับหน้าแรกพร้อมสถานะสำเร็จ
        header("Location: index.php?status=success"); 
        exit(); 

    } else {
        // กรณีหนังสือหมดพอดีตอนกด
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        header("Location: index.php?status=error");
        exit();
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    header("Location: index.php?status=error");
    exit();
}
?>