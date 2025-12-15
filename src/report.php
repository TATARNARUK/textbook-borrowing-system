<?php
session_start();
require_once 'config.php';
require_once 'header.php';

// เช็คสิทธิ์ Admin (นักเรียนห้ามเข้า)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); exit();
}

// กำหนดค่าเริ่มต้นของวันที่ (ถ้าไม่เลือก ให้เป็นวันที่ปัจจุบัน)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // วันที่ 1 ของเดือน
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // วันนี้

// Query ข้อมูลตามช่วงเวลา
$sql = "SELECT t.*, u.fullname, u.student_id, b.title, bi.book_code 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        JOIN book_items bi ON t.book_item_id = bi.id
        JOIN book_masters b ON bi.book_master_id = b.id
        WHERE date(t.borrow_date) BETWEEN :start AND :end
        ORDER BY t.borrow_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['start' => $start_date, 'end' => $end_date]);
$transactions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานสรุปการยืม-คืน</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8f9fa; }
        
        /* CSS พิเศษสำหรับตอนสั่ง Print (ซ่อนปุ่ม/เมนู) */
        @media print {
            .no-print { display: none !important; } /* ซ่อนสิ่งที่ไม่อยากให้พิมพ์ */
            .card { border: none !important; box-shadow: none !important; }
            body { background-color: white; }
            .container { max-width: 100%; width: 100%; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="container mt-4 no-print">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="index.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก</a>
            <h3 class="mb-0">รายงานสรุปการยืม-คืนหนังสือ</h3>
            <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> พิมพ์รายงาน / PDF</button>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">ตั้งแต่วันที่</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ถึงวันที่</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-magnifying-glass"></i> ค้นหาข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container bg-white p-4 rounded">
        
        <div class="text-center mb-4">
            <h2 class="fw-bold">รายงานสรุปการยืม-คืนหนังสือเรียน</h2>
            <p>วิทยาลัยพณิชยการบางนา (BNCC Textbook System)</p>
            <p class="text-muted">ข้อมูลระหว่างวันที่: <?php echo date('d/m/Y', strtotime($start_date)); ?> ถึง <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
        </div>

        <table class="table table-bordered border-dark">
            <thead class="table-light">
                <tr class="text-center">
                    <th width="5%">ลำดับ</th>
                    <th width="15%">วันที่ยืม</th>
                    <th width="15%">รหัสนักเรียน</th>
                    <th width="20%">ชื่อ-สกุล</th>
                    <th width="25%">ชื่อหนังสือ</th>
                    <th width="10%">สถานะ</th>
                    <th width="10%">วันที่คืน</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (count($transactions) > 0) {
                    $i = 1;
                    foreach ($transactions as $row) { 
                        $status_color = ($row['status'] == 'borrowed') ? 'text-danger' : 'text-success';
                        $status_text = ($row['status'] == 'borrowed') ? 'ยังไม่คืน' : 'คืนแล้ว';
                ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($row['borrow_date'])); ?></td>
                    <td class="text-center"><?php echo $row['student_id']; ?></td>
                    <td><?php echo $row['fullname']; ?></td>
                    <td><?php echo $row['title']; ?> <br> <small class="text-muted">(<?php echo $row['book_code']; ?>)</small></td>
                    <td class="text-center fw-bold <?php echo $status_color; ?>"><?php echo $status_text; ?></td>
                    <td class="text-center">
                        <?php echo ($row['return_date']) ? date('d/m/Y', strtotime($row['return_date'])) : '-'; ?>
                    </td>
                </tr>
                <?php 
                    } 
                } else {
                ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">ไม่พบข้อมูลในช่วงเวลานี้</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="d-none d-print-block mt-5">
            <div class="d-flex justify-content-between px-5">
                <div class="text-center">
                    <p>ลงชื่อ ....................................................... ผู้จัดทำ</p>
                    <p>(.......................................................)</p>
                </div>
                <div class="text-center">
                    <p>ลงชื่อ ....................................................... ครูที่ปรึกษา</p>
                    <p>(.......................................................)</p>
                </div>
            </div>
        </div>

    </div>

</body>
</html>