<?php
// 1. ตั้งค่า Header และปิด Error กวนใจ
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ----------------------------------------------------------------------
    // ส่วนที่ 1: เช็ค ADMIN (Admin ต้องเช็คจากฐานข้อมูลเราเสมอ)
    // ----------------------------------------------------------------------
    try {
        $stmt = $pdo->prepare("SELECT id, fullname, role, password FROM users WHERE student_id = :id AND role = 'admin'");
        $stmt->execute([':id' => $student_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            session_start();
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
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Accept: */*",
            "X-Application-Key: 7f2dabbc4721bb7229846555123b42fc", // Key ปัจจุบัน
            "X-Application-Name: check_auth_rms",
            "X-Application-Parameter: " . $apiParameter,
            "X-Application-Request: pornchai"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        // กรณีเน็ตวิทยาลัยล่ม ให้ลองเช็ค Local DB เป็น Backup (Optional)
        echo json_encode(['status' => 'error', 'message' => 'เชื่อมต่อระบบ RMS ไม่ได้']);
        exit;
    }

    $data = json_decode($response, true);

    // ตรวจสอบว่า API ตอบกลับมาว่า Login สำเร็จไหม
    if (!empty($data['result']) && count($data['result']) > 0) {
        
        // --- ✅ Login RMS ผ่านแล้ว! ---
        $userData = $data['result'][0];
        
        // เตรียมข้อมูลสำหรับบันทึกลง DB
        $std_code = trim($userData['username']); // หรือ $userData['std_code'] แล้วแต่ API ส่ง
        $prefix = $userData['std_prefix'] ?? ''; // บางที API login ไม่ส่ง prefix มา อาจต้องข้าม
        $fname = $userData['first_name'];
        $lname = $userData['last_name'];
        $full_name = trim("$prefix$fname $lname");
        $dept = $userData['department'] ?? 'นักเรียน';

        // ------------------------------------------------------------------
        // 🔥 AUTO SYNC: บันทึกหรืออัปเดตข้อมูลลงฐานข้อมูลเราทันที
        // ------------------------------------------------------------------
        try {
            // เช็คว่ามีคนนี้ใน DB เราหรือยัง
            $stmt = $pdo->prepare("SELECT id FROM users WHERE student_id = :id");
            $stmt->execute([':id' => $std_code]);
            $local_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($local_user) {
                // กรณี A: มีอยู่แล้ว -> อัปเดตชื่อให้เป็นปัจจุบัน (เผื่อเปลี่ยนชื่อ)
                $updateSql = "UPDATE users SET fullname = :fullname WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':fullname' => $full_name,
                    ':id' => $local_user['id']
                ]);
                $user_db_id = $local_user['id'];
            } else {
                // กรณี B: ยังไม่มี (เด็กใหม่) -> เพิ่มลง DB เลย!
                $insertSql = "INSERT INTO users (student_id, password, fullname, role) VALUES (:student_id, :password, :fullname, 'student')";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    ':student_id' => $std_code,
                    ':password' => password_hash('RMS_LOGIN', PASSWORD_DEFAULT), // รหัสหลอก
                    ':fullname' => $full_name
                ]);
                $user_db_id = $pdo->lastInsertId(); // รับ ID ที่เพิ่งสร้าง
            }

            // ------------------------------------------------------------------
            // สร้าง Session เพื่อเข้าสู่ระบบ
            // ------------------------------------------------------------------
            session_start();
            $_SESSION['user_id'] = $user_db_id;
            $_SESSION['fullname'] = $full_name;
            $_SESSION['role'] = 'student';
            $_SESSION['student_id'] = $std_code;
            $_SESSION['department'] = $dept;

            echo json_encode(['status' => 'success', 'message' => 'เข้าสู่ระบบสำเร็จ (ข้อมูลอัปเดตแล้ว)']);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Login RMS ผ่าน แต่บันทึกข้อมูลลงระบบไม่ได้: ' . $e->getMessage()]);
        }

    } else {
        // --- ❌ RMS บอกว่ารหัสผิด ---
        echo json_encode(['status' => 'error', 'message' => 'รหัสนักเรียน หรือ รหัสผ่าน ไม่ถูกต้อง']);
    }
    exit;
}
?>