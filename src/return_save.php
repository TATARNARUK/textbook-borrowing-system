<?php
session_start();
require_once 'config.php';
include 'line_helper.php'; // เรียกไฟล์ฟังก์ชันเข้ามา

// เมื่อทำรายการสำเร็จแล้ว ก็เรียกใช้งานฟังก์ชัน
$uid = $userData['line_user_id']; // ID ไลน์นักเรียนจากฐานข้อมูล
$message = "ยืนยันการทำรายการสำเร็จ!";
sendLinePush($uid, $message);

// นำฟังก์ชัน sendLinePush ที่เราทำไว้มาใช้ (แนะนำให้แยกเป็นไฟล์ functions.php แล้ว include เข้ามาจะดีมากครับ)
function sendLinePush($to, $message) {
    $access_token = '1nKci4ldstfiR5FpGC1r+1HYvNu34Hdl3VZj6ua9QMXeEJq2BG0QaalaoXgsp6y1MjQxB36Xb0yVEnD5wv9i+Ea0U6gWJ32SIrTEMn0nnkYBoQ8ybvYNUmY3lQEgouyT0a1A9Okfs6vD03mij5yARAdB04t89/1O/w1cDnyilFU='; 
    if(empty($access_token) || empty($to)) return false;

    $url = 'https://api.line.me/v2/bot/message/push';
    $data = [
        'to' => $to,
        'messages' => [['type' => 'text', 'text' => $message]]
    ];
    $post_body = json_encode($data, JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// รับค่า ID รายการที่ต้องการคืน
$transaction_id = $_GET['id'] ?? null;

if (!$transaction_id) {
    header("Location: index.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. ดึงข้อมูลการยืมเพื่อดูว่าใครยืม เล่มไหน และ line_user_id คืออะไร
    $stmtData = $pdo->prepare("SELECT t.user_id, t.book_item_id, u.fullname, u.line_user_id, bm.title 
                               FROM transactions t
                               JOIN users u ON t.user_id = u.id
                               JOIN book_items bi ON t.book_item_id = bi.id
                               JOIN book_masters bm ON bi.book_master_id = bm.id
                               WHERE t.id = ? FOR UPDATE");
    $stmtData->execute([$transaction_id]);
    $data = $stmtData->fetch();

    if ($data) {
        // 2. อัปเดตสถานะรายการยืมเป็น 'returned'
        $updateTrans = $pdo->prepare("UPDATE transactions SET status = 'returned', return_date = NOW() WHERE id = ?");
        $updateTrans->execute([$transaction_id]);

        // 3. อัปเดตสถานะเล่มหนังสือเป็น 'available'
        $updateBook = $pdo->prepare("UPDATE book_items SET status = 'available' WHERE id = ?");
        $updateBook->execute([$data['book_item_id']]);

        $pdo->commit();

        // -------------------------------------------------------------
        // 🟢 ส่งแจ้งเตือนการคืนหนังสือสำเร็จ
        // -------------------------------------------------------------
        if ($data['line_user_id']) {
            $msg = "✅ คืนหนังสือเรียบร้อยแล้วครับ\n";
            $msg .= "👤 คุณ: " . $data['fullname'] . "\n";
            $msg .= "📚 หนังสือ: " . $data['title'] . "\n";
            $msg .= "⏰ วันที่คืน: " . date("d/m/Y H:i") . "\n";
            $msg .= "ขอบคุณที่ใช้บริการห้องสมุด IT Bangna ครับ";

            sendLinePush($data['line_user_id'], $msg);
        }
        // -------------------------------------------------------------

        header("Location: index.php?status=return_success");
        exit();
    } else {
        $pdo->rollBack();
        header("Location: index.php?status=error");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    header("Location: index.php?status=error");
}