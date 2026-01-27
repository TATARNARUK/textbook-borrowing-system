<?php
session_start();
require_once 'config.php';

// เช็ค Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูล User
$user_id = $_SESSION['user_id'];

// 🔥 BLOCKING LOGIC: เช็คว่ามีหนังสือเกินกำหนดส่งหรือไม่?
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM transactions 
                            WHERE user_id = ? 
                            AND status = 'borrowed' 
                            AND due_date < NOW()");
$stmtCheck->execute([$user_id]);
$overdue_count = $stmtCheck->fetchColumn();
$is_blocked = ($overdue_count > 0);

// รับค่าค้นหา
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Query หนังสือ
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM book_masters 
                           WHERE title LIKE :q 
                           OR author LIKE :q 
                           OR isbn LIKE :q 
                           ORDER BY id DESC");
    $stmt->execute([':q' => "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM book_masters ORDER BY id DESC");
}
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หนังสือทั้งหมด - ระบบยืมคืนหนังสือ</title>
    <link rel="icon" type="image/png" href="images/books.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        body { font-family: 'Noto Sans Thai', sans-serif; background-color: #f8f9fa; }
        
        .navbar-custom { background: rgba(255, 255, 255, 0.95); box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05); }
        
        /* Book Card */
        .book-card {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: none; border-radius: 15px; overflow: hidden; height: 100%;
            background: #fff; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            cursor: pointer;
        }
        .book-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1); }
        
        /* CSS เฉพาะส่วนการ์ดหน้าหลัก (ห้ามใช้ใน Modal) */
        .book-cover-container { position: relative; padding-top: 140%; overflow: hidden; background: #eee; }
        .book-cover { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .book-card:hover .book-cover { transform: scale(1.05); }
        .status-badge { position: absolute; top: 10px; right: 10px; z-index: 2; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); }

        /* 🔥 Custom Modal Styling */
        .modal-xl { max-width: 1140px; }
        .modal-content { border-radius: 20px; border: none; overflow: hidden; background: #fff; }
        .modal-body { padding: 40px; }
        
        /* รูปใน Modal */
        .detail-cover { 
            width: auto; 
            max-width: 100%; 
            max-height: 450px; 
            border-radius: 10px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
            object-fit: contain;
        }

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

    <nav class="navbar navbar-expand-lg navbar-custom fixed-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary" href="index.php">
                <i class="fa-solid fa-chevron-left"></i> กลับหน้าหลัก
            </a>
            <div class="fw-bold text-dark d-none d-md-block">📚 คลังหนังสือทั้งหมด</div>
        </div>
    </nav>
    <div style="padding-top: 80px;"></div>

    <div class="container py-4">
        
        <?php if ($is_blocked): ?>
            <div class="alert alert-danger shadow-sm rounded-4 mb-4 border-0 d-flex align-items-center" role="alert" data-aos="fade-down">
                <i class="fa-solid fa-circle-exclamation fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">สิทธิ์การยืมถูกระงับชั่วคราว!</h5>
                    <p class="mb-0 small">คุณมีหนังสือที่เกินกำหนดส่งคืนจำนวน <strong><?php echo $overdue_count; ?> เล่ม</strong> กรุณาติดต่อคืนหนังสือที่ห้องสมุดก่อน จึงจะสามารถยืมเล่มใหม่ได้</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center mb-5" data-aos="fade-down">
            <div class="col-md-8 col-lg-6">
                <form action="" method="GET" class="position-relative">
                    <input type="text" name="q" class="form-control form-control-lg rounded-pill ps-5 shadow-sm border-0"
                        placeholder="ค้นหาชื่อหนังสือ, ผู้แต่ง, ISBN..." value="<?php echo htmlspecialchars($search); ?>">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <?php if ($search): ?>
                        <a href="all_books.php" class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0"><?php echo $search ? 'ผลการค้นหา: "' . htmlspecialchars($search) . '"' : 'หนังสือทั้งหมด'; ?></h5>
            <span class="badge bg-light text-dark border"><?php echo count($books); ?> รายการ</span>
        </div>

        <div class="row g-4">
            <?php if (count($books) > 0): ?>
                <?php foreach ($books as $book):
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ? AND status = 'available'");
                    $stmtCount->execute([$book['id']]);
                    $available = $stmtCount->fetchColumn();
                    
                    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ?");
                    $stmtTotal->execute([$book['id']]);
                    $total = $stmtTotal->fetchColumn();

                    $img = $book['cover_image'] ? "uploads/" . $book['cover_image'] : "https://via.placeholder.com/300x450?text=No+Cover";
                    $statusClass = $available > 0 ? 'bg-success' : 'bg-secondary';
                    $statusText = $available > 0 ? "ว่าง $available" : 'หมด';
                    $pdfFile = !empty($book['sample_pdf']) ? $book['sample_pdf'] : '';
                ?>
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2" data-aos="fade-up">
                        <div class="card book-card h-100" 
                             data-id="<?php echo $book['id']; ?>"
                             data-title="<?php echo htmlspecialchars($book['title']); ?>"
                             data-author="<?php echo htmlspecialchars($book['author']); ?>"
                             data-publisher="<?php echo htmlspecialchars($book['publisher']); ?>"
                             data-isbn="<?php echo htmlspecialchars($book['isbn']); ?>"
                             data-price="<?php echo number_format($book['price'], 0); ?>"
                             data-img="<?php echo htmlspecialchars($img); ?>"
                             data-pdf="<?php echo htmlspecialchars($pdfFile); ?>"
                             data-stock="<?php echo $available; ?>"
                             data-total="<?php echo $total; ?>"
                             data-pages="<?php echo $book['page_count'] ?? '-'; ?>"
                             data-paper="<?php echo $book['paper_type'] ?? '-'; ?>"
                             data-print="<?php echo $book['print_type'] ?? '-'; ?>"
                             data-size="<?php echo $book['book_size'] ?? '-'; ?>"
                             data-appno="<?php echo $book['approval_no'] ?? '-'; ?>"
                             data-apporder="<?php echo $book['approval_order'] ?? '-'; ?>">

                            <div class="book-cover-container">
                                <span class="badge <?php echo $statusClass; ?> rounded-pill status-badge"><?php echo $statusText; ?></span>
                                <img src="<?php echo $img; ?>" class="book-cover" alt="Cover">
                            </div>

                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-bold text-truncate mb-1" title="<?php echo $book['title']; ?>"><?php echo $book['title']; ?></h6>
                                <small class="text-muted mb-3 d-block text-truncate"><i class="fa-solid fa-pen-nib me-1"></i> <?php echo $book['author']; ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">ไม่พบหนังสือที่คุณค้นหา</p>
                    <a href="all_books.php" class="btn btn-outline-primary rounded-pill">ดูทั้งหมด</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // 🔥 ส่งค่า Block Status ให้ JS
        const isUserBlocked = <?php echo $is_blocked ? 'true' : 'false'; ?>;

        $(document).ready(function() {
            AOS.init({ duration: 800, once: true });

            // --- 1. คลิกการ์ดหนังสือ (เพื่อเปิด Modal) ---
            $('.book-card').on('click', function(e) {
                if ($(e.target).closest('.btn-borrow').length) return;

                const d = $(this).data();

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

                const available = parseInt(d.stock);
                const total = parseInt(d.total);
                const percent = total > 0 ? (available / total) * 100 : 0;
                
                $('#m_available_text').text('ว่าง: ' + available);
                $('#m_total_text').text('ทั้งหมด: ' + total);
                $('#m_progress').css('width', percent + '%').removeClass('bg-success bg-secondary').addClass(available > 0 ? 'bg-success' : 'bg-secondary');
                
                // 🔥 Logic ปุ่มใน Modal (เช็ค Block)
                if(isUserBlocked) {
                    $('#m_stock_badge').html('<span class="status-dot inactive"></span> <span class="text-danger fw-bold small">ระงับสิทธิ์</span>');
                    $('#m_btn_borrow').prop('disabled', true).html('<i class="fa-solid fa-ban me-2"></i> คืนของเก่าก่อน').removeClass('btn-modal-borrow').addClass('btn-blocked btn-secondary w-100');
                } else if(available > 0) {
                    $('#m_stock_badge').html('<span class="status-dot active"></span> <span class="text-success fw-bold small">พร้อมยืม</span>');
                    $('#m_btn_borrow').prop('disabled', false).html('<i class="fa-solid fa-book-open me-2"></i> ยืมหนังสือ').removeClass('btn-blocked btn-secondary').addClass('btn-modal-borrow');
                    
                    $('#m_btn_borrow').off('click').on('click', function() {
                        confirmBorrow(d.id, d.title);
                    });
                } else {
                    $('#m_stock_badge').html('<span class="status-dot inactive"></span> <span class="text-danger fw-bold small">หมดชั่วคราว</span>');
                    $('#m_btn_borrow').prop('disabled', true).html('<i class="fa-solid fa-lock me-2"></i> หนังสือหมด').removeClass('btn-modal-borrow').addClass('btn btn-secondary w-100');
                }

                if (d.pdf && d.pdf !== '') {
                    $('#m_pdf_section').html(`<a href="uploads/pdfs/${d.pdf}" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill px-3 mb-3"><i class="fa-regular fa-file-pdf me-1"></i> ทดลองอ่านตัวอย่าง</a>`);
                } else {
                    $('#m_pdf_section').empty();
                }

                new bootstrap.Modal(document.getElementById('bookModal')).show();
            });

            // --- 2. คลิกปุ่ม "ยืม" บนการ์ด ---
            $('.btn-borrow').on('click', function(e) {
                e.stopPropagation();
                const card = $(this).closest('.book-card');
                const id = card.data('id');
                const title = card.data('title');
                confirmBorrow(id, title);
            });
        });

        function confirmBorrow(id, title) {
            Swal.fire({
                title: 'ยืนยันการยืม?',
                text: title,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ยืมเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'borrow_save.php?id=' + id;
                }
            })
        }

        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        if (status === 'success') {
            Swal.fire({ title: 'ยืมสำเร็จ!', text: 'อย่าลืมคืนหนังสือภายใน 7 วันนะครับ', icon: 'success', confirmButtonText: 'ตกลง' })
                .then(() => { window.history.replaceState(null, null, window.location.pathname); });
        } else if (status === 'duplicate') {
            Swal.fire({ title: 'ยืมไม่ได้!', text: 'คุณมีหนังสือเล่มนี้อยู่แล้ว', icon: 'warning', confirmButtonText: 'เข้าใจแล้ว' })
                .then(() => { window.history.replaceState(null, null, window.location.pathname); });
        } else if (status === 'error') {
            Swal.fire({ title: 'ขออภัย', text: 'หนังสือเล่มนี้หมดพอดี', icon: 'error', confirmButtonText: 'ปิด' });
        }
    </script>
</body>
</html>