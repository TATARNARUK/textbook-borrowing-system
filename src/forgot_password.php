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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* --- 🎨 White & Blue Theme --- */
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-image: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100vh;
            margin: 0;
            overflow: hidden; /* ซ่อน Scrollbar */
        }

        /* Particles */
        #particles-js {
            position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; pointer-events: none;
        }

        /* การ์ดแก้ว (Glassmorphism) */
        .card-reset {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            z-index: 1; /* ให้อยู่เหนือ Particles */
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            border: none;
            transition: transform 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }

        .alert-info {
            background-color: #e7f1ff;
            border-color: #b6d4fe;
            color: #084298;
        }
    </style>
</head>

<body>
    
    <?php require_once 'loader.php'; ?>
    
    <div id="particles-js"></div>

    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        
        <div class="card card-reset p-5 text-center" style="max-width: 500px; width: 100%;" 
             data-aos="fade-up" data-aos-duration="1000" data-aos-easing="ease-out-cubic">

            <img src="images/books.png" width="80" class="mb-4 mx-auto animate__animated animate__pulse animate__infinite">

            <h3 class="fw-bold text-primary mb-3">ลืมรหัสผ่าน?</h3>

            <p class="text-secondary mb-4">
                เพื่อความปลอดภัยของข้อมูลบัญชีผู้ใช้งาน<br>
                ระบบไม่อนุญาตให้รีเซ็ตรหัสผ่านด้วยตนเอง
            </p>

            <div class="alert alert-info border-0 rounded-4 p-4 mb-4 text-start shadow-sm">
                <strong><i class="fa-solid fa-circle-info me-2"></i> วิธีการกู้คืนรหัสผ่าน:</strong>
                <ul class="mb-0 mt-2 ps-3 small" style="line-height: 1.6;">
                    <li>เตรียม <strong>บัตรนักเรียน</strong> หรือ <strong>บัตรประชาชน</strong></li>
                    <li>
                        ติดต่อเจ้าหน้าที่ผ่านช่องทาง: 
                        <a href="https://www.facebook.com/kittikun.nookeaw?locale=th_TH" target="_blank" class="text-decoration-none fw-bold ms-1">
                            <i class="fab fa-facebook me-1"></i> ติดต่อเจ้าหน้าที่
                        </a>
                    </li>
                    <li>เจ้าหน้าที่จะทำการตรวจสอบตัวตนและตั้งรหัสผ่านใหม่ให้</li>
                </ul>
            </div>

            <a href="login.php" class="btn btn-primary rounded-pill px-5 py-2 fw-bold w-100">
                <i class="fa-solid fa-arrow-left me-2"></i> กลับไปหน้าเข้าสู่ระบบ
            </a>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        // ✅ 1. เริ่มต้น AOS Animation
        AOS.init();

        // ✅ 2. เริ่มต้น Particles (สีฟ้า)
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#0d6efd" }, /* สีฟ้า */
                "shape": { "type": "circle" },
                "opacity": { "value": 0.5, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#0d6efd", "opacity": 0.2, "width": 1 },
                "move": { "enable": true, "speed": 2 }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": { "onhover": { "enable": true, "mode": "grab" }, "onclick": { "enable": true, "mode": "push" } },
                "modes": { "grab": { "distance": 140, "line_linked": { "opacity": 1 } }, "push": { "particles_nb": 4 } }
            },
            "retina_detect": true
        });
    </script>
</body>
</html>