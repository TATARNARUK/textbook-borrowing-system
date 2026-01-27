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
    /* --- 🎨 Style Settings --- */
    body {
      font-family: 'Noto Sans Thai', sans-serif;
      /* ✅ ใช้รูปพื้นหลังเดียวกับหน้า Login/Index */
      background-image: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?q=80&w=2070&auto=format&fit=crop');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      margin: 0;
      min-height: 100vh;
      color: #333;
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

    /* Navbar แบบ Glassmorphism */
    .navbar {
      background: rgba(255, 255, 255, 0.95) !important;
      backdrop-filter: blur(10px);
      padding: 15px 0;
      position: relative;
      width: 100%;
      z-index: 1000;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .navbar-brand {
      color: #0d6efd !important;
      font-weight: 700;
    }

    /* ✅ กล่องขาวสำหรับเนื้อหา (Manual Box) */
    .manual-box {
      background-color: rgba(255, 255, 255, 0.92);
      /* สีขาวโปร่งแสงนิดๆ */
      backdrop-filter: blur(15px);
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      /* เงานุ่มๆ */
      padding: 40px;
      margin-bottom: 50px;
      border: 1px solid rgba(255, 255, 255, 0.5);
    }

    /* Step Circle */
    .step-circle {
      width: 50px;
      height: 50px;
      background: linear-gradient(45deg, #0d6efd, #0dcaf0);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      font-weight: bold;
      margin-right: 15px;
      flex-shrink: 0;
      box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
    }

    /* รูปภาพ Effect */
    .img-hover-zoom {
      transition: transform 0.5s ease, box-shadow 0.5s ease;
      border: 1px solid #dee2e6;
    }

    .img-hover-zoom:hover {
      transform: scale(1.02);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    }

    /* Tabs Design */
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
      background-color: #f8f9fa;
      color: #0d6efd;
      transform: translateY(-2px);
    }

    .nav-pills .nav-link.active {
      background: linear-gradient(45deg, #0d6efd, #0dcaf0);
      color: white;
      border: none;
      box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
      transform: scale(1.05);
    }

    footer {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(5px);
      color: #6c757d;
      margin-top: auto;
    }
  </style>
</head>

<body class="d-flex flex-column min-vh-100">
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

  <div class="container flex-grow-1">

    <div class="manual-box" data-aos="zoom-in" data-aos-duration="1000">

      <div class="text-center mb-5">
        <div class="d-inline-block bg-primary bg-opacity-10 rounded-circle p-3 mb-3">
          <i class="fa-solid fa-book-open fa-3x text-primary"></i>
        </div>
        <h2 class="fw-bold text-dark mb-2">คู่มือการใช้งานระบบ</h2>
        <p class="text-secondary">เรียนรู้วิธีการใช้งานระบบยืม-คืนหนังสือเรียนฟรี ง่ายๆ ใน 3 นาที</p>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-10">

          <ul class="nav nav-pills mb-4 justify-content-center" id="pills-tab" role="tablist">
            <li class="nav-item">
              <button class="nav-link active fw-bold" data-bs-toggle="pill" data-bs-target="#step1">1. การเข้าสู่ระบบ</button>
            </li>
            <li class="nav-item">
              <button class="nav-link fw-bold" data-bs-toggle="pill" data-bs-target="#step2">2. การค้นหาและยืม</button>
            </li>
            <li class="nav-item">
              <button class="nav-link fw-bold" data-bs-toggle="pill" data-bs-target="#step3">3. ตรวจสอบประวัติ</button>
            </li>
          </ul>

          <div class="tab-content" id="pills-tabContent">

            <div class="tab-pane fade show active" id="step1">
              <div class="border-start border-4 border-primary ps-4 py-2 mb-4 bg-light rounded-end">
                <h4 class="fw-bold m-0 text-dark">การเข้าสู่ระบบ (Login)</h4>
                <small class="text-muted">ขั้นตอนแรกสำหรับการใช้งาน</small>
              </div>

              <div class="alert alert-info border-0 rounded-3 shadow-sm">
                <i class="fas fa-info-circle me-2"></i> หากท่านยังไม่มีบัญชี ให้ติดต่อเจ้าหน้าที่เพื่อขอรับรหัสนักเรียน
              </div>

              <p class="mt-3 text-secondary">กรอก <strong class="text-primary">รหัสนักเรียน</strong> และ <strong class="text-primary">รหัสผ่าน RMS</strong> ของท่านลงในช่องให้ครบถ้วน แล้วกดปุ่ม "เข้าสู่ระบบ"</p>

              <div class="text-center mt-4">
                <img src="images/login.png" class="img-fluid rounded shadow img-hover-zoom" alt="Login" style="max-width: 90%;">
              </div><br>
              
              <h6 class="fw-bold text-primary">หากลืมรหัสผ่าน</h6>
              <p class="text-secondary ms-4">หากลืมรหัสผ่าน ให้ติดต่อเจ้าหน้าที่เพื่อขอรับรหัสผ่าน</p>
              <div class="text-center mb-5">
                <img src="images/login2.png" class="img-fluid rounded shadow img-hover-zoom" alt="Search" style="max-width: 90%;">
              </div>
            </div>

            <div class="tab-pane fade" id="step2">
              <div class="border-start border-4 border-primary ps-4 py-2 mb-4 bg-light rounded-end">
                <h4 class="fw-bold m-0 text-dark">การค้นหาและยืมหนังสือ</h4>
                <small class="text-muted">ค้นหาหนังสือที่ต้องการได้อย่างรวดเร็ว</small>
              </div>

              <h6 class="fw-bold text-primary"><i class="fas fa-search me-2"></i>2.1 ค้นหาหนังสือ</h6>
              <p class="text-secondary ms-4">กดปุ่ม "ค้นหาหนังสือ" แล้วจะเข้าไปที่หน้ารวมหนังสือ และทำการ พิมพ์ชื่อหนังสือ, รหัสวิชา หรือชื่อผู้แต่ง ในช่องค้นหา ระบบจะแสดงผลทันที</p>
              <div class="text-center mb-5">
                <img src="images/s.png" class="img-fluid rounded shadow img-hover-zoom" alt="Search" style="max-width: 90%;"><br>
                <img src="images/ss.png" class="img-fluid rounded shadow img-hover-zoom" alt="Search" style="max-width: 90%;">
              </div>

              <h6 class="fw-bold text-primary"><i class="fas fa-hand-holding-heart me-2"></i>2.2 กดขอยืม</h6>
              <p class="text-secondary ms-4">หากสถานะหนังสือเป็น <span class="badge bg-success">ว่าง</span> ให้กดปุ่ม <strong>"ยืมหนังสือ"</strong> สีเขียว</p>
              <div class="text-center">
                <img src="images/sss.png" class="img-fluid rounded shadow img-hover-zoom" alt="Borrow" style="max-width: 90%;">
              </div>
            </div>

            <div class="tab-pane fade" id="step3">
              <div class="border-start border-4 border-primary ps-4 py-2 mb-4 bg-light rounded-end">
                <h4 class="fw-bold m-0 text-dark">การตรวจสอบประวัติ</h4>
                <small class="text-muted">เช็ครายการที่ยืมและวันกำหนดส่งคืน</small>
              </div>

              <div class="row g-4">
                <div class="col-md-6">
                  <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                      <h6 class="fw-bold text-primary">1. เข้าเมนูประวัติ</h6>
                      <p class="small text-secondary">กดที่เมนู "ประวัติการยืม" ด้านบน</p>
                      <img src="images/history1.png" class="img-fluid rounded border mt-2" alt="History Menu">
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                      <h6 class="fw-bold text-danger">2. ตรวจสอบวันคืน</h6>
                      <p class="small text-secondary">ระบบจะแจ้งเตือนหากเกินกำหนดส่ง</p>
                      <img src="images/history.png" class="img-fluid rounded border mt-2" alt="History Table">
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <footer class="text-center py-4 border-top">
    <div class="container">
      <small class="text-muted">&copy; 2025 TEXTBOOK BORROWING SYSTEM. All rights reserved.</small>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

  <script>
    AOS.init({
      duration: 800,
      easing: 'ease-out-cubic',
      once: true
    });
    particlesJS("particles-js", {
      "particles": {
        "number": {
          "value": 60,
          "density": {
            "enable": true,
            "value_area": 800
          }
        },
        "color": {
          "value": "#0d6efd"
        },
        "shape": {
          "type": "circle"
        },
        "opacity": {
          "value": 0.5,
          "random": true
        },
        "size": {
          "value": 3,
          "random": true
        },
        "line_linked": {
          "enable": true,
          "distance": 150,
          "color": "#0d6efd",
          "opacity": 0.2,
          "width": 1
        },
        "move": {
          "enable": true,
          "speed": 2
        }
      },
      "interactivity": {
        "detect_on": "canvas",
        "events": {
          "onhover": {
            "enable": true,
            "mode": "grab"
          },
          "onclick": {
            "enable": true,
            "mode": "push"
          }
        },
        "modes": {
          "grab": {
            "distance": 140,
            "line_linked": {
              "opacity": 1
            }
          },
          "push": {
            "particles_nb": 4
          }
        }
      },
      "retina_detect": true
    });
  </script>
</body>

</html>