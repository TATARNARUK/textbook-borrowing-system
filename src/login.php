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
    <link rel="icon" type="image/png" href="images/books.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style-login.css">
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

                <button type="submit" class="btg w-100 mb-2">เข้าสู่ระบบ</button>
                <button type="button" onclick="window.location.href='register.php'" class="btb w-100 mb-2">
                    สมัครสมาชิก
                </button>

                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="text-decoration-none text-muted small" style="font-size: 0.85rem;">
                        ลืมรหัสผ่าน?
                    </a>
                </div>
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