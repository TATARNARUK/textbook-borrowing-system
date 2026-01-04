<?php
session_start();
require_once 'config.php';

if (isset($_POST['register'])) {
    $student_id = $_POST['student_id'];
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
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
    } else {
        // 2. เช็คว่ารหัสนักเรียนนี้มีในระบบหรือยัง
        $check = $pdo->prepare("SELECT id FROM users WHERE student_id = :id");
        $check->execute(['id' => $student_id]);

        if ($check->rowCount() > 0) {
            $error_msg = "รหัสนักเรียนนี้ถูกลงทะเบียนไปแล้ว";
        } else {
            // 3. บันทึกข้อมูลลงฐานข้อมูล
            // (ในระบบจริงควรเข้ารหัส password ด้วย password_hash($password, PASSWORD_DEFAULT))
            $sql = "INSERT INTO users (student_id, password, fullname, phone, role) VALUES (:id, :pass, :name, :phone, 'student')";
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
    <link rel="icon" type="image/png" href="images/books.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style-register.css">

</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
                <img src="images/books.png" height="40" alt="Logo">
                <div class="d-none d-md-block text-start">
                    <h5 class="m-0 fw-bold text-primary" style="font-family: 'Kanit', sans-serif;">
                        TEXTBOOK BORROWING SYSTEM
                    </h5>
                    <small class="text-muted">ระบบยืม-คืนหนังสือเรียนฟรี</small>
                </div>
            </a>

            <div class="ms-auto d-flex align-items-center gap-3">
                <a href="manual.php" class="text-decoration-none text-dark fw-medium small">
                    <i class="fas fa-book me-1"></i> คู่มือการใช้งานระบบ
                </a>
                <div class="vr mx-2 text-muted" style="height: 20px;"></div>
                <a href="https://www.facebook.com/kittikun.nookeaw?locale=th_TH" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 ms-2">
                    <i class="fas fa-headset me-1"></i> ติดต่อเจ้าหน้าที่
                </a>
            </div>
        </div>
    </nav>

    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh; padding-top: 50px;">
        <div class="register-card">
            <h3 class="text-center mb-4">สมัครสมาชิกใหม่</h3>

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

                <div class="mb-3">
                    <label class="form-label">เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" class="form-control" placeholder="เช่น 0812345678" maxlength="10">
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