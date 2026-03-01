<?php
// ไฟล์ line_helper.php สำหรับเก็บฟังก์ชันกลาง

function sendLinePush($to, $message) {
    // ใช้ Access Token ของบอทคุณ
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
    
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
?>