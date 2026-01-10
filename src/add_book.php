<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ Admin (ถ้ามี)
if (!isset($_SESSION['user_id']) /* || $_SESSION['role'] !== 'admin' */) {
    // header("Location: login.php"); exit();
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์มเดิม
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher = $_POST['publisher'];
    $price = $_POST['price'];
    
    // ✅ รับค่าใหม่ที่เพิ่งเพิ่มเข้ามา
    $approval_no = $_POST['approval_no'];
    $approval_order = $_POST['approval_order'];
    $page_count = $_POST['page_count'];
    $paper_type = $_POST['paper_type'];
    $print_type = $_POST['print_type'];
    $book_size = $_POST['book_size'];

    // จัดการรูปภาพ (โค้ดเดิม)
    $image_path = ""; 
    if(isset($_FILES['cover_img']) && $_FILES['cover_img']['error'] == 0){
        $ext = pathinfo($_FILES['cover_img']['name'], PATHINFO_EXTENSION);
        $new_name = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['cover_img']['tmp_name'], "uploads/" . $new_name);
        $image_path = $new_name;
    }

    // ✅ SQL INSERT แบบใหม่ (เพิ่มคอลัมน์ใหม่เข้าไป)
    $sql = "INSERT INTO book_masters (isbn, title, author, publisher, price, approval_no, approval_order, page_count, paper_type, print_type, book_size, cover_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$isbn, $title, $author, $publisher, $price, $approval_no, $approval_order, $page_count, $paper_type, $print_type, $book_size, $image_path])) {
        $msg = "บันทึกข้อมูลหนังสือเรียบร้อยแล้ว!";
    } else {
        $msg = "เกิดข้อผิดพลาดในการบันทึก";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มหนังสือใหม่</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> </head>
<body class="bg-light">

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                        <h4 class="fw-bold text-primary"><i class="fa-solid fa-book-medical me-2"></i> เพิ่มหนังสือใหม่</h4>
                    </div>
                    
                    <div class="card-body p-4">
                        
                        <?php if($msg): ?>
                            <div class="alert alert-success text-center rounded-3 mb-4"><?php echo $msg; ?></div>
                        <?php endif; ?>

                        <form action="" method="POST" enctype="multipart/form-data">
                            
                            <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">ข้อมูลทั่วไป</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">รหัสวิชา / ISBN</label>
                                    <input type="text" name="isbn" class="form-control bg-light" required placeholder="เช่น 20000-1201">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">ชื่อหนังสือ / ชื่อวิชา</label>
                                    <input type="text" name="title" class="form-control bg-light" required placeholder="เช่น ภาษาไทยเพื่ออาชีพ">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">ผู้แต่ง</label>
                                    <input type="text" name="author" class="form-control bg-light">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">สำนักพิมพ์</label>
                                    <input type="text" name="publisher" class="form-control bg-light">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">ราคาหน้าปก (บาท)</label>
                                    <input type="number" step="0.01" name="price" class="form-control bg-light" placeholder="0.00">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">รูปภาพปก</label>
                                    <input type="file" name="cover_img" class="form-control bg-light" accept="image/*">
                                </div>
                            </div>

                            <h6 class="text-muted fw-bold mb-3 mt-4 border-bottom pb-2">รายละเอียดการอนุมัติ & รูปเล่ม</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">ครั้งที่อนุมัติ</label>
                                    <input type="text" name="approval_no" class="form-control bg-light" placeholder="เช่น ศธ 0411.xxx/xxxx">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">ลำดับที่อนุมัติ</label>
                                    <input type="number" name="approval_order" class="form-control bg-light" placeholder="ระบุเลขลำดับ">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary">จำนวนหน้า</label>
                                    <input type="number" name="page_count" class="form-control bg-light" placeholder="0">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary">รูปแบบกระดาษ</label>
                                    <select name="paper_type" class="form-select bg-light">
                                        <option value="">- เลือก -</option>
                                        <option value="ปอนด์">กระดาษปอนด์</option>
                                        <option value="ถนอมสายตา">กระดาษถนอมสายตา (Green Read)</option>
                                        <option value="อาร์ต">กระดาษอาร์ต</option>
                                        <option value="บรู๊ฟ">กระดาษบรู๊ฟ (Newsprint)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary">รูปแบบการพิมพ์</label>
                                    <select name="print_type" class="form-select bg-light">
                                        <option value="">- เลือก -</option>
                                        <option value="1 สี">พิมพ์ 1 สี (ขาวดำ)</option>
                                        <option value="2 สี">พิมพ์ 2 สี</option>
                                        <option value="4 สี">พิมพ์ 4 สี (Color)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary">ขนาดรูปเล่ม</label>
                                    <select name="book_size" class="form-select bg-light">
                                        <option value="">- เลือก -</option>
                                        <option value="8 หน้ายก">8 หน้ายก</option>
                                        <option value="A4">A4</option>
                                        <option value="อื่นๆ">อื่นๆ</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php" class="btn btn-secondary px-4 rounded-pill">ยกเลิก</a>
                                <button type="submit" class="btn btn-success px-5 rounded-pill shadow-sm">บันทึกข้อมูล</button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>