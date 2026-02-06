<?php
session_start();
require_once 'config.php';

// 1. ตรวจสอบว่า Login หรือยัง?
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูล User จาก Session
$user_name = $_SESSION['fullname'];
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// 🔥 BLOCKING LOGIC: เช็คว่ามีหนังสือเกินกำหนดส่งหรือไม่?
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM transactions 
                            WHERE user_id = ? 
                            AND status = 'borrowed' 
                            AND due_date < NOW()");
$stmtCheck->execute([$user_id]);
$overdue_count = $stmtCheck->fetchColumn();
$is_blocked = ($overdue_count > 0);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการหนังสือ - ระบบยืมคืนหนังสือเรียนฟรี</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #f0f4f8;
            background-image: radial-gradient(#dbeafe 1px, transparent 1px);
            background-size: 20px 20px;
            margin: 0;
            min-height: 100vh;
            color: #333;
            overflow-x: hidden;
        }

        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            pointer-events: none;
        }

        .book-cover {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.9) !important;
            padding: 15px 0;
            position: relative;
            width: 100%;
            z-index: 1000;
        }

        .nav-item .nav-link {
            color: #000000 !important;
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0 12px;
            position: relative;
            transition: all 0.3s;
        }

        .nav-item .nav-link:hover,
        .nav-item .nav-link.active {
            color: #000000 !important;
        }

        .nav-item .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 50%;
            background: linear-gradient(90deg, #000000, #000000);
            transition: width 0.3s ease, left 0.3s ease;
        }

        .nav-item .nav-link:hover::after {
            width: 100%;
            left: 0;
        }

        .user-profile-box {
            border-left: 1px solid rgb(0, 0, 0);
            padding-left: 20px;
        }

        .stat-card-hover {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .stat-card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important;
        }

        #bookTable tbody tr {
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        #bookTable tbody tr:hover {
            transform: scale(1.01);
            background-color: #ffffff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            z-index: 10;
            position: relative;
        }

        #bookTable tbody tr:hover .book-cover {
            transform: scale(1.05);
        }

        /* 🔥 CSS สำหรับ Popup รูปใหญ่ (Hover) */
        #img-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        #img-overlay.show {
            opacity: 1;
        }

        #large-book-img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            transform: scale(0.8);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        #img-overlay.show #large-book-img {
            transform: scale(1);
        }
    </style>
</head>

