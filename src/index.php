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
// (ถ้ามี overdue > 0 แปลว่าโดนระงับสิทธิ์)
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

        #particles-js { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: -1; pointer-events: none; }
        .book-cover { width: 80px; height: 120px; object-fit: cover; border-radius: 5px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
        .navbar-custom { background: rgba(255, 255, 255, 0.9) !important; padding: 15px 0; position: relative; width: 100%; z-index: 1000; }
        .nav-item .nav-link { color: #000000 !important; font-size: 0.9rem; font-weight: 500; margin: 0 12px; position: relative; transition: all 0.3s; }
        .nav-item .nav-link:hover, .nav-item .nav-link.active { color: #000000 !important; }
        .nav-item .nav-link::after { content: ''; position: absolute; width: 0; height: 2px; bottom: -5px; left: 50%; background: linear-gradient(90deg, #000000, #000000); transition: width 0.3s ease, left 0.3s ease; }
        .nav-item .nav-link:hover::after { width: 100%; left: 0; }
        .user-profile-box { border-left: 1px solid rgb(0, 0, 0); padding-left: 20px; }
        .stat-card-hover { transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); }
        .stat-card-hover:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important; }
        
        #bookTable tbody tr { transition: all 0.2s ease-in-out; }
        #bookTable tbody tr:hover { transform: scale(1.01); background-color: #ffffff; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); z-index: 10; position: relative; }
        #bookTable tbody tr:hover .book-cover { transform: scale(1.05); }

        /* 🔥 CSS สำหรับ Popup รูปใหญ่ (Hover) */
        #img-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(5px); z-index: 9999; display: none; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; pointer-events: none; }
        #img-overlay.show { opacity: 1; }
        #large-book-img { max-width: 90vw; max-height: 90vh; border-radius: 15px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5); transform: scale(0.8); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        #img-overlay.show #large-book-img { transform: scale(1); }

        /* 🔥 Custom Modal Styling (Book Detail Style) */
        .modal-xl { max-width: 1140px; }
        .modal-content { border-radius: 20px; border: none; overflow: hidden; background: #fff; }
        .modal-body { padding: 40px; }
        
        /* รูปใน Modal */
        .detail-cover { width: auto; max-width: 100%; max-height: 450px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); object-fit: contain; }

        .status-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; }
        .status-dot.active { background-color: #198754; box-shadow: 0 0 10px rgba(25, 135, 84, 0.5); }
        .status-dot.inactive { background-color: #dc3545; }
        
        .spec-box { border: 1px solid #dee2e6; padding: 15px; text-align: center; background: #fff; height: 100%; }
        .spec-box .text-label { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .spec-box .text-value { font-weight: bold; font-size: 1.1rem; color: #0d6efd; }
        
        .price-tag { font-size: 2rem; font-weight: 800; color: #0d6efd; line-height: 1; }
        
        /* ปุ่มใน Modal */
        .btn-modal-borrow { background: #0d6efd; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: bold; width: 100%; transition: all 0.3s; }
        .btn-modal-borrow:hover { background: #0b5ed7; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3); }
        
        /* ปุ่มเมื่อโดนบล็อก */
        .btn-blocked { background: #6c757d !important; cursor: not-allowed; opacity: 0.8; }
        
        .btn-modal-close { border: 2px solid #dee2e6; color: #6c757d; border-radius: 10px; padding: 10px 20px; font-weight: bold; }
        .btn-modal-close:hover { background: #f8f9fa; color: #000; }
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
                        <li class="nav-item"><a class="nav-link" href="report.php">รายงานสรุป</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_book.php">เพิ่มหนังสือ</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin_users.php">จัดการผู้ใช้</a></li>
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
                        <div class="col-md-6"><div class="card stat-card-hover p-3 border-start border-4 border-primary h-100"><div class="d-flex justify-content-between align-items-center"><div><h6 class="text-muted text-uppercase mb-1">นักเรียนทั้งหมด</h6><h2 class="mb-0 fw-bold text-primary"><?php echo number_format($cnt_users); ?></h2></div><div class="fs-1 text-primary opacity-25"><i class="fa-solid fa-users"></i></div></div></div></div>
                        <div class="col-md-6"><div class="card stat-card-hover p-3 border-start border-4 border-success h-100"><div class="d-flex justify-content-between align-items-center"><div><h6 class="text-muted text-uppercase mb-1">หนังสือทั้งหมด (เล่ม)</h6><h2 class="mb-0 fw-bold text-success"><?php echo number_format($cnt_books); ?></h2></div><div class="fs-1 text-success opacity-25"><i class="fa-solid fa-book"></i></div></div></div></div>
                        <div class="col-md-6"><div class="card stat-card-hover p-3 border-start border-4 border-warning h-100"><div class="d-flex justify-content-between align-items-center"><div><h6 class="text-muted text-uppercase mb-1">กำลังถูกยืม</h6><h2 class="mb-0 fw-bold text-warning"><?php echo number_format($cnt_borrow); ?></h2></div><div class="fs-1 text-warning opacity-25"><i class="fa-solid fa-hand-holding-heart"></i></div></div></div></div>
                        <div class="col-md-6"><div class="card stat-card-hover p-3 border-start border-4 border-danger h-100"><div class="d-flex justify-content-between align-items-center"><div><h6 class="text-muted text-uppercase mb-1">เกินกำหนดส่ง!</h6><h2 class="mb-0 fw-bold text-danger"><?php echo number_format($cnt_overdue); ?></h2></div><div class="fs-1 text-danger opacity-25"><i class="fa-solid fa-bell"></i></div></div></div></div>
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
                    new Chart(ctx, { type: 'doughnut', data: { labels: ['ว่างพร้อมยืม', 'ถูกยืมออกไป'], datasets: [{ data: [<?php echo $cnt_available; ?>, <?php echo $cnt_borrow; ?>], backgroundColor: ['#198754', '#ffc107'], borderWidth: 0, hoverOffset: 4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
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
                                    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ? AND status = 'available'");
                                    $countStmt->execute([$book['id']]);
                                    $available = $countStmt->fetchColumn();
                                    
                                    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ?");
                                    $totalStmt->execute([$book['id']]);
                                    $total = $totalStmt->fetchColumn();

                                    $showImg = $book['cover_image'] ? "uploads/" . $book['cover_image'] : "https://via.placeholder.com/150?text=No+Image";
                                    $stockStatus = ($available > 0) ? "ว่าง $available เล่ม" : "หมด";
                                    $pdfFile = !empty($book['sample_pdf']) ? $book['sample_pdf'] : '';
                                ?>
                                    <tr class="hover-row book-row" style="cursor: pointer;"
                                        data-img="<?php echo $showImg; ?>"
                                        data-title="<?php echo htmlspecialchars($book['title']); ?>"
                                        data-author="<?php echo htmlspecialchars($book['author']); ?>"
                                        data-publisher="<?php echo htmlspecialchars($book['publisher']); ?>"
                                        data-isbn="<?php echo htmlspecialchars($book['isbn']); ?>"
                                        data-price="<?php echo number_format($book['price'], 0); ?>"
                                        data-pdf="<?php echo htmlspecialchars($pdfFile); ?>"
                                        data-stock="<?php echo $available; ?>"
                                        data-total="<?php echo $total; ?>"
                                        data-pages="<?php echo $book['page_count'] ?? '-'; ?>"
                                        data-paper="<?php echo $book['paper_type'] ?? '-'; ?>"
                                        data-print="<?php echo $book['print_type'] ?? '-'; ?>"
                                        data-size="<?php echo $book['book_size'] ?? '-'; ?>"
                                        data-appno="<?php echo $book['approval_no'] ?? '-'; ?>"
                                        data-apporder="<?php echo $book['approval_order'] ?? '-'; ?>">
                                        
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
                                            <?php if ($user_role == 'admin') { ?>
                                                <a href="book_stock.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-warning w-100 mb-1 btn-action" onclick="event.stopPropagation();">
                                                    <i class="fa-solid fa-layer-group"></i> จัดการสต็อก
                                                </a>
                                            <?php } ?>
                                            
                                            <button class="btn btn-sm btn-primary w-100 mb-1 btn-view btn-action">
                                                <i class="fa-solid fa-circle-info"></i> รายละเอียด
                                            </button>

                                            <?php if ($is_blocked): ?>
                                                <button class="btn btn-sm btn-secondary w-100" disabled>
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
            // ส่งค่า Block status ไปให้ JS รู้ด้วย (เผื่อใช้ใน Modal)
            const isUserBlocked = <?php echo $is_blocked ? 'true' : 'false'; ?>;

            $(document).ready(function() {
                AOS.init({ duration: 800, once: true });

                const table = $('#bookTable').DataTable({
                    language: { search: "ค้นหา:", lengthMenu: "แสดง _MENU_ รายการ", info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ", paginate: { first: "หน้าแรก", last: "หน้าสุดท้าย", next: "ถัดไป", previous: "ก่อนหน้า" }, zeroRecords: "ไม่พบข้อมูลหนังสือ" }
                });

                // --- 1. Event: คลิกปุ่ม "รายละเอียด" หรือแถว ---
                $('#bookTable tbody').on('click', '.btn-view, tr.book-row', function(e) {
                    if ($(e.target).closest('.btn-borrow, .btn-warning').length) return;
                    
                    const tr = $(this).closest('tr');
                    const d = tr.data(); 

                    // Fill Modal Data
                    $('#m_cover').attr('src', d.img);
                    $('#m_title').text(d.title);
                    $('#m_isbn').text('ISBN: ' + d.isbn);
                    $('#m_author').text(d.author);
                    $('#m_publisher').text(d.publisher);
                    $('#m_price').text(d.price);
                    $('#m_pages').text(d.pages);
                    $('#m_paper').text(d.paper);
                    $('#m_print').text(d.print);
                    $('#m_size').text(d.size);
                    $('#m_approval').text(d.appno + ' (ลำดับที่ ' + d.apporder + ')');

                    // Stock Status Logic
                    const available = parseInt(d.stock);
                    const total = parseInt(d.total);
                    const percent = total > 0 ? (available / total) * 100 : 0;
                    
                    $('#m_available_text').text('ว่าง: ' + available);
                    $('#m_total_text').text('ทั้งหมด: ' + total);
                    $('#m_progress').css('width', percent + '%').removeClass('bg-success bg-secondary').addClass(available > 0 ? 'bg-success' : 'bg-secondary');
                    
                    // 🔥 Logic ปุ่มใน Modal (เช็ค Block ด้วย)
                    if(isUserBlocked) {
                        $('#m_stock_badge').html('<span class="status-dot inactive"></span> <span class="text-danger fw-bold small">ระงับสิทธิ์</span>');
                        $('#m_btn_borrow').prop('disabled', true).html('<i class="fa-solid fa-ban me-2"></i> คืนของเก่าก่อน').removeClass('btn-modal-borrow').addClass('btn-blocked btn-secondary w-100');
                    } else if(available > 0) {
                        $('#m_stock_badge').html('<span class="status-dot active"></span> <span class="text-success fw-bold small">พร้อมยืม</span>');
                        $('#m_btn_borrow').prop('disabled', false).html('<i class="fa-solid fa-book-open me-2"></i> ยืมหนังสือ').removeClass('btn-blocked btn-secondary').addClass('btn-modal-borrow');
                        
                        // Bind Event ปุ่มยืมใน Modal
                        $('#m_btn_borrow').off('click').on('click', function() {
                            confirmBorrow(d.id, d.title);
                        });
                    } else {
                        $('#m_stock_badge').html('<span class="status-dot inactive"></span> <span class="text-danger fw-bold small">หมดชั่วคราว</span>');
                        $('#m_btn_borrow').prop('disabled', true).html('<i class="fa-solid fa-lock me-2"></i> หนังสือหมด').removeClass('btn-modal-borrow').addClass('btn btn-secondary w-100');
                    }

                    // PDF Button
                    if (d.pdf && d.pdf !== '') {
                        $('#m_pdf_section').html(`<a href="uploads/pdfs/${d.pdf}" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill px-3 mb-3"><i class="fa-regular fa-file-pdf me-1"></i> ทดลองอ่านตัวอย่าง</a>`);
                    } else {
                        $('#m_pdf_section').empty();
                    }

                    new bootstrap.Modal(document.getElementById('bookModal')).show();
                });

                // --- 2. Event: ปุ่มยืมหนังสือ (แก้ปัญหา Quote ตีกัน) ---
                $('#bookTable tbody').on('click', '.btn-borrow', function(e) {
                    e.stopPropagation(); 
                    const id = $(this).data('id');
                    const title = $(this).data('title');
                    confirmBorrow(id, title);
                });

                // --- 3. Hover รูปใหญ่ ---
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
                overlay.on('click', function() { $(this).removeClass('show').hide(); });
                
                particlesJS("particles-js", { "particles": { "number": { "value": 160, "density": { "enable": true, "value_area": 800 } }, "color": { "value": "#0d6efd" }, "shape": { "type": "circle" }, "opacity": { "value": 0.5, "random": true }, "size": { "value": 3, "random": true }, "line_linked": { "enable": true, "distance": 150, "color": "#0d6efd", "opacity": 0.2, "width": 1 }, "move": { "enable": true, "speed": 2 } }, "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": true, "mode": "grab" } }, "onclick": { "enable": true, "mode": "push" } }, "retina_detect": true });
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
            if (status === 'success') Swal.fire({ title: 'ยืมสำเร็จ!', text: 'อย่าลืมคืนหนังสือภายใน 7 วันนะครับ', icon: 'success', confirmButtonText: 'ตกลง' }).then(() => { window.history.replaceState(null, null, window.location.pathname); });
            else if (status === 'duplicate') Swal.fire({ title: 'ยืมไม่ได้!', text: 'คุณมีหนังสือเล่มนี้อยู่แล้ว', icon: 'warning', confirmButtonText: 'เข้าใจแล้ว' }).then(() => { window.history.replaceState(null, null, window.location.pathname); });
            else if (status === 'error') Swal.fire({ title: 'ขออภัย', text: 'หนังสือเล่มนี้หมดพอดี', icon: 'error', confirmButtonText: 'ปิด' });
        </script>

        <div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <div class="row g-5">
                            <div class="col-md-4 text-center">
                                <div class="mb-4 d-flex justify-content-center">
                                    <img id="m_cover" src="" class="detail-cover img-fluid" alt="Cover">
                                </div>
                                
                                <div class="p-3 rounded-3 bg-light border border-secondary-subtle">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-label" style="font-size: 0.75rem; font-weight: bold; color: #6c757d;">STOCK STATUS</span>
                                        <div id="m_stock_badge"></div>
                                    </div>
                                    <div class="progress" style="height: 6px; background-color: #e9ecef;">
                                        <div id="m_progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2 small text-secondary fw-bold">
                                        <span id="m_available_text"></span>
                                        <span id="m_total_text"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="mb-4 border-bottom pb-4">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill" id="m_isbn"></span>
                                            <h1 class="fw-bold text-dark mb-2" id="m_title"></h1>
                                            <div class="d-flex gap-3 text-secondary small mb-3">
                                                <span><i class="fa-regular fa-user me-1 text-primary"></i> <span id="m_author"></span></span>
                                                <span><i class="fa-regular fa-building me-1 text-primary"></i> <span id="m_publisher"></span></span>
                                            </div>
                                            <div id="m_pdf_section"></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="price-tag"><span id="m_price"></span>.-</div>
                                            <div class="text-secondary small">THB</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <div class="text-secondary fw-bold mb-3 small"><i class="fa-solid fa-layer-group me-2"></i>SPECIFICATIONS</div>
                                    <div class="row g-0">
                                        <div class="col-6 col-md-3"><div class="spec-box rounded-start-2"><div class="text-label">จำนวนหน้า</div><div class="text-value" id="m_pages"></div></div></div>
                                        <div class="col-6 col-md-3"><div class="spec-box" style="border-left:0;"><div class="text-label">รูปแบบกระดาษ</div><div class="text-value text-dark" id="m_paper"></div></div></div>
                                        <div class="col-6 col-md-3"><div class="spec-box" style="border-left:0;"><div class="text-label">การพิมพ์</div><div class="text-value text-dark" id="m_print"></div></div></div>
                                        <div class="col-6 col-md-3"><div class="spec-box rounded-end-2" style="border-left:0;"><div class="text-label">ขนาด</div><div class="text-value text-dark" id="m_size"></div></div></div>
                                    </div>
                                    <div class="row g-0 mt-2">
                                        <div class="col-12"><div class="spec-box d-flex justify-content-between rounded-2"><span class="text-label">APPROVAL NO.</span><span class="text-dark fw-bold" id="m_approval"></span></div></div>
                                    </div>
                                </div>

                                <div class="d-flex gap-3 mt-auto">
                                    <button id="m_btn_borrow" class="btn-modal-borrow shadow-sm">
                                        <i class="fa-solid fa-book-open me-2"></i> ยืมหนังสือ
                                    </button>
                                    <button type="button" class="btn-modal-close" data-bs-dismiss="modal">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>
</html>