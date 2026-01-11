<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); exit();
}

// รับ ID Master
if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$master_id = $_GET['id'];

// ดึงข้อมูล Master
$stmtMaster = $pdo->prepare("SELECT * FROM book_masters WHERE id = ?");
$stmtMaster->execute([$master_id]);
$bookMaster = $stmtMaster->fetch();

// --- บันทึกเพิ่มเล่ม ---
if (isset($_POST['add_stock'])) {
    $amount = (int)$_POST['amount'];
    for ($i = 0; $i < $amount; $i++) {
        $barcode = date('ymd') . rand(1000, 9999);
        $sql = "INSERT INTO book_items (book_master_id, book_code, status) VALUES (?, ?, 'available')";
        $pdo->prepare($sql)->execute([$master_id, $barcode]);
    }
    $success_msg = "เพิ่มหนังสือจำนวน $amount เล่ม เรียบร้อยแล้ว!";
}

// --- ลบเล่ม ---
if (isset($_GET['delete_item'])) {
    $del_id = $_GET['delete_item'];
    $pdo->prepare("DELETE FROM book_items WHERE id = ?")->execute([$del_id]);
    header("Location: book_stock.php?id=" . $master_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการสต็อก - <?php echo $bookMaster['title']; ?></title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* --- Dark Theme Base --- */
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #000000;
            color: #e0e0e0;
            overflow-x: hidden;
        }

        #particles-js {
            position: fixed; width: 100%; height: 100%;
            top: 0; left: 0; z-index: -1; pointer-events: none;
        }

        /* --- Glass Card --- */
        .glass-card {
            background: rgba(20, 20, 20, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8);
            padding: 30px;
            margin-bottom: 30px;
        }

        /* --- Input Dark --- */
        .form-control-dark {
            background-color: #111; border: 1px solid #333; color: #fff;
            border-radius: 4px; padding: 10px;
        }
        .form-control-dark:focus {
            background-color: #000; border-color: #fff; color: #fff; box-shadow: none;
        }

        /* --- Table --- */
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .table-custom thead th {
            color: #777; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;
        }
        .table-custom tbody tr { background-color: rgba(255, 255, 255, 0.03); transition: all 0.2s; }
        .table-custom tbody tr:hover { background-color: rgba(255, 255, 255, 0.08); transform: scale(1.005); }
        .table-custom td { border: none; padding: 15px; vertical-align: middle; color: #ccc; }
        .table-custom td:first-child { border-top-left-radius: 6px; border-bottom-left-radius: 6px; }
        .table-custom td:last-child { border-top-right-radius: 6px; border-bottom-right-radius: 6px; }

        /* --- Status Pills --- */
        .status-pill { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px; }
        .st-ok { background: rgba(25, 135, 84, 0.15); color: #2ecc71; border: 1px solid rgba(25, 135, 84, 0.3); }
        .st-borrow { background: rgba(255, 193, 7, 0.15); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }
        .st-lost { background: rgba(220, 53, 69, 0.15); color: #ff6b6b; border: 1px solid rgba(220, 53, 69, 0.3); }

        /* --- Buttons --- */
        .btn-monochrome {
            background: #fff; color: #000; border: 1px solid #fff; font-weight: 600; padding: 8px 20px; transition: all 0.3s;
        }
        .btn-monochrome:hover { background: #000; color: #fff; }
        
        .btn-del { color: #555; transition: all 0.3s; }
        .btn-del:hover { color: #ff4d4d; }

        .book-thumb-lg {
            width: 100%; max-width: 150px; border-radius: 4px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1);
        }

        /* --- Page Header Style (ตามรูปตัวอย่าง) --- */
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 40px; margin-top: 20px;
        }
        .page-header-title {
            font-size: 1.8rem; font-weight: 300; color: #fff; letter-spacing: 1px;
        }
        .page-header-icon {
            font-size: 2rem; color: #6c757d; margin-right: 15px;
        }
        .btn-header-back {
            background-color: transparent;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            padding: 8px 20px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .btn-header-back:hover {
            border-color: #fff;
            background-color: rgba(255,255,255,0.1);
        }
    </style>
</head>

<body>

    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>
    
    <div class="container py-5">
        
        <div class="page-header" data-aos="fade-down">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-boxes-stacked page-header-icon"></i>
                <div>
                    <h3 class="page-header-title mb-0">จัดการสต็อก</h3>
                    <small class="text-secondary">ระบบบริหารจัดการจำนวนเล่มหนังสือ</small>
                </div>
            </div>
            <div>
                <a href="index.php" class="btn btn-header-back">
                    <i class="fa-solid fa-arrow-left me-2"></i> กลับหน้าหลัก
                </a>
            </div>
        </div>

        <div class="glass-card" data-aos="fade-down">
            <div class="row align-items-center">
                <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                    <?php if($bookMaster['cover_image']): ?>
                        <img src="uploads/<?php echo $bookMaster['cover_image']; ?>" class="book-thumb-lg">
                    <?php else: ?>
                        <div class="book-thumb-lg d-flex align-items-center justify-content-center bg-secondary" style="height: 200px;">No Cover</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3 mb-md-0">
                    <h3 class="fw-bold text-white mb-2"><?php echo $bookMaster['title']; ?></h3>
                    <div class="d-flex flex-wrap gap-3 text-secondary small mb-3">
                        <span><i class="fa-solid fa-barcode me-1"></i> ISBN: <?php echo $bookMaster['isbn']; ?></span>
                        <span><i class="fa-regular fa-user me-1"></i> <?php echo $bookMaster['author']; ?></span>
                    </div>
                    <div class="p-3 border border-secondary border-opacity-25 rounded bg-dark bg-opacity-25">
                        <small class="text-secondary d-block mb-2">เพิ่มจำนวนหนังสือ (Add Stock)</small>
                        <form method="post" class="d-flex gap-2">
                            <input type="number" name="amount" class="form-control form-control-dark" style="max-width: 100px;" value="1" min="1" max="50">
                            <button type="submit" name="add_stock" class="btn btn-monochrome">
                                <i class="fa-solid fa-plus me-1"></i> ยืนยันการเพิ่ม
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <?php 
                        $stmtCount = $pdo->prepare("SELECT count(*) FROM book_items WHERE book_master_id = ?");
                        $stmtCount->execute([$master_id]);
                        $totalStock = $stmtCount->fetchColumn();
                    ?>
                    <div class="d-inline-block text-start p-3">
                        <div class="text-secondary small text-uppercase" style="letter-spacing: 2px;">Total Items</div>
                        <div class="display-4 fw-bold text-white"><?php echo $totalStock; ?></div>
                        <div class="text-success small">เล่มในระบบ</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass-card" data-aos="fade-up">
            <h5 class="fw-light text-white mb-4 border-bottom border-secondary border-opacity-25 pb-3">
                <i class="fa-solid fa-list-ul me-2"></i>รายการเล่มหนังสือ (INVENTORY LIST)
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
                        $stmtItems = $pdo->prepare("SELECT * FROM book_items WHERE book_master_id = ? ORDER BY id DESC");
                        $stmtItems->execute([$master_id]);
                        $count = 1;
                        if ($stmtItems->rowCount() == 0) {
                            echo '<tr><td colspan="4" class="text-center py-4 text-muted">ยังไม่มีเล่มหนังสือในสต็อก</td></tr>';
                        }
                        while ($item = $stmtItems->fetch()) {
                        ?>
                        <tr>
                            <td><span class="text-secondary"><?php echo $count++; ?></span></td>
                            <td>
                                <span class="font-monospace text-white fs-5" style="letter-spacing: 1px;">
                                    <?php echo $item['book_code']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($item['status'] == 'available'): ?>
                                    <span class="status-pill st-ok">ว่าง (Available)</span>
                                <?php elseif($item['status'] == 'borrowed'): ?>
                                    <span class="status-pill st-borrow">ถูกยืม (Borrowed)</span>
                                <?php else: ?>
                                    <span class="status-pill st-lost"><?php echo $item['status']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if($item['status'] == 'available'): ?>
                                    <a href="book_stock.php?id=<?php echo $master_id; ?>&delete_item=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-del" 
                                       onclick="return confirm('⚠️ ยืนยันการลบเล่มรหัส <?php echo $item['book_code']; ?>?');">
                                        <i class="fa-solid fa-trash-can fa-lg"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary opacity-25" title="ไม่สามารถลบได้"><i class="fa-solid fa-ban"></i></span>
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
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        AOS.init({ duration: 800, once: true });

        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 60 },
                "color": { "value": "#ffffff" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.2, "random": true },
                "size": { "value": 2, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#ffffff", "opacity": 0.1, "width": 1 },
                "move": { "enable": true, "speed": 0.5 }
            },
            "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": false } } }
        });

        <?php if (isset($success_msg)) : ?>
            Swal.fire({
                title: 'สำเร็จ!',
                text: '<?php echo $success_msg; ?>',
                icon: 'success',
                background: '#000',
                color: '#fff',
                confirmButtonColor: '#fff',
                confirmButtonText: '<span style="color:#000; font-weight:bold;">ตกลง</span>'
            });
        <?php endif; ?>
    </script>
</body>
</html>