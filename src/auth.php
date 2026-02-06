<?php
// 1. ตั้งค่า Header และ Session
session_start(); // เริ่ม Session ตั้งแต่บรรทัดแรกเลย
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($student_id) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
        exit;
    }

    // ----------------------------------------------------------------------
    // ส่วนที่ 1: เช็ค ADMIN (Admin ต้องเช็คจากฐานข้อมูลเราเสมอ)
    // ----------------------------------------------------------------------
    try {
        $stmt = $pdo->prepare("SELECT id, fullname, role, password FROM users WHERE student_id = :id AND role = 'admin'");
        $stmt->execute([':id' => $student_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            // Regenerate ID เพื่อความปลอดภัย (ป้องกัน Session Fixation)
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['fullname'] = $admin['fullname'];
            $_SESSION['role'] = $admin['role'];
            $_SESSION['student_id'] = $student_id;
            
            echo json_encode(['status' => 'success', 'message' => 'เข้าสู่ระบบ Admin สำเร็จ']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error']);
        exit;
    }

    // ----------------------------------------------------------------------
    // ส่วนที่ 2: เช็ค นักเรียน (ผ่าน RMS API + Auto Sync)
    // ----------------------------------------------------------------------
    $apiParameter = $student_id . "----" . $password;

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://rms.bncc.ac.th/api/pornchai/api.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30, // เพิ่มเวลาเผื่อเว็บวิลัยช้า
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Accept: */*",
            "X-Application-Key: 7f2dabbc4721bb7229846555123b42fc",
            "X-Application-Name: check_auth_rms",
            "X-Application-Parameter: " . $apiParameter,
            "X-Application-Request: pornchai"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถติดต่อ RMS ได้ (Connection Error)']);
        exit;
    }

    $data = json_decode($response, true);

    // ตรวจสอบว่า API ตอบกลับมาว่า Login สำเร็จไหม
    if (!empty($data['result']) && count($data['result']) > 0) {
        
        $userData = $data['result'][0];
        
        // เตรียมข้อมูล (ใช้ ?? ป้องกัน Error กรณี API ส่งมาไม่ครบ)
        $std_code = trim($userData['username'] ?? $student_id);
        $prefix   = trim($userData['std_prefix'] ?? '');
        $fname    = trim($userData['first_name'] ?? '');
        $lname    = trim($userData['last_name'] ?? '');
        
        // รวมชื่อ (ตัดช่องว่างส่วนเกินออก)
        $full_name = trim("$prefix$fname $lname");
        if (empty($full_name)) $full_name = "นักเรียน (ไม่มีชื่อจากระบบ)";

        // ------------------------------------------------------------------
        // 🔥 AUTO SYNC
        // ------------------------------------------------------------------
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE student_id = :id");
            $stmt->execute([':id' => $std_code]);
            $local_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($local_user) {
                // UPDATE: อัปเดตชื่อ
                $updateStmt = $pdo->prepare("UPDATE users SET fullname = :fullname WHERE id = :id");
                $updateStmt->execute([
                    ':fullname' => $full_name,
                    ':id' => $local_user['id']
                ]);
                $user_db_id = $local_user['id'];
            } else {
                // INSERT: เพิ่มเด็กใหม่ (ใส่รหัสผ่านหลอกๆ ไว้ เพราะเช็คผ่าน API)
                $insertStmt = $pdo->prepare("INSERT INTO users (student_id, password, fullname, role) VALUES (:student_id, :password, :fullname, 'student')");
                $insertStmt->execute([
                    ':student_id' => $std_code,
                    ':password' => password_hash('RMS_LOGIN_USER', PASSWORD_DEFAULT), 
                    ':fullname' => $full_name
                ]);
                $user_db_id = $pdo->lastInsertId();
            }

            // ตั้งค่า Session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_db_id;
            $_SESSION['fullname'] = $full_name;
            $_SESSION['role'] = 'student';
            $_SESSION['student_id'] = $std_code;

            echo json_encode(['status' => 'success', 'message' => 'เข้าสู่ระบบสำเร็จ']);

        } catch (Exception $e) {
            // ถ้าบันทึก DB ไม่ได้ ให้ Login ได้อยู่ดี (แต่ไม่มี Session ID ของ DB)
            // หรือจะเลือกให้ Error เลยก็ได้
            echo json_encode(['status' => 'error', 'message' => 'ระบบฐานข้อมูลมีปัญหา: ' . $e->getMessage()]);
        }

    } else {
        // --- RMS บอกว่ารหัสผิด ---
        echo json_encode(['status' => 'error', 'message' => 'รหัสนักเรียน หรือ รหัสผ่าน ไม่ถูกต้อง']);
    }
    exit;
}
?>