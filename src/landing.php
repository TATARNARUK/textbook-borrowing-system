<?php
session_start();

// ถ้ามี Session PHP ค้างอยู่ (Browser จำ Cookie ไว้)
if (isset($_SESSION['user_id'])) {
    // ให้ใช้ JS เช็คว่า "เป็นการเปิด Browser ใหม่หรือไม่?"
    echo "<script>
        if (sessionStorage.getItem('is_logged_in')) {
            // ถ้าค่านี้ยังอยู่ แปลว่าแค่ Refresh หน้า -> ไปหน้า Dashboard ได้เลย
            window.location.href = 'index.php';
        } else {
            // ถ้าค่านี้หายไป แปลว่าปิด Browser แล้วเปิดใหม่ -> บังคับ Logout
            window.location.href = 'logout.php';
        }
    </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยินดีต้อนรับ - ระบบยืมคืนหนังสือเรียน</title>
    <link rel="icon" type="image/png" href="images/books.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;800&family=Noto+Sans+Thai:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Prompt', sans-serif;
            overflow: hidden;
        }

        /* --- Welcome Screen Styles (เหมือนเดิม) --- */
        #welcome-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background-color: #ffffff;
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .intro-icons i {
            font-size: 2.5rem;
            color: #0b5ed7;
            margin: 0 15px;
            opacity: 0;
            transform: translateY(20px);
            animation: popUpIcon 0.8s cubic-bezier(0.68, -0.55, 0.27, 1.55) forwards;
        }

        .intro-icons i:nth-child(1) {
            animation-delay: 0.5s;
        }

        .intro-icons i:nth-child(2) {
            animation-delay: 0.7s;
        }

        .intro-icons i:nth-child(3) {
            animation-delay: 0.9s;
        }

        @keyframes popUpIcon {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-text {
            color: #333;
            font-weight: 300;
            font-size: 1.5rem;
            margin: 20px 0 5px 0;
            opacity: 0;
            animation: textFadeIn 1s ease-out forwards;
            animation-delay: 1.5s;
        }

        .gradient-text {
            font-weight: 800;
            font-size: 3rem;
            text-transform: uppercase;
            margin: 0;
            opacity: 0;
            letter-spacing: 1px;
            text-align: center;
            line-height: 1.2;
            background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: textFadeIn 1s ease-out forwards;
            animation-delay: 2.0s;
        }

        @media (max-width: 768px) {
            .gradient-text {
                font-size: 2rem;
                padding: 0 20px;
            }

            .fade-in-text {
                font-size: 1.2rem;
            }
        }

        @keyframes textFadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .typewriter-container {
            font-family: 'Noto Sans Thai', sans-serif;
            font-size: 1.1rem;
            color: #000;
            margin-top: 15px;
            min-height: 30px;
            display: flex;
            align-items: center;
            opacity: 0;
            animation: textFadeIn 1s ease-out forwards;
            animation-delay: 2.5s;
        }

        .cursor {
            display: inline-block;
            width: 2px;
            height: 1.2em;
            background-color: #0d6efd;
            margin-left: 5px;
            animation: blinkCursor 0.7s infinite;
        }

        @keyframes blinkCursor {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }
        }

        /* --- Buttons Container --- */
        .action-btn-container {
            margin-top: 40px;
            opacity: 0;
            animation: textFadeIn 1s ease-out forwards;
            animation-delay: 3.5s;
            display: flex;
            gap: 15px;
            /* ระยะห่างระหว่างปุ่ม */
        }

        .btn-enter {
            background: #0d6efd;
            color: #fff;
            padding: 12px 35px;
            font-size: 1.1rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid #0d6efd;
        }

        .btn-enter:hover {
            background: #0b5ed7;
            border-color: #0b5ed7;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.4);
            color: #fff;
        }

        /* ปุ่มรายละเอียด (สีขาว) */
        .btn-info-custom {
            background: #fff;
            color: #0d6efd;
            padding: 12px 35px;
            font-size: 1.1rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid #0d6efd;
            cursor: pointer;
        }

        .btn-info-custom:hover {
            background: #f0f8ff;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.15);
            color: #0b5ed7;
        }


        /* --- 🔥 POPUP MODAL STYLES --- */
        .info-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            /* สีจางๆ */
            backdrop-filter: blur(8px);
            /* เบลอฉากหลัง */
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .info-modal-overlay.show {
            display: flex;
            opacity: 1;
        }

        .info-modal-content {
            background: #fff;
            width: 90%;
            max-width: 500px;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            transform: translateY(50px) scale(0.9);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid #f0f0f0;
        }

        .info-modal-overlay.show .info-modal-content {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .modal-icon-top {
            width: 80px;
            height: 80px;
            background: #e7f1ff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px auto;
            color: #0d6efd;
            font-size: 2.5rem;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            text-align: left;
            margin: 25px 0;
        }

        .feature-list li {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            color: #555;
            font-size: 1rem;
        }

        .feature-list li i {
            color: #198754;
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .btn-close-modal {
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 10px 30px;
            border-radius: 50px;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-close-modal:hover {
            background: #5a6268;
        }
    </style>
</head>

<body>

    <div id="welcome-screen">
        <div class="intro-icons">
            <i class="fas fa-code"></i>
            <i class="fas fa-user-graduate"></i>
            <i class="fas fa-book"></i>
        </div>

        <h1 class="fade-in-text">Welcome To Website</h1>
        <h2 class="gradient-text">TEXTBOOK BORROWING SYSTEM</h2>

        <div class="typewriter-container">
            <span id="typewriter-text"></span><span class="cursor"></span>
        </div>

        <div class="action-btn-container">
            <a href="login.php" class="btn-enter">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </a>

            <button class="btn-info-custom" onclick="openModal()">
                <i class="fas fa-info-circle"></i> รายละเอียด
            </button>
        </div>
    </div>

    <div class="info-modal-overlay" id="infoModal">
        <div class="info-modal-content">
            <div class="modal-icon-top">
                <i class="fas fa-book-reader"></i>
            </div>
            <h3 class="fw-bold text-dark mb-2">เกี่ยวกับระบบ</h3>
            <p class="text-muted mb-4">ระบบยืม-คืนหนังสือเรียนฟรี แผนกวิชาเทคโนโลยีสารสนเทศ วิทยาลัยพณิชยการบางนา</p>

            <ul class="feature-list ps-3">
                <li><i class="fas fa-check-circle"></i> ระบบยืมหนังสือเรียนฟรี</li>
                <li><i class="fas fa-check-circle"></i> ระบบบริการยืม-คืน ค้นหาและตรวจสอบสถานะหนังสือแบบ Real-time (ว่าง/ถูกยืม)</li>
                <li><i class="fas fa-check-circle"></i> เช็คประวัติการยืม-คืนหนังสือย้อนหลังทั้งหมด
                    ตรวจสอบรายการหนังสือที่กำลังยืมและวันกำหนดส่งคืน
                    แจ้งเตือนสถานะหนังสือ (ปกติ / เกินกำหนด / คืนแล้ว)</li>
                <li><i class="fas fa-check-circle"></i> เข้าสู่ระบบด้วยรหัสนักเรียน (เชื่อมต่อฐานข้อมูลวิทยาลัย/RMS)</li>
            </ul>

            <button class="btn-close-modal" onclick="closeModal()">ปิดหน้าต่าง</button>
        </div>
    </div>

    <script>
        // --- 1. Typewriter Effect ---
        const textToType = "ระบบยืม-คืนหนังสือเรียนฟรี เทคโนโลยีสารสนเทศ";
        const typewriterElement = document.getElementById('typewriter-text');
        let charIndex = 0;

        setTimeout(() => {
            function type() {
                if (charIndex < textToType.length) {
                    typewriterElement.textContent += textToType.charAt(charIndex);
                    charIndex++;
                    setTimeout(type, 50);
                }
            }
            type();
        }, 3000);


        // --- 2. Popup Modal Logic ---
        const modal = document.getElementById('infoModal');

        function openModal() {
            modal.style.display = 'flex'; // แสดงก่อน
            setTimeout(() => {
                modal.classList.add('show'); // แล้วค่อย Fade In
            }, 10);
        }

        function closeModal() {
            modal.classList.remove('show'); // Fade Out ก่อน
            setTimeout(() => {
                modal.style.display = 'none'; // แล้วค่อยซ่อน
            }, 300); // รอ 0.3 วิ เท่ากับ transition ใน CSS
        }

        // กดที่พื้นที่ว่างข้างนอกเพื่อปิด
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>

</body>

</html>