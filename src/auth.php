<?php
session_start();
header('Content-Type: application/json'); // บอกว่าไฟล์นี้จะส่งกลับเป็น JSON เท่านั้น
require_once 'config.php';

// รับค่าที่ส่งมาจาก JavaScript
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 1. เช็ค ADMIN ก่อน (Admin เช็คจาก DB เราเอง)
    $stmt = $pdo->prepare("SELECT id, fullname, role, password FROM users WHERE student_id = :id AND role = 'admin'");
    $stmt->execute([':id' => $student_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['fullname'] = $admin['fullname'];
        $_SESSION['role'] = $admin['role'];
        $_SESSION['student_id'] = $student_id;
        
        echo json_encode(['status' => 'success', 'message' => 'เข้าสู่ระบบผู้ดูแลระบบสำเร็จ']);
        exit;
    }

    // 2. เช็ค RMS API (สำหรับนักเรียน)
    // จัดรูปแบบ Parameter ตามที่ครูระบุ: "User----Pass"
    $apiParameter = $student_id . "----" . $password;

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://rms.bncc.ac.th/api/pornchai/api.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Accept: */*",
            "X-Application-Key: 7f2dabbc4721bb7229846555123b42fc", // Key ครู
            "X-Application-Name: check_auth_rms",
            "X-Application-Parameter: " . $apiParameter, // User----Pass
            "X-Application-Request: pornchai"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo json_encode(['status' => 'error', 'message' => 'cURL Error: ' . $err]);
        exit;
    }

    $data = json_decode($response, true);

    // ตรวจสอบผลลัพธ์จาก API
    if (!empty($data['result']) && count($data['result']) > 0) {
        
        // --- API ผ่านแล้ว ---
        $userData = $data['result'][0];

        // 3. เช็คว่ามีชื่อในฐานข้อมูลเราไหม (เพื่อเก็บ Session)
        $stmt = $pdo->prepare("SELECT id, fullname, role FROM users WHERE student_id = :id");
        $stmt->execute([':id' => $student_id]);
        $local_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($local_user) {
            // มีข้อมูล -> เข้าสู่ระบบ
            $_SESSION['user_id'] = $local_user['id'];
            $_SESSION['fullname'] = $local_user['fullname'];
            $_SESSION['role'] = 'student'; 
            $_SESSION['student_id'] = $student_id; 
            
            // เก็บข้อมูลเสริมจาก RMS (ถ้าต้องการ)
            $_SESSION['department'] = $userData['department'];

            echo json_encode(['status' => 'success', 'message' => 'เข้าสู่ระบบสำเร็จ']);
        } else {
            // API ผ่าน แต่ไม่มีชื่อใน DB
            echo json_encode(['status' => 'error', 'message' => 'ยืนยันตัวตน RMS สำเร็จ แต่ไม่พบชื่อในระบบห้องสมุด (กรุณาติดต่อ Admin เพื่อ Sync ข้อมูล)']);
        }

    } else {
        // API ไม่ผ่าน (รหัสผิด)
        echo json_encode(['status' => 'error', 'message' => 'รหัสนักเรียน หรือ รหัสผ่าน ไม่ถูกต้อง']);
    }
    exit;
}
?>