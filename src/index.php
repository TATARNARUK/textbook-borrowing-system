<?php
session_start();
require_once 'config.php';

// 1. ตรวจสอบว่า Login หรือยัง? ถ้ายังให้ดีดกลับไปหน้า Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูล User จาก Session
$user_name = $_SESSION['fullname'];
$user_role = $_SESSION['role']; // admin หรือ student
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการหนังสือ - ระบบยืมคืนหนังสือเรียนฟรี</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .book-cover { width: 80px; height: 120px; object-fit: cover; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .top-nav { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 15px 0; margin-bottom: 30px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <nav class="top-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <img src="images/LOGO-BNCC.png" height="40" alt="Logo"> <div>
                    <h5 class="m-0 fw-bold text-primary">TEXTBOOK SYSTEM</h5>
                    <small class="text-muted">ระบบยืม-คืนหนังสือเรียนฟรี</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span>สวัสดี, <strong><?php echo $user_name; ?></strong> (<?php echo ucfirst($user_role); ?>)</span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">ออกจากระบบ</a>
            </div>
        </div>
        </nav>

    <div class="container">
        
        <?php if($user_role == 'admin') { 
            // Query ข้อมูลสรุป
            $cnt_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
            $cnt_books = $pdo->query("SELECT COUNT(*) FROM book_items")->fetchColumn();
            $cnt_borrow = $pdo->query("SELECT COUNT(*) FROM book_items WHERE status='borrowed'")->fetchColumn();
            
            // นับหนังสือที่เกินกำหนด (Due date น้อยกว่าวันนี้ และยังไม่ได้คืน)
            $cnt_overdue = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status='borrowed' AND due_date < NOW()")->fetchColumn();
        ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <h3><?php echo $cnt_users; ?></h3>
                        <p class="mb-0"><i class="fa-solid fa-users"></i> นักเรียนทั้งหมด</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white shadow-sm">
                    <div class="card-body">
                        <h3><?php echo $cnt_books; ?></h3>
                        <p class="mb-0"><i class="fa-solid fa-book"></i> หนังสือในสต็อก (เล่ม)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark shadow-sm">
                    <div class="card-body">
                        <h3><?php echo $cnt_borrow; ?></h3>
                        <p class="mb-0"><i class="fa-solid fa-hand-holding"></i> กำลังถูกยืม</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white shadow-sm">
                    <div class="card-body">
                        <h3><?php echo $cnt_overdue; ?></h3>
                        <p class="mb-0"><i class="fa-solid fa-circle-exclamation"></i> เกินกำหนดคืน!</p>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
    </nav>

    <div class="container">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>📚 รายชื่อหนังสือเรียนทั้งหมด</h3>
            
            <div>
                <a href="my_history.php" class="btn btn-primary text-white ms-2">
                    <i class="fa-solid fa-file-pdf"></i> ประวัติการยืม
                </a>
                                <?php if($user_role == 'admin') { ?>
                <a href="report.php" class="btn btn-info text-white ms-2">
                    <i class="fa-solid fa-file-pdf"></i> รายงานสรุป
                </a>
                <?php } ?>
                <?php if($user_role == 'admin') { ?>
                    <a href="add_book.php" class="btn btn-success">
                        <i class="fa-solid fa-plus"></i> เพิ่มหนังสือใหม่
                    </a>
                <?php } ?>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <table id="bookTable" class="table table-hover align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">ปก</th>
                            <th width="15%">รหัสวิชา/ISBN</th>
                            <th width="30%">ชื่อหนังสือ</th>
                            <th width="15%">ผู้แต่ง</th>
                            <th width="10%">คงเหลือ</th>
                            <th width="20%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM book_masters ORDER BY id DESC");
                        while ($book = $stmt->fetch()) {
                            // เช็คจำนวนหนังสือที่ว่าง (คำนวณจาก table book_items)
                            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ? AND status = 'available'");
                            $countStmt->execute([$book['id']]);
                            $available = $countStmt->fetchColumn();
                        ?>
                        <tr>
                            <td>
                                <?php if($book['cover_image']): ?>
                                    <img src="uploads/<?php echo $book['cover_image']; ?>" class="book-cover">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/80x120?text=No+Cover" class="book-cover">
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo $book['isbn']; ?></span></td>
                            <td class="fw-bold text-primary"><?php echo $book['title']; ?></td>
                            <td><?php echo $book['author']; ?></td>
                            <td>
                                <?php if($available > 0): ?>
                                    <span class="badge bg-success">ว่าง <?php echo $available; ?> เล่ม</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">หมด</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($user_role == 'admin') { ?>
                                <a href="book_stock.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-warning w-100 mb-1">
                                <i class="fa-solid fa-layer-group"></i> จัดการสต็อก</a>
                                <?php } ?>
                                <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary w-100 mb-1">
                                    <i class="fa-solid fa-circle-info"></i> รายละเอียด
                                </a>
                                <?php if($available > 0): ?>
                                    <button onclick="confirmBorrow(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?>')" 
                                            class="btn btn-sm btn-outline-success w-100">
                                        ยืมหนังสือ
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary w-100" disabled>หนังสือหมด</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function () {
            $('#bookTable').DataTable({
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                    paginate: {
                        first: "หน้าแรก",
                        last: "หน้าสุดท้าย",
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    },
                    zeroRecords: "ไม่พบข้อมูลหนังสือ"
                }
            });
        });
        
    </script>
    <script>
        // ฟังก์ชันยืนยันการยืม
        function confirmBorrow(id, title) {
            Swal.fire({
                title: 'ยืนยันการยืม?',
                text: "คุณต้องการยืมหนังสือ: " + title,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่-ขอยืมเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // ถ้ากดยืนยัน ให้วิ่งไปไฟล์ borrow_save.php
                    window.location.href = 'borrow_save.php?id=' + id;
                }
            })
        }

        // เช็คค่าที่ส่งกลับมาจาก borrow_save.php เพื่อแสดงแจ้งเตือน
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        
        if (status === 'success') {
            Swal.fire('สำเร็จ!', 'ทำรายการยืมเรียบร้อยแล้ว', 'success')
                .then(() => { window.history.replaceState(null, null, window.location.pathname); }); // ล้าง URL
        } else if (status === 'error') {
            Swal.fire('ล้มเหลว', 'หนังสือเล่มนี้หมดพอดี', 'error');
        }
    </script>
</body>
</html>