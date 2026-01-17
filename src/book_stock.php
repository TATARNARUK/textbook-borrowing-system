<?php
session_start();
require_once 'config.php';

// 1. เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); exit();
}

// 2. รับ ID Master
if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$master_id = $_GET['id'];

// 3. ดึงข้อมูล Master
$stmtMaster = $pdo->prepare("SELECT * FROM book_masters WHERE id = ?");
$stmtMaster->execute([$master_id]);
$bookMaster = $stmtMaster->fetch();

if (!$bookMaster) {
    echo "<script>alert('ไม่พบข้อมูลหนังสือ'); window.location='index.php';</script>";
    exit();
}

// --- 4. บันทึกเพิ่มเล่ม (Logic ใหม่: ISBN + 0001) ---
if (isset($_POST['add_stock'])) {
    $amount = (int)$_POST['amount'];
    $isbn = trim($bookMaster['isbn']); // ตัดช่องว่างเผื่อมี

    // ถ้าไม่มี ISBN ให้ใช้ ID แทน (กันระบบพัง)
    if (empty($isbn)) {
        $isbn = "BK" . str_pad($master_id, 4, '0', STR_PAD_LEFT);
    }

    // หาเลขรันล่าสุดของหนังสือเล่มนี้ (เฉพาะที่ขึ้นต้นด้วย ISBN นี้)
    // เรียงความยาวก่อน แล้วค่อยเรียงตัวเลข เพื่อให้ 100 มาหลัง 99
    $stmtSeq = $pdo->prepare("SELECT book_code FROM book_items WHERE book_master_id = ? AND book_code LIKE ? ORDER BY length(book_code) DESC, book_code DESC LIMIT 1");
    $stmtSeq->execute([$master_id, $isbn . '%']);
    $lastItem = $stmtSeq->fetch();

    $nextNum = 1; // ค่าเริ่มต้น
    
    if ($lastItem) {
        $lastCode = $lastItem['book_code'];
        // ตัด ISBN ออกเหลือแต่เลขท้าย
        // เช่น รหัสเต็ม 9781230005 -> ตัด 978123 ออก -> เหลือ 0005
        $numberPart = substr($lastCode, strlen($isbn));
        
        // เช็คว่าเป็นตัวเลขหรือไม่ เพื่อความชัวร์แล้วบวก 1
        if (is_numeric($numberPart)) {
            $nextNum = (int)$numberPart + 1;
        }
    }

    // วนลูปบันทึกตามจำนวนที่ระบุ
    for ($i = 0; $i < $amount; $i++) {
        // แปลงเลขให้เป็น 4 หลัก (เช่น 1 -> 0001, 15 -> 0015)
        // ถ้าเลขเกิน 9999 ก็จะกลายเป็น 10000 ตามธรรมชาติ
        $runningCode = str_pad($nextNum, 4, '0', STR_PAD_LEFT); 
        $barcode = $isbn . $runningCode;
        
        $sql = "INSERT INTO book_items (book_master_id, book_code, status) VALUES (?, ?, 'available')";
        $pdo->prepare($sql)->execute([$master_id, $barcode]);
        
        $nextNum++; // เตรียมเลขสำหรับรอบถัดไป
    }
    
    $success_msg = "เพิ่มหนังสือจำนวน $amount เล่ม เรียบร้อยแล้ว!";
}

