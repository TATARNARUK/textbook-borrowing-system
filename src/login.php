<?php
session_start();
require_once 'config.php'; // เรียกใช้ไฟล์ config ที่เราทำไว้ใน Docker

// ตรวจสอบข้อมูลเมื่อมีการกดปุ่ม Login
if (isset($_POST['student_id']) && isset($_POST['password'])) {
    
    $student_id = $_POST['student_id'];
    $password = $_POST['password']; // รับรหัสผ่าน (ถ้าจะให้ดีควรใช้ password_verify ในอนาคต)

    // 1. เช็คข้อมูลในตาราง users
    // (เปรียบเทียบรหัสผ่านแบบ SHA1 ตามโค้ดเดิมของคุณ)
    $stmt = $pdo->prepare("SELECT id, fullname, role FROM users WHERE student_id = :id AND password = :pass");
    $stmt->bindValue(':id', $student_id, PDO::PARAM_STR);
    $stmt->bindValue(':pass', $password, PDO::PARAM_STR); // *ข้อควรระวัง: ในการใช้งานจริงควร Hash รหัสผ่าน
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        // 2. ถ้าเจอข้อมูล ให้สร้าง Session
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['fullname'] = $row['fullname'];
        $_SESSION['role'] = $row['role']; // เก็บว่าเป็น admin หรือ student

        // ส่งไปหน้าแรก
        header('location: index.php');
        exit();
    } else {
        // 3. ถ้าไม่เจอ (รหัสผิด)
        $error_msg = "รหัสนักเรียน หรือ รหัสผ่าน ไม่ถูกต้อง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบยืมหนังสือเรียน</title>
    <link rel="icon" type="image/png" href="images/LOGO-BNCC.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-image: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?q=80&w=2070&auto=format&fit=crop'); /* รูป Background ชั่วคราว */
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify_content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .topbar-bncc {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            
            display: flex;           /* ต้องมีบรรทัดนี้ */
            align-items: center;     /* จัดกึ่งกลางแนวตั้ง */
            justify-content: center; /* <--- เพิ่มบรรทัดนี้! เพื่อจัดกึ่งกลางแนวนอน */
            
            gap: 15px;
            z-index: 1000;
        }
        .topbar-bncc img { height: 50px; }
        .topbar-bncc-title { font-weight: bold; font-size: 1.5rem; line-height: 1.2; }
        .topbar-bncc-subtitle { font-size: 1rem; color: #666; }
    </style>
</head>
<body>

    <div class="topbar-bncc">
        <div class="text-center">
            <div class="topbar-bncc-title">TEXTBOOK BORROWING SYSTEM</div>
            <div class="topbar-bncc-subtitle">ระบบยืม-คืนหนังสือเรียนฟรี</div>
        </div>
    </div>

    <div class="container d-flex justify-content-center">
        <div class="login-card">
            <h3 class="text-center mb-4">เข้าสู่ระบบ</h3>
            
            <form action="" method="post">
                <div class="mb-3">
                    <label class="form-label">รหัสนักเรียน / ชื่อผู้ใช้</label>
                    <input type="text" name="student_id" class="form-control" placeholder="กรอกรหัสนักเรียน" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" placeholder="กรอกรหัสผ่าน" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-2">เข้าสู่ระบบ</button>
                <a href="register.php" class="btn btn-outline-danger w-100">สมัครสมาชิก</a>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (isset($error_msg)) : ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: '<?php echo $error_msg; ?>',
            confirmButtonText: 'ตกลง'
        });
    </script>
    <?php endif; ?>

</body>
</html>