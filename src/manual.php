<?php
session_start();
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>คู่มือการใช้งาน - User manual</title>
  <link rel="icon" type="image/png" href="images/books.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Noto Sans Thai', sans-serif;
      background-color: black;
      margin: 0;
      min-height: 100vh;
      color: #333;
      overflow-x: hidden; /* ป้องกันไม่ให้มี Scroll แนวนอนตอน Animation เข้ามา */
    }

    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      z-index: -1;
      pointer-events: none;
    }

    .step-circle {
      width: 50px;
      height: 50px;
      background-color: black;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      font-weight: bold;
      margin-right: 15px;
      flex-shrink: 0;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    }

    .screenshot-box {
      border: 2px dashed #a4a1ff;
      background-color: #fff;
      border-radius: 10px;
      padding: 20px;
      text-align: center;
      margin: 20px 0;
      color: #aaa;
      min-height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
    }

    .screenshot-box img {
      max-width: 100%;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .nav-pills .nav-link.active {
      background-color: white; /* ปรับเป็นขาวเพื่อให้เข้ากับธีมดำ */
      color: black;
      border-radius: 50px;
      border: none;
      box-shadow: 0 0 15px rgba(255, 255, 255, 0.5); /* เรืองแสง */
      transform: scale(1.05); /* ขยายหนิดหน่อยตอนเลือก */
      transition: all 0.3s;
    }

    .nav-pills .nav-link {
      color: #aaa;
      background-color: rgba(255, 255, 255, 0.1);
      margin-right: 5px;
      border-radius: 50px;
      padding: 10px 25px;
      transition: all 0.3s;
    }

    .nav-pills .nav-link:hover {
      color: white;
      background-color: rgba(255, 255, 255, 0.2);
    }
    
    /* เพิ่มคลาสสำหรับรูปภาพให้ดูนูนขึ้นมา */
    .img-hover-zoom {
        transition: transform 0.5s ease;
    }
    .img-hover-zoom:hover {
        transform: scale(1.02);
    }
  </style>
</head>

<body>
  <?php require_once 'loader.php'; ?>
  <div id="particles-js"></div>

  <nav class="navbar navbar-expand-lg shadow-sm" data-aos="fade-down" data-aos-duration="1000">
    <div class="container">
      <a class="navbar-brand fw-bold text-white d-flex align-items-center" href="index.php">
        <img src="images/books.png" width="35" height="35" class="me-2">
        คู่มือการใช้งาน - User manual
      </a>
      <a href="index.php" class="btn btn-outline-light rounded-pill btn-sm">
        <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
      </a>
    </div>
  </nav>

  <div class="text-white py-5 text-center mb-5" data-aos="zoom-in" data-aos-duration="1200">
    <div class="container">
      <h1 class="fw-bold text-white mb-3">
        <i class="fa-solid fa-book-open me-2 text-white"></i> คู่มือการใช้งานระบบ
      </h1>
      <p class="opacity-75 text-white fs-5">เรียนรู้วิธีการใช้งานระบบยืม-คืนหนังสือเรียนฟรี ง่ายๆ ใน 3 นาที</p>
    </div>
  </div>

  <div class="container mb-5">
    <div class="row justify-content-center">
      <div class="col-lg-10">

        <ul class="nav nav-pills mb-5 justify-content-center" id="pills-tab" role="tablist" 
            data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
          <li class="nav-item">
            <button class="nav-link active fw-bold" data-bs-toggle="pill" data-bs-target="#step1">
              1. การเข้าสู่ระบบ
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold" data-bs-toggle="pill" data-bs-target="#step2">
              2. การค้นหาและยืม
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold" data-bs-toggle="pill" data-bs-target="#step3">
              3. ตรวจสอบประวัติ
            </button>
          </li>
        </ul>

        <div class="tab-content" id="pills-tabContent" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">

          <div class="tab-pane fade show active" id="step1">
            <div class="card border-0 shadow-sm rounded-4 p-4">
              <div class="d-flex align-items-center mb-4">
                <div class="step-circle">1</div>
                <h3 class="fw-bold m-0 text-dark">การเข้าสู่ระบบ (Login)</h3>
              </div>
              <p class="fs-5 text-muted">นักเรียนสามารถเข้าใช้งานด้วยรหัสประจำตัวนักเรียนของนักเรียนเอง</p>

              <div class="alert alert-info border-0 rounded-3">
                <li class="mb-1">หากท่านยังไม่มีบัญชี ให้<a href="https://www.facebook.com/kittikun.nookeaw?locale=th_TH" target="_blank" class="text-decoration-none">
                    <i class="fas fa-headset me-1"></i> ติดต่อเจ้าหน้าที่
                  </a></li>
              </div>
              
              <h5 class="fw-bold mt-3">1.1 เข้าสู่ระบบ</h5>
              <p>ท่านต้องการเข้าใช้งาน ให้ท่านกรอก รหัสนักเรียน และรหัสผ่าน <strong>"ลงในช่อง"</strong> กรอกข้อมูลให้ครบถ้วน แล้วกดเข้าสู่ระบบ</p>
              
              <div class="mt-3 text-center">
                  <img src="images/manual_login1.png" class="img-fluid rounded shadow border img-hover-zoom" alt="หน้า Login">
              </div>
              <br>
            </div>
          </div>

          <div class="tab-pane fade" id="step2">
            <div class="card border-0 shadow-sm rounded-4 p-4">
              <div class="d-flex align-items-center mb-4">
                <div class="step-circle">2</div>
                <h3 class="fw-bold m-0 text-dark">การค้นหาและยืมหนังสือ</h3>
              </div>

              <h5 class="fw-bold mt-3">2.1 ค้นหาหนังสือที่ต้องการ</h5>
              <p>ท่านสามารถกดตรงปุ่มค้นหาและทำการพิมพ์ชื่อหนังสือ, รหัสวิชา หรือชื่อผู้แต่ง ในช่อง <strong>"ค้นหา"</strong> ด้านขวามือ ระบบจะแสดงผลทันที</p>

              <div class="mt-3 mb-4 text-center">
                <img src="images/index2.png" class="img-fluid rounded shadow border img-hover-zoom" alt="หน้า index">
              </div>

              <h5 class="fw-bold mt-4">2.2 ดูรายละเอียดและกดขอยืม</h5>
              <p>
                - คลิกที่ <strong>รูปปกหนังสือ</strong> หรือ <strong>รายละเอียด</strong> เพื่อดูรายละเอียดเพิ่มเติม (Modal)<br>
                - คลิกปุ่มสีเขียว <strong>"ยืมหนังสือ"</strong> เพื่อทำการยืม
              </p>

              <div class="mt-3 text-center">
                <img src="images/manual_borrow1.png" class="img-fluid rounded shadow border img-hover-zoom" alt="หน้า Modal">
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="step3">
            <div class="card border-0 shadow-sm rounded-4 p-4">
              <div class="d-flex align-items-center mb-4">
                <div class="step-circle">3</div>
                <h3 class="fw-bold m-0 text-dark">การตรวจสอบประวัติ</h3>
              </div>
              <p class="fs-5 text-muted">ตรวจสอบว่าเรายืมอะไรไปบ้าง และต้องคืนวันไหน?</p>

              <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item"><i class="fa-solid fa-check text-success me-2"></i> กดที่เมนู <strong>"ประวัติการยืม"</strong> ปุ่มสีฟ้าด้านขวา</li>
                
                <div class="my-3 text-center">
                    <img src="images/index1.png" class="img-fluid rounded shadow border img-hover-zoom" alt="หน้า index">
                </div>
                
                <li class="list-group-item"><i class="fa-solid fa-check text-success me-2"></i> ระบบจะแสดงรายการหนังสือทั้งหมด พร้อม <strong>"วันกำหนดส่ง"</strong></li>
                <li class="list-group-item"><i class="fa-solid fa-check text-success me-2"></i> หากเกินกำหนด จะมีแจ้งเตือนสีแดง</li>
                
                <div class="mt-3 text-center">
                    <img src="images/history.png" class="img-fluid rounded shadow border img-hover-zoom" alt="หน้า history">
                </div>
              </ul>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <footer class="text-center text-white py-4 border-top bg-dark" style="border-color: #333 !important;">
    <div class="container">
      <small class="opacity-75">&copy; 2025 TEXTBOOK BORROWING SYSTEM. All rights reserved.</small>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

  <script>
    // เริ่มต้น AOS
    AOS.init({
      duration: 800, // ปรับให้เร็วนิดนึง ให้ความรู้สึกกระฉับกระเฉง (Minimal Style)
      easing: 'ease-out-cubic', // จังหวะนุ่มๆ
      once: true
    });

    // เริ่มต้น Particles
    particlesJS("particles-js", {
      "particles": {
        "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
        "color": { "value": "#ffffff" },
        "shape": { "type": "circle", "stroke": { "width": 0, "color": "#000000" }, "polygon": { "nb_sides": 5 } },
        "opacity": { "value": 0.5, "random": true, "anim": { "enable": false, "speed": 1, "opacity_min": 0.1, "sync": false } },
        "size": { "value": 3, "random": true, "anim": { "enable": false, "speed": 40, "size_min": 0.1, "sync": false } },
        "line_linked": { "enable": true, "distance": 150, "color": "#ffffff", "opacity": 0.4, "width": 1 },
        "move": { "enable": true, "speed": 2, "direction": "none", "random": false, "straight": false, "out_mode": "out", "bounce": false, "attract": { "enable": false, "rotateX": 600, "rotateY": 1200 } }
      },
      "interactivity": {
        "detect_on": "canvas",
        "events": { "onhover": { "enable": true, "mode": "grab" }, "onclick": { "enable": true, "mode": "push" }, "resize": true },
        "modes": { "grab": { "distance": 140, "line_linked": { "opacity": 1 } }, "bubble": { "distance": 400, "size": 40, "duration": 2, "opacity": 8, "speed": 3 }, "repulse": { "distance": 200, "duration": 0.4 }, "push": { "particles_nb": 4 }, "remove": { "particles_nb": 2 } }
      },
      "retina_detect": true
    });
  </script>
</body>
</html>