// --- 5. ลบเล่ม (แบบ Force Delete: ลบประวัติทิ้งด้วย) ---
if (isset($_GET['delete_item'])) {
    $del_id = $_GET['delete_item'];
    
    try {
        // 1. เช็คสถานะปัจจุบันก่อน
        $chk = $pdo->prepare("SELECT status, book_code FROM book_items WHERE id = ?");
        $chk->execute([$del_id]);
        $itemData = $chk->fetch();

        if ($itemData['status'] == 'borrowed') {
            // ถ้ายืมอยู่ ห้ามลบเด็ดขาด (เพราะของไม่อยู่ที่ห้องสมุด)
            $error_msg = "ลบไม่ได้! หนังสือรหัส " . $itemData['book_code'] . " กำลังถูกยืมอยู่ (ต้องคืนก่อน)";
        } else {
            // เริ่ม Transaction (เพื่อให้การลบ 2 ตารางเกิดขึ้นพร้อมกัน)
            $pdo->beginTransaction();

            // 2. 🔥 ลบประวัติการยืม (transactions) ของเล่มนี้ทิ้งก่อน!
            $pdo->prepare("DELETE FROM transactions WHERE book_item_id = ?")->execute([$del_id]);

            // 3. ✅ ลบเล่มหนังสือ (book_items)
            $pdo->prepare("DELETE FROM book_items WHERE id = ?")->execute([$del_id]);

            $pdo->commit(); // ยืนยันการลบ

            header("Location: book_stock.php?id=" . $master_id . "&msg=deleted");
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack(); // ถ้าผิดพลาด ให้ยกเลิกการลบทั้งหมด
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสต็อก - <?php echo $bookMaster['title']; ?></title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* --- 🎨 White & Blue Theme CSS --- */
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #f0f4f8;
            background-image: radial-gradient(#dbeafe 1px, transparent 1px);
            background-size: 20px 20px;
            color: #333;
            overflow-x: hidden;
        }

        #particles-js {
            position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: -1; pointer-events: none;
        }

        /* Card */
        .glass-card {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(13, 110, 253, 0.1);
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        /* Table */
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .table-custom thead th {
            background-color: #e7f1ff; color: #0d6efd;
            font-size: 0.85rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; border: none; padding: 15px;
        }
        .table-custom thead th:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-custom thead th:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .table-custom tbody tr {
            background-color: #fff; transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .table-custom tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.1);
            background-color: #f8f9fa;
        }
        .table-custom td {
            border: 1px solid #f0f0f0; border-width: 1px 0; padding: 15px; vertical-align: middle; color: #555;
        }
        .table-custom td:first-child { border-left: 1px solid #f0f0f0; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-custom td:last-child { border-right: 1px solid #f0f0f0; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        /* Buttons */
        .btn-custom-primary {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            color: #fff; border: none; font-weight: 600;
            border-radius: 10px; padding: 8px 20px; transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(13, 110, 253, 0.3); color: #fff;
        }
        
        .btn-outline-custom {
            background: transparent; color: #0d6efd; border: 1px solid #0d6efd;
            border-radius: 10px; font-weight: 600; padding: 8px 20px; transition: all 0.3s;
        }
        .btn-outline-custom:hover { background: #0d6efd; color: #fff; }

        .btn-del {
            color: #dc3545; background: #fff; border: 1px solid #f5c2c7;
            border-radius: 8px; padding: 6px 12px; transition: all 0.3s;
        }
        .btn-del:hover { background: #dc3545; color: #fff; border-color: #dc3545; }

        /* Status Pills */
        .status-pill { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
        .st-ok { background: #d1e7dd; color: #0f5132; }
        .st-borrow { background: #fff3cd; color: #856404; }

        .book-thumb-lg {
            width: 100%; max-width: 140px; border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 1px solid #eee;
        }
        
        .stats-box {
            background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 15px; padding: 20px; text-align: center;
        }
    </style>
</head>

<body>

    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>
    
    <div class="container py-5">
        
        <div class="d-flex justify-content-between align-items-center mb-5" data-aos="fade-down">
            <div class="d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 me-3">
                    <i class="fa-solid fa-boxes-stacked fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0">จัดการสต็อก</h3>
                    <small class="text-secondary">บริหารจัดการจำนวนเล่มหนังสือ</small>
                </div>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-custom">
                    <i class="fa-solid fa-arrow-left me-2"></i> กลับหน้าหลัก
                </a>
            </div>
        </div>

        <div class="glass-card" data-aos="fade-up">
            <div class="row align-items-center">
                <div class="col-md-2 text-center text-md-start mb-4 mb-md-0">
                    <?php if($bookMaster['cover_image']): ?>
                        <img src="uploads/<?php echo $bookMaster['cover_image']; ?>" class="book-thumb-lg">
                    <?php else: ?>
                        <div class="book-thumb-lg d-flex align-items-center justify-content-center bg-light text-muted" style="height: 180px;">
                            <i class="fa-regular fa-image fs-1"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-7 mb-4 mb-md-0">
                    <span class="badge bg-primary bg-opacity-10 text-primary mb-2">MASTER ID: <?php echo $bookMaster['id']; ?></span>
                    <h3 class="fw-bold text-dark mb-2"><?php echo $bookMaster['title']; ?></h3>
                    <div class="d-flex flex-wrap gap-3 text-secondary small mb-4">
                        <span><i class="fa-solid fa-barcode me-1"></i> ISBN: <?php echo $bookMaster['isbn']; ?></span>
                        <span><i class="fa-regular fa-user me-1"></i> <?php echo $bookMaster['author']; ?></span>
                    </div>
                    
                    <div class="bg-light p-4 rounded-4 border">
                        <label class="text-dark fw-bold mb-2 small text-uppercase">
                            <i class="fa-solid fa-plus-circle me-1 text-primary"></i> เพิ่มจำนวนหนังสือ (Add Stock)
                        </label>
                        <form method="post" class="d-flex gap-2">
                            <input type="number" name="amount" class="form-control text-center fw-bold border-primary" style="max-width: 100px;" value="1" min="1" max="50">
                            <button type="submit" name="add_stock" class="btn btn-custom-primary">
                                ยืนยันการเพิ่ม
                            </button>
                        </form>
                        <div class="small text-muted mt-2">
                            * ระบบจะสร้างรหัสบาร์โค้ด: <strong><?php echo $bookMaster['isbn']; ?>xxxx</strong>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <?php 
                        $stmtCount = $pdo->prepare("SELECT count(*) FROM book_items WHERE book_master_id = ?");
                        $stmtCount->execute([$master_id]);
                        $totalStock = $stmtCount->fetchColumn();
                    ?>
                    <div class="stats-box h-100 d-flex flex-column justify-content-center">
                        <div class="text-secondary small text-uppercase fw-bold mb-1">Total Items</div>
                        <div class="display-3 fw-bold text-primary mb-0"><?php echo $totalStock; ?></div>
                        <div class="text-success small fw-bold">เล่มในระบบ</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass-card" data-aos="fade-up" data-aos-delay="100">
            <h5 class="fw-bold text-dark mb-4 border-bottom pb-3">
                <i class="fa-solid fa-list-ul me-2 text-primary"></i>รายการเล่มหนังสือ (INVENTORY LIST)
            </h5>
            
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th width="10%">#</th>
                            <th width="40%">BOOK CODE (BARCODE)</th>
                            <th width="30%">STATUS</th>
                            <th width="20%" class="text-end">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // เรียงลำดับตาม Book Code เพื่อให้เห็นเล่มล่าสุดอยู่ล่าง
                        $stmtItems = $pdo->prepare("SELECT * FROM book_items WHERE book_master_id = ? ORDER BY book_code ASC");
                        $stmtItems->execute([$master_id]);
                        $count = 1;
                        if ($stmtItems->rowCount() == 0) {
                            echo '<tr><td colspan="4" class="text-center py-5 text-muted">ยังไม่มีเล่มหนังสือในสต็อก</td></tr>';
                        }
                        while ($item = $stmtItems->fetch()) {
                        ?>
                        <tr>
                            <td><span class="text-secondary fw-bold"><?php echo $count++; ?></span></td>
                            <td>
                                <span class="font-monospace text-primary fw-bold fs-5" style="letter-spacing: 1px;">
                                    <?php echo $item['book_code']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($item['status'] == 'available'): ?>
                                    <span class="status-pill st-ok"><i class="fa-solid fa-check me-1"></i> ว่าง (Available)</span>
                                <?php elseif($item['status'] == 'borrowed'): ?>
                                    <span class="status-pill st-borrow"><i class="fa-solid fa-hand-holding me-1"></i> ถูกยืม (Borrowed)</span>
                                <?php else: ?>
                                    <span class="status-pill bg-secondary text-white"><?php echo $item['status']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="book_stock.php?id=<?php echo $master_id; ?>&delete_item=<?php echo $item['id']; ?>" 
                                   class="btn btn-del btn-sm" 
                                   onclick="return confirm('⚠️ ยืนยันการลบเล่มรหัส <?php echo $item['book_code']; ?>?');">
                                    <i class="fa-solid fa-trash-can"></i> ลบ
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        AOS.init({ duration: 800, once: true });

        /* Particles Config */
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 160, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#0d6efd" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.5, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#0d6efd", "opacity": 0.2, "width": 1 },
                "move": { "enable": true, "speed": 2 }
            },
            "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": true, "mode": "grab" } } },
            "retina_detect": true
        });

        <?php if (isset($success_msg)) : ?>
            Swal.fire({
                title: 'สำเร็จ!',
                text: '<?php echo $success_msg; ?>',
                icon: 'success',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'ตกลง'
            });
        <?php endif; ?>

        <?php if (isset($error_msg)) : ?>
            Swal.fire({
                title: 'ข้อผิดพลาด!',
                text: '<?php echo $error_msg; ?>',
                icon: 'error',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'ตกลง'
            });
        <?php endif; ?>
    </script>
</body>
</html>