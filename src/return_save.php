<?php
session_start();
require_once 'config.php';
// ดึงฟังก์ชันมาจากไฟล์ Helper แค่ครั้งเดียวพอครับ ไม่ต้องเขียนซ้ำในไฟล์นี้แล้ว
require_once 'line_helper.php'; 

// 1. เช็คว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. รับค่า ID รายการที่ต้องการคืน (แก้ไขให้ตรงกับ URL ในรูปภาพคือ trans_id)
$transaction_id = $_GET['trans_id'] ?? null;

if (!$transaction_id) {
    header("Location: index.php"); // หรือหน้าประวัติการยืม
    exit();
}

try {
    // เริ่มทำงานกับฐานข้อมูล
    $pdo->beginTransaction();

    // 3. ดึงข้อมูลการยืมเพื่อดูว่าใครยืม เล่มไหน และ line_user_id คืออะไร
    $stmtData = $pdo->prepare("SELECT t.user_id, t.book_item_id, u.fullname, u.line_user_id, bm.title 
                               FROM transactions t
                               JOIN users u ON t.user_id = u.id
                               JOIN book_items bi ON t.book_item_id = bi.id
                               JOIN book_masters bm ON bi.book_master_id = bm.id
                               WHERE t.id = ? FOR UPDATE");
    $stmtData->execute([$transaction_id]);
    $data = $stmtData->fetch();

    if ($data) {
        // 4. อัปเดตสถานะรายการยืมเป็น 'returned' (ส่งคืนแล้ว)
        $updateTrans = $pdo->prepare("UPDATE transactions SET status = 'returned', return_date = NOW() WHERE id = ?");
        $updateTrans->execute([$transaction_id]);

        // 5. อัปเดตสถานะเล่มหนังสือเป็น 'available' (ว่าง)
        $updateBook = $pdo->prepare("UPDATE book_items SET status = 'available' WHERE id = ?");
        $updateBook->execute([$data['book_item_id']]);

        // บันทึกข้อมูลลงฐานข้อมูล
        $pdo->commit();

        // -------------------------------------------------------------
        // 🟢 ส่งแจ้งเตือนการคืนหนังสือสำเร็จเข้า LINE
        // -------------------------------------------------------------
        try {
            // ส่งหานักเรียนคนที่คืน (ถ้าเขาผูก LINE ไว้)
            if (!empty($data['line_user_id'])) {
                $msg = "✅ คืนหนังสือเรียบร้อยแล้วครับ\n";
                $msg .= "👤 คุณ: " . $data['fullname'] . "\n";
                $msg .= "📚 หนังสือ: " . $data['title'] . "\n";
                $msg .= "⏰ วันที่คืน: " . date("d/m/Y H:i") . "\n";
                $msg .= "ขอบคุณที่ใช้บริการห้องสมุด IT ครับ";

                sendLinePush($data['line_user_id'], $msg);
            }
            
            // ส่งหา Admin (ให้รู้ว่ามีหนังสือคืนเข้าระบบแล้ว)
            $admin_user_id = 'Ua019e53e001e2e7288fa2ef981c0921a';
            $admin_msg = "✅ มีนักเรียนคืนหนังสือเข้าสู่ระบบ\nผู้คืน: " . $data['fullname'] . "\nหนังสือ: " . $data['title'];
            
            sendLinePush($admin_user_id, $admin_msg);
            
        } catch (Exception $lineError) {
            // หากส่ง LINE ไม่ได้ ก็ไม่ให้กระทบการคืนหนังสือ
            error_log("Line Notification Error on Return: " . $lineError->getMessage());
        }
        // -------------------------------------------------------------

        // เด้งกลับไปหน้าที่คุณตั้งไว้ (ส่วนใหญ่มักจะเด้งกลับไปหน้าประวัติของแอดมินหรือหน้าผู้ใช้)
        // แนะนำให้แก้ตรงนี้ให้เด้งกลับไปหน้าจอที่คุณเคยกดปุ่มคืนมาครับ
        header("Location: my_history.php?status=success"); 
        exit();
    } else {
        $pdo->rollBack();
        header("Location: my_history.php?status=error");
        exit();
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    header("Location: my_history.php?status=error");
    exit();
}
?>