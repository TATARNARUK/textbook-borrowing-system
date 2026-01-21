<?php
session_start();
require_once 'config.php';

// ถ้าล็อกอินค้างไว้อยู่แล้ว ให้เด้งไปหน้าหลักทันที
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php require_once 'loader.php'; ?>

    <div id="welcome-screen">
        <div class="intro-content text-center">
            <div class="intro-icons mb-3 text-white">
                <i class="fas fa-code"></i>
                <i class="fas fa-user-graduate"></i>
                <i class="fas fa-book"></i>
            </div>
            <h1 class="fade-in-text fw-bold text-dark">Welcome To Website</h1>
            <h2 class="gradient-text">Textbook Borrowing System</h2>

            <p class="text-black mt-2 fw-bold" style="min-height: 30px; font-size: 1.1rem;">
                <span id="typewriter-text"></span><span class="cursor" style="color: black;">|</span>
            </p>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-custom fixed-top py-3" data-aos="fade-down" data-aos-duration="1500">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
                <img src="images/books.png" height="45" alt="Logo">
                <div class="d-none d-md-block text-start">
                    <h5 class="m-0 fw-bold text-primary" style="font-family: 'Noto Sans Thai', sans-serif;">
                        TEXTBOOK BORROWING SYSTEM
                    </h5>
                    <small>ระบบยืม-คืนหนังสือเรียนฟรี</small>
                </div>
            </a>

            <div class="ms-auto d-flex align-items-center gap-3">
                <a href="manual.php" class="text-decoration-none nav-link-custom small">
                    <i class="fas fa-book me-1"></i> คู่มือการใช้งาน
                </a>
                <div class="vr mx-2 text-secondary"></div>
                <a href="https://www.facebook.com/kittikun.nookeaw?locale=th_TH" target="_blank"
                    class="btn btn-sm btn-outline-primary rounded-pill px-3 ms-2">
                    <i class="fas fa-headset me-1"></i> ติดต่อเจ้าหน้าที่
                </a>
            </div>
        </div>
    </nav>

    <div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="login-card" data-aos="fade-up">
            <div class="text-center mb-4">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                    <i class="fas fa-user-lock fa-2x text-primary"></i>
                </div>
                <h3 class="fw-bold text-dark">เข้าสู่ระบบ</h3>
                <p class="text-muted small">กรุณากรอกข้อมูลเพื่อยืนยันตัวตน</p>
            </div>

            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label text-secondary fw-medium">รหัสนักเรียน / ชื่อผู้ใช้</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-primary"><i class="fas fa-user"></i></span>
                        <input type="text" name="student_id" class="form-control border-start-0 ps-0" placeholder="กรอกรหัสนักเรียน" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary fw-medium">รหัสผ่าน</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-primary"><i class="fas fa-key"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="กรอกรหัสผ่านrms" required>
                    </div>
                </div>

                <button type="submit" class="btg w-100 mb-3 rounded-pill">
                    <i class="fas fa-sign-in-alt me-2"></i> กำลังตรวจสอบ...
                </button>
                <div class="text-center">
                    <a href="forgot_password.php" class="text-decoration-none text-primary small fw-medium">
                        ลืมรหัสผ่านใช่หรือไม่?
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
                title: 'เข้าสู่ระบบไม่สำเร็จ',
                text: '<?php echo $error_msg; ?>',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'ลองใหม่อีกครั้ง'
            });
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        AOS.init();

        // จัดการ Intro Screen
        const welcomeScreen = document.getElementById('welcome-screen');
        if (sessionStorage.getItem('introShown')) {
            if (welcomeScreen) welcomeScreen.style.display = 'none';
        } else {
            sessionStorage.setItem('introShown', 'true');
            setTimeout(() => {
                if (welcomeScreen) {
                    welcomeScreen.style.opacity = '0';
                    setTimeout(() => welcomeScreen.style.display = 'none', 1000);
                }
            }, 2500);
        }

        // 🔥 AJAX Login Logic
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault(); // ปิดการ Refresh หน้าจอ

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังตรวจสอบ...';
            btn.disabled = true;

            const formData = new FormData(this);

            fetch('auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: 'กำลังเข้าสู่ระบบ...',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = 'index.php'; // เด้งไปหน้าแรก
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เข้าสู่ระบบไม่สำเร็จ',
                            text: data.message,
                            confirmButtonColor: '#0d6efd'
                        });
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    });
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        });
    </script>

</body>

</html>