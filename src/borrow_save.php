<?php
session_start();
require_once 'config.php';

// =========================================================
// 🟢 ฟังก์ชันส่งการแจ้งเตือนผ่าน LINE Messaging API
// =========================================================
function sendLineMessageAPI($message) {
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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$master_id = $_GET['id'];

try {
    $stmtBlock = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'borrowed' AND due_date < NOW()");
    $stmtBlock->execute([$user_id]);
    if ($stmtBlock->fetchColumn() > 0) {
        echo "<script>alert('คุณมีหนังสือค้างส่งเกินกำหนด ไม่สามารถยืมเพิ่มได้'); window.location='index.php';</script>";
        exit();
    }

    $stmtDup = $pdo->prepare("SELECT COUNT(*) FROM transactions t 
                              JOIN book_items bi ON t.book_item_id = bi.id 
                              WHERE t.user_id = ? AND t.status = 'borrowed' AND bi.book_master_id = ?");
    $stmtDup->execute([$user_id, $master_id]);
    if ($stmtDup->fetchColumn() > 0) {
        header("Location: index.php?status=duplicate");
        exit();
    }

    $pdo->beginTransaction();

    $stmtFind = $pdo->prepare("SELECT id FROM book_items WHERE book_master_id = ? AND status = 'available' LIMIT 1 FOR UPDATE");
    $stmtFind->execute([$master_id]);
    $bookItem = $stmtFind->fetch();

    if ($bookItem) {
        $item_id = $bookItem['id'];

        $updateBook = $pdo->prepare("UPDATE book_items SET status = 'borrowed' WHERE id = ?");
        $updateBook->execute([$item_id]);

        $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));

        $insertTrans = $pdo->prepare("INSERT INTO transactions (user_id, book_item_id, borrow_date, due_date, status) VALUES (?, ?, NOW(), ?, 'borrowed')");
        $insertTrans->execute([$user_id, $item_id, $due_date]);

        $pdo->commit();

        // -------------------------------------------------------------
        // 🟢 ส่งแจ้งเตือนเข้า LINE บอท
        // -------------------------------------------------------------
        try {
            $stmtBook = $pdo->prepare("SELECT title FROM book_masters WHERE id = ?");
            $stmtBook->execute([$master_id]);
            $bookData = $stmtBook->fetch();
            $book_title = $bookData ? $bookData['title'] : "ไม่ทราบชื่อหนังสือ";

            // 🔥 แก้ไขแล้ว: เปลี่ยนจาก username เป็น fullname
            // ⚠️ ถ้าฐานข้อมูลของคุณใช้ชื่ออื่น (เช่น name) อย่าลืมเปลี่ยนคำว่า fullname เป็นคำนั้นด้วยนะครับ
            $stmtUser = $pdo->prepare("SELECT fullname FROM users WHERE id = ?"); 
            $stmtUser->execute([$user_id]);
            $userData = $stmtUser->fetch();
            $student_name = $userData ? $userData['fullname'] : "รหัสผู้ใช้: " . $user_id;

            $msg = "🔔 มีรายการยืมหนังสือใหม่!\n";
            $msg .= "👤 ผู้ยืม: " . $student_name . "\n";
            $msg .= "📚 หนังสือ: " . $book_title . "\n";
            $msg .= "⏰ วันที่ยืม: " . date("d/m/Y H:i") . "\n";
            $msg .= "📅 กำหนดคืน: " . date("d/m/Y", strtotime($due_date));

            sendLineMessageAPI($msg);
            
        } catch (Exception $lineError) {
            // โชว์ Error ให้เห็นว่าเกิดปัญหาอะไร จะได้แก้ถูกจุด
            die("<h3 style='color:red;'>🚨 เกิดข้อผิดพลาดตอนดึงข้อมูลส่ง LINE:</h3> <p>" . $lineError->getMessage() . "</p><p>โปรดตรวจสอบชื่อคอลัมน์ในตาราง users ให้ตรงกันด้วยครับ</p>");
        }
        // -------------------------------------------------------------

        header("Location: index.php?status=success"); 
        exit(); 

    } else {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: index.php?status=error");
        exit();
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    header("Location: index.php?status=error");
    exit();
}
?>