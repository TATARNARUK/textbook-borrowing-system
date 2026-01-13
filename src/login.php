<?php
session_start();
require_once 'config.php';

// ตรวจสอบข้อมูลเมื่อมีการกดปุ่ม Login
if (isset($_POST['student_id']) && isset($_POST['password'])) {

    $student_id = trim($_POST['student_id']); 
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, fullname, role, password FROM users WHERE student_id = :id");
    $stmt->bindValue(':id', $student_id, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['student_id'] = $student_id; 

        header('location: index.php');
        exit();
    } else {
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
            <h1 class="fade-in-text fw-bold">Welcome To Website</h1>
            <h2 class="gradient-text">Textbook Borrowing System</h2>
            <p class="text-white-50 mt-2" style="min-height: 30px; font-size: 1.1rem;">
                <span id="typewriter-text"></span><span class="cursor">|</span>
            </p>
        </div>
    </div>

    <div id="particles-js"></div>

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
        <div class="login-card" data-aos="fade-up" data-aos-duration="1200">
            <div class="text-center mb-4">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                    <i class="fas fa-user-lock fa-2x text-primary"></i>
                </div>
                <h3 class="fw-bold text-dark">เข้าสู่ระบบ</h3>
                <p class="text-muted small">กรุณากรอกข้อมูลเพื่อยืนยันตัวตน</p>
            </div>

            <form action="" method="post">
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
                        <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="กรอกรหัสผ่าน" required>
                    </div>
                </div>

                <button type="submit" class="btg w-100 mb-3 rounded-pill">
                    <i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ
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

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    
    <script>
        /* --- ตั้งค่า Particles สีฟ้า/เทา สำหรับพื้นขาว --- */
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#0d6efd" }, /* เปลี่ยนดาวเป็นสีฟ้า */
                "shape": { "type": "circle" },
                "opacity": { "value": 0.5, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#0d6efd", /* เปลี่ยนเส้นเชื่อมเป็นสีฟ้า */
                    "opacity": 0.2,
                    "width": 1
                },
                "move": { "enable": true, "speed": 2, "direction": "none", "random": false, "straight": false, "out_mode": "out", "bounce": false }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": { "onhover": { "enable": true, "mode": "grab" }, "onclick": { "enable": true, "mode": "push" } },
                "modes": { "grab": { "distance": 140, "line_linked": { "opacity": 1 } } }
            },
            "retina_detect": true
        });
    </script>

     <script>
        document.addEventListener("DOMContentLoaded", function() {
            const welcomeScreen = document.getElementById('welcome-screen');
            const typewriterText = document.getElementById('typewriter-text');

            function startAOS() {
                AOS.init({ duration: 1000, once: true, easing: 'ease-out-cubic' });
            }

            if (sessionStorage.getItem('introShown')) {
                if (welcomeScreen) welcomeScreen.style.display = 'none';
                startAOS();
            } else {
                sessionStorage.setItem('introShown', 'true');
                const textToType = "ระบบยืม-คืนหนังสือเรียน";
                let charIndex = 0;

                function type() {
                    if (charIndex < textToType.length) {
                        typewriterText.innerHTML += textToType.charAt(charIndex);
                        charIndex++;
                        setTimeout(type, 80);
                    }
                }
                setTimeout(type, 1500); // เริ่มพิมพ์เร็วขึ้นหน่อย

                setTimeout(function() {
                    if(welcomeScreen) {
                        welcomeScreen.style.opacity = '0';
                        setTimeout(() => welcomeScreen.style.display = 'none', 1000);
                    }
                    startAOS();
                }, 4500); // ลดเวลา intro ลงนิดหน่อยให้กระชับ
            }
        });
    </script>
</body>
</html>