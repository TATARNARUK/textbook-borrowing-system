<?php
session_start();
require_once 'config.php';

// 1. เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$msg_type = '';

// 2. บันทึกเปลี่ยนรหัสผ่าน
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $target_user_id = $_POST['user_id'];
    $new_password = $_POST['new_pass'];

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $target_user_id])) {
            $message = "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว!";
            $msg_type = "success";
        } else {
            $message = "เกิดข้อผิดพลาดในการบันทึก";
            $msg_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - Admin</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* --- 🎨 White & Blue Theme CSS --- */
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #f0f4f8; /* พื้นหลังสีเทาอมฟ้าอ่อน */
            background-image: radial-gradient(#dbeafe 1px, transparent 1px); /* ลายจุดจางๆ */
            background-size: 20px 20px;
            color: #333;
            overflow-x: hidden;
        }

        #particles-js {
            position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: -1; pointer-events: none;
        }

        /* --- White Card --- */
        .glass-card {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(13, 110, 253, 0.1); /* เงาสีฟ้าจางๆ */
            padding: 30px;
            position: relative;
            z-index: 1;
        }

        /* --- Table Styling (Light Theme) --- */
        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .table-custom thead th {
            background-color: #e7f1ff;
            color: #0d6efd;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: none;
            padding: 15px;
        }
        /* มุมโค้งหัวตาราง */
        .table-custom thead th:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-custom thead th:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .table-custom tbody tr {
            background-color: #fff;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .table-custom tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.1);
            background-color: #f8f9fa;
        }

        .table-custom td {
            border: 1px solid #f0f0f0;
            border-width: 1px 0;
            padding: 15px;
            vertical-align: middle;
            color: #555;
        }
        .table-custom td:first-child { border-left: 1px solid #f0f0f0; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-custom td:last-child { border-right: 1px solid #f0f0f0; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        /* --- DataTables Overrides --- */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: #6c757d !important;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        
        .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }
        .page-link { color: #0d6efd; }

        /* --- Badges --- */
        .role-badge {
            padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px;
        }
        .role-admin { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .role-user { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }

        /* --- Buttons --- */
        .btn-custom-primary {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            color: #fff; border: none; font-weight: 600;
            border-radius: 10px; padding: 8px 20px;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(13, 110, 253, 0.3);
            color: #fff;
        }

        .btn-outline-custom {
            background: transparent; color: #0d6efd; border: 1px solid #0d6efd;
            border-radius: 10px; font-weight: 600;
            transition: all 0.3s;
        }
        .btn-outline-custom:hover { background: #0d6efd; color: #fff; }

        .btn-icon-only {
            width: 35px; height: 35px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            background: #e7f1ff; color: #0d6efd;
            border: none; transition: all 0.3s;
        }
        .btn-icon-only:hover {
            background: #0d6efd; color: #fff; transform: scale(1.1);
        }

        /* --- Modal --- */
        .modal-content {
            border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .modal-header { background-color: #0d6efd; color: white; border-top-left-radius: 15px; border-top-right-radius: 15px; }
        .btn-close { filter: invert(1); }
    </style>
</head>

<body>
    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>

    <div class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-down">
            <div>
                <h3 class="fw-bold text-primary mb-0" style="letter-spacing: 1px;">
                    <i class="fa-solid fa-users-gear me-2"></i>MANAGE USERS
                </h3>
                <small class="text-muted">จัดการข้อมูลสมาชิกและรหัสผ่าน</small>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-custom">
                    <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
                </a>
                <a href="sync_students.php" class="btn btn-custom-primary" onclick="return confirm('⚠️ ยืนยันการดึงข้อมูลจาก RMS?');">
                    <i class="fa-solid fa-cloud-arrow-down me-2"></i> SYNC RMS
                </a>
            </div>
        </div>

        <div class="glass-card" data-aos="fade-up">
            <div class="table-responsive">
                <table id="userTable" class="table-custom">
                    <thead>
                        <tr>
                            <th width="20%">USERNAME / ID</th>
                            <th width="30%">FULL NAME</th>
                            <th width="20%">PHONE</th>
                            <th width="15%">ROLE</th>
                            <th width="15%" class="text-center">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, id DESC");
                        while ($row = $stmt->fetch()) {
                        ?>
                            <tr>
                                <td>
                                    <span class="font-monospace text-primary fw-bold"><?php echo $row['student_id']; ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 rounded-circle me-2 d-flex justify-content-center align-items-center" style="width:35px; height:35px;">
                                            <i class="fa-regular fa-user text-primary"></i>
                                        </div>
                                        <span class="fw-bold text-dark"><?php echo $row['fullname']; ?></span>
                                    </div>
                                </td>
                                <td><?php echo $row['phone'] ? $row['phone'] : '<span class="text-muted small">-</span>'; ?></td>
                                <td>
                                    <?php if ($row['role'] == 'admin'): ?>
                                        <span class="role-badge role-admin"><i class="fa-solid fa-shield-halved me-1"></i> Admin</span>
                                    <?php else: ?>
                                        <span class="role-badge role-user">Student</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button onclick="openResetModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['fullname']); ?>')"
                                            class="btn-icon-only" title="เปลี่ยนรหัสผ่าน">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="modal fade" id="resetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-lock me-2"></i> RESET PASSWORD
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="reset_password" value="1">
                        <input type="hidden" name="user_id" id="modal_user_id">

                        <div class="text-center mb-4">
                            <div class="bg-primary bg-opacity-10 d-inline-block rounded-circle p-3 mb-2">
                                <i class="fa-solid fa-user-lock fa-2x text-primary"></i>
                            </div>
                            <div class="text-muted small mb-1">กำลังเปลี่ยนรหัสผ่านให้</div>
                            <h5 class="text-dark fw-bold" id="modal_user_name">...</h5>
                        </div>

                        <div class="mb-3">
                            <label class="text-secondary fw-bold small mb-2">NEW PASSWORD</label>
                            <input type="text" name="new_pass" class="form-control" required placeholder="กรอกรหัสผ่านใหม่..." autocomplete="off">
                            <div class="form-text text-muted mt-2">* กรอกรหัสใหม่ที่ต้องการให้ผู้ใช้งาน</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill btn-sm px-3" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary rounded-pill btn-sm fw-bold px-4">บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        AOS.init({ duration: 800, once: true });

        // Particles สีฟ้า
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 60 },
                "color": { "value": "#0d6efd" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.5, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#0d6efd", "opacity": 0.2, "width": 1 },
                "move": { "enable": true, "speed": 2 }
            },
            "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": false } } },
            "retina_detect": true
        });

        // DataTable Config
        $(document).ready(function() {
            $('#userTable').DataTable({
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ คน",
                    paginate: { first: "หน้าแรก", last: "สุดท้าย", next: "ถัดไป", previous: "ก่อนหน้า" },
                    zeroRecords: "ไม่พบข้อมูล"
                },
                dom: '<"d-flex justify-content-between mb-3"lf>rt<"d-flex justify-content-between mt-3"ip>'
            });
        });

        // Modal Logic
        function openResetModal(id, name) {
            document.getElementById('modal_user_id').value = id;
            document.getElementById('modal_user_name').innerText = name;
            var myModal = new bootstrap.Modal(document.getElementById('resetModal'));
            myModal.show();
        }

        // SweetAlert (Light Theme)
        <?php if ($message): ?>
            Swal.fire({
                icon: '<?php echo $msg_type; ?>',
                title: '<?php echo $msg_type == "success" ? "สำเร็จ!" : "ผิดพลาด"; ?>',
                text: '<?php echo $message; ?>',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'ตกลง'
            });
        <?php endif; ?>
    </script>
</body>

</html>