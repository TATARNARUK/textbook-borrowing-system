<?php
set_time_limit(0); 
ini_set('memory_limit', '512M');

session_start();
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("❌ Access Denied");
}

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://rms.bncc.ac.th/api/pornchai/api.php",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_HTTPHEADER => [
        "X-Application-Request: pornjira",
        "X-Application-Key: 32ec3d9bec382ca253b8230a0d9b33c4",
        "X-Application-Name: copy_student_data",
        "X-Application-Parameter: 0"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) { die("cURL Error: " . $err); }

$data = json_decode($response, true);

if (!empty($data) && isset($data['result'])) {
    
    $students_list = $data['result'];
    $count = 0;

    // เตรียม SQL
    $sql = "INSERT INTO users (student_id, password, fullname, role) 
            VALUES (:student_id, :password, :fullname, 'student') 
            ON DUPLICATE KEY UPDATE fullname = :fullname_update";
            
    $stmt = $pdo->prepare($sql);
    
    // เริ่ม Transaction
    $pdo->beginTransaction();

    try {
        foreach ($students_list as $student) {
            
            // ดึงค่าแผนกออกมา (ถ้าไม่มีให้เป็นค่าว่าง)
            $major = $student['std_major'] ?? '';

            // ✅ เช็คคำว่า "เทคโนโลยีสารสนเทศ" เหมือนเดิม
            if (strpos($major, 'เทคโนโลยีสารสนเทศ') !== false) {
                
                $std_code = $student['std_code']; 
                $full_name = trim($student['std_prefix'] . $student['std_firstname'] . ' ' . $student['std_lastname']);

                if ($std_code && $full_name) {
                    $default_password = password_hash($std_code, PASSWORD_DEFAULT);
                    $stmt->execute([
                        ':student_id' => $std_code,        
                        ':password' => $default_password,
                        ':fullname' => $full_name,       
                        ':fullname_update' => $full_name 
                    ]);
                    $count++;
                }
            }
        }
        
        $pdo->commit();

        echo "<script>
            alert('✅ สำเร็จ! ดึงนักเรียนสาขา \"เทคโนโลยีสารสนเทศ\" ได้ทั้งหมด " . number_format($count) . " คน');
            window.location.href = 'admin_users.php'; // เปิดใช้งานบรรทัดนี้แล้ว
        </script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "❌ Error: " . $e->getMessage();
    }

} else {
    echo "❌ ไม่พบข้อมูลจาก API";
}
?>