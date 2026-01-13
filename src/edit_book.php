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
        // ลบรูปภาพเก่า (ถ้ามี)
        $stmt_img = $pdo->prepare("SELECT cover_image FROM book_masters WHERE id = ?");
        $stmt_img->execute([$id_book]);
        $img = $stmt_img->fetchColumn();
        if ($img && file_exists("uploads/" . $img)) {
            unlink("uploads/" . $img);
        }

        $stmt = $pdo->prepare("DELETE FROM book_masters WHERE id = ?");
        $stmt->execute([$id_book]);
        $success_msg = "deleted"; // ส่งสัญญาณเพื่อ JS Redirect
    }
}

// ------------------------------------------
// ส่วนบันทึกการแก้ไข (Update)
// ------------------------------------------
if (isset($_POST['update_book'])) {
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher = $_POST['publisher'];
    $price = $_POST['price'];
    $approval_no = $_POST['approval_no'];
    $approval_order = $_POST['approval_order'];
    $page_count = $_POST['page_count'];
    $paper_type = $_POST['paper_type'];
    $print_type = $_POST['print_type'];
    $book_size = $_POST['book_size'];

    // SQL เริ่มต้น
    $sql_update = "UPDATE book_masters SET 
                    isbn=?, title=?, author=?, publisher=?, price=?,
                    approval_no=?, approval_order=?, page_count=?, paper_type=?, print_type=?, book_size=?
                   WHERE id=?";

    $data_update = [$isbn, $title, $author, $publisher, $price, $approval_no, $approval_order, $page_count, $paper_type, $print_type, $book_size, $id_book];

    // เช็ครูปภาพ
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $file_ext = pathinfo($_FILES["cover_image"]["name"], PATHINFO_EXTENSION);
        $new_name = "book_" . uniqid() . "." . $file_ext;
        move_uploaded_file($_FILES["cover_image"]["tmp_name"], "uploads/" . $new_name);

        // SQL กรณีมีรูป
        $sql_update = "UPDATE book_masters SET 
                        isbn=?, title=?, author=?, publisher=?, price=?,
                        approval_no=?, approval_order=?, page_count=?, paper_type=?, print_type=?, book_size=?,
                        cover_image=? 
                       WHERE id=?";
        
        // แทรกชื่อรูปเข้าไปใน array ก่อนตัวสุดท้าย (id)
        array_splice($data_update, count($data_update)-1, 0, $new_name);
    }

    $stmt = $pdo->prepare($sql_update);
    if ($stmt->execute($data_update)) {
        $success_msg = "updated";
    } else {
        $error_msg = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
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

        /* White Card */
        .glass-card {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(13, 110, 253, 0.1);
            position: relative;
            z-index: 1;
        }

        /* Form Styling */
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

        /* Typography */
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
            padding-top: 5px; padding-bottom: 5px;
            border-radius: 0 5px 5px 0;
        }

        /* Image Preview */
        .current-img-box {
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 10px;
            background: #fff;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .current-img-box img {
            max-height: 150px; object-fit: cover; border-radius: 5px;
        }

        /* Buttons */
        .btn-custom-primary {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            color: #fff; border: none; font-weight: 600;
            border-radius: 10px; padding: 10px 25px;
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
            border-radius: 10px; font-weight: 600; padding: 10px 20px;
            transition: all 0.3s;
        }
        .btn-outline-custom:hover {
            border-color: #6c757d; background: #e9ecef; color: #333;
        }

        .btn-outline-danger-custom {
            background: #fff; color: #dc3545; border: 1px solid #f5c2c7;
            border-radius: 10px; font-weight: 600; padding: 10px 20px;
            transition: all 0.3s;
        }
        .btn-outline-danger-custom:hover {
            background: #dc3545; color: #fff; border-color: #dc3545;
        }
    </style>
</head>

