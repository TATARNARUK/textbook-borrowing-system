<?php
session_start();
require_once 'config.php';

// =========================================================
// 🟢 ฟังก์ชันส่งการแจ้งเตือนผ่าน LINE Messaging API
// =========================================================
function sendLineMessageAPI($message) {
    // ใช้รหัสเดิมที่คุณตั้งค่าผ่าน LINE Developers
    $access_token = '1nKci4ldstfiR5FpGC1r+1HYvNu34Hdl3VZj6ua9QMXeEJq2BG0QaalaoXgsp6y1MjQxB36Xb0yVEnD5wv9i+Ea0U6gWJ32SIrTEMn0nnkYBoQ8ybvYNUmY3lQEgouyT0a1A9Okfs6vD03mij5yARAdB04t89/1O/w1cDnyilFU='; 
    $admin_user_id = 'Ua019e53e001e2e7288fa2ef981c0921a'; 

    if(empty($access_token) || empty($admin_user_id)) return false;

    $url = 'https://api.line.me/v2/bot/message/push';
    $data = [
        'to' => $admin_user_id,
        'messages' => [
            [
                'type' => 'text',
                'text' => $message
            ]
        ]
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
    
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
// =========================================================

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

        // -------------------------------------------------------------
        // 🟢 ดึงข้อมูลเพื่อส่งแจ้งเตือนเข้า LINE (ทำก่อน Commit เพื่อความชัวร์)
        // -------------------------------------------------------------
        $stmtInfo = $pdo->prepare("
            SELECT b.title, u.fullname 
            FROM transactions t 
            JOIN book_items bi ON t.book_item_id = bi.id 
            JOIN book_masters b ON bi.book_master_id = b.id 
            JOIN users u ON t.user_id = u.id 
            WHERE t.id = ?
        ");
        $stmtInfo->execute([$trans_id]);
        $info = $stmtInfo->fetch();

        $pdo->commit();

        // -------------------------------------------------------------
        // 🟢 ส่งข้อความเข้า LINE
        // -------------------------------------------------------------
        if ($info) {
            $msg = "🟢 คืนหนังสือสำเร็จ!\n";
            $msg .= "👤 ผู้คืน: " . $info['fullname'] . "\n";
            $msg .= "📚 หนังสือ: " . $info['title'] . "\n";
            $msg .= "📅 วันที่คืน: " . date("d/m/Y H:i") . "\n";
            $msg .= "✅ สถานะ: กลับเข้าสู่ระบบพร้อมยืมต่อ";

            sendLineMessageAPI($msg);
        }
        
        // ส่งกลับไปหน้าประวัติพร้อมแจ้งเตือน
        header("Location: my_history.php?status=returned");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "Error: " . $e->getMessage();
    }
}
?>