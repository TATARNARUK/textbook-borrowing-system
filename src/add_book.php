<?php
session_start();
require_once 'config.php';
require_once 'header.php';

// เช็คความปลอดภัย: ต้องเป็น Admin เท่านั้นถึงจะเข้าหน้านี้ได้
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location='index.php';</script>";
    exit();
}

// เมื่อมีการกดปุ่ม "บันทึกข้อมูล"
if (isset($_POST['save_book'])) {
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher = $_POST['publisher'];
    $price = $_POST['price'];

    // --- ส่วนจัดการอัปโหลดรูปภาพ ---
    $cover_image = ""; // ค่าเริ่มต้นถ้าไม่ได้อัปโหลดรูป
    
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "uploads/";
        
        // สร้างชื่อไฟล์ใหม่ไม่ให้ซ้ำกัน (เช่น book_654a1b2c.jpg)
        $file_extension = pathinfo($_FILES["cover_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = "book_" . uniqid() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        // ย้ายไฟล์จาก Temp ไปโฟลเดอร์ uploads
        if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
            $cover_image = $new_filename;
        }
    }
    // ----------------------------

    // บันทึกลงฐานข้อมูล (Table: book_masters)
    try {
        $sql = "INSERT INTO book_masters (isbn, title, author, publisher, price, cover_image) 
                VALUES (:isbn, :title, :author, :pub, :price, :img)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'isbn' => $isbn,
            'title' => $title,
            'author' => $author,
            'pub' => $publisher,
            'price' => $price,
            'img' => $cover_image
        ]);

        $success_msg = "เพิ่มหนังสือเรียบร้อยแล้ว!";
    } catch (PDOException $e) {
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มหนังสือใหม่ - ระบบห้องสมุด</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .form-card { max-width: 800px; margin: 30px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <div class="container">
        <div class="form-card">
            <h3 class="mb-4 text-primary"><i class="fa-solid fa-book-medical"></i> เพิ่มหนังสือใหม่</h3>
            
            <form action="" method="post" enctype="multipart/form-data"> <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">รหัสวิชา / ISBN</label>
                        <input type="text" name="isbn" class="form-control" required placeholder="เช่น 20000-1201">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ชื่อหนังสือ / ชื่อวิชา</label>
                        <input type="text" name="title" class="form-control" required placeholder="เช่น ภาษาไทยเพื่ออาชีพ">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ผู้แต่ง</label>
                        <input type="text" name="author" class="form-control" placeholder="ชื่อผู้แต่ง">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">สำนักพิมพ์</label>
                        <input type="text" name="publisher" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ราคาต่อเล่ม (บาท)</label>
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">รูปภาพปก</label>
                        <input type="file" name="cover_image" class="form-control" accept="image/*">
                        <small class="text-muted">รองรับไฟล์ jpg, png, jpeg</small>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-secondary">ยกเลิก</a>
                    <button type="submit" name="save_book" class="btn btn-success">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if (isset($success_msg)) : ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: '<?php echo $success_msg; ?>',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            window.location = 'index.php'; // บันทึกเสร็จให้เด้งกลับหน้าแรก
        });
    </script>
    <?php endif; ?>

</body>
</html>