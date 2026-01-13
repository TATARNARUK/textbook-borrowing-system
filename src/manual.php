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
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <style>
    /* --- 🎨 White & Blue Theme --- */
    body {
      font-family: 'Noto Sans Thai', sans-serif;
      background-color: #f0f4f8; /* พื้นหลังสีเทาอมฟ้าอ่อน */
      background-image: radial-gradient(#dbeafe 1px, transparent 1px); /* ลายจุดจางๆ */
      background-size: 20px 20px;
      margin: 0;
      min-height: 100vh;
      color: #333;
      overflow-x: hidden;
    }

    /* Particles ให้เป็นสีฟ้า */
    #particles-js {
      position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: -1; pointer-events: none;
    }

    /* Navbar สีขาว เงาบางๆ */
    .navbar {
      background-color: rgba(255, 255, 255, 0.95) !important;
      backdrop-filter: blur(10px);
      border-bottom: 1px solid #e9ecef;
    }
    .navbar-brand { color: #0d6efd !important; font-weight: 700; } /* สีหัวข้อเป็นสีฟ้า */

    /* วงกลมตัวเลข (Step) */
    .step-circle {
      width: 50px; height: 50px;
      background: linear-gradient(45deg, #0d6efd, #0dcaf0); /* สีฟ้าไล่ระดับ */
      color: white;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px; font-weight: bold;
      margin-right: 15px; flex-shrink: 0;
      box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
    }

    /* กล่องรูปภาพ */
    .screenshot-box {
      border: 2px dashed #0d6efd;
      background-color: #fff;
      border-radius: 10px;
      padding: 20px;
      text-align: center;
      margin: 20px 0;
      color: #aaa;
    }

    /* Effect รูปภาพ */
    .img-hover-zoom {
      transition: transform 0.5s ease, box-shadow 0.5s ease;
      border: 1px solid #dee2e6;
    }
    .img-hover-zoom:hover {
      transform: scale(1.02);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    }

    /* ปุ่มเลือกหัวข้อ (Tabs) */
    .nav-pills .nav-link {
      color: #6c757d;
      background-color: #fff;
      margin: 0 5px;
      border-radius: 50px;
      padding: 10px 25px;
      transition: all 0.3s;
      border: 1px solid #dee2e6;
    }

    .nav-pills .nav-link:hover {
      background-color: #e9ecef;
      color: #0d6efd;
    }

    .nav-pills .nav-link.active {
      background: linear-gradient(45deg, #0d6efd, #0dcaf0);
      color: white;
      border: none;
      box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
      transform: scale(1.05);
    }

    /* Footer */
    footer {
        background-color: #fff !important;
        border-top: 1px solid #e9ecef;
        color: #6c757d;
    }
  </style>
</head>

<body>
  <?php require_once 'loader.php'; ?>
  <div id="particles-js"></div>

  <nav class="navbar navbar-expand-lg fixed-top py-3" data-aos="fade-down" data-aos-duration="1000">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="images/books.png" width="35" height="35" class="me-2">
        <span>คู่มือการใช้งาน <small class="text-secondary fw-normal ms-1" style="font-size: 0.9rem;">User Manual</small></span>
      </a>
      <a href="index.php" class="btn btn-outline-primary rounded-pill btn-sm fw-bold px-3">
        <i class="fa-solid fa-arrow-left me-1"></i> กลับหน้าหลัก
      </a>
    </div>
  </nav>

  <div style="padding-top: 100px;"></div>

  <div class="py-4 text-center mb-4" data-aos="zoom-in" data-aos-duration="1200">
    <div class="container">
      <div class="d-inline-block bg-primary bg-opacity-10 rounded-circle p-3 mb-3">
        <i class="fa-solid fa-book-open fa-3x text-primary"></i>
      </div>
      <h2 class="fw-bold text-dark mb-2">คู่มือการใช้งานระบบ</h2>
      <p class="text-secondary">เรียนรู้วิธีการใช้งานระบบยืม-คืนหนังสือเรียนฟรี ง่ายๆ ใน 3 นาที</p>
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
            <div class="card border-0 shadow rounded-4 p-4 p-lg-5 bg-white">
              <div class="d-flex align-items-center mb-4 border-bottom pb-3">
                <div class="step-circle">1</div>
                <div>
                    <h4 class="fw-bold m-0 text-dark">การเข้าสู่ระบบ (Login)</h4>
                    <small class="text-muted">ขั้นตอนแรกสำหรับการใช้งาน</small>
                </div>
              </div>
              
              <div class="alert alert-primary bg-primary bg-opacity-10 border-0 rounded-3 text-primary">
                <i class="fas fa-info-circle me-2"></i> หากท่านยังไม่มีบัญชี ให้ติดต่อเจ้าหน้าที่ห้องสมุดเพื่อขอรับรหัสนักเรียน
              </div>
              
              <h6 class="fw-bold mt-4 text-dark">1.1 กรอกข้อมูลเพื่อยืนยันตัวตน</h6>
              <p class="text-secondary">ท่านต้องการเข้าใช้งาน ให้ท่านกรอก <strong>รหัสนักเรียน</strong> และ <strong>รหัสผ่าน</strong> ลงในช่องให้ครบถ้วน แล้วกดปุ่ม "เข้าสู่ระบบ"</p>
              
              <div class="mt-4 text-center">
                  <img src="images/manual_login1.png" class="img-fluid rounded shadow-sm img-hover-zoom" alt="หน้า Login" style="max-width: 80%;">
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="step2">
            <div class="card border-0 shadow rounded-4 p-4 p-lg-5 bg-white">
              <div class="d-flex align-items-center mb-4 border-bottom pb-3">
                <div class="step-circle">2</div>
                <div>
                    <h4 class="fw-bold m-0 text-dark">การค้นหาและยืมหนังสือ</h4>
                    <small class="text-muted">ค้นหาหนังสือที่ต้องการได้อย่างรวดเร็ว</small>
                </div>
              </div>

              <h6 class="fw-bold mt-3 text-dark">2.1 ค้นหาหนังสือที่ต้องการ</h6>
              <p class="text-secondary">พิมพ์ชื่อหนังสือ, รหัสวิชา หรือชื่อผู้แต่ง ในช่อง <strong>"ค้นหา"</strong> ระบบจะแสดงผลทันทีแบบ Real-time</p>

              <div class="mt-3 mb-5 text-center">
                <img src="images/index2.png" class="img-fluid rounded shadow-sm img-hover-zoom" alt="หน้า index" style="max-width: 90%;">
              </div>

              <h6 class="fw-bold mt-4 text-dark">2.2 ดูรายละเอียดและกดขอยืม</h6>
              <ul class="text-secondary">
                <li>คลิกที่ <strong>รูปปกหนังสือ</strong> หรือปุ่ม <strong>รายละเอียด</strong> เพื่อดูข้อมูลเพิ่มเติม</li>
                <li>หากสถานะหนังสือเป็น <span class="badge bg-success">ว่าง</span> ให้กดปุ่ม <strong>"ยืมหนังสือ"</strong></li>
              </ul>

              <div class="mt-3 text-center">
                <img src="images/manual_borrow1.png" class="img-fluid rounded shadow-sm img-hover-zoom" alt="หน้า Modal" style="max-width: 60%;">
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="step3">
            <div class="card border-0 shadow rounded-4 p-4 p-lg-5 bg-white">
              <div class="d-flex align-items-center mb-4 border-bottom pb-3">
                <div class="step-circle">3</div>
                <div>
                    <h4 class="fw-bold m-0 text-dark">การตรวจสอบประวัติ</h4>
                    <small class="text-muted">เช็ครายการที่ยืมและวันกำหนดส่งคืน</small>
                </div>
              </div>

              <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item border-0 ps-0"><i class="fa-solid fa-circle-check text-primary me-2"></i> กดที่เมนู <strong>"ประวัติการยืม"</strong> ในหน้าหลัก</li>
                
                <div class="my-3 text-center">
                    <img src="images/index1.png" class="img-fluid rounded shadow-sm img-hover-zoom" alt="ปุ่มประวัติ" style="max-width: 80%;">
                </div>
                
                <li class="list-group-item border-0 ps-0"><i class="fa-solid fa-circle-check text-primary me-2"></i> ระบบจะแสดงรายการหนังสือทั้งหมด พร้อม <strong>"วันกำหนดส่ง"</strong></li>
                <li class="list-group-item border-0 ps-0"><i class="fa-solid fa-circle-check text-danger me-2"></i> หากเกินกำหนดส่ง ระบบจะแจ้งเตือนเป็นสถานะสีแดง</li>
                
                <div class="mt-3 text-center">
                    <img src="images/history.png" class="img-fluid rounded shadow-sm img-hover-zoom" alt="หน้า history" style="max-width: 90%;">
                </div>
              </ul>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <footer class="text-center py-4 mt-5">
    <div class="container">
      <small class="text-muted">&copy; 2025 TEXTBOOK BORROWING SYSTEM. All rights reserved.</small>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

  <script>
    // เริ่มต้น AOS
    AOS.init({
      duration: 800,
      easing: 'ease-out-cubic',
      once: true
    });

    // เริ่มต้น Particles (เปลี่ยนเป็นสีฟ้า)
    particlesJS("particles-js", {
      "particles": {
        "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
        "color": { "value": "#0d6efd" }, /* สีฟ้า */
        "shape": { "type": "circle", "stroke": { "width": 0, "color": "#000000" }, "polygon": { "nb_sides": 5 } },
        "opacity": { "value": 0.5, "random": true, "anim": { "enable": false, "speed": 1, "opacity_min": 0.1, "sync": false } },
        "size": { "value": 3, "random": true, "anim": { "enable": false, "speed": 40, "size_min": 0.1, "sync": false } },
        "line_linked": { "enable": true, "distance": 150, "color": "#0d6efd", "opacity": 0.2, "width": 1 }, /* เส้นสีฟ้าจางๆ */
        "move": { "enable": true, "speed": 2, "direction": "none", "random": false, "straight": false, "out_mode": "out", "bounce": false }
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