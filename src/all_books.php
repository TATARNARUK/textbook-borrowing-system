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

// --- ส่วนจัดการหมวดหมู่ ---
try {
    $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    $has_categories = true;
} catch (Exception $e) {
    $cats = [];
    $has_categories = false;
}

// รับค่าค้นหา และ หมวดหมู่
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_cat = isset($_GET['cat']) ? $_GET['cat'] : '';

// สร้าง SQL Query
$sql = "SELECT * FROM book_masters WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (title LIKE :q OR author LIKE :q OR isbn LIKE :q)";
    $params[':q'] = "%$search%";
}

if ($filter_cat && $has_categories) {
    $sql .= " AND category_id = :cat";
    $params[':cat'] = $filter_cat;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    
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
        
        .book-cover-container { position: relative; padding-top: 140%; overflow: hidden; background: #eee; }
        .book-cover { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .book-card:hover .book-cover { transform: scale(1.05); }
        .status-badge { position: absolute; top: 10px; right: 10px; z-index: 2; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); }
        .btn-modal-borrow { background: #0d6efd; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: bold; width: 100%; transition: all 0.3s; }
        .btn-modal-borrow:hover { background: #0b5ed7; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3); }
        .btn-blocked { background: #6c757d !important; cursor: not-allowed; opacity: 0.8; }
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
            <div class="col-md-10 col-lg-8">
                <form action="" method="GET" class="position-relative d-flex gap-2">
                    
                    <?php if ($has_categories): ?>
                    <select name="cat" class="form-select form-select-lg rounded-pill shadow-sm border-0" style="max-width: 200px;" onchange="this.form.submit()">
                        <option value="">ทุกหมวดหมู่</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($filter_cat == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>

                    <div class="position-relative flex-grow-1">
                        <input type="text" name="q" class="form-control form-control-lg rounded-pill ps-5 shadow-sm border-0"
                            placeholder="ค้นหาชื่อหนังสือ, ผู้แต่ง, ISBN..." value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <?php if ($search || $filter_cat): ?>
                            <a href="all_books.php" class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted"><i class="fa-solid fa-xmark"></i></a>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">ค้นหา</button>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">
                <?php 
                    $cat_name = "หนังสือทั้งหมด";
                    if($filter_cat && $has_categories) {
                        foreach($cats as $c) { if($c['id'] == $filter_cat) $cat_name = $c['name']; }
                    }
                    echo $search ? 'ผลการค้นหา: "' . htmlspecialchars($search) . '"' : $cat_name; 
                ?>
            </h5>
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

                    // 🔥 แก้ไข Logic การดึงรูปภาพ (เพื่อให้รูปขึ้น)
                    $cover = $book['cover_image'];
                    $cover = str_replace(' ', '%20', $cover);

                    if (strpos($cover, 'http') === 0) {
                        $img = $cover;
                    } elseif (!empty($cover)) {
                        if (file_exists("uploads/" . urldecode($cover))) {
                            $img = "uploads/" . $cover;
                        } else {
                            $img = "https://itdev.bncc.ac.th/vbss/Education_system/other/img/uploads/" . $cover;
                        }
                    } else {
                        $img = "https://via.placeholder.com/300x450?text=No+Cover";
                    }

                    $statusClass = $available > 0 ? 'bg-success' : 'bg-secondary';
                    $statusText = $available > 0 ? "ว่าง $available" : 'หมด';
                ?>
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2" data-aos="fade-up">
                        <div class="card book-card h-100" data-id="<?php echo $book['id']; ?>">

                            <div class="book-cover-container">
                                <span class="badge <?php echo $statusClass; ?> rounded-pill status-badge"><?php echo $statusText; ?></span>
                                <img src="<?php echo $img; ?>" class="book-cover" alt="Cover">
                            </div>

                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-bold text-truncate mb-1" title="<?php echo $book['title']; ?>"><?php echo $book['title']; ?></h6>
                                <small class="text-muted mb-3 d-block text-truncate"><i class="fa-solid fa-pen-nib me-1"></i> <?php echo $book['author']; ?></small>
                                
                                <div class="mt-auto">

                                    <?php if ($is_blocked): ?>
                                        <button class="btn btn-sm btn-secondary w-100 rounded-pill border" disabled>
                                            <i class="fa-solid fa-ban me-1"></i> ระงับสิทธิ์
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light text-muted w-100 rounded-pill border" disabled>ของหมด</button>
                                    <?php endif; ?>
                                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        $(document).ready(function() {
            AOS.init({ duration: 800, once: true });

            // 🔥 คลิกที่การ์ด -> ไปหน้า Detail
            $('.book-card').on('click', function(e) {
                // ถ้าคลิกโดนปุ่มยืมหรือลิงก์รายละเอียด ไม่ต้องทำซ้ำ
                if ($(e.target).closest('.btn-borrow, a').length) return;

                const id = $(this).data('id');
                if(id) window.location.href = 'book_detail.php?id=' + id;
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