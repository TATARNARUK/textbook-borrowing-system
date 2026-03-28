<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_book = $_GET['id'];

// ------------------------------------------
// ส่วนลบหนังสือ (Delete)
// ------------------------------------------
if (isset($_POST['delete_book'])) {
    $check_stock = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ?");
    $check_stock->execute([$id_book]);

    if ($check_stock->fetchColumn() > 0) {
        $error_msg = "ลบไม่ได้! ยังมีหนังสือเล่มจริงเหลืออยู่ในสต็อก กรุณาลบเล่มในสต็อกออกให้หมดก่อน";
    } else {
        // ดึงชื่อไฟล์รูปและ PDF เก่ามาลบ
        $stmt_file = $pdo->prepare("SELECT cover_image, sample_pdf FROM book_masters WHERE id = ?");
        $stmt_file->execute([$id_book]);
        $files = $stmt_file->fetch();

        // ลบรูป
        if ($files['cover_image'] && file_exists("uploads/" . $files['cover_image'])) {
            unlink("uploads/" . $files['cover_image']);
        }
        // ลบ PDF
        if ($files['sample_pdf'] && file_exists("uploads/pdfs/" . $files['sample_pdf'])) {
            unlink("uploads/pdfs/" . $files['sample_pdf']);
        }

        $stmt = $pdo->prepare("DELETE FROM book_masters WHERE id = ?");
        $stmt->execute([$id_book]);
        $success_msg = "deleted";
    }
}

// ------------------------------------------
// ส่วนบันทึกการแก้ไข (Update)
// ------------------------------------------
if (isset($_POST['update_book'])) {
    $approval_no = $_POST['approval_no'] ?? '-';
    $approval_order = $_POST['approval_order'] ?? '';
    $book_code = $_POST['book_code'] ?? '';
    $isbn = $_POST['isbn'] ?? '';
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $publisher = $_POST['publisher'] ?? '';
    $price = $_POST['price'] ?? 0;
    $page_count = $_POST['page_count'] ?? 0;
    $paper_type = $_POST['paper_type'] ?? '';
    $print_type = $_POST['print_type'] ?? '';
    $book_size = $_POST['book_size'] ?? '';

    // SQL พื้นฐาน (เพิ่ม book_code เข้าไป)
    $sql_update = "UPDATE book_masters SET 
                   book_code=?, isbn=?, title=?, author=?, publisher=?, price=?,
                   approval_no=?, approval_order=?, page_count=?, paper_type=?, print_type=?, book_size=?
                   WHERE id=?";

    $data_update = [$book_code, $isbn, $title, $author, $publisher, $price, $approval_no, $approval_order, $page_count, $paper_type, $print_type, $book_size, $id_book];

    $upload_error = false; // ตัวแปรสำหรับเช็คว่าอัปโหลดไฟล์ผ่านไหม

    // 1. จัดการรูปภาพ (Image)
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        
        // 🔥 เช็คและสร้างโฟลเดอร์ uploads อัตโนมัติ
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $file_ext = pathinfo($_FILES["cover_image"]["name"], PATHINFO_EXTENSION);
        $new_name = "book_" . uniqid() . "." . $file_ext;
        
        // 🔥 ดักเช็คสิทธิ์ Server การบันทึกไฟล์
        if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], "uploads/" . $new_name)) {
            // แทรก SQL และ Parameter สำหรับรูปภาพ
            $sql_update = str_replace("WHERE id=?", ", cover_image=? WHERE id=?", $sql_update);
            array_splice($data_update, count($data_update) - 1, 0, $new_name);
        } else {
            $upload_error = true;
            $error_msg = "อัปโหลดรูปภาพไม่สำเร็จ! (เซิร์ฟเวอร์ปฏิเสธการเขียนไฟล์ โปรดตั้งค่า Permission โฟลเดอร์ uploads เป็น 777)";
        }
    }

    // 2. จัดการไฟล์ตัวอย่าง (PDF)
    if (!$upload_error && isset($_FILES['sample_pdf']) && $_FILES['sample_pdf']['error'] == 0) {
        $pdf_ext = pathinfo($_FILES["sample_pdf"]["name"], PATHINFO_EXTENSION);
        if (strtolower($pdf_ext) == 'pdf') {
            $new_pdf_name = "sample_" . uniqid() . ".pdf";

            // 🔥 เช็คและสร้างโฟลเดอร์ pdfs อัตโนมัติ
            if (!is_dir('uploads/pdfs')) {
                mkdir('uploads/pdfs', 0777, true);
            }

            // 🔥 ดักเช็คสิทธิ์ Server การบันทึกไฟล์
            if (move_uploaded_file($_FILES["sample_pdf"]["tmp_name"], "uploads/pdfs/" . $new_pdf_name)) {
                // แทรก SQL และ Parameter สำหรับ PDF
                $sql_update = str_replace("WHERE id=?", ", sample_pdf=? WHERE id=?", $sql_update);
                array_splice($data_update, count($data_update) - 1, 0, $new_pdf_name);
            } else {
                $upload_error = true;
                $error_msg = "อัปโหลดไฟล์ PDF ไม่สำเร็จ! (เซิร์ฟเวอร์ปฏิเสธการเขียนไฟล์ โปรดตั้งค่า Permission โฟลเดอร์ uploads/pdfs เป็น 777)";
            }
        }
    }

    // ถ้าไม่มี Error จากการอัปโหลดไฟล์ ค่อยบันทึกข้อมูลลงฐานข้อมูล
    if (!$upload_error) {
        $stmt = $pdo->prepare($sql_update);
        if ($stmt->execute($data_update)) {
            $success_msg = "updated";
        } else {
            $error_msg = "เกิดข้อผิดพลาดในการบันทึกข้อมูลลงฐานข้อมูล";
        }
    }
}

