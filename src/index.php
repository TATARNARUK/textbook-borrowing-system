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
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #000000ff;
            margin: 0;
        }

        #particles-js {
            position: fixed;
            /* ให้มันลอยอยู่กับที่ ไม่ต้องเลื่อนตาม Scroll bar */
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            /* ✅ สำคัญมาก! สั่งให้ไปอยู่ข้างหลังสุด */
            pointer-events: none;
            /* สั่งให้เม้าส์คลิกทะลุผ่านไปได้ (เผื่อไว้ก่อน) */
        }

        .book-cover {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }


        .navbar-custom {
            padding: 15px 0;
            /* ✅ เปลี่ยนจาก Fixed เป็น Relative เพื่อไม่ให้ลอยตาม */
            position: relative;
            width: 100%;
            z-index: 1000;
        }

        .navbar-brand-text {
            font-family: 'Noto Sans Thai', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .nav-item .nav-link {
            color: #ccc !important;
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0 12px;
            position: relative;
            transition: all 0.3s;
        }

        .nav-item .nav-link:hover,
        .nav-item .nav-link.active {
            color: #fff !important;
        }

        .nav-item .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 50%;
            background: linear-gradient(90deg, #fff, #aaa);
            transition: width 0.3s ease, left 0.3s ease;
        }

        .nav-item .nav-link:hover::after {
            width: 100%;
            left: 0;
        }

        .user-profile-box {
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            padding-left: 20px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body><?php require_once 'loader.php'; ?><div id="particles-js"></div>

   <nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid px-lg-5">
        
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <span class="navbar-brand-text">TEXTBOOK BORROWING SYSTEM</span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <i class="fa-solid fa-bars text-white fs-3"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                
                <li class="nav-item">
                    <a class="nav-link" href="my_history.php">ประวัติการยืม</a>
                </li>

                <?php if ($user_role == 'admin') { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="report.php">รายงานสรุป</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_book.php">เพิ่มหนังสือ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_users.php">จัดการผู้ใช้</a>
                    </li>
                <?php } ?>

            </ul>

            <div class="d-flex align-items-center gap-3 ms-lg-4 user-profile-box mt-3 mt-lg-0">
                <div class="text-end d-none d-lg-block">
                    <span class="d-block text-white fw-bold" style="font-size: 0.9rem; letter-spacing: 0.5px;">
                        <?php echo ($user_role == 'admin') ? 'ผู้ดูแลระบบสูงสุด' : $user_name; ?>
                    </span>
                    <span class="d-block text-white small text-uppercase" style="font-size: 0.7rem;">
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

        <?php if ($user_role == 'admin') {
            // Query ข้อมูลตัวเลข
            $cnt_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
            $cnt_books = $pdo->query("SELECT COUNT(*) FROM book_items")->fetchColumn();
            $cnt_borrow = $pdo->query("SELECT COUNT(*) FROM book_items WHERE status='borrowed'")->fetchColumn();
            $cnt_available = $pdo->query("SELECT COUNT(*) FROM book_items WHERE status='available'")->fetchColumn();
            $cnt_overdue = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status='borrowed' AND due_date < NOW()")->fetchColumn();
        ?>
            <div class="row mb-5">
                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card p-3 border-start border-4 border-primary h-100">
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
                            <div class="card p-3 border-start border-4 border-success h-100">
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
                            <div class="card p-3 border-start border-4 border-warning h-100">
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
                            <div class="card p-3 border-start border-4 border-danger h-100">
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
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-3">สถานะคลังหนังสือ</h6>
                            <div style="height: 200px; position: relative;">
                                <canvas id="stockChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                // รอเว็บโหลดเสร็จค่อยวาดกราฟ
                document.addEventListener("DOMContentLoaded", function() {
                    const ctx = document.getElementById('stockChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'doughnut', // กราฟวงกลมแบบโดนัท
                        data: {
                            labels: ['ว่างพร้อมยืม', 'ถูกยืมออกไป'],
                            datasets: [{
                                data: [<?php echo $cnt_available; ?>, <?php echo $cnt_borrow; ?>],
                                backgroundColor: ['#198754', '#ffc107'], // สีเขียว, สีเหลือง
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            </nav>

            <div class="container">
                <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden text-white"
                    style="background: linear-gradient(135deg, #000000ff 0%, #c2c2c2ff 100%);">
                    <div class="card-body p-5 position-relative">
                        <div class="row align-items-center position-relative" style="z-index: 2;">
                            <div class="col-lg-8">
                                <h1 class="fw-bold mb-2">ยินดีต้อนรับสู่ห้องสมุด IT 📖</h1>
                                <p class="fs-5 opacity-75 mb-4">แหล่งเรียนรู้ ยืม-คืนง่าย ได้ความรู้ฟรี!</p>
                                <div class="d-flex gap-2">
                                    <button onclick="focusSearch()" class="btn btn-light text-dark rounded-pill px-4 fw-bold shadow-sm">
                                        <i class="fa-solid fa-magnifying-glass"></i> ค้นหาหนังสือ
                                    </button>
                                    <a href="manual.php" class="btn btn-outline-light rounded-pill px-4">
                                        <i class="fa-solid fa-book-open"></i> คู่มือการใช้งาน
                                    </a>
                                </div>
                            </div>
                            <div class="col-lg-4 d-none d-lg-block text-center">
                                <i class="fa-solid fa-book-open-reader fa-10x opacity-50 text-white"></i>
                            </div>
                        </div>
                        <div class="position-absolute top-0 end-0 opacity-10">
                            <i class="fa-solid fa-shapes fa-10x" style="transform: rotate(30deg); margin-top: -50px; margin-right: -50px;"></i>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row text-white justify-content-between align-items-center mb-4 gap-3">
                    <h3>📚 รายชื่อหนังสือเรียนทั้งหมด</h3>
                </div>

                <div class="card shadow-sm border-0 rounded-4">
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
                                    $count = 0;  // <--- ✅ เพิ่มบรรทัดนี้ (เริ่มนับ 0)

                                    while ($book = $stmt->fetch()) {
                                        $count++; // <--- ✅ เพิ่มบรรทัดนี้ (นับเพิ่มทีละ 1)

                                        // ... (โค้ดเดิมของคุณต่อจากนี้) ...
                                        // เช็คจำนวนหนังสือที่ว่าง (คำนวณจาก table book_items)
                                        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ? AND status = 'available'");
                                        $countStmt->execute([$book['id']]);
                                        $available = $countStmt->fetchColumn();
                                        // เช็ครูปภาพ (ถ้าไม่มีให้ใช้รูปแทน)
                                        $showImg = $book['cover_image'] ? "uploads/" . $book['cover_image'] : "https://via.placeholder.com/150?text=No+Image";
                                        // เช็คสถานะ (ข้อความ)
                                        $stockStatus = ($available > 0) ? "ว่าง $available เล่ม" : "หมด";
                                    ?>
                                        <tr style="cursor: pointer; transition: 0.2s;"
                                            onmouseover="this.style.backgroundColor='#f1f3f5';"
                                            onmouseout="this.style.backgroundColor='';"
                                            onclick="showBookModal(
                                '<?php echo addslashes($book['title']); ?>', 
                                '<?php echo addslashes($book['author']); ?>', 
                                '<?php echo $book['isbn']; ?>', 
                                '<?php echo $stockStatus; ?>', 
                                '<?php echo $showImg; ?>'
                            )">
                                            <td>
                                                <?php if ($book['cover_image']): ?>
                                                    <img src="uploads/<?php echo $book['cover_image']; ?>" class="book-cover">
                                                <?php else: ?>
                                                    <img src="https://via.placeholder.com/80x120?text=No+Cover" class="book-cover">
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-secondary"><?php echo $book['isbn']; ?></span></td>
                                            <td class="fw-bold text-primary">
                                                <?php echo $book['title']; ?>
                                                <?php if ($count <= 5): ?>
                                                    <span class="badge bg-danger rounded-pill ms-2 small shadow-sm animate__animated animate__pulse animate__infinite">New!</span>
                                                <?php endif; ?>
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
                                                <?php if ($user_role == 'admin') { ?>
                                                    <a href="book_stock.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-warning w-100 mb-1">
                                                        <i class="fa-solid fa-layer-group"></i> จัดการสต็อก</a>
                                                <?php } ?>
                                                <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary w-100 mb-1">
                                                    <i class="fa-solid fa-circle-info"></i> รายละเอียด
                                                </a>
                                                <?php if ($available > 0): ?>
                                                    <button onclick="event.stopPropagation(); confirmBorrow(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?>')"
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
            </div>

            <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

            <script>
                $(document).ready(function() {
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
                        .then(() => {
                            window.history.replaceState(null, null, window.location.pathname);
                        }); // ล้าง URL
                } else if (status === 'error') {
                    Swal.fire('ล้มเหลว', 'หนังสือเล่มนี้หมดพอดี', 'error');
                }
            </script>
            <div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow-lg">
                        <div class="modal-header border-0">
                            <h5 class="modal-title fw-bold text-primary">รายละเอียดหนังสือ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center pb-4 px-4">
                            <img id="m_cover" src="" class="rounded shadow mb-3" style="max-height: 350px; max-width: 250%;">
                            <h4 id="m_title" class="fw-bold mb-2"></h4>
                            <p id="m_author" class="text-muted mb-3"></p>

                            <div class="bg-light p-3 rounded-3 text-start d-inline-block w-100">
                                <div><strong>ISBN:</strong> <span id="m_isbn"></span></div>
                                <div><strong>คงเหลือ:</strong> <span id="m_stock"></span></div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 justify-content-center">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

            <script>
                function showBookModal(title, author, isbn, stock, image) {
                    // เอารับค่ามาใส่ใน Modal
                    document.getElementById('m_title').innerText = title;
                    document.getElementById('m_author').innerText = author;
                    document.getElementById('m_isbn').innerText = isbn;
                    document.getElementById('m_stock').innerText = stock;
                    document.getElementById('m_cover').src = image;

                    // สั่งเปิด Modal
                    var myModal = new bootstrap.Modal(document.getElementById('bookModal'));
                    myModal.show();
                }

                function focusSearch() {
                    // 1. เลื่อนหน้าจอลงมาที่ตาราง
                    document.getElementById('bookTable').scrollIntoView({
                        behavior: 'smooth'
                    });

                    // 2. สั่งให้เมาส์ไปคลิกที่ช่องค้นหาของ DataTables อัตโนมัติ
                    setTimeout(function() {
                        $('div.dataTables_filter input').focus();
                    }, 500); // รอ 0.5 วินาทีให้เลื่อนถึงก่อนค่อยโฟกัส
                }
            </script>
            <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
            <script>
                /* เรียกใช้ particles.js ที่กล่อง id="particles-js" */
                particlesJS("particles-js", {
                    "particles": {
                        "number": {
                            "value": 80,
                            /* จำนวนดาว (ยิ่งเยอะยิ่งรก) ลองปรับดูที่ 50-100 */
                            "density": {
                                "enable": true,
                                "value_area": 800
                            }
                        },
                        "color": {
                            "value": "#ffffff" /* สีของดาว (สีขาว) */
                        },
                        "shape": {
                            "type": "circle",
                            /* รูปร่าง (วงกลม) */
                            "stroke": {
                                "width": 0,
                                "color": "#000000"
                            },
                            "polygon": {
                                "nb_sides": 5
                            }
                        },
                        "opacity": {
                            "value": 0.5,
                            /* ความจางของดาว (0.5 คือครึ่งๆ) */
                            "random": true,
                            /* ให้จางไม่เท่ากัน ดูมีมิติ */
                            "anim": {
                                "enable": false,
                                "speed": 1,
                                "opacity_min": 0.1,
                                "sync": false
                            }
                        },
                        "size": {
                            "value": 3,
                            /* ขนาดของดาว */
                            "random": true,
                            /* เล็กใหญ่ไม่เท่ากัน */
                            "anim": {
                                "enable": false,
                                "speed": 40,
                                "size_min": 0.1,
                                "sync": false
                            }
                        },
                        "line_linked": {
                            "enable": true,
                            /* ✅ ถ้าไม่อยากได้เส้นเชื่อม ให้แก้เป็น false */
                            "distance": 150,
                            /* ระยะห่างที่จะให้มีเส้นเชื่อม */
                            "color": "#ffffff",
                            /* สีของเส้น */
                            "opacity": 0.4,
                            /* ความจางของเส้น */
                            "width": 1
                        },
                        "move": {
                            "enable": true,
                            /* สั่งให้ขยับ */
                            "speed": 2,
                            /* ความเร็วในการวิ่ง (ยิ่งเยอะยิ่งเร็ว) */
                            "direction": "none",
                            /* ทิศทาง (none คือมั่ว) */
                            "random": false,
                            "straight": false,
                            "out_mode": "out",
                            "bounce": false,
                            "attract": {
                                "enable": false,
                                "rotateX": 600,
                                "rotateY": 1200
                            }
                        }
                    },
                    "interactivity": {
                        /* ส่วนนี้คือเวลาเอาเมาส์ไปโดน */
                        "detect_on": "canvas",
                        "events": {
                            "onhover": {
                                "enable": true,
                                /* ถ้า true เวลาเอาเมาส์ไปชี้ ดาวจะวิ่งหนีหรือวิ่งเข้าหา */
                                "mode": "grab" /* grab = มีเส้นดูดเข้าหาเมาส์, repulse = วิ่งหนี */
                            },
                            "onclick": {
                                "enable": true,
                                "mode": "push" /* คลิกแล้วมีดาวเพิ่ม */
                            },
                            "resize": true
                        },
                        "modes": {
                            "grab": {
                                "distance": 140,
                                "line_linked": {
                                    "opacity": 1
                                }
                            },
                            "bubble": {
                                "distance": 400,
                                "size": 40,
                                "duration": 2,
                                "opacity": 8,
                                "speed": 3
                            },
                            "repulse": {
                                "distance": 200,
                                "duration": 0.4
                            },
                            "push": {
                                "particles_nb": 4
                            },
                            "remove": {
                                "particles_nb": 2
                            }
                        }
                    },
                    "retina_detect": true
                });
            </script>
</body>

</html>