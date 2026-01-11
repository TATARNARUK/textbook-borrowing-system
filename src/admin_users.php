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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* --- Dark Theme Base --- */
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #000000;
            color: #e0e0e0;
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

        /* --- Glass Card --- */
        .glass-card {
            background: rgba(15, 15, 15, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8);
            padding: 30px;
        }

        /* --- Modern Table --- */
        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .table-custom thead th {
            color: #777;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            padding-bottom: 15px;
        }

        .table-custom tbody tr {
            background-color: rgba(255, 255, 255, 0.03);
            transition: all 0.2s;
        }

        .table-custom tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08);
            transform: scale(1.005);
        }

        .table-custom td {
            border: none;
            padding: 15px;
            vertical-align: middle;
            color: #ccc;
        }

        .table-custom td:first-child {
            border-top-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }

        .table-custom td:last-child {
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
        }

        /* --- DataTables Overrides --- */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: #aaa !important;
            margin-top: 15px;
        }

        .dataTables_wrapper .form-select,
        .dataTables_wrapper .form-control {
            background-color: #111;
            border: 1px solid #333;
            color: #fff;
        }

        .page-item.active .page-link {
            background-color: #fff;
            border-color: #fff;
            color: #000;
        }

        .page-link {
            background-color: #111;
            border-color: #333;
            color: #fff;
        }

        .page-link:hover {
            background-color: #333;
            border-color: #333;
            color: #fff;
        }

        /* --- Badges --- */
        .role-badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .role-admin {
            background: rgba(220, 53, 69, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .role-user {
            background: rgba(25, 135, 84, 0.15);
            color: #2ecc71;
            border: 1px solid rgba(25, 135, 84, 0.3);
        }

        /* --- Buttons --- */
        .btn-monochrome {
            background: #fff;
            color: #000;
            border: 1px solid #fff;
            font-weight: 600;
            padding: 8px 20px;
            transition: all 0.3s;
        }

        .btn-monochrome:hover {
            background: #000;
            color: #fff;
        }

        .btn-icon-only {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
        }

        .btn-icon-only:hover {
            background: #fff;
            color: #000;
        }

        .btn-back {
            background: transparent;
            border: 1px solid #fff;
            color: #fff;
            border-radius: 50px;
            padding: 6px 20px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: #fff;
            color: #000;
        }

        /* --- Modal Dark --- */
        .modal-content {
            background-color: #111;
            border: 1px solid #333;
            color: #fff;
        }

        .modal-header {
            border-bottom: 1px solid #333;
        }

        .modal-footer {
            border-top: 1px solid #333;
        }

        .btn-close {
            filter: invert(1);
        }

        .form-control-dark {
            background-color: #000;
            border: 1px solid #333;
            color: #fff;
        }

        .form-control-dark:focus {
            background-color: #000;
            border-color: #fff;
            color: #fff;
            box-shadow: none;
        }
    </style>
</head>

<body>
    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>

    <div class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-down">
            <div>
                <h3 class="fw-light text-white mb-0" style="letter-spacing: 1px;">
                    <i class="fa-solid fa-users-gear me-2 text-secondary"></i>MANAGE USERS
                </h3>
                <small class="text-secondary">จัดการข้อมูลสมาชิกและรหัสผ่าน</small>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-monochrome">
                    <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
                </a>
                <a href="sync_students.php" class="btn btn-monochrome" onclick="return confirm('⚠️ ยืนยันการดึงข้อมูลจาก RMS?');">
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
                                    <span class="font-monospace text-white"><?php echo $row['student_id']; ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-secondary bg-opacity-25 rounded-circle me-2 d-flex justify-content-center align-items-center" style="width:30px; height:30px;">
                                            <i class="fa-regular fa-user text-light" style="font-size: 0.8rem;"></i>
                                        </div>
                                        <?php echo $row['fullname']; ?>
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
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h5 class="modal-title fw-light text-white">
                        <i class="fa-solid fa-lock me-2"></i> RESET PASSWORD
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="reset_password" value="1">
                        <input type="hidden" name="user_id" id="modal_user_id">

                        <div class="text-center mb-4">
                            <div class="text-secondary small mb-1">กำลังเปลี่ยนรหัสผ่านให้</div>
                            <h4 class="text-white fw-bold" id="modal_user_name">...</h4>
                        </div>

                        <div class="mb-3">
                            <label class="text-secondary small mb-2">NEW PASSWORD</label>
                            <input type="text" name="new_pass" class="form-control form-control-dark py-2" required placeholder="กรอกรหัสผ่านใหม่..." autocomplete="off">
                            <div class="form-text text-secondary opacity-50 mt-2">* กรอกรหัสใหม่ที่ต้องการให้ผู้ใช้งาน</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-0 btn-sm" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" class="btn btn-light rounded-0 btn-sm fw-bold">CONFIRM CHANGE</button>
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
        AOS.init({
            duration: 800,
            once: true
        });

        // Particles
        particlesJS("particles-js", {
            "particles": {
                "number": {
                    "value": 60
                },
                "color": {
                    "value": "#ffffff"
                },
                "shape": {
                    "type": "circle"
                },
                "opacity": {
                    "value": 0.2,
                    "random": true
                },
                "size": {
                    "value": 2,
                    "random": true
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#ffffff",
                    "opacity": 0.1,
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 0.5
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": false
                    }
                }
            }
        });

        // DataTable Config
        $(document).ready(function() {
            $('#userTable').DataTable({
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ คน",
                    paginate: {
                        first: "หน้าแรก",
                        last: "สุดท้าย",
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    },
                    zeroRecords: "ไม่พบข้อมูล"
                },
                // ปรับแต่ง DOM เพื่อจัดวางช่องค้นหาใหม่ (ถ้าต้องการ)
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

        // SweetAlert
        <?php if ($message): ?>
            Swal.fire({
                icon: '<?php echo $msg_type; ?>',
                title: '<?php echo $msg_type == "success" ? "สำเร็จ!" : "ผิดพลาด"; ?>',
                text: '<?php echo $message; ?>',
                background: '#000',
                color: '#fff',
                iconColor: '#fff',
                confirmButtonColor: '#fff',
                confirmButtonText: '<span style="color:#000; font-weight:bold;">OK</span>'
            });
        <?php endif; ?>
    </script>
</body>

</html>