<?php
session_start();
require_once 'config.php';
require_once 'header.php'; // เรียก Header สวยๆ มาใช้

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// SQL: ถ้าเป็น Admin เห็นหมด, ถ้าเป็น User เห็นแค่ของตัวเอง
if ($role == 'admin') {
    $sql = "SELECT t.*, b.title, b.cover_image, bi.book_code, u.fullname 
            FROM transactions t 
            JOIN book_items bi ON t.book_item_id = bi.id 
            JOIN book_masters b ON bi.book_master_id = b.id
            JOIN users u ON t.user_id = u.id
            ORDER BY t.id DESC";
    $stmt = $pdo->query($sql);
} else {
    $sql = "SELECT t.*, b.title, b.cover_image, bi.book_code 
            FROM transactions t 
            JOIN book_items bi ON t.book_item_id = bi.id 
            JOIN book_masters b ON bi.book_master_id = b.id
            WHERE t.user_id = ? 
            ORDER BY t.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
}
?>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        
        <h4 class="mb-0"><i class="fa-solid fa-clock-rotate-left"></i> ประวัติการยืม-คืนหนังสือ</h4>
        
        <a href="index.php" class="btn btn-dark btn-sm">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
        </a>
        
    </div>
    <div class="card-body">
        <table class="table table-hover datatable">
            <thead class="table-light">
                <tr>
                    <th>ภาพ</th>
                    <th>ชื่อหนังสือ</th>
                    <th>รหัสเล่ม</th>
                    <?php if($role == 'admin') echo "<th>ผู้ยืม</th>"; ?>
                    <th>วันที่ยืม</th>
                    <th>กำหนดคืน</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch()) { 
                    $is_overdue = (strtotime($row['due_date']) < time()) && ($row['status'] == 'borrowed');
                ?>
                <tr>
                    <td>
                        <?php if($row['cover_image']): ?>
                            <img src="uploads/<?php echo $row['cover_image']; ?>" width="50" class="rounded">
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row['title']; ?></td>
                    <td><span class="badge bg-secondary"><?php echo $row['book_code']; ?></span></td>
                    
                    <?php if($role == 'admin') echo "<td>" . $row['fullname'] . "</td>"; ?>
                    
                    <td><?php echo date('d/m/Y', strtotime($row['borrow_date'])); ?></td>
                    <td>
                        <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                            <?php echo date('d/m/Y', strtotime($row['due_date'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php if($row['status'] == 'borrowed'): ?>
                            <?php if($is_overdue): ?>
                                <span class="badge bg-danger">เกินกำหนด</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">กำลังยืม</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-success">คืนแล้ว</span>
                            <br><small class="text-muted"><?php echo date('d/m/Y', strtotime($row['return_date'])); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($role == 'admin' && $row['status'] == 'borrowed'): ?>
                            <button onclick="confirmReturn(<?php echo $row['id']; ?>, <?php echo $row['book_item_id']; ?>)" 
                                    class="btn btn-sm btn-success">
                                <i class="fa-solid fa-check"></i> รับคืน
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function confirmReturn(transId, itemId) {
        Swal.fire({
            title: 'ยืนยันการคืนหนังสือ?',
            text: "ตรวจสอบสภาพหนังสือเรียบร้อยแล้วใช่หรือไม่",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, รับคืนเรียบร้อย'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `return_save.php?trans_id=${transId}&item_id=${itemId}`;
            }
        })
    }

    // แจ้งเตือนเมื่อคืนสำเร็จ
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'returned') {
        Swal.fire('สำเร็จ!', 'บันทึกการคืนหนังสือเรียบร้อยแล้ว', 'success')
            .then(() => window.history.replaceState(null, null, window.location.pathname));
    }
</script>

<?php require_once 'footer.php'; ?>