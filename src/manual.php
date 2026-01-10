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
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
 body {
    font-family: 'Prompt', sans-serif;
    /* ✅ เปลี่ยนพื้นหลังเป็นไล่สี */
    background-color: black;
    margin: 0;
    min-height: 100vh; /* ให้สูงเต็มจอเสมอ */
    color: #333;
}
       #particles-js {
         position: fixed;
         /* ให้มันลอยอยู่กับที่ ไม่ต้องเลื่อนตาม Scroll bar */
         width: 100%;
         height: 100%;
         top: 0;
         left: 0;
         z-index: -1;
         /* ✅ สำคัญมาก! สั่งให้ไปอยู่ข้างหลังสุด */
         pointer-events: none;
         /* สั่งให้เม้าส์คลิกทะลุผ่านไปได้ (เผื่อไว้ก่อน) */
       }

.step-circle {
    width: 50px;
    height: 50px;
    /* ✅ เปลี่ยนวงกลมตัวเลขเป็นไล่สี */
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
    /* เพิ่มเงาให้นูนสวยขึ้น */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
}

.screenshot-box {
    /* ✅ เปลี่ยนเส้นปะเป็นสีม่วงอ่อนๆ ให้เข้าธีม */
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

/* ปุ่มเมนูแบบเลือกอยู่ (Active) */
.nav-pills .nav-link.active {
    /* ✅ เปลี่ยนพื้นหลังปุ่มเป็นไล่สี */
    background-color: black;
    border-radius: 50px;
    border: none; /* เอาขอบออก */
    box-shadow: 0 4px 10px rgba(255, 255, 255, 0.4); /* เงาสีม่วงฟุ้งๆ */
}

/* แถม: ปรับสีตัวหนังสือของปุ่มที่ยังไม่เลือก ให้เห็นชัดขึ้น */
.nav-pills .nav-link {
    color: #fff; /* หรือสีอื่นที่ตัดกับพื้นหลัง */
    background-color: rgba(255, 255, 255, 0.2); /* พื้นหลังจางๆ รองรับ */
    margin-right: 5px;
    border-radius: 50px;
}

        .nav-pills .nav-link {
            color: #555;
            border-radius: 50px;
            padding: 10px 25px;
        }
    </style>
</head>

<body><?php require_once 'loader.php'; ?><div id="particles-js"></div>

    <nav class="navbar navbar-expand-lg shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-white d-flex align-items-center" href="index.php">
                <img src="images/books.png" width="35" height="35" class="me-2">
                คู่มือการใช้งาน - User manual
            </a>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill btn-sm">
                <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
            </a>
        </div>
    </nav>

    <div class=" text-white py-5 text-center mb-5">
        <div class="container">
            <h1 class="fw-bold text-white"><i class="fa-solid fa-book-open me-2 text-white"></i> คู่มือการใช้งานระบบ</h1>
            <p class="opacity-75 text-white">เรียนรู้วิธีการใช้งานระบบยืม-คืนหนังสือเรียนฟรี ง่ายๆ ใน 3 นาที</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <ul class="nav nav-pills mb-5 justify-content-center" id="pills-tab" role="tablist">
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

                <div class="tab-content" id="pills-tabContent">

                    <div class="tab-pane fade show active" id="step1">
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <div class="d-flex align-items-center mb-4">
                                <div class="step-circle">1</div>
                                <h3 class="fw-bold m-0 text-dark">การเข้าสู่ระบบ (Login)</h3>
                            </div>
                            <p class="fs-5 text-muted">นักเรียนสามารถเข้าใช้งานด้วยรหัสประจำตัวนักเรียนของนักเรียนเอง</p>

                            <div class="alert alert-info border-0 rounded-3">
                                <li class="mb-1">หากท่านยังไม่มีบัญชี ให้<a href="https://www.facebook.com/kittikun.nookeaw?locale=th_TH" target="_blank" class="text-decoration-none">
                                        <i class="fas fa-headset me-เ1"></i> ติดต่อเจ้าหน้าที่
                                    </a></li>
                            </div>
                            <h5 class="fw-bold mt-3">1.1 เข้าสู่ระบบ</h5>
                            <p>ท่านต้องการเข้าใช้งาน ให้ท่านกรอก รหัสนักเรียน และรหัสผ่าน  <strong>"ลงในช่อง"</strong> กรอกข้อมูลให้ครบถ้วน แล้วกดเข้าสู่ระบบ</p>
                            <img src="images/manual_login1.png" class="img-fluid rounded shadow border" alt="หน้า Login"><br>
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

                            <img src="images/index2.png" class="img-fluid rounded shadow border" alt="หน้า index">

                            <h5 class="fw-bold mt-4">2.2 ดูรายละเอียดและกดขอยืม</h5>
                            <p>
                                - คลิกที่ <strong>รูปปกหนังสือ</strong> หรือ <strong>รายละเอียด</strong> เพื่อดูรายละเอียดเพิ่มเติม (Modal)<br>
                                - คลิกปุ่มสีเขียว <strong>"ยืมหนังสือ"</strong> เพื่อทำการยืม
                            </p>

                            <img src="images/manual_borrow1.png" class="img-fluid rounded shadow border" alt="หน้า Modal">
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
                                <img src="images/index1.png" class="img-fluid rounded shadow border" alt="หน้า index">
                                <li class="list-group-item"><i class="fa-solid fa-check text-success me-2"></i> ระบบจะแสดงรายการหนังสือทั้งหมด พร้อม <strong>"วันกำหนดส่ง"</strong></li>
                                <li class="list-group-item"><i class="fa-solid fa-check text-success me-2"></i> หากเกินกำหนด จะมีแจ้งเตือนสีแดง</li>
                                <img src="images/history.png" class="img-fluid rounded shadow border" alt="หน้า history">
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <footer class="text-center text-white py-4 border-top bg-dark">
        <div class="container">
            <small>&copy; 2025 TEXTBOOK BORROWING SYSTEM. All rights reserved.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
  AOS.init({
    duration: 1000, // ความเร็วในการขยับ (1000ms = 1 วินาที) ยิ่งเลขเยอะยิ่งช้าและนุ่ม
    once: true      // ให้เล่นแค่ครั้งเดียวตอนเลื่อนลงมา (ไม่ต้องเล่นซ้ำตอนเลื่อนขึ้น)
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
    /* เรียกใช้ particles.js ที่กล่อง id="particles-js" */
    particlesJS("particles-js", {
      "particles": {
        "number": {
          "value": 80, /* จำนวนดาว (ยิ่งเยอะยิ่งรก) ลองปรับดูที่ 50-100 */
          "density": {
            "enable": true,
            "value_area": 800
          }
        },
        "color": {
          "value": "#ffffff" /* สีของดาว (สีขาว) */
        },
        "shape": {
          "type": "circle", /* รูปร่าง (วงกลม) */
          "stroke": {
            "width": 0,
            "color": "#000000"
          },
          "polygon": {
            "nb_sides": 5
          }
        },
        "opacity": {
          "value": 0.5, /* ความจางของดาว (0.5 คือครึ่งๆ) */
          "random": true, /* ให้จางไม่เท่ากัน ดูมีมิติ */
          "anim": {
            "enable": false,
            "speed": 1,
            "opacity_min": 0.1,
            "sync": false
          }
        },
        "size": {
          "value": 3, /* ขนาดของดาว */
          "random": true, /* เล็กใหญ่ไม่เท่ากัน */
          "anim": {
            "enable": false,
            "speed": 40,
            "size_min": 0.1,
            "sync": false
          }
        },
        "line_linked": {
          "enable": true, /* ✅ ถ้าไม่อยากได้เส้นเชื่อม ให้แก้เป็น false */
          "distance": 150, /* ระยะห่างที่จะให้มีเส้นเชื่อม */
          "color": "#ffffff", /* สีของเส้น */
          "opacity": 0.4, /* ความจางของเส้น */
          "width": 1
        },
        "move": {
          "enable": true, /* สั่งให้ขยับ */
          "speed": 2, /* ความเร็วในการวิ่ง (ยิ่งเยอะยิ่งเร็ว) */
          "direction": "none", /* ทิศทาง (none คือมั่ว) */
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
      "interactivity": { /* ส่วนนี้คือเวลาเอาเมาส์ไปโดน */
        "detect_on": "canvas",
        "events": {
          "onhover": {
            "enable": true, /* ถ้า true เวลาเอาเมาส์ไปชี้ ดาวจะวิ่งหนีหรือวิ่งเข้าหา */
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
</body>

</html>