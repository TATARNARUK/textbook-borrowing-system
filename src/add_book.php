<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ (เปิดคอมเมนต์เมื่อใช้งานจริง)
if (!isset($_SESSION['user_id']) /* || $_SESSION['role'] !== 'admin' */) {
    // header("Location: login.php"); exit();
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher = $_POST['publisher'];
    $price = $_POST['price'];

    // ค่าใหม่
    $approval_no = $_POST['approval_no'];
    $approval_order = $_POST['approval_order'];
    $page_count = $_POST['page_count'];
    $paper_type = $_POST['paper_type'];
    $print_type = $_POST['print_type'];
    $book_size = $_POST['book_size'];

    // รูปภาพ
    $image_path = "";
    if (isset($_FILES['cover_img']) && $_FILES['cover_img']['error'] == 0) {
        $ext = pathinfo($_FILES['cover_img']['name'], PATHINFO_EXTENSION);
        $new_name = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['cover_img']['tmp_name'], "uploads/" . $new_name);
        $image_path = $new_name;
    }

    // SQL
    $sql = "INSERT INTO book_masters (isbn, title, author, publisher, price, approval_no, approval_order, page_count, paper_type, print_type, book_size, cover_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$isbn, $title, $author, $publisher, $price, $approval_no, $approval_order, $page_count, $paper_type, $print_type, $book_size, $image_path])) {
        $msg = "success";
    } else {
        $msg = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มหนังสือใหม่ - Admin</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* --- 🎨 White & Blue Theme CSS --- */
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #f0f4f8; /* พื้นหลังสีเทาอมฟ้าอ่อน */
            background-image: radial-gradient(#dbeafe 1px, transparent 1px); /* ลายจุดจางๆ */
            background-size: 20px 20px;
            color: #333;
            overflow-x: hidden;
        }

        #particles-js {
            position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: -1; pointer-events: none;
        }

        /* --- White Card --- */
        .glass-card {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(13, 110, 253, 0.1);
            position: relative;
            z-index: 1;
        }

        /* --- Inputs Styling (Light Mode) --- */
        .form-control, .form-select {
            background-color: #f8f9fa !important;
            border: 1px solid #dee2e6;
            color: #333 !important;
            border-radius: 10px;
            padding: 12px 15px;
            font-weight: 400;
        }

        .form-control:focus, .form-select:focus {
            background-color: #fff !important;
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
            color: #333 !important;
        }

        .form-floating > label {
            color: #6c757d;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: #0d6efd;
            background-color: transparent !important;
            font-weight: 600;
        }
        
        /* แก้ปัญหา Background ขาวทับ Label */
        .form-floating>.form-control:-webkit-autofill~label {
            background-color: transparent !important;
        }

        /* --- Upload Zone --- */
        .upload-zone {
            border: 2px dashed #dee2e6;
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            height: 100%;
            min-height: 150px;
        }

        .upload-zone:hover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }

        .upload-zone input[type="file"] {
            position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer;
        }

        /* --- Typography & Elements --- */
        .section-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0d6efd;
            font-weight: 700;
            margin-bottom: 25px;
            border-left: 4px solid #0d6efd;
            padding-left: 12px;
            background-color: #e7f1ff;
            padding-top: 5px;
            padding-bottom: 5px;
            border-radius: 0 5px 5px 0;
        }

        /* --- Buttons --- */
        .btn-custom-primary {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            color: #fff;
            border: none;
            font-weight: 600; border-radius: 10px; padding: 12px 20px;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(13, 110, 253, 0.3);
            color: #fff;
        }

        .btn-outline-custom {
            background: transparent; color: #6c757d; border: 1px solid #dee2e6;
            border-radius: 10px; font-weight: 600;
            transition: all 0.3s;
        }
        .btn-outline-custom:hover {
            color: #0d6efd; border-color: #0d6efd; background: #fff;
        }
    </style>
</head>