<body>
    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

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

                        <div class="section-title">ข้อมูลทั่วไป</div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <div class="form-floating mb-3">
                                    <input type="text" name="title" class="form-control" id="title" value="<?php echo $old_data['title']; ?>" required>
                                    <label for="title">ชื่อหนังสือ</label>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="isbn" class="form-control" id="isbn" value="<?php echo $old_data['isbn']; ?>">
                                            <label for="isbn">ISBN</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" name="price" class="form-control" id="price" value="<?php echo $old_data['price']; ?>">
                                            <label for="price">ราคา</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="author" class="form-control" id="author" value="<?php echo $old_data['author']; ?>">
                                            <label for="author">ผู้แต่ง</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="publisher" class="form-control" id="publisher" value="<?php echo $old_data['publisher']; ?>">
                                            <label for="publisher">สำนักพิมพ์</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="current-img-box h-100 d-flex flex-column justify-content-center align-items-center">
                                    <label class="small text-secondary fw-bold mb-2">รูปปกปัจจุบัน</label>
                                    <?php if ($old_data['cover_image']): ?>
                                        <img src="uploads/<?php echo $old_data['cover_image']; ?>" class="img-fluid shadow-sm mb-3">
                                    <?php else: ?>
                                        <div class="text-muted py-4"><i class="fa-regular fa-image fa-3x"></i><br>ไม่มีรูปภาพ</div>
                                    <?php endif; ?>
                                    
                                    <div class="w-100 mt-auto">
                                        <label class="form-label small text-primary fw-bold text-start w-100">เปลี่ยนรูปปก (ถ้าต้องการ)</label>
                                        <input type="file" name="cover_image" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-title mt-5">รายละเอียดรูปเล่ม</div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" name="approval_no" class="form-control" id="approval_no" value="<?php echo $old_data['approval_no']; ?>">
                                    <label for="approval_no">ครั้งที่อนุมัติ</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" name="approval_order" class="form-control" id="approval_order" value="<?php echo $old_data['approval_order']; ?>">
                                    <label for="approval_order">ลำดับที่อนุมัติ</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" name="page_count" class="form-control" id="page_count" value="<?php echo $old_data['page_count']; ?>">
                                    <label for="page_count">จำนวนหน้า</label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="paper_type" class="form-select" id="paper_type">
                                        <option value="">- เลือก -</option>
                                        <option value="ปอนด์" <?php echo ($old_data['paper_type'] == 'ปอนด์') ? 'selected' : ''; ?>>กระดาษปอนด์</option>
                                        <option value="ถนอมสายตา" <?php echo ($old_data['paper_type'] == 'ถนอมสายตา') ? 'selected' : ''; ?>>กระดาษถนอมสายตา</option>
                                        <option value="อาร์ต" <?php echo ($old_data['paper_type'] == 'อาร์ต') ? 'selected' : ''; ?>>กระดาษอาร์ต</option>
                                        <option value="บรู๊ฟ" <?php echo ($old_data['paper_type'] == 'บรู๊ฟ') ? 'selected' : ''; ?>>กระดาษบรู๊ฟ</option>
                                    </select>
                                    <label for="paper_type">รูปแบบกระดาษ</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="print_type" class="form-select" id="print_type">
                                        <option value="">- เลือก -</option>
                                        <option value="1 สี" <?php echo ($old_data['print_type'] == '1 สี') ? 'selected' : ''; ?>>พิมพ์ 1 สี</option>
                                        <option value="2 สี" <?php echo ($old_data['print_type'] == '2 สี') ? 'selected' : ''; ?>>พิมพ์ 2 สี</option>
                                        <option value="4 สี" <?php echo ($old_data['print_type'] == '4 สี') ? 'selected' : ''; ?>>พิมพ์ 4 สี</option>
                                    </select>
                                    <label for="print_type">รูปแบบการพิมพ์</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="book_size" class="form-select" id="book_size">
                                        <option value="">- เลือก -</option>
                                        <option value="8 หน้ายก" <?php echo ($old_data['book_size'] == '8 หน้ายก') ? 'selected' : ''; ?>>8 หน้ายก</option>
                                        <option value="A4" <?php echo ($old_data['book_size'] == 'A4') ? 'selected' : ''; ?>>A4</option>
                                        <option value="อื่นๆ" <?php echo ($old_data['book_size'] == 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ</option>
                                    </select>
                                    <label for="book_size">ขนาดรูปเล่ม</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top">
                            <button type="submit" name="delete_book" class="btn btn-outline-danger-custom" onclick="return confirm('⚠️ คำเตือน!\n\nคุณแน่ใจหรือไม่ที่จะลบหนังสือเล่มนี้?\nการกระทำนี้ไม่สามารถกู้คืนได้!')">
                                <i class="fa-solid fa-trash-can me-2"></i> ลบหนังสือ
                            </button>

                            <button type="submit" name="update_book" class="btn btn-custom-primary btn-lg">
                                <i class="fa-solid fa-save me-2"></i> บันทึกการแก้ไข
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

        /* Particles Config (Blue) */
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
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

        // SweetAlert
        <?php if (isset($success_msg)): ?>
            <?php if ($success_msg == 'deleted'): ?>
                Swal.fire({
                    icon: 'success', title: 'ลบข้อมูลสำเร็จ', text: 'หนังสือถูกลบออกจากระบบแล้ว', confirmButtonColor: '#0d6efd'
                }).then(() => { window.location = 'index.php'; });
            <?php else: ?>
                Swal.fire({
                    icon: 'success', title: 'บันทึกสำเร็จ', text: 'ข้อมูลหนังสือถูกอัปเดตแล้ว', confirmButtonColor: '#0d6efd'
                }).then(() => { window.location = 'book_detail.php?id=<?php echo $id_book; ?>'; });
            <?php endif; ?>
        <?php elseif (isset($error_msg)): ?>
            Swal.fire({
                icon: 'error', title: 'เกิดข้อผิดพลาด', text: '<?php echo $error_msg; ?>', confirmButtonColor: '#dc3545'
            });
        <?php endif; ?>
    </script>
</body>

</html>