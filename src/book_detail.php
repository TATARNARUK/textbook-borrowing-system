<?php
session_start();
require_once 'config.php';
require_once 'header.php'; // เรียก Header มาใช้เลย จะได้มีเมนูครบ

// รับค่า ID หนังสือที่ส่งมา
if (!isset($_GET['id'])) {
    echo "<script>window.location='index.php';</script>";
    exit();
}

$id = $_GET['id'];

// 1. ดึงข้อมูลรายละเอียดหนังสือ (Master)
$stmt = $pdo->prepare("SELECT * FROM book_masters WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    echo "<div class='container mt-5'><h3>ไม่พบข้อมูลหนังสือ</h3></div>";
    exit();
}

// 2. เช็คจำนวนสต็อก (ทั้งหมด / ว่าง / ถูกยืม)
$stmtStock = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed
    FROM book_items 
    WHERE book_master_id = ?
");
$stmtStock->execute([$id]);
$stock = $stmtStock->fetch();

// ป้องกันค่า NULL กรณีเพิ่งเพิ่มหนังสือแต่ยังไม่มีเล่มจริง
$total_items = $stock['total'] ?? 0;
$available_items = $stock['available'] ?? 0;
?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-4 text-center">
                    <?php if($book['cover_image']): ?>
                        <img src="uploads/<?php echo $book['cover_image']; ?>" class="img-fluid rounded shadow" style="max-height: 400px;">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/300x450?text=No+Cover" class="img-fluid rounded shadow">
                    <?php endif; ?>
                </div>

                <div class="col-md-8">
                    <h2 class="fw-bold text-primary mb-3"><?php echo $book['title']; ?></h2>
                    
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">รหัสวิชา / ISBN:</th>
                            <td><span class="badge bg-secondary fs-6"><?php echo $book['isbn']; ?></span></td>
                        </tr>
                        <tr>
                            <th>ผู้แต่ง:</th>
                            <td><?php echo $book['author']; ?></td>
                        </tr>
                        <tr>
                            <th>สำนักพิมพ์:</th>
                            <td><?php echo $book['publisher']; ?></td>
                        </tr>
                        <tr>
                            <th>ราคาต่อเล่ม:</th>
                            <td><?php echo number_format($book['price'], 2); ?> บาท</td>
                        </tr>
                        <tr>
                            <th>สถานะสต็อก:</th>
                            <td>
                                <span class="badge bg-info text-dark">ทั้งหมด <?php echo $total_items; ?> เล่ม</span>
                                <span class="badge bg-success">ว่าง <?php echo $available_items; ?> เล่ม</span>
                                <span class="badge bg-warning text-dark">ถูกยืม <?php echo $stock['borrowed'] ?? 0; ?> เล่ม</span>
                            </td>
                        </tr>
                    </table>

                    <hr>

                    <div class="mt-4"> 
                        <?php if($available_items > 0): ?>
                            <h4 class="text-success mb-3"><i class="fa-solid fa-check-circle"></i> มีหนังสือว่างพร้อมยืม</h4>
                        <?php else: ?>
                            <h4 class="text-danger mb-3"><i class="fa-solid fa-circle-xmark"></i> หนังสือหมดชั่วคราว</h4>
                        <?php endif; ?>
                        <div class="d-flex gap-2">
                            
                            <?php if($available_items > 0): ?>
                                <button onclick="confirmBorrowDetail(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')" 
                                        class="btn btn-lg btn-success px-4">
                                    <i class="fa-solid fa-book-open"></i> ยืมเล่มนี้ทันที
                                </button>
                            <?php else: ?>
                                <button class="btn btn-lg btn-secondary px-4" disabled>ไม่สามารถยืมได้</button>
                            <?php endif; ?>

                            <a href="index.php" class="btn btn-lg btn-outline-secondary px-4">
                                <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
                            </a>
                            <?php if($_SESSION['role'] == 'admin'): ?>
                                <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-lg btn-warning px-4 ms-2">
                                    <i class="fa-solid fa-edit"></i> แก้ไข
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmBorrowDetail(id, title) {
    Swal.fire({
        title: 'ยืนยันการยืม?',
        text: "คุณต้องการยืมหนังสือ: " + title,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ใช่, ขอยืมเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'borrow_save.php?id=' + id;
        }
    })
}
</script>

<?php require_once 'footer.php'; ?>