<body>
    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">

                <div class="glass-card p-4 p-md-5" data-aos="fade-up" data-aos-duration="1000">

                    <div class="d-flex align-items-center justify-content-between mb-5 border-bottom pb-3">
                        <h3 class="fw-bold mb-0 text-dark">
                            <span class="text-primary bg-primary bg-opacity-10 rounded-circle p-2 me-2 d-inline-flex justify-content-center align-items-center" style="width: 45px; height: 45px;">
                                <i class="fa-solid fa-plus fs-5"></i>
                            </span>
                            เพิ่มหนังสือใหม่
                        </h3>
                        <a href="index.php" class="btn btn-outline-custom btn-sm px-3">
                            <i class="fa-solid fa-arrow-left me-1"></i> กลับหน้าหลัก
                        </a>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data">

                        <div class="section-title">ข้อมูลทั่วไป</div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="isbn" name="isbn" placeholder="ISBN" required autocomplete="off">
                                    <label for="isbn">รหัสวิชา / ISBN</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="title" name="title" placeholder="ชื่อหนังสือ" required autocomplete="off">
                                    <label for="title">ชื่อหนังสือ / ชื่อวิชา</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="author" name="author" placeholder="ผู้แต่ง" autocomplete="off">
                                    <label for="author">ชื่อผู้แต่ง</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="publisher" name="publisher" placeholder="สำนักพิมพ์" autocomplete="off">
                                    <label for="publisher">สำนักพิมพ์</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" placeholder="ราคา" autocomplete="off">
                                    <label for="price">ราคาหน้าปก (บาท)</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="upload-zone h-100 d-flex flex-column justify-content-center">
                                    <input type="file" name="cover_img" id="cover_img" accept="image/*" onchange="previewFile()">
                                    <div id="upload-label">
                                        <div class="mb-2">
                                            <i class="fa-regular fa-image fs-1 text-primary bg-primary bg-opacity-10 rounded-circle p-3"></i>
                                        </div>
                                        <span class="fw-bold text-dark">อัปโหลดรูปหน้าปก</span><br>
                                        <small class="text-muted">คลิกเพื่อเลือกไฟล์รูปภาพ</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-title mt-5">รายละเอียดและอนุมัติ</div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="approval_no" name="approval_no" placeholder="ครั้งที่อนุมัติ" autocomplete="off">
                                    <label for="approval_no">ครั้งที่อนุมัติ</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="approval_order" name="approval_order" placeholder="ลำดับ" autocomplete="off">
                                    <label for="approval_order">ลำดับที่อนุมัติ</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="page_count" name="page_count" placeholder="หน้า" autocomplete="off">
                                    <label for="page_count">จำนวนหน้า</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-floating">
                                    <select class="form-select" id="paper_type" name="paper_type">
                                        <option value="">- เลือก -</option>
                                        <option value="ปอนด์">ปอนด์</option>
                                        <option value="ถนอมสายตา">ถนอมสายตา</option>
                                        <option value="อาร์ต">อาร์ต</option>
                                        <option value="บรู๊ฟ">บรู๊ฟ</option>
                                    </select>
                                    <label for="paper_type">กระดาษ</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-floating">
                                    <select class="form-select" id="print_type" name="print_type">
                                        <option value="">- เลือก -</option>
                                        <option value="1 สี">1 สี</option>
                                        <option value="2 สี">2 สี</option>
                                        <option value="4 สี">4 สี</option>
                                    </select>
                                    <label for="print_type">การพิมพ์</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-floating">
                                    <select class="form-select" id="book_size" name="book_size">
                                        <option value="">- เลือก -</option>
                                        <option value="8 หน้ายก">8 หน้ายก</option>
                                        <option value="A4">A4</option>
                                        <option value="อื่นๆ">อื่นๆ</option>
                                    </select>
                                    <label for="book_size">ขนาด</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" class="btn btn-custom-primary btn-lg rounded-pill py-3">
                                <i class="fa-solid fa-save me-2"></i> บันทึกข้อมูลหนังสือ
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });

        /* Particles สีฟ้า */
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
            "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": true, "mode": "grab" } } },
            "retina_detect": true
        });

        // Script Show Filename
        function previewFile() {
            const fileInput = document.getElementById('cover_img');
            const label = document.getElementById('upload-label');
            if (fileInput.files.length > 0) {
                label.innerHTML = '<i class="fa-solid fa-check-circle text-success fs-1 mb-2"></i><br><span class="fw-bold text-success">' + fileInput.files[0].name + '</span>';
                document.querySelector('.upload-zone').style.borderColor = '#198754';
                document.querySelector('.upload-zone').style.backgroundColor = '#f0fff4';
            }
        }

        // SweetAlert Style (Light Theme)
        <?php if ($msg == 'success'): ?>
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: 'เพิ่มข้อมูลหนังสือเรียบร้อยแล้ว',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.location = 'index.php';
            });
        <?php elseif ($msg == 'error'): ?>
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'ปิด'
            });
        <?php endif; ?>
    </script>
</body>

</html>