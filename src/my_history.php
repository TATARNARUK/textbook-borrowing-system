<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// SQL: Admin เห็นหมด / User เห็นแค่ของตัวเอง
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

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการยืม-คืนหนังสือ</title>
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
            box-shadow: 0 10px 40px rgba(13, 110, 253, 0.1); /* เงาสีฟ้าจางๆ */
            position: relative;
            z-index: 1;
        }

        /* --- Table Styling (Light Theme) --- */
        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        
        .table-custom thead th {
            background-color: #e7f1ff; /* หัวตารางสีฟ้าอ่อน */
            color: #0d6efd; /* ตัวหนังสือสีฟ้า */
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            padding: 15px;
            font-weight: 700;
        }
        /* ทำมุมโค้งให้หัวตาราง */
        .table-custom thead th:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-custom thead th:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .table-custom tbody tr {
            background-color: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .table-custom tbody tr:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.1);
            background-color: #f8f9fa;
        }

        .table-custom td {
            border: 1px solid #f0f0f0;
            border-width: 1px 0;
            padding: 15px;
            vertical-align: middle;
            color: #555;
        }

        .table-custom tbody tr td:first-child { border-left: 1px solid #f0f0f0; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-custom tbody tr td:last-child { border-right: 1px solid #f0f0f0; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        /* --- Images --- */
        .book-thumb {
            width: 50px; height: 70px; object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* --- Status Badges --- */
        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .status-borrowed { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-returned { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .status-overdue  { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        .dot { width: 8px; height: 8px; border-radius: 50%; background-color: currentColor; }

        /* --- Buttons --- */
        .btn-outline-custom {
            color: #0d6efd; border: 1px solid #0d6efd;
            background: transparent;
            transition: all 0.3s;
        }
        .btn-outline-custom:hover { background: #0d6efd; color: #fff; }

        .btn-action {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            color: #fff;
            border: none; font-weight: 600;
            padding: 6px 15px; border-radius: 50px;
            font-size: 0.85rem;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(13, 110, 253, 0.3);
        }
    </style>
</head>

<body>
    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>
    
    <div class="container py-5">
        
        <div class="glass-card p-4 p-md-5" data-aos="fade-up" data-aos-duration="1000">
            
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 2px solid #f0f4f8;">
                <div>
                    <h3 class="fw-bold text-primary mb-0">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i>HISTORY
                    </h3>
                    <small class="text-muted">ประวัติการยืมและคืนหนังสือทั้งหมด</small>
                </div>
                <a href="index.php" class="btn btn-outline-custom btn-sm rounded-pill px-4 fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i> กลับหน้าหลัก
                </a>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th width="80">ปกหนังสือ</th>
                            <th>ชื่อหนังสือ</th>
                            <th>รหัสหนังสือ</th>
                            <?php if($role == 'admin') echo "<th>ชื่อผู้ยืม</th>"; ?>
                            <th>วันที่ยืม</th>
                            <th>วันที่คืน</th>
                            <th>สถานะ</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody> 
                        <?php 
                        if ($stmt->rowCount() == 0) {
                            echo '<tr><td colspan="8" class="text-center py-5 text-muted">ไม่พบประวัติการยืม-คืน</td></tr>';
                        }

                        while ($row = $stmt->fetch()) { 
                            $is_overdue = (strtotime($row['due_date']) < time()) && ($row['status'] == 'borrowed');
                        ?>
                        <tr>
                            <td>
                                <?php if($row['cover_image']): ?>
                                    <img src="uploads/<?php echo $row['cover_image']; ?>" class="book-thumb" alt="Cover">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/50x70/eee/999?text=No+Img" class="book-thumb" alt="No Image">
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo $row['title']; ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border fw-normal">
                                    <?php echo $row['book_code']; ?>
                                </span>
                            </td>
                            
                            <?php if($role == 'admin'): ?>
                                <td>
                                    <div class="d-flex align-items-center text-secondary">
                                        <div class="avatar bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center me-2" style="width:30px; height:30px; font-size: 0.8rem;">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                        <?php echo $row['fullname']; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                            
                            <td class="text-secondary"><?php echo date('d/m/Y', strtotime($row['borrow_date'])); ?></td>
                            
                            <td>
                                <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : 'text-secondary'; ?>">
                                    <?php echo date('d/m/Y', strtotime($row['due_date'])); ?>
                                </span>
                            </td>
                            
                            <td>
                                <?php if($row['status'] == 'borrowed'): ?>
                                    <?php if($is_overdue): ?>
                                        <span class="status-badge status-overdue"><span class="dot"></span> เกินกำหนด</span>
                                    <?php else: ?>
                                        <span class="status-badge status-borrowed"><span class="dot"></span> กำลังยืม</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="d-flex flex-column">
                                        <span class="status-badge status-returned mb-1"><span class="dot"></span> คืนแล้ว</span>
                                        <small style="font-size: 0.7rem; color: #888;">
                                            <?php echo date('d/m/y', strtotime($row['return_date'])); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if($role == 'admin' && $row['status'] == 'borrowed'): ?>
                                    <button onclick="confirmReturn(<?php echo $row['id']; ?>, <?php echo $row['book_item_id']; ?>)" 
                                            class="btn-action">
                                        รับหนังสือคืน
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted opacity-25">-</span>
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

        // ✅ ตั้งค่า Particles สีฟ้า
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 160, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#0d6efd" }, /* สีฟ้า */
                "shape": { "type": "circle" },
                "opacity": { "value": 0.5, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#0d6efd", "opacity": 0.2, "width": 1 },
                "move": { "enable": true, "speed": 2 }
            },
            "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": true, "mode": "grab" }, "resize": true } },
            "retina_detect": true
        });

        // ✅ ฟังก์ชันแจ้งเตือน (ปรับเป็น Theme ขาว)
        function confirmReturn(transId, itemId) {
            Swal.fire({
                title: 'ยืนยันการคืน?',
                text: "คุณต้องการบันทึกการรับคืนหนังสือใช่หรือไม่",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, รับคืน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `return_save.php?trans_id=${transId}&item_id=${itemId}`;
                }
            })
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'returned') {
            Swal.fire({
                title: 'สำเร็จ!',
                text: 'บันทึกการคืนเรียบร้อยแล้ว',
                icon: 'success',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'ตกลง'
            }).then(() => window.history.replaceState(null, null, window.location.pathname));
        }
    </script>
</body>
</html>