<body>
    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>

    <div id="img-overlay">
        <img id="large-book-img" src="" alt="Large Cover">
    </div>

    <nav class="navbar navbar-expand-lg navbar-custom fixed-top py-3" data-aos="fade-down" data-aos-duration="1500">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
                <img src="images/books.png" height="40" alt="Logo">
                <div class="d-none d-md-block text-start">
                    <h5 class="m-0 fw-bold text-primary" style="font-family: 'Noto Sans Thai', sans-serif;">
                        TEXTBOOK BORROWING SYSTEM
                    </h5>
                    <small class="text-dark">ระบบยืม-คืนหนังสือเรียนฟรี</small>
                </div>
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <i class="fa-solid fa-bars text-white fs-3"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="all_books.php">
                            <i class="fa-solid fa-list me-1"></i> หนังสือทั้งหมด
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_history.php">ประวัติการยืม</a>
                    </li>
                    <?php if ($user_role == 'admin') { ?>
                        <li class="nav-item dropdown ms-lg-2">
                            <a class="nav-link dropdown-toggle btn btn-light text-primary border px-3 rounded-pill" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-screwdriver-wrench me-1"></i> ส่วนผู้ดูแล
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 rounded-3">
                                <li>
                                    <h6 class="dropdown-header text-uppercase small text-muted">รายงาน & สถิติ</h6>
                                </li>
                                <li><a class="dropdown-item" href="report.php"><i class="fa-solid fa-chart-pie me-2 text-warning"></i> รายงานสรุป</a></li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <li>
                                    <h6 class="dropdown-header text-uppercase small text-muted">จัดการข้อมูล</h6>
                                </li>
                                <li><a class="dropdown-item" href="add_book.php"><i class="fa-solid fa-book-medical me-2 text-success"></i> เพิ่มหนังสือใหม่</a></li>
                                <li><a class="dropdown-item" href="book_stock_list.php"><i class="fa-solid fa-boxes-stacked me-2 text-primary"></i> จัดการสต็อกหนังสือ</a></li>
                                <li><a class="dropdown-item" href="manage_categories.php"><i class="fa-solid fa-layer-group me-2 text-info"></i> จัดการหมวดหมู่</a></li>
                                <li><a class="dropdown-item" href="admin_users.php"><i class="fa-solid fa-users-gear me-2 text-danger"></i> จัดการผู้ใช้</a></li>
                                <li><a class="dropdown-item" href="import_api.php"><i class="fa-solid fa-cloud-arrow-down me-2 text-secondary"></i> นำเข้าหนังสือจาก API</a></li>
                            </ul>
                        </li>
                    <?php } ?>
                </ul>

                <div class="d-flex align-items-center gap-3 ms-lg-4 user-profile-box mt-3 mt-lg-0">
                    <div class="text-end d-none d-lg-block">
                        <span class="d-block text-dark fw-bold" style="font-size: 0.9rem;">
                            <?php echo ($user_role == 'admin') ? 'ผู้ดูแลระบบสูงสุด' : $user_name; ?>
                        </span>
                        <span class="d-block text-dark small text-uppercase" style="font-size: 0.7rem;">
                            <?php echo ucfirst($user_role); ?>
                        </span>
                    </div>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-bold">
                        <i class="fa-solid fa-power-off me-1"></i> ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div style="padding-top: 100px;"></div>

    <div class="container">

        <?php if ($is_blocked): ?>
            <div class="alert alert-danger shadow-sm rounded-4 mb-4 border-0 d-flex align-items-center" role="alert" data-aos="fade-down">
                <i class="fa-solid fa-circle-exclamation fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">สิทธิ์การยืมถูกระงับชั่วคราว!</h5>
                    <p class="mb-0 small">คุณมีหนังสือที่เกินกำหนดส่งคืนจำนวน <strong><?php echo $overdue_count; ?> เล่ม</strong> กรุณาติดต่อคืนหนังสือที่ห้องสมุดก่อน จึงจะสามารถยืมเล่มใหม่ได้</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($user_role == 'admin') {
            $cnt_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
            $cnt_books = $pdo->query("SELECT COUNT(*) FROM book_items")->fetchColumn();
            $cnt_borrow = $pdo->query("SELECT COUNT(*) FROM book_items WHERE status='borrowed'")->fetchColumn();
            $cnt_available = $pdo->query("SELECT COUNT(*) FROM book_items WHERE status='available'")->fetchColumn();
            $cnt_overdue = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status='borrowed' AND due_date < NOW()")->fetchColumn();
        ?>
            <div class="row mb-5" data-aos="fade-up">
                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card stat-card-hover p-3 border-start border-4 border-primary h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted text-uppercase mb-1">นักเรียนทั้งหมด</h6>
                                        <h2 class="mb-0 fw-bold text-primary"><?php echo number_format($cnt_users); ?></h2>
                                    </div>
                                    <div class="fs-1 text-primary opacity-25"><i class="fa-solid fa-users"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card stat-card-hover p-3 border-start border-4 border-success h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted text-uppercase mb-1">หนังสือทั้งหมด (เล่ม)</h6>
                                        <h2 class="mb-0 fw-bold text-success"><?php echo number_format($cnt_books); ?></h2>
                                    </div>
                                    <div class="fs-1 text-success opacity-25"><i class="fa-solid fa-book"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card stat-card-hover p-3 border-start border-4 border-warning h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted text-uppercase mb-1">กำลังถูกยืม</h6>
                                        <h2 class="mb-0 fw-bold text-warning"><?php echo number_format($cnt_borrow); ?></h2>
                                    </div>
                                    <div class="fs-1 text-warning opacity-25"><i class="fa-solid fa-hand-holding-heart"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card stat-card-hover p-3 border-start border-4 border-danger h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted text-uppercase mb-1">เกินกำหนดส่ง!</h6>
                                        <h2 class="mb-0 fw-bold text-danger"><?php echo number_format($cnt_overdue); ?></h2>
                                    </div>
                                    <div class="fs-1 text-danger opacity-25"><i class="fa-solid fa-bell"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm stat-card-hover">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-3">สถานะคลังหนังสือ</h6>
                            <div style="height: 200px; position: relative;"><canvas id="stockChart"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const ctx = document.getElementById('stockChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['ว่างพร้อมยืม', 'ถูกยืมออกไป'],
                            datasets: [{
                                data: [<?php echo $cnt_available; ?>, <?php echo $cnt_borrow; ?>],
                                backgroundColor: ['#198754', '#ffc107'],
                                borderWidth: 0,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                });
            </script>
        <?php } ?>

        <div class="container">
            <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden text-white" style="background: linear-gradient(135deg, #003cff 0%, rgb(255, 255, 255) 100%);" data-aos="fade-up" data-aos-delay="100">
                <div class="card-body p-5 position-relative">
                    <div class="row align-items-center position-relative" style="z-index: 2;">
                        <div class="col-lg-8">
                            <h1 class="fw-bold mb-2">ยินดีต้อนรับสู่ห้องสมุด IT 📖</h1>
                            <p class="fs-5 opacity-75 mb-4">แหล่งเรียนรู้ ยืม-คืนง่าย ได้ความรู้ฟรี!</p>
                            <div class="d-flex gap-2">
                                <a href="all_books.php" class="btn btn-light text-dark rounded-pill px-4 fw-bold shadow-sm">
                                    <i class="fa-solid fa-magnifying-glass"></i> ค้นหาหนังสือ
                                </a>
                                <a href="manual.php" class="btn btn-outline-light rounded-pill px-4">
                                    <i class="fa-solid fa-book-open"></i> คู่มือการใช้งาน
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-4 d-none d-lg-block text-center">
                            <i class="fa-solid fa-book-open-reader fa-10x opacity-50 text-white floating-icon"></i>
                        </div>
                    </div>
                    <div class="position-absolute top-0 end-0 opacity-10">
                        <i class="fa-solid fa-shapes fa-10x" style="transform: rotate(30deg); margin-top: -50px; margin-right: -50px;"></i>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row text-dark justify-content-between align-items-center mb-4 gap-3" data-aos="fade-up" data-aos-delay="100">
                <h3>📚 รายชื่อหนังสือเรียนทั้งหมด</h3>
            </div>

            <div class="card shadow-sm border-0 rounded-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card-body">
                    <div class="table-responsive">
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
                                $count = 0;
                                while ($book = $stmt->fetch()) {
                                    $count++;
                                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ? AND status = 'available'");
                                    $stmtCount->execute([$book['id']]);
                                    $available = $stmtCount->fetchColumn();

                                    // 🔥 Logic รูปภาพ: ถ้าเป็น URL (http) ให้ใช้เลย ถ้าไม่ใช่เติม uploads/
                                    $cover = $book['cover_image'];
                                    if (strpos($cover, 'http') === 0) {
                                        $showImg = $cover;
                                    } else {
                                        $showImg = $cover ? "uploads/" . $cover : "https://via.placeholder.com/150?text=No+Image";
                                    }
                                ?>
                                    <tr class="hover-row book-row" data-id="<?php echo $book['id']; ?>" data-img="<?php echo $showImg; ?>">
                                        <td>
                                            <img src="<?php echo $showImg; ?>" class="book-cover">
                                        </td>
                                        <td><span class="badge bg-secondary"><?php echo $book['isbn']; ?></span></td>
                                        <td class="fw-bold text-primary">
                                            <?php echo $book['title']; ?>
                                            <?php if ($count <= 5): ?><span class="badge bg-danger rounded-pill ms-2 small shadow-sm animate__animated animate__pulse animate__infinite">New!</span><?php endif; ?>
                                        </td>
                                        <td><?php echo $book['author']; ?></td>
                                        <td>
                                            <?php if ($available > 0): ?>
                                                <span class="badge bg-success">ว่าง <?php echo $available; ?> เล่ม</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">หมด</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary w-100 mb-1 btn-action" onclick="event.stopPropagation();">
                                                <i class="fa-solid fa-circle-info"></i> รายละเอียด
                                            </a>

                                            <?php if ($is_blocked): ?>
                                                <button class="btn btn-sm btn-secondary w-100" disabled onclick="event.stopPropagation();">
                                                    <i class="fa-solid fa-ban me-1"></i> ระงับสิทธิ์
                                                </button>
                                            <?php elseif ($available > 0): ?>
                                                <button class="btn btn-sm btn-outline-success w-100 btn-borrow btn-action"
                                                    data-id="<?php echo $book['id']; ?>"
                                                    data-title="<?php echo htmlspecialchars($book['title']); ?>">
                                                    <i class="fa-solid fa-hand-holding-heart me-1"></i> ยืมหนังสือ
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
        </div>

        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

        <script>
            $(document).ready(function() {
                AOS.init({
                    duration: 800,
                    once: true
                });

                const table = $('#bookTable').DataTable({
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

                // 🔥 คลิกที่แถว -> ไปหน้า Detail
                $('#bookTable tbody').on('click', 'tr.book-row', function(e) {
                    // ถ้าคลิกโดนปุ่มยืมหรือสต็อก ไม่ต้องไปหน้า Detail
                    if ($(e.target).closest('.btn-borrow, .btn-warning, .btn-secondary').length) return;

                    const id = $(this).data('id');
                    if (id) {
                        window.location.href = 'book_detail.php?id=' + id;
                    }
                });

                // ปุ่มยืมหนังสือ
                $('#bookTable tbody').on('click', '.btn-borrow', function(e) {
                    e.stopPropagation(); // หยุดไม่ให้คลิกทะลุไปโดนแถว
                    const id = $(this).data('id');
                    const title = $(this).data('title');
                    confirmBorrow(id, title);
                });

                // Hover รูปใหญ่
                let hoverTimeout;
                const overlay = $('#img-overlay');
                const largeImg = $('#large-book-img');

                $('#bookTable tbody').on('mouseenter', 'tr.book-row', function() {
                    const imgSrc = $(this).data('img');
                    if (imgSrc) {
                        hoverTimeout = setTimeout(() => {
                            largeImg.attr('src', imgSrc);
                            overlay.css('display', 'flex').addClass('show');
                        }, 2000);
                    }
                }).on('mouseleave', 'tr.book-row', function() {
                    clearTimeout(hoverTimeout);
                    overlay.removeClass('show').hide();
                });
                overlay.on('click', function() {
                    $(this).removeClass('show').hide();
                });

                particlesJS("particles-js", {
                    "particles": {
                        "number": {
                            "value": 160,
                            "density": {
                                "enable": true,
                                "value_area": 800
                            }
                        },
                        "color": {
                            "value": "#0d6efd"
                        },
                        "shape": {
                            "type": "circle"
                        },
                        "opacity": {
                            "value": 0.5,
                            "random": true
                        },
                        "size": {
                            "value": 3,
                            "random": true
                        },
                        "line_linked": {
                            "enable": true,
                            "distance": 150,
                            "color": "#0d6efd",
                            "opacity": 0.2,
                            "width": 1
                        },
                        "move": {
                            "enable": true,
                            "speed": 2
                        }
                    },
                    "interactivity": {
                        "detect_on": "canvas",
                        "events": {
                            "onhover": {
                                "enable": true,
                                "mode": "grab"
                            }
                        },
                        "onclick": {
                            "enable": true,
                            "mode": "push"
                        }
                    },
                    "retina_detect": true
                });
            });

            // Confirm Borrow Function
            function confirmBorrow(id, title) {
                Swal.fire({
                    title: 'ยืนยันการยืม?',
                    text: title,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'borrow_save.php?id=' + id;
                    }
                });
            }

            // Alerts
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            if (status === 'success') Swal.fire({
                title: 'ยืมสำเร็จ!',
                text: 'อย่าลืมคืนหนังสือภายใน 7 วันนะครับ',
                icon: 'success',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.history.replaceState(null, null, window.location.pathname);
            });
            else if (status === 'duplicate') Swal.fire({
                title: 'ยืมไม่ได้!',
                text: 'คุณมีหนังสือเล่มนี้อยู่แล้ว',
                icon: 'warning',
                confirmButtonText: 'เข้าใจแล้ว'
            }).then(() => {
                window.history.replaceState(null, null, window.location.pathname);
            });
            else if (status === 'error') Swal.fire({
                title: 'ขออภัย',
                text: 'หนังสือเล่มนี้หมดพอดี',
                icon: 'error',
                confirmButtonText: 'ปิด'
            });
        </script>
</body>

</html>