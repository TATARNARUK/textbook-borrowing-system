<?php
session_start();
require_once 'config.php';

// 1. เช็คว่าเป็น Admin หรือไม่?
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['fullname'];
$message = '';
$msg_type = '';

// 2. ส่วนทำงานเมื่อกดปุ่ม "บันทึกเปลี่ยนรหัสผ่าน"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $target_user_id = $_POST['user_id'];
    $new_password = $_POST['new_pass'];
    
    if (!empty($new_password)) {
        // Hash รหัสผ่านใหม่
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $target_user_id])) {
            $message = "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว!";
            $msg_type = "success";
        } else {
            $message = "เกิดข้อผิดพลาด";
            $msg_type = "danger";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .top-nav { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 15px 0; margin-bottom: 30px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <nav class="top-nav">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <img src="images/books.png" height="40">
                <div>
                    <h5 class="m-0 fw-bold text-primary">ระบบจัดการผู้ใช้งาน</h5>
                    <small class="text-muted">สำหรับผู้ดูแลระบบ</small>
                </div>
            </div>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill">
                <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
            </a>
        </div>
    </nav>

    <div class="container">
        
        <?php if ($message): ?>
            <script>
                Swal.fire({
                    icon: '<?php echo $msg_type; ?>',
                    title: '<?php echo $message; ?>',
                    showConfirmButton: false,
                    timer: 1500
                })
            </script>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3">
                <h5 class="m-0 fw-bold"><i class="fa-solid fa-users-gear text-primary me-2"></i> รายชื่อสมาชิกทั้งหมด</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="userTable" class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>รหัสนักเรียน/Username</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>เบอร์โทร</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, id DESC");
                            while ($row = $stmt->fetch()) {
                            ?>
                            <tr>
                                <td><?php echo $row['student_id']; ?></td>
                                <td><?php echo $row['fullname']; ?></td>
                                <td><?php echo $row['phone'] ? $row['phone'] : '-'; ?></td>
                                <td>
                                    <?php if($row['role'] == 'admin'): ?>
                                        <span class="badge bg-danger">ผู้ดูแลระบบ</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">นักเรียน</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="openResetModal(<?php echo $row['id']; ?>, '<?php echo $row['fullname']; ?>')" 
                                            class="btn btn-sm btn-warning text-dark shadow-sm">
                                        <i class="fa-solid fa-key"></i> รีเซ็ตรหัส
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

    <div class="modal fade" id="resetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header bg-warning bg-opacity-10 border-0">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-key me-2"></i> เปลี่ยนรหัสผ่านใหม่
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="reset_password" value="1">
                        <input type="hidden" name="user_id" id="modal_user_id">
                        
                        <div class="alert alert-light border text-center mb-3">
                            กำลังเปลี่ยนรหัสผ่านให้: <br>
                            <strong id="modal_user_name" class="text-primary fs-5">...</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted">ตั้งรหัสผ่านใหม่</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fa-solid fa-lock"></i></span>
                                <input type="text" name="new_pass" class="form-control bg-light border-0" required placeholder="กรอกรหัสใหม่ที่นี่..." autocomplete="off">
                            </div>
                            <div class="form-text">Admin สามารถพิมพ์รหัสง่ายๆ (เช่น 1234) ให้ User ใช้ชั่วคราวได้</div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">บันทึกการเปลี่ยน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#userTable').DataTable({
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ คน",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ คน",
                    paginate: { first: "หน้าแรก", last: "หน้าสุดท้าย", next: "ถัดไป", previous: "ก่อนหน้า" }
                }
            });
        });

        function openResetModal(id, name) {
            // ส่งค่า ID และชื่อคน ไปใส่ใน Modal
            document.getElementById('modal_user_id').value = id;
            document.getElementById('modal_user_name').innerText = name;
            
            // เปิด Modal
            var myModal = new bootstrap.Modal(document.getElementById('resetModal'));
            myModal.show();
        }
    </script>
</body>
</html>