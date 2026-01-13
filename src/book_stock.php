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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* --- 🎨 White & Blue Theme CSS --- */
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #f0f4f8; /* พื้นหลังสีเทาอมฟ้าอ่อน */
            background-image: radial-gradient(#dbeafe 1px, transparent 1px); /* ลายจุดจางๆ */
            background-size: 20px 20px;
            color: #333;
            overflow-x: hidden;
        }

        #particles-js {
            position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: -1; pointer-events: none;
        }

        /* --- White Card --- */
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

        /* --- Input Style --- */
        .form-control {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #333;
            border-radius: 8px;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }

        /* --- Table Style --- */
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .table-custom thead th {
            background-color: #e7f1ff;
            color: #0d6efd;
            font-size: 0.85rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; border: none; padding: 15px;
        }
        .table-custom thead th:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-custom thead th:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .table-custom tbody tr {
            background-color: #fff;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .table-custom tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.1);
        }
        .table-custom td {
            border: 1px solid #f0f0f0; border-width: 1px 0;
            padding: 15px; vertical-align: middle; color: #555;
        }
        .table-custom td:first-child { border-left: 1px solid #f0f0f0; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-custom td:last-child { border-right: 1px solid #f0f0f0; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        /* --- Status Pills --- */
        .status-pill { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px; }
        .st-ok { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .st-borrow { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .st-lost { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        /* --- Buttons --- */
        .btn-custom-primary {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            color: #fff; border: none; font-weight: 600;
            border-radius: 8px; padding: 8px 20px;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(13, 110, 253, 0.3);
            color: #fff;
        }
        
        .btn-outline-custom {
            background: transparent; color: #0d6efd; border: 1px solid #0d6efd;
            border-radius: 8px; font-weight: 600; padding: 8px 20px;
            transition: all 0.3s;
        }
        .btn-outline-custom:hover { background: #0d6efd; color: #fff; }

        .btn-del { color: #dc3545; background: #fff5f5; border: 1px solid #f5c2c7; border-radius: 6px; padding: 5px 10px; transition: all 0.3s; }
        .btn-del:hover { background: #dc3545; color: #fff; }

        .book-thumb-lg {
            width: 100%; max-width: 150px; border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1); border: 1px solid #eee;
        }

        /* --- Header --- */
        .page-header-icon {
            font-size: 2.5rem; color: #0d6efd; margin-right: 15px;
            background: #e7f1ff; padding: 15px; border-radius: 15px;
        }
    </style>
</head>

<body>

    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>
    
    <div class="container py-5">
        
        <div class="d-flex justify-content-between align-items-center mb-5" data-aos="fade-down">
            <div class="d-flex align-items-center">
                <div class="page-header-icon shadow-sm">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0">จัดการสต็อก</h3>
                    <small class="text-secondary">ระบบบริหารจัดการจำนวนเล่มหนังสือ</small>
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
                <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                    <?php if($bookMaster['cover_image']): ?>
                        <img src="uploads/<?php echo $bookMaster['cover_image']; ?>" class="book-thumb-lg">
                    <?php else: ?>
                        <div class="book-thumb-lg d-flex align-items-center justify-content-center bg-light text-muted" style="height: 200px;">No Cover</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3 mb-md-0">
                    <h3 class="fw-bold text-primary mb-2"><?php echo $bookMaster['title']; ?></h3>
                    <div class="d-flex flex-wrap gap-3 text-secondary small mb-3">
                        <span class="badge bg-light text-dark border"><i class="fa-solid fa-barcode me-1"></i> ISBN: <?php echo $bookMaster['isbn']; ?></span>
                        <span class="badge bg-light text-dark border"><i class="fa-regular fa-user me-1"></i> <?php echo $bookMaster['author']; ?></span>
                    </div>
                    
                    <div class="p-4 border rounded-4 bg-light">
                        <label class="text-dark fw-bold mb-2 small text-uppercase">เพิ่มจำนวนหนังสือ (Add Stock)</label>
                        <form method="post" class="d-flex gap-2">
                            <input type="number" name="amount" class="form-control text-center fw-bold" style="max-width: 100px;" value="1" min="1" max="50">
                            <button type="submit" name="add_stock" class="btn btn-custom-primary">
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
                    <div class="d-inline-block text-center p-4 bg-white rounded-4 border shadow-sm">
                        <div class="text-secondary small text-uppercase fw-bold mb-1">Total Items</div>
                        <div class="display-4 fw-bold text-primary"><?php echo $totalStock; ?></div>
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
                        $stmtItems = $pdo->prepare("SELECT * FROM book_items WHERE book_master_id = ? ORDER BY id DESC");
                        $stmtItems->execute([$master_id]);
                        $count = 1;
                        if ($stmtItems->rowCount() == 0) {
                            echo '<tr><td colspan="4" class="text-center py-4 text-muted">ยังไม่มีเล่มหนังสือในสต็อก</td></tr>';
                        }
                        while ($item = $stmtItems->fetch()) {
                        ?>
                        <tr>
                            <td><span class="text-secondary fw-bold"><?php echo $count++; ?></span></td>
                            <td>
                                <span class="font-monospace text-dark fs-5 fw-bold" style="letter-spacing: 1px;">
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
                                        <i class="fa-solid fa-trash-can"></i> ลบ
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary opacity-50" title="ไม่สามารถลบได้"><i class="fa-solid fa-ban"></i></span>
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

        /* Particles สีฟ้า */
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#0d6efd" }, /* สีฟ้า */
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
    </script>
</body>
</html>