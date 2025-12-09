<?php
session_start();
require_once 'config.php';

if (isset($_POST['register'])) {
    $student_id = $_POST['student_id'];
    $fullname = $_POST['fullname'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
if (strlen($student_id) !== 11) {
        $error_msg = "รหัสนักเรียนต้องมี 11 หลัก";
    }
    // 2. เช็คความยาวรหัสผ่าน (ที่ทำไปเมื่อกี้)
    elseif (strlen($password) < 8) {
        $error_msg = "รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
    }
    // 3. เช็คว่ารหัสผ่านตรงกันไหม
    elseif ($password !== $confirm_password) {
        $error_msg = "รหัสผ่านยืนยันไม่ตรงกัน";
    }
    else {
        // 2. เช็คว่ารหัสนักเรียนนี้มีในระบบหรือยัง
        $check = $pdo->prepare("SELECT id FROM users WHERE student_id = :id");
        $check->execute(['id' => $student_id]);
        
        if ($check->rowCount() > 0) {
            $error_msg = "รหัสนักเรียนนี้ถูกลงทะเบียนไปแล้ว";
        } else {
            // 3. บันทึกข้อมูลลงฐานข้อมูล
            // (ในระบบจริงควรเข้ารหัส password ด้วย password_hash($password, PASSWORD_DEFAULT))
            $sql = "INSERT INTO users (student_id, password, fullname, role) VALUES (:id, :pass, :name, 'student')";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute(['id' => $student_id, 'pass' => $password, 'name' => $fullname])) {
                $success_msg = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
            } else {
                $error_msg = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ระบบยืมหนังสือเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
<style>
        body {
            font-family: 'Prompt', sans-serif;
            /* 👇 แก้บรรทัดนี้ให้ชื่อไฟล์ตรงกับหน้า Login ครับ */
            background-image: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?q=80&w=2070&auto=format&fit=crop'); 
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
            
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }

        .register-card {
            /* ปรับความโปร่งใสให้เหมือนหน้า Login */
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 500px;
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
        <div class="register-card">
            <h3 class="text-center mb-4 text-primary">สมัครสมาชิกใหม่</h3>
            
            <form action="" method="post">
            <div class="mb-3">
                <label class="form-label">รหัสนักเรียน</label>
                <input type="text" name="student_id" class="form-control" 
                    placeholder="เช่น 66209010001" 
                    required minlength="11" maxlength="11">
                <small class="text-muted" style="font-size: 0.8rem;">*กรอกเลขประจำตัว 11 หลัก</small>
            </div>
                
                <div class="mb-3">
                    <label class="form-label">ชื่อ-นามสกุล</label>
                    <input type="text" name="fullname" class="form-control" placeholder="เช่น นายรักเรียน เพียรศึกษา" required>
                </div>
                
                <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                    <small class="text-danger" style="font-size: 0.8rem;">*ต้องไม่ต่ำกว่า 8 ตัวอักษร</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">ยืนยันรหัสผ่าน</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>
                <button type="submit" name="register" class="btn btn-success w-100 mb-2">ลงทะเบียน</button>
                <a href="login.php" class="btn btn-outline-secondary w-100">กลับไปหน้าเข้าสู่ระบบ</a>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if (isset($error_msg)) : ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'ขออภัย',
            text: '<?php echo $error_msg; ?>',
            confirmButtonText: 'ตกลง'
        });
    </script>
    <?php endif; ?>

    <?php if (isset($success_msg)) : ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: '<?php echo $success_msg; ?>',
            confirmButtonText: 'เข้าสู่ระบบ'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'login.php';
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>