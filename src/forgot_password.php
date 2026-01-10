<?php
session_start();
require_once 'config.php';

$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = trim($_POST['student_id']);
    $fullname = trim($_POST['fullname']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "รหัสผ่านใหม่ไม่ตรงกัน";
        $msg_type = "danger";
    } else {
        // 1. เช็คว่ามี User นี้จริงไหม และชื่อตรงกันไหม
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND fullname = ?");
        $stmt->execute([$student_id, $fullname]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. ถ้าข้อมูลถูกต้อง ให้เปลี่ยนรหัสผ่านทันที
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($updateStmt->execute([$hashed_password, $user['id']])) {
                $message = "เปลี่ยนรหัสผ่านสำเร็จ! กรุณาเข้าสู่ระบบใหม่";
                $msg_type = "success";
            } else {
                $message = "เกิดข้อผิดพลาดในการบันทึก";
                $msg_type = "danger";
            }
        } else {
            $message = "ไม่พบข้อมูล! รหัสนักเรียน หรือ ชื่อ-สกุล ไม่ถูกต้อง";
            $msg_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - TEXTBOOK BORROWING SYSTEM</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-image: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?q=80&w=2070&auto=format&fit=crop');
            /* รูป Background ชั่วคราว */
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ตกแต่ง Navbar ให้ดูพรีเมียม */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            /* ทำพื้นหลัง Nav เบลอนิดๆ จะสวยมาก */
            border-bottom: 2px solid #0d6efd;
            /* เพิ่มเส้นสีน้ำเงินบางๆ ด้านล่าง */
        }

        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        .topbar-bncc {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);

            display: flex;
            /* ต้องมีบรรทัดนี้ */
            align-items: center;
            /* จัดกึ่งกลางแนวตั้ง */
            justify-content: center;
            /* <--- เพิ่มบรรทัดนี้! เพื่อจัดกึ่งกลางแนวนอน */

            gap: 15px;
            z-index: 1000;
        }

        .topbar-bncc img {
            height: 50px;
        }

        .topbar-bncc-title {
            font-weight: bold;
            font-size: 1.5rem;
            line-height: 1.2;
        }

        .topbar-bncc-subtitle {
            font-size: 1rem;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card card-reset p-5 text-center shadow-lg border-0" style="max-width: 500px;">

            <img src="images/books.png" width="80" class="mb-4 mx-auto">

            <h3 class="fw-bold text-primary mb-3">ลืมรหัสผ่าน?</h3>

            <p class="text-muted mb-4">
                เพื่อความปลอดภัยของข้อมูลบัญชีผู้ใช้งาน<br>
                ระบบไม่อนุญาตให้รีเซ็ตรหัสผ่านด้วยตนเอง
            </p>

            <div class="alert alert-info border-0 bg-light-info text-dark rounded-4 p-4 mb-4 text-start">
                <strong><i class="fa-solid fa-circle-info me-2"></i> วิธีการกู้คืนรหัสผ่าน:</strong>
                <ul class="mb-0 mt-2 ps-3 small">
                    <li class="mb-1">เตรียม <strong>บัตรนักเรียน</strong> หรือ <strong>บัตรประชาชน</strong></li>
                    <li class="mb-1">และทำการติดต่อเจ้าหน้าที่ตรงนี้<a href="https://www.facebook.com/kittikun.nookeaw?locale=th_TH" target="_blank" class="text-decoration-none">
                            <i class="fas fa-headset me-เ1"></i> ติดต่อเจ้าหน้าที่
                        </a></li>
                    <li>เจ้าหน้าที่จะทำการตรวจสอบตัวตนและตั้งรหัสผ่านใหม่ให้</li>
                </ul>
            </div>

            <a href="login.php" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i> กลับไปหน้าเข้าสู่ระบบ
            </a>

        </div>
    </div>
</body>

</html>