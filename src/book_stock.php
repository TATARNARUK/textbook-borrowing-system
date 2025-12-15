<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); exit();
}

// รับ ID ของหนังสือต้นแบบ (Master ID) ที่ส่งมาจากหน้าแรก
if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$master_id = $_GET['id'];

// ดึงข้อมูลหนังสือต้นแบบมาโชว์หัวข้อ
$stmtMaster = $pdo->prepare("SELECT * FROM book_masters WHERE id = ?");
$stmtMaster->execute([$master_id]);
$bookMaster = $stmtMaster->fetch();

// --- ส่วนบันทึกเมื่อกดเพิ่มเล่ม (Add Item) ---
if (isset($_POST['add_stock'])) {
    $amount = (int)$_POST['amount']; // จำนวนเล่มที่ต้องการเพิ่ม
    
    for ($i = 0; $i < $amount; $i++) {
        // สร้างรหัสบาร์โค้ดอัตโนมัติ (เช่น B001-TIMESTAMP-RANDOM) หรือจะกรอกเองก็ได้
        // ในที่นี้ผมจะเจนให้เลยเพื่อความง่าย
        $barcode = date('ymd') . rand(1000, 9999);
        
        $sql = "INSERT INTO book_items (book_master_id, book_code, status) VALUES (?, ?, 'available')";
        $pdo->prepare($sql)->execute([$master_id, $barcode]);
    }
    
    $success_msg = "เพิ่มหนังสือจำนวน $amount เล่ม เรียบร้อยแล้ว!";
}
// ----------------------------------------

// --- ส่วนลบเล่มหนังสือ ---
if (isset($_GET['delete_item'])) {
    $del_id = $_GET['delete_item'];
    $pdo->prepare("DELETE FROM book_items WHERE id = ?")->execute([$del_id]);
    header("Location: book_stock.php?id=" . $master_id); // รีเฟรชหน้าเดิม
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการสต็อกหนังสือ</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <div class="container mt-5">
        <a href="index.php" class="btn btn-secondary mb-3"><i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก</a>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                         <?php if($bookMaster['cover_image']): ?>
                            <img src="uploads/<?php echo $bookMaster['cover_image']; ?>" class="w-100 rounded">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-10">
                        <h3><?php echo $bookMaster['title']; ?></h3>
                        <p class="text-muted">ISBN: <?php echo $bookMaster['isbn']; ?> | ผู้แต่ง: <?php echo $bookMaster['author']; ?></p>
                        
                        <hr>
                        
                        <form method="post" class="d-flex align-items-end gap-2 mb-4">
                            <div>
                                <label>จำนวนเล่มที่ต้องการเพิ่ม</label>
                                <input type="number" name="amount" class="form-control" value="1" min="1" max="50">
                            </div>
                            <button type="submit" name="add_stock" class="btn btn-success"><i class="fa-solid fa-plus"></i> เพิ่มสต็อก</button>
                        </form>
                    </div>
                </div>

                <h5 class="mt-4">รายการเล่มหนังสือทั้งหมด (Items)</h5>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>ลำดับ</th>
                            <th>รหัสบาร์โค้ด (Book Code)</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stmtItems = $pdo->prepare("SELECT * FROM book_items WHERE book_master_id = ? ORDER BY id DESC");
                        $stmtItems->execute([$master_id]);
                        $count = 1;
                        while ($item = $stmtItems->fetch()) {
                        ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><span class="badge bg-dark"><?php echo $item['book_code']; ?></span></td>
                            <td>
                                <?php if($item['status'] == 'available'): ?>
                                    <span class="badge bg-success">ว่าง</span>
                                <?php elseif($item['status'] == 'borrowed'): ?>
                                    <span class="badge bg-warning text-dark">ถูกยืม</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?php echo $item['status']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($item['status'] == 'available'): ?>
                                    <a href="book_stock.php?id=<?php echo $master_id; ?>&delete_item=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-danger" onclick="return confirm('ลบเล่มนี้จริงหรือไม่?')">ลบ</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (isset($success_msg)) : ?>
    <script>Swal.fire('สำเร็จ!', '<?php echo $success_msg; ?>', 'success');</script>
    <?php endif; ?>
</body>
</html>