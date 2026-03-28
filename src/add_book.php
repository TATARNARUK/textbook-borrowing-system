<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ (Admin เท่านั้น)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'];
$user_role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $approval_order = $_POST['approval_order'] ?? '';
    $book_code = $_POST['book_code'] ?? '';
    $isbn = $_POST['isbn'] ?? '';
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $publisher = $_POST['publisher'] ?? '';
    $price = $_POST['price'] ?? 0;
    $page_count = $_POST['page_count'] ?? 0;

    // รับค่าส่วนที่ 2 (รูปแบบกระดาษ การพิมพ์ ฯลฯ)
    $paper_type = $_POST['paper_type'] ?? '';
    $print_type = $_POST['print_type'] ?? '';
    $book_size = $_POST['book_size'] ?? '';
    $approval_no = $_POST['approval_no'] ?? '-';

    // 1. จัดการรูปภาพ (Cover Image)
    $image_path = "";
    if (isset($_FILES['cover_img']) && $_FILES['cover_img']['error'] == 0) {
        $ext = pathinfo($_FILES['cover_img']['name'], PATHINFO_EXTENSION);
        $new_name = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['cover_img']['tmp_name'], "uploads/" . $new_name);
        $image_path = $new_name;
    }

    // 2. จัดการไฟล์ตัวอย่าง (PDF Sample)
    $pdf_path = "";
    if (isset($_FILES['sample_pdf']) && $_FILES['sample_pdf']['error'] == 0) {
        $ext_pdf = pathinfo($_FILES['sample_pdf']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext_pdf) == 'pdf') {
            $new_pdf_name = "sample_" . uniqid() . ".pdf";
            if (!is_dir('uploads/pdfs')) {
                mkdir('uploads/pdfs', 0777, true);
            }
            move_uploaded_file($_FILES['sample_pdf']['tmp_name'], "uploads/pdfs/" . $new_pdf_name);
            $pdf_path = $new_pdf_name;
        }
    }

    // SQL (เพิ่มข้อมูล)
    $sql = "INSERT INTO book_masters (book_code, isbn, title, author, publisher, price, approval_no, approval_order, page_count, paper_type, print_type, book_size, cover_image, sample_pdf) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$book_code, $isbn, $title, $author, $publisher, $price, $approval_no, $approval_order, $page_count, $paper_type, $print_type, $book_size, $image_path, $pdf_path])) {
        $msg = "success";
    } else {
        $msg = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มหนังสือใหม่ - Library Hub</title>
    <link rel="icon" type="image/png" href="images/books.png">
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        /* === นำ CSS หลักมาใช้ (InsightHub Style) === */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --sidebar-w: 260px; --sidebar-bg: #ffffff; --body-bg: #f8fafc;
            --accent: #2563eb; --accent-light: #dbeafe; --accent-soft: #eff6ff;
            --text-primary: #0f172a; --text-secondary: #64748b; --text-muted: #94a3b8;
            --border: #f1f5f9; --card-bg: #ffffff; --card-radius: 16px;
            --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
            --font-main: 'DM Sans', 'Noto Sans Thai', sans-serif;
            --shadow-sm: 0 4px 15px rgba(0, 0, 0, 0.02);
            --shadow-md: 0 10px 25px rgba(0, 0, 0, 0.06);
        }
        body { font-family: var(--font-main); background: var(--body-bg); color: var(--text-primary); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* Sidebar & Topbar */
        .sidebar { width: var(--sidebar-w); height: 100vh; background: var(--sidebar-bg); border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; z-index: 1000; transition: transform .3s ease; }
        .sidebar-logo { height: 90px; padding: 0 20px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .logo-icon { width: 40px; height: 40px; background: var(--accent); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; }
        .logo-text { line-height: 1.2; padding-right: 5px; }
        .logo-text strong { font-size: 13px; font-weight: 800; color: var(--text-primary); display: block; word-wrap: break-word; line-height: 1.3; }
        .logo-text small { font-size: 11px; color: var(--text-muted); display: block; margin-top: 2px; }
        .sidebar-section { padding: 20px 16px 8px; font-size: 13px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--text-muted); }
        .sidebar-nav { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 8px; }
        .sidebar-nav::-webkit-scrollbar { width: 5px; } .sidebar-nav::-webkit-scrollbar-track { background: transparent; } .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 10px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 10px; color: var(--text-secondary); font-size: 16px; font-weight: 500; cursor: pointer; transition: all .2s; text-decoration: none; margin-bottom: 4px; }
        .nav-item:hover { background: var(--accent-soft); color: var(--accent); }
        .nav-item.active { background: var(--accent); color: white; }
        .nav-item .nav-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; background: transparent; transition: all .2s; flex-shrink: 0; }
        .nav-item.active .nav-icon { background: rgba(255, 255, 255, .2); }
        .nav-item:hover:not(.active) .nav-icon { background: var(--accent-light); color: var(--accent); }
        .sidebar-footer { padding: 16px; border-top: 1px solid var(--border); flex-shrink: 0; background-color: var(--sidebar-bg); position: relative; }
        
        .user-card { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; background: var(--accent-soft); cursor: pointer; transition: background .2s; }
        .user-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), #7c3aed); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 16px; flex-shrink: 0; }
        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: 15px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 13px; color: var(--text-muted); }
        
        .user-dropdown { position: absolute; bottom: calc(100% + 5px); left: 16px; right: 16px; background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); padding: 8px; opacity: 0; visibility: hidden; transform: translateY(10px); transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 1050; }
        .user-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; color: var(--text-primary); text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.2s; }
        .dropdown-item:hover { background: var(--body-bg); color: var(--accent); }
        .dropdown-item i { font-size: 14px; color: var(--text-muted); width: 20px; text-align: center; transition: all 0.2s; }
        .dropdown-item:hover i { color: var(--accent); }
        .dropdown-divider { height: 1px; background: var(--border); margin: 6px 0; }
        .dropdown-item.text-danger { color: var(--danger); }
        .dropdown-item.text-danger i { color: var(--danger); }
        .dropdown-item.text-danger:hover { background: #fee2e2; color: #b91c1c; }
        .dropdown-item.text-danger:hover i { color: #b91c1c; }

        .main-wrapper { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar { height: 90px; background: rgba(255, 255, 255, .85); backdrop-filter: blur(12px); border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; padding: 0 32px; gap: 16px; position: sticky; top: 0; z-index: 50; justify-content: space-between; }
        .topbar-greeting { flex: 1; display: flex; align-items: center; gap: 16px; }
        .menu-toggle { display: none; width: 36px; height: 36px; align-items: center; justify-content: center; border: 1px solid #e2e8f0; border-radius: 8px; background: white; cursor: pointer; font-size: 16px; color: var(--text-secondary); }

        /* === Page Specific (Add Book) === */
        .page-content { padding: 32px; flex: 1; display: flex; justify-content: center;}
        
        .insight-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            padding: 40px;
            width: 100%;
            max-width: 1000px; /* จำกัดความกว้างฟอร์มให้พอดี ไม่ยืดเกินไป */
            margin-bottom: 24px;
        }

        .header-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .header-icon-box {
            width: 40px; height: 40px;
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }

        /* --- Clean Form Styling --- */
        .form-section-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
            margin-top: 32px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-section-title:first-child { margin-top: 0; }

        .form-floating > label {
            color: var(--text-muted);
            font-weight: 500;
            padding-left: 16px; /* ขยับ label ออกมาจากขอบซ้ายนิดนึง */
        }
        
        .form-control, .form-select {
            background-color: var(--body-bg) !important;
            border: 1px solid #e2e8f0;
            color: var(--text-primary) !important;
            border-radius: 12px;
            padding: 12px 16px; /* เพิ่ม Padding ให้ช่องกรอกดูอ้วนขึ้น */
            font-weight: 500;
            transition: all 0.2s;
            box-shadow: none !important;
        }

        .form-control:focus, .form-select:focus {
            background-color: #ffffff !important;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light) !important;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--accent);
            font-weight: 600;
            transform: scale(0.85) translateY(-0.8rem) translateX(0.15rem); /* ปรับตำแหน่ง Label ตอนลอยขึ้น */
            background: transparent !important;
        }

        /* --- Upload Zone --- */
        .upload-zone {
            border: 2px dashed #cbd5e1;
            background-color: var(--body-bg);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            height: 100%;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .upload-zone:hover {
            border-color: var(--accent);
            background-color: var(--accent-soft);
        }

        .upload-zone input[type="file"] {
            position: absolute; width: 100%; height: 100%; top: 0; left: 0;
            opacity: 0; cursor: pointer;
        }
        
        .upload-icon {
            width: 50px; height: 50px;
            background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 12px;
        }

        .btn-modern {
            padding: 14px 24px; border-radius: 12px; font-weight: 600; font-size: 15px;
            border: none; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-primary-modern { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        .btn-primary-modern:hover { background: #1d4ed8; transform: translateY(-2px); color: white;}

        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeup { animation: fadeUp .5s ease both; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); box-shadow: 0 0 50px rgba(0, 0, 0, 0.2); }
            .main-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; }
            .page-content { padding: 20px 16px; }
            .insight-card { padding: 20px; }
        }
    </style>
</head>

<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fa-solid fa-book-open"></i></div>
        <div class="logo-text">
            <strong>TEXTBOOK BORROWING</strong>
            <small>ระบบยืม-คืนหนังสือเรียนฟรี</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section">เมนูหลัก</div>
        <a href="index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>"><div class="nav-icon"><i class="fa-solid fa-table-columns"></i></div><span>Dashboard</span></a>
        <a href="all_books.php" class="nav-item <?php echo ($current_page == 'all_books.php') ? 'active' : ''; ?>"><div class="nav-icon"><i class="fa-solid fa-book"></i></div><span>รายการหนังสือ</span></a>
        <a href="my_history.php" class="nav-item <?php echo ($current_page == 'my_history.php') ? 'active' : ''; ?>"><div class="nav-icon"><i class="fa-solid fa-clock-rotate-left"></i></div><span>ประวัติการยืม</span></a>

        <?php if ($user_role == 'admin'): ?>
            <div class="sidebar-section" style="margin-top:8px;">ผู้ดูแลระบบ</div>
            <a href="report.php" class="nav-item <?php echo ($current_page == 'report.php') ? 'active' : ''; ?>"><div class="nav-icon"><i class="fa-solid fa-chart-pie"></i></div><span>รายงานสรุป</span></a>
            <a href="add_book.php" class="nav-item <?php echo ($current_page == 'add_book.php') ? 'active' : ''; ?>"><div class="nav-icon"><i class="fa-solid fa-book-medical"></i></div><span>เพิ่มหนังสือใหม่</span></a>
            <a href="book_stock_list.php" class="nav-item <?php echo ($current_page == 'book_stock_list.php') ? 'active' : ''; ?>"><div class="nav-icon"><i class="fa-solid fa-boxes-stacked"></i></div><span>จัดการสต็อก</span></a>
            <a href="manage_categories.php" class="nav-item <?php echo ($current_page == 'manage_categories.php') ? 'active' : ''; ?>"><div class="nav-icon"><i class="fa-solid fa-layer-group"></i></div><span>จัดการหมวดหมู่</span></a>
            <a href="admin_users.php" class="nav-item <?php echo ($current_page == 'admin_users.php') ? 'active' : ''; ?>"><div class="nav-icon"><i class="fa-solid fa-users-gear"></i></div><span>จัดการผู้ใช้</span></a>
            <a href="import_api.php" class="nav-item <?php echo ($current_page == 'import_api.php') ? 'active' : ''; ?>"><div class="nav-icon"><i class="fa-solid fa-cloud-arrow-down"></i></div><span>นำเข้าจาก API</span></a>
        <?php endif; ?>

        <div class="sidebar-section" style="margin-top:8px;">อื่นๆ</div>
        <a href="manual.php" class="nav-item <?php echo ($current_page == 'manual.php') ? 'active' : ''; ?>"><div class="nav-icon"><i class="fa-solid fa-circle-question"></i></div><span>คู่มือการใช้งาน</span></a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-dropdown" id="userDropdown">
            <a href="profile.php" class="dropdown-item"><i class="fa-regular fa-id-badge"></i> ข้อมูลส่วนตัว</a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="dropdown-item text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> ออกจากระบบ</a>
        </div>
        <div class="user-card" id="userDropdownTrigger">
            <div class="user-avatar"><?php echo mb_substr($user_name, 0, 1); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                <div class="user-role"><?php echo $user_role == 'admin' ? 'ผู้ดูแลระบบ' : 'นักเรียน'; ?></div>
            </div>
            <i class="fa-solid fa-chevron-up" id="userDropdownIcon" style="color:var(--text-muted); font-size:12px; transition: transform 0.2s;"></i>
        </div>
    </div>
</aside>

<div class="main-wrapper">

    <header class="topbar">
        <div class="topbar-greeting">
            <button class="menu-toggle" id="menuToggle"><i class="fa-solid fa-bars"></i></button>
            <h2 class="mb-0" style="font-size: 18px; font-weight: 700;">➕ เพิ่มหนังสือใหม่</h2>
        </div>
    </header>

    <main class="page-content">

        <div class="insight-card animate-fadeup">
            
            <div class="header-title">
                <div class="header-icon-box"><i class="fa-solid fa-book-medical"></i></div>
                <div>เพิ่มข้อมูลหนังสือเข้าสู่ระบบห้องสมุด</div>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">

                <div class="form-section-title"><i class="fa-solid fa-circle-info"></i> 1. ข้อมูลทั่วไปของหนังสือ</div>
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="number" class="form-control" id="approval_order" name="approval_order" placeholder="ลำดับที่" autocomplete="off" required>
                            <label for="approval_order">ลำดับที่</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="book_code" name="book_code" placeholder="รหัสหนังสือ" required autocomplete="off">
                            <label for="book_code">รหัสหนังสือ (Book Code)</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="isbn" name="isbn" placeholder="รหัส ISBN" autocomplete="off">
                            <label for="isbn">รหัส ISBN</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="title" name="title" placeholder="ชื่อหนังสือ" required autocomplete="off">
                            <label for="title">ชื่อหนังสือ (Title)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="author" name="author" placeholder="ผู้แต่ง" autocomplete="off">
                            <label for="author">ผู้แต่ง (Author)</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="publisher" name="publisher" placeholder="สำนักพิมพ์" autocomplete="off">
                            <label for="publisher">สำนักพิมพ์ (Publisher)</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="price" name="price" placeholder="ราคาหนังสือ" autocomplete="off">
                            <label for="price">ราคาหนังสือ (บาท)</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number" class="form-control" id="page_count" name="page_count" placeholder="จำนวนหน้า" autocomplete="off">
                            <label for="page_count">จำนวนหน้า</label>
                        </div>
                    </div>
                </div>

                <div class="form-section-title"><i class="fa-solid fa-book-open"></i> 2. ลักษณะรูปเล่ม</div>
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select name="paper_type" class="form-select" id="paper_type">
                                <option value="ปอนด์">ปอนด์</option>
                                <option value="ถนอมสายตา">ถนอมสายตา</option>
                                <option value="อาร์ต">อาร์ต</option>
                                <option value="บรู๊ฟ">บรู๊ฟ</option>
                            </select>
                            <label for="paper_type">รูปแบบกระดาษ</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select name="print_type" class="form-select" id="print_type">
                                <option value="1 สี">1 สี</option>
                                <option value="2 สี">2 สี</option>
                                <option value="4 สี">4 สี</option>
                            </select>
                            <label for="print_type">รูปแบบการพิมพ์</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select name="book_size" class="form-select" id="book_size">
                                <option value="8 หน้ายก">8 หน้ายก</option>
                                <option value="A4">A4</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                            <label for="book_size">ขนาดรูปเล่ม</label>
                        </div>
                    </div>
                </div>

                <div class="form-section-title"><i class="fa-solid fa-cloud-arrow-up"></i> 3. อัปโหลดรูปภาพและไฟล์ตัวอย่าง</div>
                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="upload-zone" id="img-zone">
                            <input type="file" name="cover_img" id="cover_img" accept="image/*" onchange="previewFile()">
                            <div id="upload-label">
                                <div class="upload-icon text-primary"><i class="fa-regular fa-image"></i></div>
                                <span class="fw-bold" style="color:var(--text-primary);">อัปโหลดรูปหน้าปกหนังสือ</span><br>
                                <small style="color:var(--text-muted);">คลิก หรือ ลากไฟล์รูปภาพมาวางที่นี่</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="upload-zone" id="pdf-zone">
                            <input type="file" name="sample_pdf" id="sample_pdf" accept="application/pdf" onchange="previewPdf()">
                            <div id="pdf-upload-label">
                                <div class="upload-icon text-danger"><i class="fa-regular fa-file-pdf"></i></div>
                                <span class="fw-bold" style="color:var(--text-primary);">อัปโหลดไฟล์ตัวอย่าง (PDF)</span><br>
                                <small style="color:var(--text-muted);">คลิกเพื่อเลือกไฟล์ PDF (ไม่เกิน 40MB)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-modern btn-primary-modern w-100">
                    <i class="fa-solid fa-save"></i> บันทึกข้อมูลเข้าสู่ระบบ
                </button>

            </form>

        </div>

    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
$(document).ready(function() {
    AOS.init({ duration: 600, once: true });

    // Toggle Sidebar บนมือถือ
    $('#menuToggle').on('click', function(e) {
        e.stopPropagation();
        $('#sidebar').toggleClass('open');
    });

    // Profile Dropdown
    $('#userDropdownTrigger').on('click', function(e) {
        e.stopPropagation();
        $('#userDropdown').toggleClass('show');
        $('#userDropdownIcon').css('transform', $('#userDropdown').hasClass('show') ? 'rotate(180deg)' : 'rotate(0deg)');
    });

    // ปิดเมนูเมื่อคลิกที่อื่น
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.sidebar-footer').length) {
            $('#userDropdown').removeClass('show');
            $('#userDropdownIcon').css('transform', 'rotate(0deg)');
        }
        if ($(window).width() <= 768 && !$(e.target).closest('#sidebar, #menuToggle').length) {
            $('#sidebar').removeClass('open');
        }
    });
});

// Script Show Filename (Image)
function previewFile() {
    const fileInput = document.getElementById('cover_img');
    const label = document.getElementById('upload-label');
    const zone = document.getElementById('img-zone');

    if (fileInput.files.length > 0) {
        label.innerHTML = '<div class="upload-icon text-success"><i class="fa-solid fa-check"></i></div><span class="fw-bold" style="color:#10b981;">' + fileInput.files[0].name + '</span><br><small style="color:var(--text-muted);">พร้อมสำหรับอัปโหลด</small>';
        zone.style.borderColor = '#10b981';
        zone.style.backgroundColor = '#ecfdf5';
    }
}

// Script Show Filename (PDF)
function previewPdf() {
    const fileInput = document.getElementById('sample_pdf');
    const label = document.getElementById('pdf-upload-label');
    const zone = document.getElementById('pdf-zone');

    if (fileInput.files.length > 0) {
        const fileName = fileInput.files[0].name;
        const fileSize = (fileInput.files[0].size / 1024 / 1024).toFixed(2); // แปลงเป็น MB

        // เช็คขนาดไฟล์ (เตือนถ้าเกิน 40MB)
        if (fileSize > 40) {
            Swal.fire({
                icon: 'warning',
                title: 'ไฟล์ใหญ่เกินไป',
                text: 'ไฟล์ PDF ควรมีขนาดไม่เกิน 40MB (ไฟล์ของคุณ: ' + fileSize + ' MB)',
                confirmButtonColor: '#f59e0b',
                customClass: { popup: 'rounded-4' }
            });
            fileInput.value = ""; // ล้างค่า
            return;
        }

        label.innerHTML = '<div class="upload-icon text-danger"><i class="fa-solid fa-file-pdf"></i></div><span class="fw-bold" style="color:#ef4444;">' + fileName + '</span><br><small style="color:var(--text-muted);">ขนาดไฟล์: ' + fileSize + ' MB</small>';
        zone.style.borderColor = '#ef4444';
        zone.style.backgroundColor = '#fef2f2';
    }
}

// SweetAlert Style (Light Theme)
<?php if ($msg == 'success'): ?>
    Swal.fire({
        icon: 'success',
        title: 'สำเร็จ!',
        text: 'เพิ่มข้อมูลหนังสือเข้าสู่ระบบเรียบร้อยแล้ว',
        confirmButtonColor: '#2563eb',
        confirmButtonText: 'ตกลง',
        customClass: { popup: 'rounded-4' }
    }).then(() => {
        window.location = 'index.php';
    });
<?php elseif ($msg == 'error'): ?>
    Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: 'ไม่สามารถบันทึกข้อมูลได้ กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง',
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'ปิด',
        customClass: { popup: 'rounded-4' }
    });
<?php endif; ?>
</script>
</body>
</html>