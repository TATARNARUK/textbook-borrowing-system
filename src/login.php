<?php
session_start();
require_once 'config.php';

// ตรวจสอบข้อมูลเมื่อมีการกดปุ่ม Login
if (isset($_POST['student_id']) && isset($_POST['password'])) {

    $student_id = trim($_POST['student_id']); // ตัดช่องว่างหน้าหลังออกเผื่อพลาด
    $password = $_POST['password'];

    // 1. ดึงข้อมูล User ออกมาก่อน (ยังไม่เช็คพาสเวิร์ดที่ SQL)
    // ✅ ต้อง SELECT password ออกมาด้วย เพื่อเอามาเช็คทีหลัง
    $stmt = $pdo->prepare("SELECT id, fullname, role, password FROM users WHERE student_id = :id");
    $stmt->bindValue(':id', $student_id, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. เช็คว่ามี User นี้ไหม? และ รหัสผ่านตรงกันไหม? (ด้วย password_verify)
    if ($user && password_verify($password, $user['password'])) {

        // ✅ รหัสถูกต้อง! สร้าง Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['student_id'] = $student_id; // เก็บ student_id ไว้ใช้ด้วยเผื่อจำเป็น

        // ส่งไปหน้าแรก
        header('location: index.php');
        exit();
    } else {
        // ❌ รหัสผิด หรือ หาชื่อไม่เจอ
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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body><?php require_once 'loader.php'; ?><div id="particles-js"></div>
    <div id="welcome-screen">
        <div class="intro-content text-center">

            <div class="intro-icons mb-3">
                <i class="fas fa-code"></i>
                <i class="fas fa-user-graduate"></i>
                <i class="fas fa-book"></i>
            </div>

            <h1 class="fade-in-text">Welcome To Website</h1>

            <h2 class="gradient-text">Textbook Borrowing System</h2>

            <p class="text-white mt-2" style="min-height: 30px; font-size: 1.1rem;">
                <span id="typewriter-text"></span><span class="cursor">|</span>
            </p>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg shadow-sm fixed-top py-3" data-aos="fade-down" data-aos-duration="2000">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
                <img src="images/books.png" height="40" alt="Logo">
                <div class="d-none d-md-block text-start" data-aos="fade-down" data-aos-duration="2000">
                    <h5 class="m-0 fw-bold text-white" style="font-family: 'Noto Sans Thai', sans-serif;">
                        TEXTBOOK BORROWING SYSTEM
                    </h5>
                    <small class="text-white">ระบบยืม-คืนหนังสือเรียนฟรี</small>
                </div>
            </a>

            <div class="ms-auto d-flex align-items-center gap-3" data-aos="fade-down" data-aos-duration="2000">
                <a href="manual.php" class="text-decoration-none text-white fw-medium small">
                    <i class="fas fa-book me-1"></i> คู่มือการใช้งานระบบ
                </a>
                <div class="vr mx-2 text-white" style="height: 20px;"></div>
                <a href="https://www.facebook.com/kittikun.nookeaw?locale=th_TH" target="_blank"
                    class="btn btn-sm btn-outline-light rounded-pill px-3 ms-2 ">
                    <i class="fas fa-headset me-1"></i> ติดต่อเจ้าหน้าที่
                </a>
            </div>
        </div>
    </nav>

    <div class="container d-flex justify-content-center">
        <div class="login-card" data-aos="fade-down" data-aos-duration="1200">
            <h3 class="text-center mb-4 text-dark">เข้าสู่ระบบ</h3>

            <form action="" method="post">
                <div class="mb-3">
                    <label class="form-label text-dark">รหัสนักเรียน / ชื่อผู้ใช้</label>
                    <input type="text" name="student_id" class="form-control" placeholder="กรอกรหัสนักเรียน" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark">รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" placeholder="กรอกรหัสผ่าน" required>
                </div>

                <button type="submit" class="btg w-100 mb-2 rounded-pill">เข้าสู่ระบบ</button>

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

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        /* เรียกใช้ particles.js ที่กล่อง id="particles-js" */
        particlesJS("particles-js", {
            "particles": {
                "number": {
                    "value": 80,
                    /* จำนวนดาว (ยิ่งเยอะยิ่งรก) ลองปรับดูที่ 50-100 */
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": {
                    "value": "#ffffff" /* สีของดาว (สีขาว) */
                },
                "shape": {
                    "type": "circle",
                    /* รูปร่าง (วงกลม) */
                    "stroke": {
                        "width": 0,
                        "color": "#000000"
                    },
                    "polygon": {
                        "nb_sides": 5
                    }
                },
                "opacity": {
                    "value": 0.5,
                    /* ความจางของดาว (0.5 คือครึ่งๆ) */
                    "random": true,
                    /* ให้จางไม่เท่ากัน ดูมีมิติ */
                    "anim": {
                        "enable": false,
                        "speed": 1,
                        "opacity_min": 0.1,
                        "sync": false
                    }
                },
                "size": {
                    "value": 3,
                    /* ขนาดของดาว */
                    "random": true,
                    /* เล็กใหญ่ไม่เท่ากัน */
                    "anim": {
                        "enable": false,
                        "speed": 40,
                        "size_min": 0.1,
                        "sync": false
                    }
                },
                "line_linked": {
                    "enable": true,
                    /* ✅ ถ้าไม่อยากได้เส้นเชื่อม ให้แก้เป็น false */
                    "distance": 150,
                    /* ระยะห่างที่จะให้มีเส้นเชื่อม */
                    "color": "#ffffff",
                    /* สีของเส้น */
                    "opacity": 0.4,
                    /* ความจางของเส้น */
                    "width": 1
                },
                "move": {
                    "enable": true,
                    /* สั่งให้ขยับ */
                    "speed": 2,
                    /* ความเร็วในการวิ่ง (ยิ่งเยอะยิ่งเร็ว) */
                    "direction": "none",
                    /* ทิศทาง (none คือมั่ว) */
                    "random": false,
                    "straight": false,
                    "out_mode": "out",
                    "bounce": false,
                    "attract": {
                        "enable": false,
                        "rotateX": 600,
                        "rotateY": 1200
                    }
                }
            },
            "interactivity": {
                /* ส่วนนี้คือเวลาเอาเมาส์ไปโดน */
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        /* ถ้า true เวลาเอาเมาส์ไปชี้ ดาวจะวิ่งหนีหรือวิ่งเข้าหา */
                        "mode": "grab" /* grab = มีเส้นดูดเข้าหาเมาส์, repulse = วิ่งหนี */
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push" /* คลิกแล้วมีดาวเพิ่ม */
                    },
                    "resize": true
                },
                "modes": {
                    "grab": {
                        "distance": 140,
                        "line_linked": {
                            "opacity": 1
                        }
                    },
                    "bubble": {
                        "distance": 400,
                        "size": 40,
                        "duration": 2,
                        "opacity": 8,
                        "speed": 3
                    },
                    "repulse": {
                        "distance": 200,
                        "duration": 0.4
                    },
                    "push": {
                        "particles_nb": 4
                    },
                    "remove": {
                        "particles_nb": 2
                    }
                }
            },
            "retina_detect": true
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // ตัวละคร
            const welcomeScreen = document.getElementById('welcome-screen');
            const typewriterText = document.getElementById('typewriter-text');

            // ⚙️ ตั้งค่า AOS ตรงนี้แทน (รวมศูนย์ไว้ที่นี่)
            function startAOS() {
                AOS.init({
                    duration: 1000, // ความเร็ว
                    once: true, // เล่นครั้งเดียว
                    easing: 'ease-out-cubic'
                });
            }

            // --- เช็คว่าเคยเข้าเว็บมารึยัง? ---
            if (sessionStorage.getItem('introShown')) {

                // 🟢 กรณี: เคยเข้าแล้ว (กด Refresh)
                // 1. ซ่อนฉากดำทันที
                if (welcomeScreen) welcomeScreen.style.display = 'none';

                // 2. สั่ง AOS ทำงานทันที (ไม่ต้องรอ)
                startAOS();

            } else {

                // 🔴 กรณี: เพิ่งเข้าครั้งแรก (New User)
                sessionStorage.setItem('introShown', 'true');

                // 1. เริ่มเอฟเฟกต์พิมพ์ดีด
                const textToType = "ระบบยืม-คืนหนังสือเรียน";
                let charIndex = 0;

                function type() {
                    if (charIndex < textToType.length) {
                        typewriterText.innerHTML += textToType.charAt(charIndex);
                        charIndex++;
                        setTimeout(type, 80);
                    }
                }
                // เริ่มพิมพ์ตอนวินาทีที่ 2.5
                setTimeout(type, 2500);

                // 2. ⏰ รอให้ฉากดำเล่นจบก่อน... แล้วค่อยปลุก AOS ตื่น!
                // (ตั้งเวลา 6500 ให้ตรงกับ animation-delay ใน CSS เป๊ะๆ)
                setTimeout(function() {
                    startAOS(); // 👈 สั่งเริ่มตรงนี้! การ์ดถึงจะเด้งตอนเห็นภาพ
                }, 6500);
            }
        });
    </script>
</body>

</html>