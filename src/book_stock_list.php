<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// 🔥 ฟังก์ชันลบหนังสือ "ทั้งหมดในระบบ" (Delete All Books System)
if (isset($_POST['delete_all_system'])) {
    try {
        // 1. เช็คก่อนว่ามีเล่มไหน "กำลังถูกยืม" อยู่ไหม?
        $stmtCheck = $pdo->query("SELECT COUNT(*) FROM book_items WHERE status = 'borrowed'");
        $borrowedCount = $stmtCheck->fetchColumn();

        if ($borrowedCount > 0) {
            $error_msg = "ไม่สามารถลบได้! มีหนังสือถูกยืมค้างอยู่ $borrowedCount เล่ม (ต้องรับคืนให้ครบก่อน)";
        } else {
            $pdo->beginTransaction();
            
            // 2. ลบประวัติการยืม-คืนทั้งหมด
            $pdo->exec("DELETE FROM transactions");
            
            // 3. ลบเล่มหนังสือทั้งหมด (Items)
            $pdo->exec("DELETE FROM book_items"); 
            
            // 4. 🔥 ลบข้อมูลหนังสือหลักทั้งหมด (Masters) - เพิ่มส่วนนี้ตามที่ขอ
            $pdo->exec("DELETE FROM book_masters"); 
            
            $pdo->commit();
            
            $success_msg = "ลบหนังสือและข้อมูลทั้งหมดในระบบเรียบร้อยแล้ว (Reset System)!";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ค้นหา
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM book_masters WHERE title LIKE ? OR isbn LIKE ? ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%"]);
$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เลือกหนังสือจัดการสต็อก</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Thai', sans-serif; background-color: #f0f4f8; background-image: radial-gradient(#dbeafe 1px, transparent 1px); background-size: 20px 20px; color: #333; overflow-x: hidden; }
        #particles-js { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: -1; pointer-events: none; }
        
        .btn-delete-system { background-color: #dc3545; color: white; border: none; font-weight: bold; transition: 0.3s; }
        .btn-delete-system:hover { background-color: #b02a37; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(220, 53, 69, 0.4); color: white; }
    </style>
</head>

<body>
    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>
    
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-down" data-aos-duration="800">
            <h3>📦 เลือกหนังสือเพื่อจัดการสต็อก</h3>
            
            <div class="d-flex gap-2">
                <form method="POST" onsubmit="return confirm('⚠️⚠️ คำเตือนครั้งสุดท้าย! ⚠️⚠️\n\nคุณกำลังจะลบข้อมูล \'หนังสือทุกเล่ม\' ออกจากระบบอย่างถาวร!\n\n- ข้อมูลหนังสือหลัก (Master) จะหายไป\n- จำนวนสต็อกจะหายไป\n- ประวัติการยืมจะหายไป\n\nยืนยันที่จะทำต่อหรือไม่?');">
                    <button type="submit" name="delete_all_system" class="btn btn-delete-system rounded-pill px-3 shadow-sm">
                        <i class="fa-solid fa-radiation me-2"></i> ลบหนังสือทั้งหมด (Reset)
                    </button>
                </form>

                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold border-2">
                    <i class="fa-solid fa-arrow-left me-2"></i> กลับหน้าหลัก
                </a>
            </div>
        </div>

        <form method="GET" class="mb-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="800">
            <div class="input-group">
                <input type="text" name="q" class="form-control rounded-start-pill ps-4" placeholder="ค้นหาชื่อหนังสือ หรือ ISBN..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary rounded-end-pill px-4" type="submit"><i class="fa-solid fa-magnifying-glass"></i> ค้นหา</button>
            </div>
        </form>

        <div class="card shadow-sm border-0 rounded-4" data-aos="fade-up" data-aos-delay="200" data-aos-duration="800">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ISBN</th>
                                <th>ชื่อหนังสือ</th>
                                <th class="text-center">จำนวนเล่ม (ทั้งหมด)</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book):
                                // นับจำนวนเล่ม
                                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ?");
                                $stmtCount->execute([$book['id']]);
                                $count = $stmtCount->fetchColumn();
                            ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border"><?php echo $book['isbn']; ?></span></td>
                                    <td class="fw-bold text-primary"><?php echo $book['title']; ?></td>
                                    <td class="text-center">
                                        <?php if($count > 0): ?>
                                            <span class="badge bg-success rounded-pill"><?php echo $count; ?> เล่ม</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill text-white-50">0 เล่ม</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="book_stock.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-warning fw-bold rounded-pill px-3 shadow-sm">
                                            <i class="fa-solid fa-boxes-stacked me-1"></i> จัดการสต็อก
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        // เริ่มต้น AOS Animation
        AOS.init({ once: true, offset: 50 });

        particlesJS("particles-js", { "particles": { "number": { "value": 160, "density": { "enable": true, "value_area": 800 } }, "color": { "value": "#0d6efd" }, "shape": { "type": "circle" }, "opacity": { "value": 0.5, "random": true }, "size": { "value": 3, "random": true }, "line_linked": { "enable": true, "distance": 150, "color": "#0d6efd", "opacity": 0.2, "width": 1 }, "move": { "enable": true, "speed": 2 } }, "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": true, "mode": "grab" } }, "onclick": { "enable": true, "mode": "push" } }, "retina_detect": true });

        <?php if (isset($success_msg)) : ?>
            Swal.fire({ title: 'สำเร็จ!', text: '<?php echo $success_msg; ?>', icon: 'success', confirmButtonColor: '#0d6efd', confirmButtonText: 'ตกลง' });
        <?php endif; ?>

        <?php if (isset($error_msg)) : ?>
            Swal.fire({ title: 'ข้อผิดพลาด!', text: '<?php echo $error_msg; ?>', icon: 'error', confirmButtonColor: '#dc3545', confirmButtonText: 'ตกลง' });
        <?php endif; ?>
    </script>
</body>
</html>