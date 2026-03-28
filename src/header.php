<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบยืม-คืนหนังสือเรียนฟรี</title>
    
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    
    <style>
        /* --- ตั้งค่าธีมหลัก --- */
        body { 
            font-family: 'Prompt', sans-serif; 
            background-color: #f4f7f6; /* สีพื้นหลังเทาอมฟ้าอ่อนๆ สบายตา */
            color: #333;
        }

        /* Navbar สีขาวสะอาดตา + เงาบางๆ */
        .navbar {
            background-color: #ffffff !important;
            box-shadow: 0 2px 15px rgba(0,0,0,0.03);
            padding: 1rem 0;
        }
        .navbar-brand { font-weight: 600; color: #2c3e50 !important; }

        /* การ์ด (กล่องเนื้อหา) */
        .card {
            border: none; /* เอาขอบออก */
            border-radius: 15px; /* มุมโค้งมน */
            box-shadow: 0 5px 20px rgba(0,0,0,0.03); /* เงาฟุ้งๆ */
            transition: transform 0.2s;
            background: white;
        }
        /* เอฟเฟกต์ตอนชี้การ์ด */
        .card-hover:hover {
            transform: translateY(-5px);
        }

        /* ปุ่มกด */
        .btn {
            border-radius: 10px; /* ปุ่มโค้งมน */
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary { background-color: #4e73df; border-color: #4e73df; }
        .btn-primary:hover { background-color: #2e59d9; }

        /* ตาราง */
        .table thead th {
            background-color: #f8f9fc;
            color: #858796;
            font-weight: 600;
            border-bottom: none;
        }
        .table td { vertical-align: middle; }
        
        /* Badge (ป้ายสถานะ) */
        .badge {
            font-weight: 400;
            padding: 0.5em 0.8em;
            border-radius: 6px;
        }
        
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="#">
                <img src="images/books.png" alt="Logo" width="35" height="35" class="me-2"> 
                BNCC Library
            </a>
            
            <div class="d-flex align-items-center gap-3">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span class="d-none d-md-inline text-secondary">
                        <i class="fa-solid fa-user-circle"></i> 
                        <?php echo htmlspecialchars($_SESSION['fullname']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                        <i class="fa-solid fa-sign-out-alt"></i> ออกจากระบบ
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
        <footer class="text-center text-muted py-4 border-top bg-white">
        <div class="container">
            <small>&copy; 2025 TEXTBOOK BORROWING SYSTEM. All rights reserved.</small>
        </div>
    </footer>
    
    <div class="container" style="margin-top: 100px;">