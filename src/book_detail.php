<?php
session_start();
require_once 'config.php';

// รับค่า ID
if (!isset($_GET['id'])) {
    echo "<script>window.location='index.php';</script>";
    exit();
}

$id = $_GET['id'];

// 1. ดึงข้อมูลหนังสือ
$stmt = $pdo->prepare("SELECT * FROM book_masters WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    echo "<div class='container mt-5 text-center'><h3>ไม่พบข้อมูลหนังสือ</h3><a href='index.php' class='btn btn-primary'>กลับหน้าหลัก</a></div>";
    exit();
}

// 2. เช็คสต็อก
$stmtStock = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed
    FROM book_items 
    WHERE book_master_id = ?
");
$stmtStock->execute([$id]);
$stock = $stmtStock->fetch();

$total_items = $stock['total'] ?? 0;
$available_items = $stock['available'] ?? 0;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $book['title']; ?></title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* --- 🎨 White & Blue Theme CSS --- */
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #f0f4f8;
            background-image: radial-gradient(#dbeafe 1px, transparent 1px);
            background-size: 20px 20px;
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

        /* --- White Card --- */
        .glass-card {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(13, 110, 253, 0.15);
            position: relative;
            z-index: 1;
        }

        /* --- Image Styling --- */
        .book-cover-container {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            background: #fff;
            padding: 5px;
            border: 1px solid #dee2e6;
        }

        .book-cover-container:hover {
            transform: translateY(-5px);
        }

        .book-cover {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 8px;
        }

        /* --- Typography --- */
        .text-label {
            color: #6c757d;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
            font-weight: 600;
        }

        .text-value {
            color: #0d6efd;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .price-tag {
            font-size: 2.2rem;
            font-weight: 800;
            color: #0d6efd;
            letter-spacing: -1px;
            line-height: 1;
        }

        .isbn-badge {
            background-color: #e7f1ff;
            color: #0d6efd;
            padding: 5px 12px;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-radius: 50px;
            font-weight: 600;
            display: inline-block;
        }

        /* --- Spec Grid (Light Theme) --- */
        .spec-box {
            background: #fff;
            border: 1px solid #dee2e6;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
        }

        .spec-box:hover {
            background-color: #f8f9fa;
            border-color: #0d6efd;
            z-index: 2;
            position: relative;
        }

        /* --- Buttons --- */
        .btn-custom-primary {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            color: #fff;
            border: none;
            font-weight: 600;
            border-radius: 10px;
            padding: 12px 30px;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }

        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(13, 110, 253, 0.3);
            color: #fff;
        }

        .btn-custom-primary:disabled {
            background: #6c757d;
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
        }

        .btn-outline-custom {
            background: transparent;
            color: #6c757d;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.3s;
        }

        .btn-outline-custom:hover {
            color: #0d6efd;
            border-color: #0d6efd;
            background: #fff;
        }

        /* 🔥 [แก้ไข] ปุ่ม PDF ให้ Hover แล้วสวย */
        .pdf-btn {
            color: #dc3545;
            border-color: #dc3545;
            transition: all 0.3s;
        }

        .pdf-btn:hover {
            color: #fff !important;
            /* บังคับขาว */
            background-color: #dc3545;
            border-color: #dc3545;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        /* --- Status Indicator --- */
        .status-dot {
            height: 10px;
            width: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .status-dot.active {
            background-color: #198754;
            box-shadow: 0 0 5px #198754;
        }

        .status-dot.inactive {
            background-color: #dc3545;
            box-shadow: 0 0 5px #dc3545;
        }
    </style>
</head>

<body>
    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <div class="glass-card p-4 p-lg-5" data-aos="fade-up" data-aos-duration="1000">
                    <div class="row g-5">

                        <div class="col-md-4">
                            <div class="book-cover-container">
                                <?php
                                // 🔥 แก้ไข Logic รูปภาพ (ให้รองรับทั้ง API และ Upload)
                                $cover = $book['cover_image'];
                                $cover = str_replace(' ', '%20', $cover); // แก้ URL มีช่องว่าง

                                if (strpos($cover, 'http') === 0) {
                                    // กรณีเป็นลิงก์จาก API (ขึ้นต้นด้วย http) -> ใช้ได้เลย
                                    $showImg = $cover;
                                } elseif (!empty($cover)) {
                                    // กรณีเป็นไฟล์ในเครื่อง -> เติม uploads/ ข้างหน้า
                                    $showImg = "uploads/" . $cover;
                                } else {
                                    // กรณีไม่มีรูป -> ใช้รูป Placeholder
                                    $showImg = "https://via.placeholder.com/400x600/eee/999?text=No+Cover";
                                }
                                ?>
                                <img src="<?php echo $showImg; ?>" class="book-cover" alt="Cover"
                                    onerror="this.src='https://via.placeholder.com/400x600/eee/999?text=Image+Error'">
                            </div>

                            <div class="mt-4 text-center p-3 rounded-3 bg-light border border-secondary-subtle">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-label" style="font-size: 0.75rem;">STOCK STATUS</span>
                                    <div>
                                        <?php if ($available_items > 0): ?>
                                            <span class="status-dot active"></span> <span class="text-success fw-bold small">พร้อมยืม</span>
                                        <?php else: ?>
                                            <span class="status-dot inactive"></span> <span class="text-danger fw-bold small">หมดชั่วคราว</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="progress" style="height: 6px; background-color: #e9ecef;">
                                    <?php
                                    $percent = ($total_items > 0) ? ($available_items / $total_items) * 100 : 0;
                                    $color = ($available_items > 0) ? 'bg-success' : 'bg-secondary';
                                    ?>
                                    <div class="progress-bar <?php echo $color; ?>" role="progressbar" style="width: <?php echo $percent; ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-2 small text-secondary fw-bold">
                                    <span>ว่าง: <?php echo $available_items; ?></span>
                                    <span>ทั้งหมด: <?php echo $total_items; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">

                            <div class="mb-4 border-bottom pb-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="isbn-badge mb-2">ISBN: <?php echo $book['isbn']; ?></span>
                                        <h1 class="fw-bold text-dark mb-2"><?php echo $book['title']; ?></h1>

                                        <div class="d-flex gap-3 text-secondary small mb-3">
                                            <span><i class="fa-regular fa-user me-1 text-primary"></i> <?php echo $book['author']; ?></span>
                                            <span><i class="fa-regular fa-building me-1 text-primary"></i> <?php echo $book['publisher']; ?></span>
                                        </div>

                                        <?php if (!empty($book['sample_pdf'])): ?>
                                            <a href="uploads/pdfs/<?php echo $book['sample_pdf']; ?>" target="_blank"
                                                class="btn btn-sm btn-outline-danger rounded-pill px-3 pdf-btn">
                                                <i class="fa-regular fa-file-pdf me-1"></i> ทดลองอ่านตัวอย่าง
                                            </a>
                                        <?php endif; ?>

                                    </div>
                                    <div class="text-end">
                                        <div class="price-tag"><?php echo number_format($book['price'], 0); ?>.-</div>
                                        <div class="text-label text-end" style="font-size: 0.7rem;">THB</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-5">
                                <div class="text-secondary fw-bold mb-3 small"><i class="fa-solid fa-layer-group me-2"></i>SPECIFICATIONS</div>
                                <div class="row g-0">
                                    <div class="col-6 col-md-3">
                                        <div class="spec-box rounded-start-2">
                                            <div class="text-label">จำนวนหน้า</div>
                                            <div class="text-value"><?php echo !empty($book['page_count']) ? $book['page_count'] : '-'; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="spec-box" style="border-left: 0;">
                                            <div class="text-label">รูปแบบกระดาษ</div>
                                            <div class="text-dark fw-bold"><?php echo !empty($book['paper_type']) ? $book['paper_type'] : '-'; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="spec-box" style="border-left: 0;">
                                            <div class="text-label">ประเภทการพิมพ์</div>
                                            <div class="text-dark fw-bold"><?php echo !empty($book['print_type']) ? $book['print_type'] : '-'; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="spec-box rounded-end-2" style="border-left: 0;">
                                            <div class="text-label">ขนาดหนังสือ</div>
                                            <div class="text-dark fw-bold"><?php echo !empty($book['book_size']) ? $book['book_size'] : '-'; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-0 mt-2">
                                    <div class="col-12">
                                        <div class="spec-box d-flex justify-content-between rounded-2">
                                            <span class="text-label">APPROVAL NO.</span>
                                            <span class="text-dark fw-bold"><?php echo !empty($book['approval_no']) ? $book['approval_no'] : '-'; ?> (ลำดับที่ <?php echo !empty($book['approval_order']) ? $book['approval_order'] : '-'; ?>)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-3 mt-auto">
                                <?php if ($available_items > 0): ?>
                                    <button onclick="confirmBorrowDetail(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')"
                                        class="btn btn-custom-primary flex-grow-1 shadow-sm">
                                        <i class="fa-solid fa-book-open me-2"></i> ยืมหนังสือ
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary flex-grow-1" disabled>
                                        <i class="fa-solid fa-lock me-2"></i> หนังสือหมด (Out of Stock)
                                    </button>
                                <?php endif; ?>

                                <a href="index.php" class="btn btn-outline-custom">
                                    <i class="fa-solid fa-arrow-left"></i>
                                </a>

                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                    <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-outline-warning border-warning d-flex align-items-center justify-content-center gap-2">
                                        <i class="fa-solid fa-pen"></i> <span>แก้ไข</span>
                                    </a>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        AOS.init({
            duration: 800,
            once: true
        });

        /* Particles Config (Blue Dots) */
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
                /* สีฟ้า */
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
                }
            },
            "retina_detect": true
        });

        /* SweetAlert (Default Light Theme) */
        function confirmBorrowDetail(id, title) {
            Swal.fire({
                title: 'ยืนยันการยืม?',
                text: "ต้องการยืมหนังสือ: " + title,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, ขอยืมเลย',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'borrow_save.php?id=' + id;
                }
            })
        }
    </script>
</body>

</html>