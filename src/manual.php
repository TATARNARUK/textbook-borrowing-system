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
    
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .step-circle {
            width: 50px; height: 50px;
            background-color: #0d6efd; color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; font-weight: bold;
            margin-right: 15px; flex-shrink: 0;
        }
        .screenshot-box {
            border: 2px dashed #ccc;
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            color: #aaa;
            min-height: 200px;
            display: flex; align-items: center; justify-content: center;
            flex-direction: column;
        }
        .screenshot-box img {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            border-radius: 50px;
        }
        .nav-pills .nav-link {
            color: #555;
            border-radius: 50px;
            padding: 10px 25px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="index.php">
                <img src="images/books.png" width="35" height="35" class="me-2"> 
                คู่มือการใช้งาน - User manual
            </a>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill btn-sm">
                <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
            </a>
        </div>
    </nav>

    <div class="bg-primary text-white py-5 text-center mb-5">
        <div class="container">
            <h1 class="fw-bold"><i class="fa-solid fa-book-open me-2"></i> คู่มือการใช้งานระบบ</h1>
            <p class="opacity-75">เรียนรู้วิธีการใช้งานระบบยืม-คืนหนังสือเรียนฟรี ง่ายๆ ใน 3 นาที</p>
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
                                <h3 class="fw-bold m-0 text-primary">การเข้าสู่ระบบ (Login)</h3>
                            </div>
                            <p class="fs-5 text-muted">นักเรียนสามารถเข้าใช้งานด้วยรหัสนักเรียนที่ลงทะเบียนไว้</p>
                            
                            <div class="alert alert-info border-0 rounded-3">
                                <i class="fa-solid fa-circle-info me-2"></i> 
                                หากยังไม่มีบัญชี กรุณากดปุ่ม <strong>"สมัครสมาชิก"</strong> และกรอกข้อมูลให้ครบถ้วน
                            </div>
                            <h5 class="fw-bold mt-3">1.1 เข้าสู่ระบบ</h5>
                             <p>ท่านต้องการเข้าใช้งาน ให้ท่านกรอก รหัสนักเรียน และรหัสผ่าน ที่ท่านได้สมัครไว้ <strong>"ลงในช่อง"</strong> กรอกข้อมูลให้ครบถ้วน แล้วกดเข้าสู่ระบบ</p>
                            <img src="images/manual_login.png" class="img-fluid rounded shadow border" alt="หน้า Login"><br>
                            <h5 class="fw-bold mt-3">1.2 สมัครสมาชิก</h5>
                            <p>หากท่านยังไม่มีบัญชีท่านต้องสมัครสมาชิก โดยให้ท่านกรอก <strong>รหัสนักเรียน ชื่อ-นามสกุล และรหัสผ่าน</strong> ที่ท่านต้องการ <strong>"ลงในช่อง"</strong><br>
                             กรอกข้อมูลให้ครบถ้วน แล้วกดลงทะเบียน</p>
                            <img src="images/register.png" class="img-fluid rounded shadow border" alt="หน้า register">
                        </div>
                    </div>

                    <div class="tab-pane fade" id="step2">
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <div class="d-flex align-items-center mb-4">
                                <div class="step-circle">2</div>
                                <h3 class="fw-bold m-0 text-primary">การค้นหาและยืมหนังสือ</h3>
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
                                <h3 class="fw-bold m-0 text-primary">การตรวจสอบประวัติ</h3>
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

    <footer class="text-center text-muted py-4 border-top bg-white">
        <div class="container">
            <small>&copy; 2025 TEXTBOOK BORROWING SYSTEM. All rights reserved.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>