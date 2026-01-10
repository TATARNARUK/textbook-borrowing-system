<?php
session_start();
require_once 'config.php';

// เช็คว่าเป็นแอดมินหรือเปล่า
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
        $error_msg = "ลบไม่ได้ครับ! ยังมีหนังสือเล่มจริงเหลืออยู่ ต้องไปลบเล่มจริงออกให้หมดก่อน";
    } else {
        $stmt = $pdo->prepare("DELETE FROM book_masters WHERE id = ?");
        $stmt->execute([$id_book]);
        echo "<script>alert('ลบหนังสือเรียบร้อยแล้วครับ'); window.location='index.php';</script>";
        exit();
    }
}

// ------------------------------------------
// ส่วนบันทึกการแก้ไข (Update)
// ------------------------------------------
if (isset($_POST['update_book'])) {
    // รับค่าข้อมูลทั่วไป
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher = $_POST['publisher'];
    $price = $_POST['price'];

    // ✅ รับค่ารายละเอียดรูปเล่ม (ต้องแก้ชื่อตัวแปรให้ตรงกับ Database)
    $approval_no = $_POST['approval_no'];   // ครั้งที่อนุมัติ
    $approval_order = $_POST['approval_order']; // ลำดับที่อนุมัติ
    $page_count = $_POST['page_count'];     // จำนวนหน้า
    $paper_type = $_POST['paper_type'];     // รูปแบบกระดาษ
    $print_type = $_POST['print_type'];     // รูปแบบการพิมพ์
    $book_size = $_POST['book_size'];       // ขนาดรูปเล่ม

    // ✅ อัพเดท SQL (เพิ่มฟิลด์ใหม่เข้าไป)
    $sql_update = "UPDATE book_masters SET 
                    isbn=?, title=?, author=?, publisher=?, price=?,
                    approval_no=?, approval_order=?, page_count=?, paper_type=?, print_type=?, book_size=?
                   WHERE id=?";

    $data_update = [
        $isbn,
        $title,
        $author,
        $publisher,
        $price,
        $approval_no,
        $approval_order,
        $page_count,
        $paper_type,
        $print_type,
        $book_size,
        $id_book
    ];

    // เช็ครูปภาพ
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $file_ext = pathinfo($_FILES["cover_image"]["name"], PATHINFO_EXTENSION);
        $new_name = "book_" . uniqid() . "." . $file_ext;
        move_uploaded_file($_FILES["cover_image"]["tmp_name"], "uploads/" . $new_name);

        // ถ้ามีรูป ต้องแก้ SQL ให้เพิ่ม cover_image
        $sql_update = "UPDATE book_masters SET 
                        isbn=?, title=?, author=?, publisher=?, price=?,
                        approval_no=?, approval_order=?, page_count=?, paper_type=?, print_type=?, book_size=?,
                        cover_image=? 
                       WHERE id=?";

        $data_update = [
            $isbn,
            $title,
            $author,
            $publisher,
            $price,
            $approval_no,
            $approval_order,
            $page_count,
            $paper_type,
            $print_type,
            $book_size,
            $new_name,
            $id_book
        ];
    }

    $stmt = $pdo->prepare($sql_update);
    if ($stmt->execute($data_update)) {
        $success_msg = "บันทึกข้อมูลเรียบร้อยแล้วครับ";
    } else {
        $error_msg = "เกิดข้อผิดพลาดในการบันทึก";
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
    <title>แก้ไขข้อมูลหนังสือ</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8f9fa;
            background-image: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?q=80&w=2070&auto=format&fit=crop');
            /* รูป Background ชั่วคราว */
            background-size: cover;
            background-position: center;
        }
    </style>
</head>

