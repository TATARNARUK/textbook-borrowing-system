<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 1. เพิ่มหมวดหมู่
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
    // เช็คก่อนว่ามีหนังสือใช้หมวดนี้ไหม?
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM book_masters WHERE category_id = ?");
    $stmtCheck->execute([$id]);
    if ($stmtCheck->fetchColumn() > 0) {
        header("Location: manage_categories.php?status=error_used"); // ลบไม่ได้ มีหนังสือใช้อยู่
    } else {
        $stmtDel = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmtDel->execute([$id]);
        header("Location: manage_categories.php?status=deleted");
    }
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

        /* 🔥 2. CSS สำหรับพื้นหลัง Particles */
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
        }
    </style>
</head>

<body>

    <div id="particles-js"></div>

    <div class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-down" data-aos-duration="1000">
            <h3>📂 จัดการหมวดหมู่หนังสือ</h3>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill"><i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก</a>
        </div>

        <div class="row">
            <div class="col-md-4 mb-4" data-aos="fade-right" data-aos-delay="100" data-aos-duration="1000">
                <div class="card p-4">
                    <h5 class="fw-bold mb-3">เพิ่มหมวดหมู่ใหม่</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">ชื่อหมวดหมู่</label>
                            <input type="text" name="cat_name" class="form-control rounded-pill" required placeholder="เช่น วิทยาศาสตร์">
                        </div>
                        <button type="submit" name="add_cat" class="btn btn-primary w-100 rounded-pill">
                            <i class="fa-solid fa-plus"></i> บันทึกข้อมูล
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-8" data-aos="fade-left" data-aos-delay="200" data-aos-duration="1000">
                <div class="card p-4">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ชื่อหมวดหมู่</th>
                                <th class="text-center" width="150">จำนวนหนังสือ</th>
                                <th class="text-center" width="100">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM book_masters b WHERE b.category_id = c.id) as book_count FROM categories c ORDER BY id DESC");
                            while ($row = $stmt->fetch()) { ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info text-dark rounded-pill"><?php echo $row['book_count']; ?> เล่ม</span>
                                    </td>
                                    <td class="text-center">
                                        <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-sm btn-outline-danger border-0">
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        // เริ่มต้น AOS
        AOS.init({
            duration: 800,
            once: true
        });

        // เริ่มต้น Particles
        particlesJS("particles-js", {
            "particles": {
                "number": {
                    "value": 80,
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
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push"
                    }
                }
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
                confirmButtonText: 'ลบเลย',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'manage_categories.php?delete=' + id;
                }
            })
        }

        // เช็ค Alert จาก URL
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        if (status === 'added') Swal.fire('สำเร็จ', 'เพิ่มหมวดหมู่เรียบร้อย', 'success').then(() => window.history.replaceState(null, null, window.location.pathname));
        else if (status === 'deleted') Swal.fire('สำเร็จ', 'ลบข้อมูลแล้ว', 'success').then(() => window.history.replaceState(null, null, window.location.pathname));
        else if (status === 'error_used') Swal.fire('ลบไม่ได้', 'หมวดหมู่นี้มีหนังสืออยู่ กรุณาย้ายหมวดหมู่หนังสือก่อน', 'error');
    </script>
</body>

</html>