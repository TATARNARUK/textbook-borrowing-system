<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); exit();
}

// กำหนดค่าเริ่มต้น
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Query
$sql = "SELECT t.*, u.fullname, u.student_id, b.title, bi.book_code 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        JOIN book_items bi ON t.book_item_id = bi.id
        JOIN book_masters b ON bi.book_master_id = b.id
        WHERE date(t.borrow_date) BETWEEN :start AND :end
        ORDER BY t.borrow_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['start' => $start_date, 'end' => $end_date]);
$transactions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานสรุปการยืม-คืน</title>
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

        /* White Card Panel */
        .glass-panel {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(13, 110, 253, 0.1);
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        /* Inputs (Light Theme) */
        .form-control {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #333;
            border-radius: 10px;
            padding: 10px;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }

        /* Buttons */
        .btn-custom-primary {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            color: #fff;
            border: none;
            font-weight: 600; border-radius: 10px; padding: 10px 20px;
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
            border-radius: 10px; font-weight: 600;
        }
        .btn-outline-custom:hover {
            background: #0d6efd; color: #fff;
        }

        /* Modern Table (Light Theme) */
        .table-custom {
            width: 100%; border-collapse: separate; border-spacing: 0 10px;
        }
        .table-custom thead th {
            background-color: #e7f1ff; /* หัวตารางสีฟ้าอ่อน */
            color: #0d6efd;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.5px; border: none; padding: 15px;
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

        /* Status Badges */
        .status-pill {
            padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600;
        }
        .st-borrow { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .st-return { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }


        /* --- 🖨️ PRINT MODE (Clean White) --- */
        @media print {
            body { background-color: #fff !important; color: #000 !important; }
            #particles-js, .no-print, .btn, header, nav { display: none !important; }
            
            .glass-panel {
                background: none !important; border: none !important;
                box-shadow: none !important; padding: 0 !important; margin: 0 !important;
            }

            .table-custom { border-collapse: collapse !important; border-spacing: 0 !important; }
            .table-custom th, .table-custom td {
                border: 1px solid #000 !important; color: #000 !important; padding: 8px !important;
            }
            .table-custom tbody tr { background: none !important; box-shadow: none !important; }
            
            /* Hide Badges Background for print clarity */
            .status-pill { border: none !important; color: #000 !important; padding: 0 !important; }
            
            /* Page Setup */
            @page { size: auto; margin: 10mm; }
            .container { max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body>

    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>

    <div class="container py-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4 no-print" data-aos="fade-down">
            <div>
                <h3 class="text-primary fw-bold mb-0" style="letter-spacing: 1px;">
                    <i class="fa-solid fa-file-invoice me-2"></i>รายงานสรุป
                </h3>
                <small class="text-secondary">ระบบรายงานข้อมูลการยืม-คืนหนังสือ</small>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-custom me-2">
                    <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
                </a>
                <button onclick="window.print()" class="btn btn-custom-primary">
                    <i class="fa-solid fa-print me-2"></i> พิมพ์รายงาน
                </button>
            </div>
        </div>

        <div class="glass-panel no-print" data-aos="fade-up">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="text-secondary fw-bold small mb-2">ตั้งแต่วันที่</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="text-secondary fw-bold small mb-2">ถึงวันที่</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-custom-primary w-100">
                        <i class="fa-solid fa-magnifying-glass me-2"></i> ค้นหาข้อมูล
                    </button>
                </div>
            </form>
        </div>

        <div class="glass-panel" data-aos="fade-up" data-aos-delay="100">
            
            <div class="d-none d-print-block text-center mb-4">
                <h2 class="fw-bold">รายงานสรุปการยืม-คืนหนังสือเรียน</h2>
                <p>แผนกเทคโนโลยีสารสนเทศ (IT Textbook System)</p>
                <p class="small">ข้อมูลระหว่างวันที่: <?php echo date('d/m/Y', strtotime($start_date)); ?> ถึง <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">#</th>
                            <th width="15%">วันที่ยืม</th>
                            <th width="15%">รหัสนักเรียน</th>
                            <th width="20%">ชื่อ-สกุล</th>
                            <th width="25%">ชื่อหนังสือ</th>
                            <th width="10%" class="text-center">สถานะ</th>
                            <th width="10%" class="text-center">วันที่คืน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php $i = 1; foreach ($transactions as $row): 
                                $is_borrowed = $row['status'] == 'borrowed';
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td>
                                    <span class="text-dark"><?php echo date('d/m/Y', strtotime($row['borrow_date'])); ?></span>
                                </td>
                                <td><span class="text-secondary font-monospace"><?php echo $row['student_id']; ?></span></td>
                                <td class="text-dark fw-bold"><?php echo $row['fullname']; ?></td>
                                <td>
                                    <div class="text-primary fw-bold"><?php echo $row['title']; ?></div>
                                    <small class="text-muted" style="font-size: 0.8rem;">รหัสเล่ม: <?php echo $row['book_code']; ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if($is_borrowed): ?>
                                        <span class="status-pill st-borrow">กำลังยืม</span>
                                    <?php else: ?>
                                        <span class="status-pill st-return">คืนแล้ว</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo ($row['return_date']) ? date('d/m/y', strtotime($row['return_date'])) : '-'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <div class="opacity-50 mb-3">
                                        <i class="fa-solid fa-folder-open fs-1"></i>
                                    </div>
                                    ไม่พบข้อมูลในช่วงเวลานี้
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-none d-print-block mt-5 pt-5">
                <div class="d-flex justify-content-between px-5">
                    <div class="text-center">
                        <p>ลงชื่อ ....................................................... ผู้จัดทำ</p>
                        <p>(.......................................................)</p>
                    </div>
                    <div class="text-center">
                        <p>ลงชื่อ ....................................................... ครูที่ปรึกษา</p>
                        <p>(.......................................................)</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
        
        // Particles สีฟ้า (Blue)
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
            "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": true, "mode": "grab" }, "resize": true },
            "modes": { "grab": { "distance": 140, "line_linked": { "opacity": 1 } } } },
            "retina_detect": true
        });
    </script>
</body>
</html>