<body>
    <div class="container mt-5 mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">แก้ไขข้อมูลหนังสือ</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error_msg)) {
                    echo "<div class='alert alert-danger'>$error_msg</div>";
                } ?>
                <?php if (isset($success_msg)) {
                    echo "<div class='alert alert-success'>$success_msg</div>";
                } ?>

                <form method="post" enctype="multipart/form-data">

                    <h5 class="text-primary mb-3">ข้อมูลทั่วไป</h5>
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

                    <hr>

                    <h5 class="text-primary mb-3">รายละเอียดรูปเล่มและการพิมพ์</h5>

                    <div class="row">
                        <div class="col-md-6 col-lg-4 mb-3">
                            <label class="form-label small fw-bold text-secondary">ครั้งที่อนุมัติ (เช่น 2/2562)</label>
                            <input type="text" name="approval_no" class="form-control" value="<?php echo isset($old_data['approval_no']) ? $old_data['approval_no'] : ''; ?>">
                        </div>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <label class="form-label small fw-bold text-secondary">ลำดับที่อนุมัติ</label>
                            <input type="text" name="approval_order" class="form-control" value="<?php echo isset($old_data['approval_order']) ? $old_data['approval_order'] : ''; ?>">
                        </div>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <label class="form-label small fw-bold text-secondary">จำนวนหน้า</label>
                            <input type="text" name="page_count" class="form-control" value="<?php echo isset($old_data['page_count']) ? $old_data['page_count'] : ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-lg-4 mb-3">
                            <label class="form-label small fw-bold text-secondary">รูปแบบกระดาษ</label>
                            <select name="paper_type" class="form-select bg-light">
                                <option value="">- เลือก -</option>
                                <option value="ปอนด์" <?php echo (isset($old_data['paper_type']) && $old_data['paper_type'] == 'ปอนด์') ? 'selected' : ''; ?>>กระดาษปอนด์</option>
                                <option value="ถนอมสายตา" <?php echo (isset($old_data['paper_type']) && $old_data['paper_type'] == 'ถนอมสายตา') ? 'selected' : ''; ?>>กระดาษถนอมสายตา (Green Read)</option>
                                <option value="อาร์ต" <?php echo (isset($old_data['paper_type']) && $old_data['paper_type'] == 'อาร์ต') ? 'selected' : ''; ?>>กระดาษอาร์ต</option>
                                <option value="บรู๊ฟ" <?php echo (isset($old_data['paper_type']) && $old_data['paper_type'] == 'บรู๊ฟ') ? 'selected' : ''; ?>>กระดาษบรู๊ฟ (Newsprint)</option>
                            </select>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-3">
                            <label class="form-label small fw-bold text-secondary">รูปแบบการพิมพ์</label>
                            <select name="print_type" class="form-select bg-light">
                                <option value="">- เลือก -</option>
                                <option value="1 สี" <?php echo (isset($old_data['print_type']) && $old_data['print_type'] == '1 สี') ? 'selected' : ''; ?>>พิมพ์ 1 สี (ขาวดำ)</option>
                                <option value="2 สี" <?php echo (isset($old_data['print_type']) && $old_data['print_type'] == '2 สี') ? 'selected' : ''; ?>>พิมพ์ 2 สี</option>
                                <option value="4 สี" <?php echo (isset($old_data['print_type']) && $old_data['print_type'] == '4 สี') ? 'selected' : ''; ?>>พิมพ์ 4 สี (Color)</option>
                            </select>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-3">
                            <label class="form-label small fw-bold text-secondary">ขนาดรูปเล่ม</label>
                            <select name="book_size" class="form-select bg-light">
                                <option value="">- เลือก -</option>
                                <option value="8 หน้ายก" <?php echo (isset($old_data['book_size']) && $old_data['book_size'] == '8 หน้ายก') ? 'selected' : ''; ?>>8 หน้ายก</option>
                                <option value="A4" <?php echo (isset($old_data['book_size']) && $old_data['book_size'] == 'A4') ? 'selected' : ''; ?>>A4</option>
                                <option value="อื่นๆ" <?php echo (isset($old_data['book_size']) && $old_data['book_size'] == 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ</option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label>เปลี่ยนรูปปก (ถ้าไม่เปลี่ยนก็ปล่อยว่างไว้)</label>
                        <input type="file" name="cover_image" class="form-control">
                        <?php if ($old_data['cover_image']): ?>
                            <img src="uploads/<?php echo $old_data['cover_image']; ?>" width="100" class="mt-2 rounded shadow-sm">
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