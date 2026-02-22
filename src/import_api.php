<?php
session_start();
require_once 'config.php';

// 1. เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// -------------------------------------------------------------
// ฟังก์ชันสำหรับแปลงลิงก์ Google Drive ให้เป็นแบบ Embed/Preview
// -------------------------------------------------------------
function convertGoogleDriveLink($url) {
    if (empty($url)) return '';

    if (strpos($url, 'drive.google.com/file/d/') !== false) {
        preg_match('/d\/(.*?)\//', $url, $matches);
        if (isset($matches[1])) {
            $fileId = $matches[1];
            return "https://drive.google.com/file/d/{$fileId}/preview";
        }
    }
    return $url;
}

// 2. ฟังก์ชันดึงข้อมูล API
function getBooksFromApi()
{
    $url = "https://itdev.bncc.ac.th/vbss/Education_system/api/v1.php?path=get_book";
    $apiKey = "76802395e80ea1ef8147f683e59f9c62";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-key: $apiKey"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return json_decode($response, true);
    } else {
        return null;
    }
}

// ดึงข้อมูลมาเตรียมไว้
$apiResult = getBooksFromApi();
$books = isset($apiResult['data']) ? $apiResult['data'] : [];

// --- ส่วนทำงานเมื่อมีการกดปุ่ม (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function saveBook($pdo, $data)
    {
        // Map ข้อมูล
        $title = $data['name'] ?? '-';
        $isbn  = (!empty($data['isbn']) && $data['isbn'] != '-') ? $data['isbn'] : ($data['code'] ?? 'NO-CODE');
        $author = $data['author'] ?? '-';
        $price = $data['price'] ?? 0;

        // รูปภาพ
        $cover = $data['image'] ?? '';
        $cover = str_replace(' ', '%20', $cover);
        if (!empty($cover) && strpos($cover, 'http') === false) {
            $cover = 'https://itdev.bncc.ac.th/vbss/Education_system/other/img/uploads/' . $cover;
        }

        // 🔥 PDF (จุดสำคัญ) : แปลงลิงก์ก่อนบันทึกลง Database
        $raw_pdf = $data['linkExp'] ?? '';
        $pdf = convertGoogleDriveLink($raw_pdf);

        $desc = $data['detail'] ?? '';
        $pages = $data['countPage'] ?? 0;
        $paper = $data['paperFormat'] ?? '-';
        $print = $data['color'] ?? '-';
        $size  = $data['size'] ?? '-';
        $app_no = $data['approval_time'] ?? '-';
        $app_order = $data['approval_number'] ?? '-';

        // 1. เช็คว่ามี ISBN นี้ในระบบหรือยัง?
        $stmtCheck = $pdo->prepare("SELECT id FROM book_masters WHERE isbn = ?");
        $stmtCheck->execute([$isbn]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            // 🔥 กรณีมีอยู่แล้ว: ให้อัปเดตไฟล์ PDF (เผื่อของเก่าไม่มี)
            if (!empty($pdf)) {
                $sqlUpdate = "UPDATE book_masters SET sample_pdf = ? WHERE id = ?";
                $pdo->prepare($sqlUpdate)->execute([$pdf, $existingId]);
            }
            return true;
        } else {
            // 2. กรณียังไม่มี: เพิ่มใหม่
            $sql = "INSERT INTO book_masters 
                    (title, author, isbn, publisher, price, cover_image, sample_pdf, description, 
                     page_count, paper_type, print_type, book_size, approval_no, approval_order) 
                    VALUES (?, ?, ?, '-', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $author, $isbn, $price, $cover, $pdf, $desc, $pages, $paper, $print, $size, $app_no, $app_order]);
            return true;
        }
    }

    // กรณี 1: นำเข้าทีละเล่ม
    if (isset($_POST['import_book'])) {
        $bookData = [
            'name' => $_POST['title'],
            'author' => $_POST['author'],
            'isbn' => $_POST['isbn'],
            'code' => $_POST['isbn'],
            'price' => $_POST['price'],
            'image' => $_POST['cover'],
            'linkExp' => $_POST['pdf'], // ส่งค่า PDF มาด้วย
            'detail' => $_POST['description'],
            'countPage' => $_POST['pages'],
            'paperFormat' => $_POST['paper'],
            'color' => $_POST['print'],
            'size' => $_POST['size'],
            'approval_time' => $_POST['app_no'],
            'approval_number' => $_POST['app_order']
        ];

        if (saveBook($pdo, $bookData)) {
            echo "<script>setTimeout(function() { Swal.fire('สำเร็จ!', 'นำเข้า/อัปเดต ข้อมูลเรียบร้อย', 'success'); }, 500);</script>";
        } else {
            echo "<script>setTimeout(function() { Swal.fire('แจ้งเตือน', 'เกิดข้อผิดพลาด', 'warning'); }, 500);</script>";
        }
    }

    // กรณี 2: นำเข้าทั้งหมด
    if (isset($_POST['import_all'])) {
        $count = 0;
        foreach ($books as $book) {
            if (saveBook($pdo, $book)) {
                $count++;
            }
        }
        echo "<script>setTimeout(function() { Swal.fire('เสร็จสิ้น!', 'ประมวลผลหนังสือจำนวน $count เล่ม เรียบร้อย', 'success'); }, 500);</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>นำเข้าหนังสือจาก API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #f8f9fa;
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

        .card-book {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-book:hover {
            transform: translateY(-5px);
        }

        .book-cover {
            height: 220px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .btn-import-all {
            background: linear-gradient(45deg, #198754, #20c997);
            color: white;
            border: none;
            font-weight: bold;
        }

        .btn-import-all:hover {
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.4);
            transform: translateY(-2px);
            color: white;
        }
    </style>
</head>

<body>

    <div id="particles-js"></div>

    <div class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm" data-aos="fade-down" data-aos-duration="1000">
            <div>
                <h3 class="fw-bold m-0"><i class="fa-solid fa-cloud-arrow-down text-primary"></i> นำเข้าหนังสือจาก API</h3>
                <small class="text-muted">พบหนังสือทั้งหมด <?php echo count($books); ?> เล่ม</small>
            </div>
            <div class="d-flex gap-2">
                <form method="POST" onsubmit="return confirm('ยืนยันการนำเข้าข้อมูล?\n(ระบบจะเพิ่มเล่มใหม่ และอัปเดต PDF ให้เล่มเดิม)');">
                    <button type="submit" name="import_all" class="btn btn-import-all rounded-pill px-4">
                        <i class="fa-solid fa-layer-group me-2"></i> นำเข้า / อัปเดต ทั้งหมด
                    </button>
                </form>

                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="fa-solid fa-arrow-left me-2"></i> กลับหน้าหลัก
                </a>
            </div>
        </div>

        <?php if (!empty($books)): ?>
            <div class="row g-4">
                <?php foreach ($books as $book):
                    $b_title = $book['name'] ?? '-';
                    $b_isbn  = (!empty($book['isbn']) && $book['isbn'] != '-') ? $book['isbn'] : $book['code'];
                    $b_author = $book['author'] ?? '-';
                    $b_price = $book['price'] ?? 0;

                    $b_img_raw = $book['image'] ?? '';
                    $b_img = str_replace(' ', '%20', $b_img_raw);
                    if (!empty($b_img) && strpos($b_img, 'http') === false) {
                        $b_img = 'https://itdev.bncc.ac.th/vbss/Education_system/other/img/uploads/' . $b_img;
                    }

                    // 🔥 PDF Link (แปลงลิงก์ก่อนส่งไปโชว์หน้าเว็บ)
                    $raw_b_pdf = $book['linkExp'] ?? '';
                    $b_pdf = convertGoogleDriveLink($raw_b_pdf);

                    $b_desc = $book['detail'] ?? '';
                    $b_pages = $book['countPage'] ?? 0;
                    $b_paper = $book['paperFormat'] ?? '-';
                    $b_print = $book['color'] ?? '-';
                    $b_size  = $book['size'] ?? '-';
                    $b_app_time = $book['approval_time'] ?? '-';
                    $b_app_num  = $book['approval_number'] ?? '-';
                ?>
                    <div class="col-md-3" data-aos="fade-up" data-aos-duration="800">
                        <div class="card card-book h-100">
                            <?php if ($b_img): ?>
                                <img src="<?php echo $b_img; ?>" class="book-cover card-img-top" onerror="this.src='https://via.placeholder.com/300x450?text=No+Image'">
                            <?php else: ?>
                                <div class="bg-light text-center py-5 text-muted book-cover d-flex align-items-center justify-content-center">ไม่มีรูป</div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title fw-bold text-truncate" title="<?php echo htmlspecialchars($b_title); ?>">
                                    <?php echo $b_title; ?>
                                </h6>
                                <div class="mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary"><?php echo $b_isbn; ?></span>
                                    <span class="badge bg-success bg-opacity-10 text-success"><?php echo $b_price; ?> บ.</span>
                                </div>
                                <p class="card-text small text-muted mb-2 text-truncate">
                                    <i class="fa-solid fa-user-pen"></i> <?php echo $b_author; ?>
                                </p>

                                <?php if ($b_pdf): ?>
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-sm btn-danger w-100 text-white shadow-sm" 
                                                onclick="viewPDF('<?php echo htmlspecialchars($b_pdf); ?>', '<?php echo htmlspecialchars($b_title, ENT_QUOTES); ?>')">
                                            <i class="fa-regular fa-file-pdf"></i> อ่าน E-Book
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-3">
                                        <button class="btn btn-sm btn-light border w-100 text-muted" disabled>ไม่มีไฟล์ E-Book</button>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-auto">
                                    <form method="POST">
                                        <input type="hidden" name="title" value="<?php echo htmlspecialchars($b_title); ?>">
                                        <input type="hidden" name="author" value="<?php echo htmlspecialchars($b_author); ?>">
                                        <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($b_isbn); ?>">
                                        <input type="hidden" name="price" value="<?php echo htmlspecialchars($b_price); ?>">
                                        <input type="hidden" name="cover" value="<?php echo htmlspecialchars($b_img); ?>">
                                        <input type="hidden" name="pdf" value="<?php echo htmlspecialchars($raw_b_pdf); ?>"> <input type="hidden" name="description" value="<?php echo htmlspecialchars($b_desc); ?>">
                                        <input type="hidden" name="pages" value="<?php echo htmlspecialchars($b_pages); ?>">
                                        <input type="hidden" name="paper" value="<?php echo htmlspecialchars($b_paper); ?>">
                                        <input type="hidden" name="print" value="<?php echo htmlspecialchars($b_print); ?>">
                                        <input type="hidden" name="size" value="<?php echo htmlspecialchars($b_size); ?>">
                                        <input type="hidden" name="app_no" value="<?php echo htmlspecialchars($b_app_time); ?>">
                                        <input type="hidden" name="app_order" value="<?php echo htmlspecialchars($b_app_num); ?>">

                                        <?php
                                        $chk = $pdo->prepare("SELECT id FROM book_masters WHERE isbn = ?");
                                        $chk->execute([$b_isbn]);
                                        if ($chk->fetch()) {
                                            echo '<button type="submit" name="import_book" class="btn btn-warning w-100 btn-sm rounded-pill shadow-sm text-dark"><i class="fa-solid fa-rotate"></i> อัปเดตข้อมูล</button>';
                                        } else {
                                            echo '<button type="submit" name="import_book" class="btn btn-primary w-100 btn-sm rounded-pill shadow-sm"><i class="fa-solid fa-download"></i> นำเข้าข้อมูล</button>';
                                        }
                                        ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center p-5 rounded-4" data-aos="zoom-in">
                <i class="fa-solid fa-circle-exclamation fa-3x mb-3 text-warning"></i><br>
                <h4 class="fw-bold">ไม่พบข้อมูลหนังสือ</h4>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow-lg" style="height: 90vh; border-radius: 15px; overflow: hidden;">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold" id="pdfModalTitle"><i class="fa-solid fa-book-open me-2"></i> อ่านหนังสือ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closePDF()"></button>
                </div>
                <div class="modal-body p-0 bg-light position-relative">
                    <div id="pdfLoader" class="position-absolute top-50 start-50 translate-middle text-center text-muted">
                        <div class="spinner-border mb-2" role="status"></div>
                        <p>กำลังโหลดเอกสาร...</p>
                    </div>
                    <iframe id="pdfIframe" src="" width="100%" height="100%" style="border:none; position: relative; z-index: 2;" allow="autoplay"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        AOS.init({
            duration: 800,
            once: true
        });

        // 🔥 ฟังก์ชันสำหรับเปิดหน้าต่าง PDF
        function viewPDF(pdfUrl, title) {
            document.getElementById('pdfModalTitle').innerText = title;
            document.getElementById('pdfIframe').style.opacity = '0'; // ซ่อน Iframe ไว้แปปนึงตอนโหลด
            document.getElementById('pdfIframe').src = pdfUrl; // ใส่ URL ที่แปลงแล้ว

            // แสดง Modal
            var myModal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
            myModal.show();

            // เมื่อ iframe โหลดเสร็จ ค่อยโชว์
            document.getElementById('pdfIframe').onload = function() {
                document.getElementById('pdfIframe').style.opacity = '1';
                document.getElementById('pdfLoader').style.display = 'none';
            };
        }

        // ฟังก์ชันล้างค่าตอนปิด เพื่อไม่ให้กินเน็ต/ค้าง
        function closePDF() {
            document.getElementById('pdfIframe').src = "";
            document.getElementById('pdfLoader').style.display = 'block';
        }

        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 160, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#0d6efd" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.5, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#0d6efd", "opacity": 0.2, "width": 1 },
                "move": { "enable": true, "speed": 2 }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": { "onhover": { "enable": true, "mode": "grab" } },
                "onclick": { "enable": true, "mode": "push" }
            },
            "retina_detect": true
        });
    </script>
</body>

</html>