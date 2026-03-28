<?php
session_start();
require_once 'config.php';

// รับค่าจาก LINE Messaging API (กรณีทำ Webhook) 
// หรือรับจาก URL Parameter (แบบง่ายสำหรับโปรเจกต์)
$line_uid = $_GET['uid'] ?? ''; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $u_id = $_SESSION['user_id'];
    $l_uid = $_POST['line_uid'];

    // บันทึก LINE User ID ลงตาราง users
    $sql = "UPDATE users SET line_user_id = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$l_uid, $u_id])) {
        $status = 'success';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เชื่อมต่อ LINE - IT Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card shadow-sm border-0 rounded-4 p-4 text-center" style="max-width: 400px;">
            <i class="fab fa-line text-success fa-4x mb-3"></i>
            <h4 class="fw-bold">ยืนยันตัวตนรับแจ้งเตือน</h4>
            <p class="text-muted small">ล็อกอินเพื่อเชื่อมต่อบัญชี LINE เข้ากับรหัสนักเรียนของคุณครับ</p>
            
            <?php if (isset($status) && $status == 'success'): ?>
                <div class="alert alert-success rounded-pill">เชื่อมต่อสำเร็จ! ต่อไปนี้คุณจะได้รับแจ้งเตือนผ่าน LINE ครับ</div>
                <a href="index.php" class="btn btn-primary w-100 rounded-pill mt-3">กลับหน้าหลัก</a>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="line_uid" value="<?php echo htmlspecialchars($line_uid); ?>">
                    <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold py-2">
                        <i class="fas fa-link me-1"></i> ยืนยันเชื่อมต่อ LINE
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>