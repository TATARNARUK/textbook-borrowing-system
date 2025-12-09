<?php 
require 'config.php';
require 'header.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    // ... รับค่าอื่นๆ ...
    
    // การจัดการ Upload รูปภาพ
    $target_dir = "uploads/";
    // ตรวจสอบว่ามีโฟลเดอร์ uploads ไหม ถ้าไม่มีให้สร้าง (ใน Docker ต้องระวังเรื่อง Permission)
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $filename = uniqid() . "_" . basename($_FILES["cover_image"]["name"]);
    $target_file = $target_dir . $filename;
    
    if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
        // บันทึกลง Database
        $sql = "INSERT INTO book_masters (title, cover_image) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $filename]);
        
        // SweetAlert2 แบบ Success
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'สำเร็จ!',
                    text: 'บันทึกข้อมูลหนังสือเรียบร้อยแล้ว',
                    icon: 'success'
                }).then(() => {
                    window.location = 'index.php';
                });
            });
        </script>";
    }
}
?>

<div class="card">
    <div class="card-header">เพิ่มหนังสือใหม่</div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data"> <div class="mb-3">
                <label>ชื่อวิชา/ชื่อเรื่อง</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>รูปปกหนังสือ</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-success">บันทึก</button>
        </form>
    </div>
</div>

<?php require 'footer.php'; ?>