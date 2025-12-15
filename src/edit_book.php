<?php
session_start();
require_once 'config.php';

// เช็คว่าเป็นแอดมินหรือเปล่า ถ้าไม่ใช่แอดมิน ดีดกลับไปหน้าแรกเลย กันคนมั่วเข้ามา
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); 
    exit();
}

// เช็คว่ามี id ส่งมามั้ย ถ้าไม่มีก็ไม่รู้จะแก้อะไร ส่งกลับหน้าแรกไป
if (!isset($_GET['id'])) { 
    header("Location: index.php"); 
    exit(); 
}

$id_book = $_GET['id']; // รับ id มาเก็บไว้ก่อน

// ------------------------------------------
// ส่วนลบหนังสือ (Delete) - ทำงานตอนกดปุ่มลบ
// ------------------------------------------
if (isset($_POST['delete_book'])) {
    
    // ก่อนลบ ต้องเช็คก่อนว่ามี "เล่มจริง" ค้างในสต็อกมั้ย 
    // ถ้ามีค้างอยู่ ห้ามลบนะ เดี๋ยวข้อมูลรวน
    $check_stock = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ?");
    $check_stock->execute([$id_book]);
    
    if ($check_stock->fetchColumn() > 0) {
        $error_msg = "ลบไม่ได้ครับ! ยังมีหนังสือเล่มจริงเหลืออยู่ ต้องไปลบเล่มจริงออกให้หมดก่อน";
    } else {
        // ถ้าเคลียร์สต็อกหมดแล้ว ก็ลบข้อมูลหลักได้เลย
        $stmt = $pdo->prepare("DELETE FROM book_masters WHERE id = ?");
        $stmt->execute([$id_book]);
        
        // ลบเสร็จ เด้งไปบอกหน้าแรกว่าเรียบร้อย
        echo "<script>alert('ลบหนังสือเรียบร้อยแล้วครับ'); window.location='index.php';</script>";
        exit();
    }
}

// ------------------------------------------
// ส่วนบันทึกการแก้ไข (Update) - ทำงานตอนกดบันทึก
// ------------------------------------------
if (isset($_POST['update_book'])) {
    // รับค่าจากฟอร์มมาใส่ตัวแปร
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher = $_POST['publisher'];
    $price = $_POST['price'];

    // เตรียมคำสั่ง SQL อัพเดทข้อมูลทั่วไปก่อน
    $sql_update = "UPDATE book_masters SET isbn=?, title=?, author=?, publisher=?, price=? WHERE id=?";
    $data_update = [$isbn, $title, $author, $publisher, $price, $id_book];

    // เช็คว่ามีการอัพโหลดรูปปกใหม่มามั้ย?
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        
        // ตั้งชื่อไฟล์ใหม่สุ่มเลข มั่วๆ กันชื่อซ้ำ
        $file_ext = pathinfo($_FILES["cover_image"]["name"], PATHINFO_EXTENSION);
        $new_name = "book_" . uniqid() . "." . $file_ext;
        
        // ย้ายไฟล์ลงโฟลเดอร์ uploads
        move_uploaded_file($_FILES["cover_image"]["tmp_name"], "uploads/" . $new_name);
        
        // ถ้ามีรูป ต้องแก้คำสั่ง SQL ให้อัพเดทช่อง cover_image ด้วย
        $sql_update = "UPDATE book_masters SET isbn=?, title=?, author=?, publisher=?, price=?, cover_image=? WHERE id=?";
        $data_update = [$isbn, $title, $author, $publisher, $price, $new_name, $id_book];
    }

    // รันคำสั่ง SQL ลง Database
    $stmt = $pdo->prepare($sql_update);
    if ($stmt->execute($data_update)) {
        $success_msg = "โอเค! แก้ไขข้อมูลเรียบร้อยแล้ว";
    }
}

// ดึงข้อมูลเก่าออกมาโชว์ในฟอร์มก่อนแก้
$stmt_show = $pdo->prepare("SELECT * FROM book_masters WHERE id = ?");
$stmt_show->execute([$id_book]);
$old_data = $stmt_show->fetch();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลหนังสือ</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">แก้ไขข้อมูลหนังสือ</h4>
            </div>
            <div class="card-body">
                <?php if(isset($error_msg)) { echo "<div class='alert alert-danger'>$error_msg</div>"; } ?>
                <?php if(isset($success_msg)) { echo "<div class='alert alert-success'>$success_msg</div>"; } ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label>ชื่อหนังสือ</label>
                        <input type="text" name="title" class="form-control" value="<?php echo $old_data['title']; ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>ISBN</label>
                            <input type="text" name="isbn" class="form-control" value="<?php echo $old_data['isbn']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>ราคา</label>
                            <input type="number" name="price" class="form-control" value="<?php echo $old_data['price']; ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>ผู้แต่ง</label>
                            <input type="text" name="author" class="form-control" value="<?php echo $old_data['author']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>สำนักพิมพ์</label>
                            <input type="text" name="publisher" class="form-control" value="<?php echo $old_data['publisher']; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label>เปลี่ยนรูปปก (ถ้าไม่เปลี่ยนก็ปล่อยว่างไว้)</label>
                        <input type="file" name="cover_image" class="form-control">
                        <?php if($old_data['cover_image']): ?>
                            <img src="uploads/<?php echo $old_data['cover_image']; ?>" width="100" class="mt-2 rounded">
                        <?php endif; ?>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <button type="submit" name="delete_book" class="btn btn-danger" onclick="return confirm('จะลบจริงๆ เหรอ? กู้คืนไม่ได้นะ!')">ลบหนังสือทิ้ง</button>
                        
                        <div>
                            <a href="book_detail.php?id=<?php echo $id_book; ?>" class="btn btn-secondary">ยกเลิก</a>
                            <button type="submit" name="update_book" class="btn btn-primary">บันทึกการแก้ไข</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>