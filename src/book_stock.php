<?php
session_start();
require_once 'config.php';

// 1. เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// 2. รับ ID Master
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$master_id = $_GET['id'];

// 3. ดึงข้อมูล Master
$stmtMaster = $pdo->prepare("SELECT * FROM book_masters WHERE id = ?");
$stmtMaster->execute([$master_id]);
$bookMaster = $stmtMaster->fetch();

if (!$bookMaster) {
    echo "<script>alert('ไม่พบข้อมูลหนังสือ'); window.location='index.php';</script>";
    exit();
}

// ==========================================
// 🌍 ZONE: Global Actions (จัดการทุกเล่มในระบบ)
// ==========================================

// --- 4. 🔥 เพิ่มสต็อกให้หนังสือ "ทุกเล่ม" (Global Add) ---
if (isset($_POST['global_add_amount'])) {
    $g_amount = (int)$_POST['global_add_amount'];
    
    if ($g_amount > 0) {
        try {
            $pdo->beginTransaction();
            
            // ดึง ID และ ISBN ของหนังสือทุกเล่มออกมา
            $allBooks = $pdo->query("SELECT id, isbn FROM book_masters")->fetchAll();
            $total_added_books = 0;

            foreach ($allBooks as $b) {
                $m_id = $b['id'];
                $m_isbn = trim($b['isbn']);
                
                if (empty($m_isbn) || $m_isbn == '-') {
                    $m_isbn = "BK" . str_pad($m_id, 4, '0', STR_PAD_LEFT);
                }

                // หาเลขรันล่าสุดของเล่มนั้นๆ
                $stmtSeq = $pdo->prepare("SELECT book_code FROM book_items WHERE book_master_id = ? AND book_code LIKE ? ORDER BY length(book_code) DESC, book_code DESC LIMIT 1");
                $stmtSeq->execute([$m_id, $m_isbn . '%']);
                $lastItem = $stmtSeq->fetch();

                $nextNum = 1;
                if ($lastItem) {
                    $numberPart = substr($lastItem['book_code'], strlen($m_isbn));
                    if (is_numeric($numberPart)) {
                        $nextNum = (int)$numberPart + 1;
                    }
                }

                // Loop Insert ตามจำนวนที่ระบุ
                for ($i = 0; $i < $g_amount; $i++) {
                    $runningCode = str_pad($nextNum, 4, '0', STR_PAD_LEFT);
                    $barcode = $m_isbn . $runningCode;

                    $ins = $pdo->prepare("INSERT INTO book_items (book_master_id, book_code, status) VALUES (?, ?, 'available')");
                    $ins->execute([$m_id, $barcode]);
                    
                    $nextNum++;
                    $total_added_books++;
                }
            }

            $pdo->commit();
            $success_msg = "เพิ่มสต็อกให้หนังสือทุกเล่มในระบบ (เล่มละ $g_amount ชิ้น) รวม $total_added_books เล่ม เรียบร้อยแล้ว!";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

// --- 5. 🔥 ล้างสต็อก "ทุกเล่ม" (Global Clear) ---
if (isset($_GET['global_clear'])) {
    try {
        // เช็คว่ามีเล่มไหนถูกยืมอยู่ไหม (ทั้งระบบ)
        $stmtCheck = $pdo->query("SELECT COUNT(*) FROM book_items WHERE status = 'borrowed'");
        $borrowedCount = $stmtCheck->fetchColumn();

        if ($borrowedCount > 0) {
            $error_msg = "ไม่สามารถล้างสต็อกทั้งระบบได้! มีหนังสือถูกยืมอยู่ $borrowedCount เล่ม (ต้องคืนให้ครบก่อน)";
        } else {
            $pdo->beginTransaction();
            
            // 1. ลบ Transactions ทั้งหมด (เฉพาะที่เกี่ยวกับ book_items)
            $pdo->exec("DELETE FROM transactions WHERE book_item_id IS NOT NULL");
            
            // 2. ลบ Book Items ทั้งหมด (ใช้ DELETE แทน TRUNCATE เพื่อแก้ปัญหา Foreign Key Error)
            $pdo->exec("DELETE FROM book_items"); 
            
            // (Optional) รีเซ็ต Auto Increment ถ้าต้องการ (อาจจะไม่ทำงานในบาง Hosting แต่ลองใส่ไว้ได้)
            // $pdo->exec("ALTER TABLE book_items AUTO_INCREMENT = 1");

            $pdo->commit();
            $success_msg = "ล้างสต็อกหนังสือทั้งระบบเรียบร้อยแล้ว!";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ==========================================
// 📦 ZONE: Local Actions (จัดการเฉพาะเล่มปัจจุบัน)
// ==========================================

// --- 6. บันทึกเพิ่มเล่ม (เฉพาะเล่มนี้) ---
if (isset($_POST['add_stock'])) {
    $amount = (int)$_POST['amount'];
    $isbn = trim($bookMaster['isbn']);

    if (empty($isbn)) {
        $isbn = "BK" . str_pad($master_id, 4, '0', STR_PAD_LEFT);
    }

    $stmtSeq = $pdo->prepare("SELECT book_code FROM book_items WHERE book_master_id = ? AND book_code LIKE ? ORDER BY length(book_code) DESC, book_code DESC LIMIT 1");
    $stmtSeq->execute([$master_id, $isbn . '%']);
    $lastItem = $stmtSeq->fetch();

    $nextNum = 1;
    if ($lastItem) {
        $numberPart = substr($lastItem['book_code'], strlen($isbn));
        if (is_numeric($numberPart)) {
            $nextNum = (int)$numberPart + 1;
        }
    }

    for ($i = 0; $i < $amount; $i++) {
        $runningCode = str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        $barcode = $isbn . $runningCode;

        $sql = "INSERT INTO book_items (book_master_id, book_code, status) VALUES (?, ?, 'available')";
        $pdo->prepare($sql)->execute([$master_id, $barcode]);

        $nextNum++;
    }

    $success_msg = "เพิ่มหนังสือจำนวน $amount เล่ม เรียบร้อยแล้ว!";
}

// --- 7. ลบเล่มทีละเล่ม ---
if (isset($_GET['delete_item'])) {
    $del_id = $_GET['delete_item'];

    try {
        $chk = $pdo->prepare("SELECT status, book_code FROM book_items WHERE id = ?");
        $chk->execute([$del_id]);
        $itemData = $chk->fetch();

        if ($itemData['status'] == 'borrowed') {
            $error_msg = "ลบไม่ได้! หนังสือรหัส " . $itemData['book_code'] . " กำลังถูกยืมอยู่";
        } else {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM transactions WHERE book_item_id = ?")->execute([$del_id]);
            $pdo->prepare("DELETE FROM book_items WHERE id = ?")->execute([$del_id]);
            $pdo->commit();
            header("Location: book_stock.php?id=" . $master_id . "&msg=deleted");
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// --- 8. ลบเล่มทั้งหมดของเล่มนี้ (Clear All Local) ---
if (isset($_GET['clear_all'])) {
    try {
        $stmtCheckBorrow = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ? AND status = 'borrowed'");
        $stmtCheckBorrow->execute([$master_id]);
        $borrowedCount = $stmtCheckBorrow->fetchColumn();

        if ($borrowedCount > 0) {
            $error_msg = "ไม่สามารถล้างสต็อกได้! มีหนังสือถูกยืมอยู่ $borrowedCount เล่ม";
        } else {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM transactions WHERE book_item_id IN (SELECT id FROM book_items WHERE book_master_id = ?)")->execute([$master_id]);
            $pdo->prepare("DELETE FROM book_items WHERE book_master_id = ?")->execute([$master_id]);
            $pdo->commit();
            $success_msg = "ล้างสต็อกหนังสือเล่มนี้ทั้งหมดเรียบร้อยแล้ว!";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    
    <title>จัดการสต็อก - <?php echo $bookMaster['title']; ?></title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        body { font-family: 'Noto Sans Thai', sans-serif; background-color: #f0f4f8; overflow-x: hidden; }
        #particles-js { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: -1; pointer-events: none; }
        .glass-card { background: #ffffff; border: none; border-radius: 20px; box-shadow: 0 10px 40px rgba(13, 110, 253, 0.1); padding: 30px; margin-bottom: 30px; position: relative; z-index: 1; }
        
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .table-custom thead th { background-color: #e7f1ff; color: #0d6efd; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; padding: 15px; border: none; }
        .table-custom thead th:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-custom thead th:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
        .table-custom tbody tr { background-color: #fff; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02); }
        .table-custom tbody tr:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(13, 110, 253, 0.1); }
        .table-custom td { padding: 15px; vertical-align: middle; color: #555; border-top: 1px solid #f0f0f0; border-bottom: 1px solid #f0f0f0; }
        .table-custom td:first-child { border-left: 1px solid #f0f0f0; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-custom td:last-child { border-right: 1px solid #f0f0f0; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
        
        .btn-custom-primary { background: linear-gradient(45deg, #0d6efd, #0dcaf0); color: #fff; border: none; font-weight: 600; border-radius: 10px; padding: 8px 20px; }
        .btn-del { color: #dc3545; background: #fff; border: 1px solid #f5c2c7; border-radius: 8px; padding: 6px 12px; }
        .btn-del:hover { background: #dc3545; color: #fff; }
        
        /* ปุ่มล้างสต็อก (Local & Global) */
        .btn-clear-all { color: #fff; background: #dc3545; border: none; border-radius: 10px; padding: 8px 20px; font-weight: bold; transition: all 0.3s; }
        .btn-clear-all:hover { background: #bb2d3b; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); color: white;}
        
        /* ปุ่ม Add Global */
        .btn-add-global { background: linear-gradient(45deg, #198754, #20c997); color: #fff; border: none; border-radius: 10px; padding: 8px 20px; font-weight: 600; transition: 0.3s; box-shadow: 0 4px 10px rgba(25, 135, 84, 0.2); }
        .btn-add-global:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(25, 135, 84, 0.3); color: #fff; }

        .status-pill { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
        .st-ok { background: #d1e7dd; color: #0f5132; }
        .st-borrow { background: #fff3cd; color: #856404; }
        .book-thumb-lg { width: 100%; max-width: 140px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }
        .stats-box { background: #f8f9fa; border-radius: 15px; padding: 20px; text-align: center; }
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
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-add-global" onclick="openAddAllModal()">
                    <i class="fa-solid fa-layer-group me-2"></i> เพิ่มสต็อก (ทุกเล่ม)
                </button>
                
                <a href="book_stock.php?id=<?php echo $master_id; ?>&global_clear=1" 
                   class="btn btn-clear-all"
                   onclick="return confirm('⚠️⚠️ อันตราย! ⚠️⚠️\n\nคุณกำลังจะลบสต็อกหนังสือ \'ทุกเล่ม\' ในระบบ!!\nข้อมูลจะไม่สามารถกู้คืนได้\n\nยืนยันหรือไม่?');">
                    <i class="fa-solid fa-dumpster-fire me-2"></i> ล้างสต็อก (ทุกเล่ม)
                </a>
                
                <a href="index.php" class="btn btn-outline-secondary rounded-pill fw-bold border-2 px-3 pt-2">
                    <i class="fa-solid fa-arrow-left me-2"></i> กลับ
                </a>
            </div>
        </div>

        <div class="glass-card" data-aos="fade-up">
            <div class="row align-items-center">
                <div class="col-md-2 text-center text-md-start mb-4 mb-md-0">
                    <?php 
                        $cover = $bookMaster['cover_image'];
                        $cover = str_replace(' ', '%20', $cover);
                        $showImg = (strpos($cover, 'http') === 0) ? $cover : "uploads/" . $cover;
                    ?>
                    <img src="<?php echo $showImg; ?>" class="book-thumb-lg" onerror="this.src='https://via.placeholder.com/150?text=No+Image'">
                </div>

                <div class="col-md-7 mb-4 mb-md-0">
                    <span class="badge bg-primary bg-opacity-10 text-primary mb-2">MASTER ID: <?php echo $bookMaster['id']; ?></span>
                    <h3 class="fw-bold text-dark mb-2"><?php echo $bookMaster['title']; ?></h3>
                    <div class="d-flex flex-wrap gap-3 text-secondary small mb-4">
                        <span><i class="fa-solid fa-barcode me-1"></i> ISBN: <?php echo $bookMaster['isbn']; ?></span>
                        <span><i class="fa-regular fa-user me-1"></i> <?php echo $bookMaster['author']; ?></span>
                    </div>

                    <div class="bg-light p-3 rounded-4 border d-flex flex-wrap align-items-center gap-3">
                        <label class="text-dark fw-bold small text-uppercase m-0 text-nowrap">
                            <i class="fa-solid fa-plus-circle me-1 text-primary"></i> เพิ่มสต็อก (เล่มนี้):
                        </label>
                        <form method="post" class="d-flex gap-2 align-items-center">
                            <input type="number" name="amount" class="form-control text-center fw-bold border-primary shadow-sm" style="width: 80px;" value="1" min="1" max="50">
                            <button type="submit" name="add_stock" class="btn btn-custom-primary text-nowrap shadow-sm">
                                <i class="fa-solid fa-check me-1"></i> ยืนยัน
                            </button>
                        </form>
                        
                        <div class="ms-auto">
                            <a href="book_stock.php?id=<?php echo $master_id; ?>&clear_all=1" 
                               class="btn btn-outline-danger btn-sm text-nowrap shadow-sm border-danger"
                               onclick="return confirm('⚠️ คำเตือน! คุณต้องการลบสต็อกหนังสือเล่มนี้ทั้งหมดหรือไม่?\n\n(การกระทำนี้ไม่สามารถย้อนกลับได้)');">
                                <i class="fa-solid fa-trash-can me-1"></i> ล้างสต็อก (เล่มนี้)
                            </a>
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
                                    <?php if ($item['status'] == 'available'): ?>
                                        <span class="status-pill st-ok"><i class="fa-solid fa-check me-1"></i> ว่าง (Available)</span>
                                    <?php elseif ($item['status'] == 'borrowed'): ?>
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

    <form id="formGlobalAdd" method="POST" style="display:none;">
        <input type="hidden" name="global_add_amount" id="inputGlobalAmount">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        AOS.init({ duration: 800, once: true });

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
            "interactivity": {
                "detect_on": "canvas",
                "events": { "onhover": { "enable": true, "mode": "grab" } },
                "onclick": { "enable": true, "mode": "push" }
            },
            "retina_detect": true
        });

        // 🔥 ฟังก์ชันเปิด Modal สำหรับเพิ่มสต็อกทุกเล่ม (Global)
        function openAddAllModal() {
            Swal.fire({
                title: '⚡ เพิ่มสต็อกให้หนังสือทุกเล่ม',
                html: '<p class="text-muted">ระบบจะเพิ่มจำนวนสต็อกให้กับหนังสือ <b>ทุกเล่มในฐานข้อมูล</b><br>โปรดระบุจำนวนที่ต้องการเพิ่มต่อเล่ม</p>',
                input: 'number',
                inputAttributes: { min: 1, max: 100, step: 1 },
                inputValue: 1,
                showCancelButton: true,
                confirmButtonText: 'ยืนยันการเพิ่ม',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#198754',
                preConfirm: (amount) => {
                    if (!amount || amount <= 0) {
                        Swal.showValidationMessage('กรุณาระบุจำนวนที่ถูกต้อง')
                    }
                    return amount;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('inputGlobalAmount').value = result.value;
                    document.getElementById('formGlobalAdd').submit();
                }
            })
        }

        <?php if (isset($success_msg)) : ?>
            Swal.fire({ title: 'สำเร็จ!', text: '<?php echo $success_msg; ?>', icon: 'success', confirmButtonColor: '#0d6efd', confirmButtonText: 'ตกลง' });
        <?php endif; ?>

        <?php if (isset($error_msg)) : ?>
            Swal.fire({ title: 'ข้อผิดพลาด!', text: '<?php echo $error_msg; ?>', icon: 'error', confirmButtonColor: '#dc3545', confirmButtonText: 'ตกลง' });
        <?php endif; ?>
    </script>
</body>
</html>