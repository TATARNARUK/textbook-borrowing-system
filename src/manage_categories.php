<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 1. เพิ่มหมวดหมู่ (Manual)
if (isset($_POST['add_cat'])) {
    $name = trim($_POST['cat_name']);
    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        header("Location: manage_categories.php?status=added");
        exit();
    }
}

// 2. ลบหมวดหมู่
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM book_masters WHERE category_id = ?");
    $stmtCheck->execute([$id]);
    if ($stmtCheck->fetchColumn() > 0) {
        header("Location: manage_categories.php?status=error_used");
    } else {
        $stmtDel = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmtDel->execute([$id]);
        header("Location: manage_categories.php?status=deleted");
    }
    exit();
}

// 3. แก้ไขหมวดหมู่
if (isset($_POST['edit_cat'])) {
    $id = $_POST['edit_id'];
    $name = trim($_POST['edit_name']);
    if ($name && $id) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        header("Location: manage_categories.php?status=edited");
        exit();
    }
}

// 🌟 4. ระบบจัดหมวดหมู่อัตโนมัติ (และสร้างหมวดหมู่ใหม่ให้เอง)
if (isset($_POST['auto_categorize'])) {
    
    // ดึงหนังสือทั้งหมดมาตรวจสอบ
    $books = $pdo->query("SELECT id, title, category_id FROM book_masters")->fetchAll();

    $updateCount = 0;
    $newCatCount = 0;

    foreach ($books as $book) {
        $matched_cat_id = null;
        $book_title = strtolower(trim($book['title']));

        // ดึงข้อมูลหมวดหมู่ทั้งหมด (อัปเดตใหม่ทุกรอบเผื่อมีหมวดถูกสร้างใหม่)
        $cats = $pdo->query("SELECT id, name FROM categories")->fetchAll();

        // 1. ลองหาว่าชื่อหนังสือมีคำตรงกับ "หมวดหมู่ที่มีอยู่แล้ว" หรือไม่
        foreach ($cats as $cat) {
            $cat_name = strtolower(trim($cat['name']));
            // กรณีชื่อหมวดหมู่อยู่ในชื่อหนังสือ (เช่น หนังสือ "ภาษาอังกฤษเบื้องต้น" ตรงกับหมวด "ภาษา")
            if (strpos($book_title, $cat_name) !== false) {
                $matched_cat_id = $cat['id'];
                break;
            }
        }

        // 🔥 2. ถ้าหาหมวดที่มีอยู่ไม่เจอเลย -> "สร้างหมวดหมู่ใหม่จากชื่อหนังสือ"
        if (!$matched_cat_id) {
            
            // ใช้เทคนิคดึงคำแรก หรือ ตัดคำจากชื่อหนังสือมาตั้งเป็นชื่อหมวด
            $words = explode(" ", $book['title']); // ตัดคำด้วยช่องว่าง
            $first_word = trim($words[0]);
            
            // ป้องกันกรณีคำสั้นเกินไปหรือไม่มีความหมาย
            if (mb_strlen($first_word, 'UTF-8') < 3) {
                $new_cat_name = "อื่นๆ (" . mb_substr($book['title'], 0, 10, 'UTF-8') . "...)";
            } else {
                // เอาคำแรกที่ตัดมาได้ไปตั้งเป็นชื่อหมวดหมู่
                $new_cat_name = "หมวด " . $first_word; 
            }

            // เช็คก่อนว่าไอ้หมวดที่กำลังจะสร้างใหม่เนี้ย มันมีคนอื่นสร้างไปก่อนหน้าในลูปนี้ไหม
            $stmtCheckNew = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmtCheckNew->execute([$new_cat_name]);
            $existingNewCat = $stmtCheckNew->fetch();

            if ($existingNewCat) {
                // ถ้ามีแล้วก็เอา ID มาใช้
                $matched_cat_id = $existingNewCat['id'];
            } else {
                // ถ้ายืนยันว่าไม่มีจริงๆ ก็ INSERT สร้างหมวดหมู่ใหม่ลงฐานข้อมูลเลย!
                $stmtInsert = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmtInsert->execute([$new_cat_name]);
                $matched_cat_id = $pdo->lastInsertId();
                $newCatCount++;
            }
        }

        // 3. ทำการ Update หนังสือให้เข้าไปอยู่ในหมวดหมู่ที่หาเจอ(หรือเพิ่งสร้าง)
        if ($matched_cat_id != $book['category_id']) {
            $stmtUpdate = $pdo->prepare("UPDATE book_masters SET category_id = ? WHERE id = ?");
            $stmtUpdate->execute([$matched_cat_id, $book['id']]);
            $updateCount++;
        }
    }

    header("Location: manage_categories.php?status=auto_success&count=" . $updateCount . "&newcats=" . $newCatCount);
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหมวดหมู่ - Admin</title>
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

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .table-hover tbody tr:hover {
            background-color: #f1f8ff;
        }

        .btn-magic {
            background: linear-gradient(45deg, #8e2de2, #4a00e0);
            color: white;
            border: none;
        }
        .btn-magic:hover {
            background: linear-gradient(45deg, #4a00e0, #8e2de2);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(142, 45, 226, 0.4);
            transition: all 0.3s ease;
        }
    </style>
</head>

<body>

    <div id="particles-js"></div>

    <div class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-down" data-aos-duration="1000">
            <h3 class="fw-bold text-primary"><i class="fa-solid fa-folder-tree me-2"></i> จัดการหมวดหมู่หนังสือ</h3>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill fw-bold"><i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก</a>
        </div>

        <div class="row">
            <div class="col-md-4 mb-4" data-aos="fade-right" data-aos-delay="100" data-aos-duration="1000">
                <div class="card p-4 h-100">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus-circle text-primary"></i> เพิ่มหมวดหมู่ใหม่</h5>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">ชื่อหมวดหมู่</label>
                            <input type="text" name="cat_name" class="form-control form-control-lg rounded-pill fs-6" required placeholder="เช่น วิทยาศาสตร์, นิยาย">
                        </div>
                        <button type="submit" name="add_cat" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm">
                            <i class="fa-solid fa-save me-1"></i> บันทึกข้อมูล
                        </button>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <h6 class="fw-bold text-dark"><i class="fa-solid fa-robot text-purple"></i> ระบบจัดหมวดหมู่อัจฉริยะ</h6>
                        <p class="text-muted small mb-3">หากมีหนังสือที่ไม่มีหมวดหมู่ ระบบจะทำการ <b>"สร้างหมวดหมู่ใหม่จากชื่อหนังสือ"</b> ให้โดยอัตโนมัติ!</p>
                        <form method="POST" id="autoCatForm">
                            <button type="button" onclick="confirmAutoCat()" class="btn btn-magic w-100 rounded-pill fw-bold py-2 shadow-sm">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> สร้างและจัดหมวดหมู่อัตโนมัติ
                            </button>
                            <input type="hidden" name="auto_categorize" value="1">
                        </form>
                    </div>

                </div>
            </div>

            <div class="col-md-8" data-aos="fade-left" data-aos-delay="200" data-aos-duration="1000">
                <div class="card p-4">
                    <div class="table-responsive">
                        <table id="categoryTable" class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ชื่อหมวดหมู่</th>
                                    <th class="text-center" width="150">จำนวนหนังสือ</th>
                                    <th class="text-center" width="120">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM book_masters b WHERE b.category_id = c.id) as book_count FROM categories c ORDER BY id DESC");
                                while ($row = $stmt->fetch()) { ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td class="text-center">
                                            <?php if ($row['book_count'] > 0): ?>
                                                <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo $row['book_count']; ?> เล่ม</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary rounded-pill px-3 py-2">0 เล่ม</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-warning border-0 me-1 edit-btn" 
                                                    data-id="<?php echo $row['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($row['name']); ?>" 
                                                    title="แก้ไข">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            
                                            <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-sm btn-outline-danger border-0" title="ลบ">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade text-start" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen text-warning me-2"></i>แก้ไขหมวดหมู่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">ชื่อหมวดหมู่</label>
                            <input type="text" name="edit_name" id="edit_name" class="form-control rounded-pill form-control-lg fs-6" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="edit_cat" class="btn btn-warning rounded-pill px-4 text-white fw-bold shadow-sm">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#categoryTable').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json" },
                "pageLength": 10,
                "ordering": false
            });

            $('.edit-btn').on('click', function() {
                var catId = $(this).data('id');
                var catName = $(this).data('name');
                $('#edit_id').val(catId);
                $('#edit_name').val(catName);
                $('#editModal').modal('show');
            });
        });

        AOS.init({ duration: 800, once: true });

        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#0d6efd" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.5, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#0d6efd", "opacity": 0.2, "width": 1 },
                "move": { "enable": true, "speed": 2 }
            },
            "interactivity": {
                "events": { "onhover": { "enable": true, "mode": "grab" }, "onclick": { "enable": true, "mode": "push" } }
            },
            "retina_detect": true
        });

        function confirmDelete(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "หากลบแล้วจะไม่สามารถกู้คืนได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, ลบเลย',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'manage_categories.php?delete=' + id;
                }
            })
        }

        function confirmAutoCat() {
            Swal.fire({
                title: 'สร้างหมวดหมู่อัตโนมัติ',
                html: "ระบบจะทำการ: <br>1. จัดหนังสือเข้าหมวดเดิมที่มีอยู่<br>2. <b>ดึงชื่อหนังสือมาสร้างเป็นหมวดหมู่ใหม่ให้เอง</b> สำหรับหนังสือที่ตกหล่น",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#8e2de2',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ตกลง ดำเนินการเลย',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังประมวลผล...',
                        text: 'กรุณารอสักครู่ ระบบกำลังสร้างและจัดหมวดหมู่ให้คุณ',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading() }
                    });
                    document.getElementById('autoCatForm').submit();
                }
            })
        }

        // เช็คข้อความแจ้งเตือน (Alert)
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const count = urlParams.get('count');
        const newcats = urlParams.get('newcats');

        if (status === 'added') Swal.fire('สำเร็จ', 'เพิ่มหมวดหมู่เรียบร้อย', 'success').then(() => window.history.replaceState(null, null, window.location.pathname));
        else if (status === 'edited') Swal.fire('สำเร็จ', 'แก้ไขหมวดหมู่เรียบร้อย', 'success').then(() => window.history.replaceState(null, null, window.location.pathname));
        else if (status === 'deleted') Swal.fire('สำเร็จ', 'ลบข้อมูลแล้ว', 'success').then(() => window.history.replaceState(null, null, window.location.pathname));
        else if (status === 'error_used') Swal.fire('ลบไม่ได้', 'หมวดหมู่นี้มีหนังสืออยู่ กรุณาย้ายหมวดหมู่หนังสือก่อน', 'error').then(() => window.history.replaceState(null, null, window.location.pathname));
        else if (status === 'auto_success') {
            Swal.fire({
                title: 'อัปเดตเรียบร้อย!',
                html: `ระบบทำการจัดหมวดหมู่ให้หนังสือจำนวน <b>${count}</b> เล่ม <br> และสร้างหมวดหมู่ใหม่ขึ้นมา <b>${newcats}</b> หมวด`,
                icon: 'success'
            }).then(() => window.history.replaceState(null, null, window.location.pathname));
        }
    </script>
</body>

</html>