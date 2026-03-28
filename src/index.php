<?php
session_start();
require_once 'config.php';

// 1. เช็คว่าล็อกอินหรือยัง?
if (!isset($_SESSION['user_id'])) {
    header("Location: landing.php");
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


if ($user_role == 'admin') {
    $cnt_users    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
    $cnt_books    = $pdo->query("SELECT COUNT(*) FROM book_items")->fetchColumn();
    $cnt_borrow   = $pdo->query("SELECT COUNT(*) FROM book_items WHERE status='borrowed'")->fetchColumn();
    $cnt_available = $pdo->query("SELECT COUNT(*) FROM book_items WHERE status='available'")->fetchColumn();
    $cnt_overdue  = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status='borrowed' AND due_date < NOW()")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบยืม-คืนหนังสือเรียนฟรี</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">

</head>

<body>

    <div id="qr-overlay">
        <img id="qr-large-img" src="images/bot_qrcode.png" alt="QR Code">
        <p class="text-white mt-4 fw-bold text-center" style="font-size:18px;">สแกนเพื่อเชื่อมต่อระบบแจ้งเตือน LINE</p>
        <small style="color:rgba(255,255,255,.65);margin-top:4px;display:block;text-align:center;">ลดปัญหาการลืมคืนหนังสือ</small>
        <button onclick="closeQR()" style="margin-top:20px;padding:8px 28px;border-radius:20px;border:1px solid rgba(255,255,255,.4);background:transparent;color:white;cursor:pointer;font-size:13px;transition:0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'">ปิดหน้าต่าง (Esc)</button>
    </div>

    <div id="img-overlay">
        <img id="large-book-img" src="" alt="Book Cover">
    </div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="fa-solid fa-book-open"></i></div>
            <div class="logo-text">
                <strong>TEXTBOOK BORROWING SYSTEM</strong>
                <small>ระบบยืม-คืนหนังสือเรียนฟรี</small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section">เมนูหลัก</div>

            <a href="index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fa-solid fa-table-columns"></i></div>
                <span>Dashboard</span>
            </a>

            <a href="all_books.php" class="nav-item <?php echo ($current_page == 'all_books.php') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fa-solid fa-book"></i></div>
                <span>รายการหนังสือ</span>
            </a>

            <a href="my_history.php" class="nav-item <?php echo ($current_page == 'my_history.php') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <span>ประวัติการยืม</span>
            </a>

            <?php if ($user_role == 'admin'): ?>
                <div class="sidebar-section" style="margin-top:8px;">ผู้ดูแลระบบ</div>

                <a href="report.php" class="nav-item <?php echo ($current_page == 'report.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-chart-pie"></i></div>
                    <span>รายงานสรุป</span>
                </a>

                <a href="add_book.php" class="nav-item <?php echo ($current_page == 'add_book.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-book-medical"></i></div>
                    <span>เพิ่มหนังสือใหม่</span>
                </a>

                <a href="book_stock_list.php" class="nav-item <?php echo ($current_page == 'book_stock_list.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <span>จัดการสต็อก</span>
                </a>

                <a href="manage_categories.php" class="nav-item <?php echo ($current_page == 'manage_categories.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-layer-group"></i></div>
                    <span>จัดการหมวดหมู่</span>
                </a>

                <a href="admin_users.php" class="nav-item <?php echo ($current_page == 'admin_users.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-users-gear"></i></div>
                    <span>จัดการผู้ใช้</span>
                </a>

                <a href="import_api.php" class="nav-item <?php echo ($current_page == 'import_api.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-cloud-arrow-down"></i></div>
                    <span>นำเข้าจาก API</span>
                </a>
            <?php endif; ?>

            <div class="sidebar-section" style="margin-top:8px;">อื่นๆ</div>

            <a href="manual.php" class="nav-item <?php echo ($current_page == 'manual.php') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fa-solid fa-circle-question"></i></div>
                <span>คู่มือการใช้งาน</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-dropdown" id="userDropdown">
                <a href="profile.php" class="dropdown-item">
                    <i class="fa-regular fa-id-badge"></i> ข้อมูลส่วนตัว
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item text-danger">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> ออกจากระบบ
                </a>
            </div>

            <div class="user-card" id="userDropdownTrigger">
                <div class="user-avatar"><?php echo mb_substr($user_name, 0, 1); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-role"><?php echo $user_role == 'admin' ? 'ผู้ดูแลระบบ' : 'นักเรียน'; ?></div>
                </div>
                <i class="fa-solid fa-chevron-up" id="userDropdownIcon" style="color:var(--text-muted); font-size:12px; transition: transform 0.2s;"></i>
            </div>
        </div>
    </aside>

    <div class="main-wrapper">

        <header class="topbar">
            <button class="menu-toggle" id="menuToggle"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-greeting d-flex flex-column justify-content-center">
                <h2 class="mb-0" style="font-size: 18px; font-weight: 700; color: var(--text-primary);">
                    สวัสดี, <?php echo htmlspecialchars($user_name); ?> 👋
                </h2>
                <p class="mb-0" style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
                    <?php echo date('l, d F Y'); ?>
                </p>
            </div>
            <div class="topbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="topSearchInput" placeholder="ค้นหาหนังสือ...">
            </div>
            <div class="topbar-actions">
                <div class="icon-btn qr-trigger" title="เชื่อมต่อ LINE">
                    <i class="fab fa-line text-success fs-5"></i>
                </div>
                <div class="icon-btn" title="การแจ้งเตือน">
                    <i class="fa-solid fa-bell"></i>
                    <?php if ($overdue_count > 0): ?><span style="position:absolute;top:6px;right:6px;width:8px;height:8px;background:var(--danger);border-radius:50%;"></span><?php endif; ?>
                </div>
            </div>
        </header>

        <main class="page-content">

            <?php if ($is_blocked): ?>
                <div class="alert-banner">
                    <div class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div>
                        <h6>สิทธิ์การยืมถูกระงับชั่วคราว!</h6>
                        <p>คุณมีหนังสือที่เกินกำหนดส่งคืน <strong><?php echo $overdue_count; ?> เล่ม</strong> — กรุณาติดต่อคืนหนังสือที่คุณครูก่อน</p>
                    </div>
                </div>
            <?php endif; ?>

<?php if ($user_role == 'admin'): ?>
                <div class="stats-grid animate-fadeup">
                    <div class="stat-card">
                        <div class="stat-label">
                            นักเรียนทั้งหมด
                            <div class="stat-icon" style="background:#eff6ff; color:#2563eb;"><i class="fa-solid fa-users"></i></div>
                        </div>
                        <div class="stat-value"><?php echo number_format($cnt_users); ?></div>
                        <div class="stat-sub">ผู้ใช้งานที่ลงทะเบียน</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">
                            หนังสือทั้งหมด
                            <div class="stat-icon" style="background:#f0fdf4; color:#10b981;"><i class="fa-solid fa-book"></i></div>
                        </div>
                        <div class="stat-value"><?php echo number_format($cnt_books); ?></div>
                        <div class="stat-sub">เล่มในคลัง</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">
                            กำลังถูกยืม
                            <div class="stat-icon" style="background:#fffbeb; color:#f59e0b;"><i class="fa-solid fa-hand-holding-heart"></i></div>
                        </div>
                        <div class="stat-value"><?php echo number_format($cnt_borrow); ?></div>
                        <div class="stat-sub">เล่มที่ออกไป</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">
                            เกินกำหนดส่ง!
                            <div class="stat-icon" style="background:#fef2f2; color:#ef4444;"><i class="fa-solid fa-bell"></i></div>
                        </div>
                        <div class="stat-value" style="color:#ef4444;"><?php echo number_format($cnt_overdue); ?></div>
                        <div class="stat-sub">รายการต้องติดตาม</div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="hero-banner animate-fadeup delay-1">
                <div class="hero-tag"><i class="fa-solid fa-sparkles"></i> TEXTBOOK BORROWING SYSTEM </div>
                <h1>ยินดีต้อนรับสู่<br>ระบบยืม-คืนหนังสือเรียนฟรี 📖</h1>
                <p>ยืม-คืนหนังสือเรียนได้ง่ายๆ ทุกที่ทุกเวลา พร้อมระบบแจ้งเตือนอัตโนมัติ</p>
                <div class="hero-actions">
                    <a href="#bookSection" class="btn-hero-primary"><i class="fa-solid fa-magnifying-glass"></i> ค้นหาหนังสือ</a>
                    <a href="manual.php" class="btn-hero-secondary"><i class="fa-solid fa-book-open"></i> คู่มือการใช้งาน</a>
                </div>
                <i class="fa-solid fa-book-open-reader hero-icon"></i>
            </div>

            <div class="content-grid animate-fadeup delay-2" id="bookSection">

                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">📚 รายชื่อหนังสือเรียนทั้งหมด</div>
                            <div class="card-subtitle">คลิกที่แถวเพื่อดูรายละเอียด</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="bookTable" class="book-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ปก</th>
                                        <th>รหัส/ISBN</th>
                                        <th>ชื่อหนังสือ</th>
                                        <th>ผู้แต่ง</th>
                                        <th>สถานะ</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("SELECT * FROM book_masters ORDER BY id DESC");
                                    $count = 0;
                                    while ($book = $stmt->fetch()):
                                        $count++;
                                        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ? AND status = 'available'");
                                        $stmtCount->execute([$book['id']]);
                                        $available = $stmtCount->fetchColumn();
                                        $cover = $book['cover_image'];
                                        $showImg = (strpos($cover, 'http') === 0) ? $cover : ($cover ? "uploads/" . $cover : "https://via.placeholder.com/150?text=No+Image");
                                    ?>
                                        <tr class="book-row" data-id="<?php echo $book['id']; ?>">
                                            <td><img src="<?php echo $showImg; ?>" class="book-cover" alt="cover"></td>
                                            <td><span class="badge badge-code"><?php echo htmlspecialchars(!empty($book['book_code']) && $book['book_code'] != '-' ? $book['book_code'] : $book['isbn']); ?></span></td>
                                            <td>
                                                <div class="book-title-cell">
                                                    <?php echo htmlspecialchars($book['title']); ?>
                                                    <?php if ($count <= 5): ?><span class="badge badge-new">New</span><?php endif; ?>
                                                </div>
                                                <div class="book-author d-sm-none"><?php echo htmlspecialchars($book['author']); ?></div>
                                            </td>
                                            <td class="d-none d-sm-table-cell" style="color:var(--text-secondary);font-size:12px;"><?php echo htmlspecialchars($book['author']); ?></td>
                                            <td>
                                                <?php if ($available > 0): ?>
                                                    <span class="badge badge-available"><i class="fa-solid fa-circle-check" style="font-size:9px;"></i> ว่าง <?php echo $available; ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-empty"><i class="fa-solid fa-circle-xmark" style="font-size:9px;"></i> หมด</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display:flex;gap:6px;">
                                                    <button class="btn-action btn-detail btn-detail-click" data-id="<?php echo $book['id']; ?>">
                                                        <i class="fa-solid fa-circle-info"></i> <span class="d-none d-xl-inline">รายละเอียด</span>
                                                    </button>
                                                    <?php if ($is_blocked): ?>
                                                        <button class="btn-action btn-disabled" disabled><i class="fa-solid fa-ban"></i></button>
                                                    <?php elseif ($available > 0): ?>
                                                        <button class="btn-action btn-borrow btn-borrow-click" data-id="<?php echo $book['id']; ?>" data-title="<?php echo htmlspecialchars($book['title']); ?>">
                                                            <i class="fa-solid fa-hand-holding-heart"></i> <span class="d-none d-xl-inline">ยืม</span>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn-action btn-disabled" disabled>หมด</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="line-card animate-fadeup delay-3">
                        <div class="line-card-top">
                            <i class="fab fa-line" style="font-size:28px;margin-bottom:4px;display:block;"></i>
                            <h5>ระบบแจ้งเตือน LINE</h5>
                            <p>รับการแจ้งเตือนอัตโนมัติ</p>
                            <div class="qr-wrap">
                                <img src="images/bot_qrcode.png" class="qr-trigger" alt="QR">
                            </div>
                        </div>
                        <div class="line-card-body">
                            <div class="line-pill"><i class="fab fa-line"></i> LINE ID: @695pbvul</div>
                            <div class="feature-tag"><i class="fa-solid fa-bell"></i> แจ้งเตือนแบบ Real-time</div>
                            <div class="feature-tag"><i class="fa-solid fa-calendar-check"></i> เตือนวันกำหนดส่งคืน</div>
                            <div class="feature-tag"><i class="fa-solid fa-shield-check"></i> ลดปัญหาการลืมคืน</div>
                        </div>
                    </div>

                    <?php if ($user_role == 'admin'): ?>
                        <div class="chart-card">
                            <div class="card-header" style="padding:16px 20px 0;">
                                <div>
                                    <div class="card-title" style="font-size:14px;">สถานะคลังหนังสือ</div>
                                    <div class="card-subtitle">ว่าง vs ถูกยืม</div>
                                </div>
                            </div>
                            <div class="card-body" style="padding:16px 20px;">
                                <div style="height:200px;position:relative;">
                                    <canvas id="stockChart"></canvas>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <footer style="margin-top: auto; padding-top: 40px; text-align: center; color: var(--text-muted); font-size: 12px;">
                &copy; <?php echo date("Y"); ?> Textbook Borrowing System. All Rights Reserved. <br> Developed for Education
            </footer>

        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // ✅ ฟังก์ชัน QR & Image Overlay แบบคลีนๆ
        function openQR() {
            $('#qr-overlay').addClass('show');
        }

        function closeQR() {
            $('#qr-overlay').removeClass('show');
        }
        // ✅ ระบบคลิกโปรไฟล์เปิด Dropdown Menu
        $('#userDropdownTrigger').on('click', function(e) {
            e.stopPropagation(); // กันไม่ให้คำสั่งคลิกทะลุไปโดนพื้นหลัง
            $('#userDropdown').toggleClass('show');

            // หมุนลูกศรขึ้นลง
            if ($('#userDropdown').hasClass('show')) {
                $('#userDropdownIcon').css('transform', 'rotate(180deg)');
            } else {
                $('#userDropdownIcon').css('transform', 'rotate(0deg)');
            }
        });

        // ✅ กดที่ว่างที่อื่นในหน้าเว็บ เพื่อปิดเมนู
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.sidebar-footer').length) {
                $('#userDropdown').removeClass('show');
                $('#userDropdownIcon').css('transform', 'rotate(0deg)');
            }
        });

        $(document).ready(function() {
            // โหลด Animation
            AOS.init({
                duration: 800,
                once: true
            });

            // Toggle Sidebar บนมือถือ
            $('#menuToggle').on('click', function(e) {
                e.stopPropagation();
                $('#sidebar').toggleClass('open');
            });
            // กดที่ว่างให้ปิด Sidebar บนมือถือ
            $(document).on('click', function(e) {
                if ($(window).width() <= 768 && !$(e.target).closest('#sidebar, #menuToggle').length) {
                    $('#sidebar').removeClass('open');
                }
            });

            // ✅ เริ่มต้น DataTables แบบซ่อนกล่องค้นหาพื้นฐานอันเก่า
            const dt = $('#bookTable').DataTable({
                destroy: true,
                // เอาตัว 'f' ออกจาก dom เพื่อซ่อนช่องค้นหาแบบเก่า
                dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
                language: {
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_",
                    paginate: {
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    },
                    zeroRecords: "ไม่พบข้อมูล"
                },
                columnDefs: [{
                    orderable: false,
                    targets: [0, 5]
                }]
            });

            // เชื่อมต่อช่องค้นหาบน Topbar ให้คุยกับ DataTables
            $('#topSearchInput').on('keyup', function() {
                dt.search($(this).val()).draw();
            });

            // QR & Image Events
            let qrTimer;
            $('.qr-trigger').on('mouseenter', function() {
                qrTimer = setTimeout(openQR, 800);
            }).on('mouseleave', function() {
                clearTimeout(qrTimer);
            });

            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeQR();
                    $('#img-overlay').removeClass('show');
                }
            });
            $('#qr-overlay').on('click', function(e) {
                if (e.target.id === 'qr-overlay') closeQR();
            });

            let coverTimer;
            $('#bookTable tbody').on('mouseenter', '.book-cover', function() {
                const src = $(this).attr('src');
                coverTimer = setTimeout(() => {
                    $('#large-book-img').attr('src', src);
                    $('#img-overlay').addClass('show');
                }, 500);
            }).on('mouseleave', '.book-cover', function() {
                clearTimeout(coverTimer);
                $('#img-overlay').removeClass('show');
            });
            $('#img-overlay').on('click', function(e) {
                if (e.target.id === 'img-overlay') $(this).removeClass('show');
            });

            // คลิกแถว เข้า Detail
            $('#bookTable tbody').on('click', 'tr.book-row', function(e) {
                if ($(e.target).closest('button, .book-cover').length) return;
                window.location.href = 'book_detail.php?id=' + $(this).data('id');
            });
            $('#bookTable tbody').on('click', '.btn-detail-click', function(e) {
                e.stopPropagation();
                window.location.href = 'book_detail.php?id=' + $(this).data('id');
            });

            // ป๊อปอัพยืนยันการยืม
            $('#bookTable tbody').on('click', '.btn-borrow-click', function(e) {
                e.stopPropagation();
                const id = $(this).data('id'),
                    title = $(this).data('title');
                Swal.fire({
                    title: 'ยืนยันการยืม?',
                    text: title,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#e2e8f0',
                    cancelButtonText: '<span style="color:#1e293b">ยกเลิก</span>',
                    confirmButtonText: 'ยืนยันการยืม',
                    borderRadius: '16px'
                }).then(r => {
                    if (r.isConfirmed) window.location.href = 'borrow_save.php?id=' + id;
                });
            });

            // URL status alerts
            const p = new URLSearchParams(window.location.search);
            const s = p.get('status');
            if (s === 'success') Swal.fire('ยืมสำเร็จ!', 'อย่าลืมคืนภายใน 7 วันนะครับ', 'success').then(() => history.replaceState(null, '', location.pathname));
            else if (s === 'duplicate') Swal.fire('ยืมไม่ได้!', 'คุณมีหนังสือเล่มนี้อยู่แล้ว', 'warning').then(() => history.replaceState(null, '', location.pathname));
            else if (s === 'error') Swal.fire('ขออภัย', 'เกิดข้อผิดพลาด', 'error').then(() => history.replaceState(null, '', location.pathname));

            <?php if ($user_role == 'admin'): ?>
                // Donut chart
                new Chart(document.getElementById('stockChart').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['ว่างพร้อมยืม', 'ถูกยืมออก'],
                        datasets: [{
                            data: [<?php echo $cnt_available; ?>, <?php echo $cnt_borrow; ?>],
                            backgroundColor: ['#10b981', '#f59e0b'],
                            borderWidth: 0,
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: {
                                        size: 12,
                                        family: 'DM Sans'
                                    },
                                    padding: 16
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>