// ดึงข้อมูลเก่าออกมาโชว์
$stmt_show = $pdo->prepare("SELECT * FROM book_masters WHERE id = ?");
$stmt_show->execute([$id_book]);
$old_data = $stmt_show->fetch();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขหนังสือ - Admin</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* --- 🎨 White & Blue Theme CSS --- */
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #f0f4f8;
            background-image: radial-gradient(#dbeafe 1px, transparent 1px);
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

        /* --- Inputs Styling --- */
        .form-control, .form-select {
            background-color: #f8f9fa !important;
            border: 1px solid #dee2e6;
            color: #333 !important;
            border-radius: 10px;
            padding: 12px 15px;
        }

        .form-control:focus, .form-select:focus {
            background-color: #fff !important;
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }

        .form-floating > label { color: #6c757d; }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: #0d6efd;
            background-color: transparent !important;
            font-weight: 600;
        }

        .form-floating>.form-control:-webkit-autofill~label { background-color: transparent !important; }

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
            min-height: 180px;
        }

        .upload-zone:hover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }

        .upload-zone input[type="file"] {
            position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer; z-index: 5;
        }
        
        /* ให้ปุ่มลิงก์กดได้ทะลุ input file */
        .upload-zone a {
            position: relative; z-index: 10;
        }

        /* --- Typography --- */
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
            font-weight: 600; border-radius: 10px; padding: 12px 30px;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(13, 110, 253, 0.3); color: #fff;
        }

        .btn-outline-custom {
            background: transparent; color: #6c757d; border: 1px solid #dee2e6;
            border-radius: 10px; font-weight: 600; padding: 10px 20px; transition: all 0.3s;
        }
        .btn-outline-custom:hover { color: #0d6efd; border-color: #0d6efd; background: #fff; }

        .btn-outline-danger-custom {
            background: #fff; color: #dc3545; border: 1px solid #f5c2c7;
            border-radius: 10px; font-weight: 600; padding: 12px 25px; transition: all 0.3s;
        }
        .btn-outline-danger-custom:hover { background: #dc3545; color: #fff; border-color: #dc3545; }
    </style>
</head>

<body>
    
    <div id="particles-js"></div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">

                <div class="glass-card p-4 p-md-5" data-aos="fade-up" data-aos-duration="1000">

                    <div class="d-flex align-items-center justify-content-between mb-5 border-bottom pb-3">
                        <h3 class="fw-bold mb-0 text-dark">
                            <span class="text-warning bg-warning bg-opacity-10 rounded-circle p-2 me-2 d-inline-flex justify-content-center align-items-center" style="width: 45px; height: 45px;">
                                <i class="fa-solid fa-pen-to-square fs-5"></i>
                            </span>
                            แก้ไขข้อมูลหนังสือ
                        </h3>
                        <a href="book_detail.php?id=<?php echo $id_book; ?>" class="btn btn-outline-custom btn-sm px-3">
                            <i class="fa-solid fa-arrow-left me-1"></i> ยกเลิก
                        </a>
                    </div>

                    <form method="post" enctype="multipart/form-data">

                        <div class="section-title">ข้อมูลหนังสือ</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="text" name="approval_no" class="form-control" id="approval_no" value="<?php echo htmlspecialchars($old_data['approval_no'] ?? ''); ?>">
                                    <label for="approval_no">ครั้งที่อนุมัติ</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="number" name="approval_order" class="form-control" id="approval_order" value="<?php echo htmlspecialchars($old_data['approval_order'] ?? ''); ?>" required>
                                    <label for="approval_order">ลำดับที่</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="text" name="book_code" class="form-control" id="book_code" value="<?php echo htmlspecialchars($old_data['book_code'] ?? ''); ?>" required>
                                    <label for="book_code">รหัสหนังสือ</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="text" name="isbn" class="form-control" id="isbn" value="<?php echo htmlspecialchars($old_data['isbn'] ?? ''); ?>">
                                    <label for="isbn">รหัส ISBN</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="title" class="form-control" id="title" value="<?php echo htmlspecialchars($old_data['title'] ?? ''); ?>" required>
                                    <label for="title">ชื่อหนังสือ</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="author" class="form-control" id="author" value="<?php echo htmlspecialchars($old_data['author'] ?? ''); ?>">
                                    <label for="author">ผู้แต่ง</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="publisher" class="form-control" id="publisher" value="<?php echo htmlspecialchars($old_data['publisher'] ?? ''); ?>">
                                    <label for="publisher">สำนักพิมพ์</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="number" step="0.01" name="price" class="form-control" id="price" value="<?php echo htmlspecialchars($old_data['price'] ?? ''); ?>">
                                    <label for="price">ราคา (บาท)</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="number" name="page_count" class="form-control" id="page_count" value="<?php echo htmlspecialchars($old_data['page_count'] ?? ''); ?>">
                                    <label for="page_count">จำนวนหน้า</label>
                                </div>
                            </div>
                        </div>

                        <div class="section-title mt-5">ลักษณะรูปเล่ม</div>
                        <?php
                            // 🔥 บังคับค่าเริ่มต้นตามที่ต้องการ (ถ้าไม่มีข้อมูลให้ใช้ค่าเหล่านี้เลย)
                            $def_paper = (!empty($old_data['paper_type']) && $old_data['paper_type'] !== '-') ? $old_data['paper_type'] : 'ปอนด์';
                            $def_print = (!empty($old_data['print_type']) && $old_data['print_type'] !== '-') ? $old_data['print_type'] : '4 สี';
                            $def_size  = (!empty($old_data['book_size'])  && $old_data['book_size'] !== '-')  ? $old_data['book_size'] : '8 หน้ายก';
                        ?>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="paper_type" class="form-select" id="paper_type">
                                        <option value="ปอนด์" <?php echo ($def_paper == 'ปอนด์') ? 'selected' : ''; ?>>ปอนด์</option>
                                        <option value="ถนอมสายตา" <?php echo ($def_paper == 'ถนอมสายตา') ? 'selected' : ''; ?>>ถนอมสายตา</option>
                                        <option value="อาร์ต" <?php echo ($def_paper == 'อาร์ต') ? 'selected' : ''; ?>>อาร์ต</option>
                                        <option value="บรู๊ฟ" <?php echo ($def_paper == 'บรู๊ฟ') ? 'selected' : ''; ?>>บรู๊ฟ</option>
                                    </select>
                                    <label for="paper_type">รูปแบบกระดาษ</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="print_type" class="form-select" id="print_type">
                                        <option value="1 สี" <?php echo ($def_print == '1 สี') ? 'selected' : ''; ?>>1 สี</option>
                                        <option value="2 สี" <?php echo ($def_print == '2 สี') ? 'selected' : ''; ?>>2 สี</option>
                                        <option value="4 สี" <?php echo ($def_print == '4 สี') ? 'selected' : ''; ?>>4 สี</option>
                                    </select>
                                    <label for="print_type">รูปแบบการพิมพ์</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="book_size" class="form-select" id="book_size">
                                        <option value="8 หน้ายก" <?php echo ($def_size == '8 หน้ายก') ? 'selected' : ''; ?>>8 หน้ายก</option>
                                        <option value="A4" <?php echo ($def_size == 'A4') ? 'selected' : ''; ?>>A4</option>
                                        <option value="อื่นๆ" <?php echo ($def_size == 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ</option>
                                    </select>
                                    <label for="book_size">ขนาดรูปเล่ม</label>
                                </div>
                            </div>
                        </div>

                        <div class="section-title mt-5">อัปโหลดไฟล์ (หากต้องการเปลี่ยน)</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="upload-zone h-100 d-flex flex-column justify-content-center" id="img-zone">
                                    <input type="file" name="cover_image" id="cover_image" accept="image/*" onchange="previewFile()">
                                    <div id="upload-label">
                                        <?php 
                                        $cover = $old_data['cover_image'] ?? '';
                                        if (!empty($cover)): 
                                            $showImg = (strpos($cover, 'http') === 0) ? $cover : "uploads/" . $cover;
                                        ?>
                                            <img src="<?php echo $showImg; ?>" style="max-height: 120px; border-radius: 8px;" class="mb-3 shadow-sm" onerror="this.src='https://via.placeholder.com/100x150?text=No+Cover'">
                                            <br><span class="fw-bold text-primary"><i class="fa-solid fa-rotate"></i> คลิกเพื่อเปลี่ยนรูปหน้าปก</span>
                                        <?php else: ?>
                                            <div class="mb-2"><i class="fa-regular fa-image fs-1 text-primary bg-primary bg-opacity-10 rounded-circle p-3"></i></div>
                                            <span class="fw-bold text-dark">อัปโหลดรูปหน้าปกหนังสือ</span><br>
                                            <small class="text-muted">คลิกเพื่อเลือกไฟล์รูปภาพ</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="upload-zone h-100 d-flex flex-column justify-content-center" id="pdf-zone">
                                    <input type="file" name="sample_pdf" id="sample_pdf" accept="application/pdf" onchange="previewPdf()">
                                    <div id="pdf-upload-label">
                                        <?php if (!empty($old_data['sample_pdf'])): ?>
                                            <div class="mb-3">
                                                <a href="book_detail.php?read_pdf=<?php echo $old_data['id']; ?>" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm">
                                                    <i class="fa-solid fa-file-pdf me-1"></i> ดูไฟล์ PDF เดิม
                                                </a>
                                            </div>
                                            <span class="fw-bold text-danger"><i class="fa-solid fa-rotate"></i> คลิกเพื่ออัปโหลดไฟล์ PDF ใหม่</span>
                                        <?php else: ?>
                                            <div class="mb-2"><i class="fa-regular fa-file-pdf fs-1 text-danger bg-danger bg-opacity-10 rounded-circle p-3"></i></div>
                                            <span class="fw-bold text-dark">อัปโหลดไฟล์ตัวอย่าง (PDF)</span><br>
                                            <small class="text-muted">คลิกเพื่อเลือกไฟล์ PDF</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-5 pt-4 border-top gap-3">
                            <button type="submit" name="delete_book" class="btn btn-outline-danger-custom" onclick="return confirm('⚠️ คำเตือน!\n\nคุณแน่ใจหรือไม่ที่จะลบหนังสือเล่มนี้?\nการกระทำนี้ไม่สามารถกู้คืนได้!')">
                                <i class="fa-solid fa-trash-can me-2"></i> ลบหนังสือ
                            </button>

                            <button type="submit" name="update_book" class="btn btn-custom-primary flex-grow-1 ms-md-auto" style="max-width: 300px;">
                                <i class="fa-solid fa-save me-2"></i> บันทึกการแก้ไข
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
     <?php include 'footer.php'; ?>                                       
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        AOS.init({ duration: 800, once: true });

        /* Particles Config */
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 160, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#0d6efd" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.5, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#0d6efd", "opacity": 0.2, "width": 1 },
                "move": { "enable": true, "speed": 2 }
            },
            "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": true, "mode": "grab" } } },
            "retina_detect": true
        });

        function previewFile() {
            const fileInput = document.getElementById('cover_image');
            const label = document.getElementById('upload-label');
            const zone = document.getElementById('img-zone');
            
            if (fileInput.files.length > 0) {
                label.innerHTML = '<i class="fa-solid fa-check-circle text-success fs-1 mb-2"></i><br><span class="fw-bold text-success">' + fileInput.files[0].name + '</span>';
                zone.style.borderColor = '#198754';
                zone.style.backgroundColor = '#f0fff4';
            }
        }

        function previewPdf() {
            const fileInput = document.getElementById('sample_pdf');
            const label = document.getElementById('pdf-upload-label');
            const zone = document.getElementById('pdf-zone');

            if (fileInput.files.length > 0) {
                const fileName = fileInput.files[0].name;
                const fileSize = (fileInput.files[0].size / 1024 / 1024).toFixed(2);

                if(fileSize > 40) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ไฟล์ใหญ่เกินไป',
                        text: 'ไฟล์ PDF ควรมีขนาดไม่เกิน 40MB (ไฟล์ของคุณ: ' + fileSize + ' MB)',
                        confirmButtonColor: '#ffc107'
                    });
                    fileInput.value = ""; 
                    return;
                }

                label.innerHTML = '<i class="fa-solid fa-file-pdf text-danger fs-1 mb-2"></i><br><span class="fw-bold text-danger">' + fileName + '</span><br><small class="text-muted">(' + fileSize + ' MB)</small>';
                zone.style.borderColor = '#dc3545';
                zone.style.backgroundColor = '#fff5f5';
            }
        }

        // SweetAlert Check
        <?php if (isset($success_msg)): ?>
            <?php if ($success_msg == 'deleted'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'ลบข้อมูลสำเร็จ',
                    text: 'หนังสือถูกลบออกจากระบบแล้ว',
                    confirmButtonColor: '#0d6efd'
                }).then(() => {
                    window.location = 'index.php';
                });
            <?php else: ?>
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    text: 'ข้อมูลหนังสือถูกอัปเดตแล้ว',
                    confirmButtonColor: '#0d6efd'
                }).then(() => {
                    window.location = 'book_detail.php?id=<?php echo $id_book; ?>';
                });
            <?php endif; ?>
        <?php elseif (isset($error_msg)): ?>
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: '<?php echo $error_msg; ?>',
                confirmButtonColor: '#dc3545'
            });
        <?php endif; ?>
    </script>
</body>

</html>