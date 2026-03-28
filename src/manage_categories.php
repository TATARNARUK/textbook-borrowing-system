<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$user_name = $_SESSION['fullname'];
$user_role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);

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
            if (strpos($book_title, $cat_name) !== false) {
                $matched_cat_id = $cat['id'];
                break;
            }
        }

        // 🔥 2. ถ้าหาหมวดที่มีอยู่ไม่เจอเลย -> "สร้างหมวดหมู่ใหม่จากชื่อหนังสือ"
        if (!$matched_cat_id) {
            
            // ใช้เทคนิคดึงคำแรก หรือ ตัดคำจากชื่อหนังสือมาตั้งเป็นชื่อหมวด
            $words = explode(" ", $book['title']); 
            $first_word = trim($words[0]);
            
            // ป้องกันกรณีคำสั้นเกินไปหรือไม่มีความหมาย
            if (mb_strlen($first_word, 'UTF-8') < 3) {
                $new_cat_name = "อื่นๆ (" . mb_substr($book['title'], 0, 10, 'UTF-8') . "...)";
            } else {
                $new_cat_name = "หมวด " . $first_word; 
            }

            // เช็คก่อนว่าไอ้หมวดที่กำลังจะสร้างใหม่เนี้ย มันมีคนอื่นสร้างไปก่อนหน้าในลูปนี้ไหม
            $stmtCheckNew = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmtCheckNew->execute([$new_cat_name]);
            $existingNewCat = $stmtCheckNew->fetch();

            if ($existingNewCat) {
                $matched_cat_id = $existingNewCat['id'];
            } else {
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
    <title>จัดการหมวดหมู่</title>
    <link rel="icon" type="image/png" href="images/books.png">
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

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

        /* === Page Specific (Manage Categories) === */
        .page-content { padding: 32px; flex: 1; display: flex; flex-direction: column; }
        
        .content-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 24px;
            align-items: start;
        }

        .insight-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            padding: 24px;
        }

        .header-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        /* Forms */
        .form-label-custom {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control-custom {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: var(--body-bg);
            font-family: var(--font-main);
            font-size: 14px;
            color: var(--text-primary);
            transition: 0.2s;
        }
        .form-control-custom:focus {
            border-color: var(--accent);
            background: white;
            outline: none;
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        .btn-modern {
            width: 100%;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary-modern { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        .btn-primary-modern:hover { background: #1d4ed8; transform: translateY(-2px); color: white;}

        /* Smart Button */
        .btn-magic {
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            color: white;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
        }
        .btn-magic:hover {
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 6px 15px rgba(139, 92, 246, 0.3);
        }
        .magic-box {
            background: #f5f3ff;
            border: 1px solid #ede9fe;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-top: 24px;
        }

        /* --- Clean Table --- */
        .table-container { overflow-x: auto; }
        .insight-table { width: 100%; border-collapse: collapse; }
        .insight-table thead th {
            font-size: 12px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
            color: var(--text-muted); padding: 12px 16px; border-bottom: 2px solid var(--border); text-align: left;
        }
        .insight-table tbody tr { border-bottom: 1px solid var(--border); transition: background 0.2s; }
        .insight-table tbody tr:hover { background-color: var(--body-bg); }
        .insight-table td { padding: 16px; vertical-align: middle; color: var(--text-primary); font-size: 14px; }

        .cat-name { font-weight: 700; color: var(--text-primary); }
        
        .count-badge {
            background-color: var(--accent-soft); color: var(--accent);
            padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 700;
        }
        .count-zero { background-color: var(--body-bg); color: var(--text-muted); }

        .action-icon {
            width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
            border: none; background: white; transition: 0.2s; cursor: pointer;
        }
        .action-edit { color: var(--warning); border: 1px solid #fde68a; }
        .action-edit:hover { background: #fffbeb; }
        .action-del { color: var(--danger); border: 1px solid #fecaca; }
        .action-del:hover { background: #fef2f2; }

        /* DATATABLES OVERRIDES */
        div.dataTables_wrapper div.dataTables_filter input { border: 1px solid var(--border); border-radius: 8px; padding: 6px 12px; font-size: 13px; outline: none; }
        div.dataTables_wrapper div.dataTables_length select { border: 1px solid var(--border); border-radius: 8px; padding: 4px 8px; font-size: 13px; }
        .dataTables_info, .dataTables_length, .dataTables_paginate { font-size: 12px !important; color: var(--text-muted) !important; margin-top: 16px; }
        .page-item.active .page-link { background: var(--accent); border-color: var(--accent); }
        .page-link { border-radius: 6px !important; margin: 0 2px;}

        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeup { animation: fadeUp .5s ease both; }

        @media (max-width: 1024px) {
            .content-layout { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); box-shadow: 0 0 50px rgba(0, 0, 0, 0.2); }
            .main-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; }
            .page-content { padding: 20px 16px; }
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
            <h2 class="mb-0" style="font-size: 18px; font-weight: 700;">📑 จัดการหมวดหมู่หนังสือ</h2>
        </div>
    </header>

    <main class="page-content">

        <div class="content-layout">
            
            <div class="animate-fadeup">
                <div class="insight-card h-100">
                    <div class="header-title">
                        <i class="fa-solid fa-plus-circle text-primary"></i> เพิ่มหมวดหมู่ใหม่
                    </div>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <span class="form-label-custom">ชื่อหมวดหมู่</span>
                            <input type="text" name="cat_name" class="form-control-custom" required placeholder="เช่น วิทยาศาสตร์, นิยาย">
                        </div>
                        <button type="submit" name="add_cat" class="btn-modern btn-primary-modern">
                            <i class="fa-solid fa-save"></i> บันทึกข้อมูล
                        </button>
                    </form>

                    <div class="magic-box">
                        <i class="fa-solid fa-robot" style="font-size: 32px; color: #8b5cf6; margin-bottom: 12px;"></i>
                        <h6 style="font-weight: 700; color: #4c1d95;">ระบบจัดหมวดหมู่อัจฉริยะ</h6>
                        <p style="font-size: 12px; color: #6d28d9; margin-bottom: 16px;">
                            สร้างหมวดหมู่ใหม่ และจัดหนังสือเข้าหมวดให้โดยอัตโนมัติ สำหรับหนังสือที่ตกหล่น
                        </p>
                        <form method="POST" id="autoCatForm">
                            <button type="button" onclick="confirmAutoCat()" class="btn-modern btn-magic">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> เริ่มการจัดหมวดหมู่
                            </button>
                            <input type="hidden" name="auto_categorize" value="1">
                        </form>
                    </div>
                </div>
            </div>

            <div class="animate-fadeup delay-1">
                <div class="insight-card">
                    <div class="table-container">
                        <table id="categoryTable" class="insight-table">
                            <thead>
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
                                        <td class="cat-name"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td class="text-center">
                                            <?php if ($row['book_count'] > 0): ?>
                                                <span class="count-badge"><?php echo $row['book_count']; ?> เล่ม</span>
                                            <?php else: ?>
                                                <span class="count-badge count-zero">0 เล่ม</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="action-icon action-edit edit-btn" 
                                                    data-id="<?php echo $row['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($row['name']); ?>" 
                                                    title="แก้ไข">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="action-icon action-del" title="ลบ">
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

    </main>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
                <h5 class="modal-title fw-bold" style="font-size: 16px;"><i class="fa-solid fa-pen text-warning me-2"></i>แก้ไขชื่อหมวดหมู่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body" style="padding: 24px;">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <span class="form-label-custom">ชื่อหมวดหมู่ใหม่</span>
                    <input type="text" name="edit_name" id="edit_name" class="form-control-custom" required>
                </div>
                <div class="modal-footer" style="border-top: none; padding: 0 24px 24px;">
                    <button type="button" class="btn-modern" style="background:#f1f5f9; color:var(--text-secondary);" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="edit_cat" class="btn-modern" style="background:var(--warning); color:white; box-shadow:0 4px 12px rgba(245, 158, 11, 0.2);">บันทึกการแก้ไข</button>
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

    // DataTables
    $('#categoryTable').DataTable({
        language: { 
            url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json",
            search: "ค้นหาหมวดหมู่:" 
        },
        pageLength: 10,
        ordering: false,
        dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>"
    });

    // Edit Modal Data
    $('.edit-btn').on('click', function() {
        var catId = $(this).data('id');
        var catName = $(this).data('name');
        $('#edit_id').val(catId);
        $('#edit_name').val(catName);
        $('#editModal').modal('show');
    });

    // แจ้งเตือน Alert
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const count = urlParams.get('count');
    const newcats = urlParams.get('newcats');

    if (status === 'added') Swal.fire({ title: 'สำเร็จ!', text: 'เพิ่มหมวดหมู่เรียบร้อย', icon: 'success', confirmButtonColor: '#2563eb', customClass: { popup: 'rounded-4' } }).then(() => window.history.replaceState(null, null, window.location.pathname));
    else if (status === 'edited') Swal.fire({ title: 'สำเร็จ!', text: 'แก้ไขชื่อหมวดหมู่เรียบร้อย', icon: 'success', confirmButtonColor: '#f59e0b', customClass: { popup: 'rounded-4' } }).then(() => window.history.replaceState(null, null, window.location.pathname));
    else if (status === 'deleted') Swal.fire({ title: 'สำเร็จ!', text: 'ลบหมวดหมู่เรียบร้อย', icon: 'success', confirmButtonColor: '#2563eb', customClass: { popup: 'rounded-4' } }).then(() => window.history.replaceState(null, null, window.location.pathname));
    else if (status === 'error_used') Swal.fire({ title: 'ลบไม่ได้!', text: 'หมวดหมู่นี้มีหนังสืออยู่ กรุณาย้ายหมวดหมู่ของหนังสือก่อน', icon: 'error', confirmButtonColor: '#ef4444', customClass: { popup: 'rounded-4' } }).then(() => window.history.replaceState(null, null, window.location.pathname));
    else if (status === 'auto_success') {
        Swal.fire({
            title: 'อัปเดตเรียบร้อย!',
            html: `ระบบทำการจัดหมวดหมู่ให้หนังสือจำนวน <b style="color:#2563eb;">${count}</b> เล่ม <br> และสร้างหมวดหมู่ใหม่ขึ้นมา <b style="color:#8b5cf6;">${newcats}</b> หมวด`,
            icon: 'success',
            confirmButtonColor: '#8b5cf6',
            customClass: { popup: 'rounded-4' }
        }).then(() => window.history.replaceState(null, null, window.location.pathname));
    }
});

function confirmDelete(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "หากลบแล้วจะไม่สามารถกู้คืนได้!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#e2e8f0',
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: '<span style="color:#1e293b">ยกเลิก</span>',
        customClass: { popup: 'rounded-4' }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'manage_categories.php?delete=' + id;
        }
    })
}

function confirmAutoCat() {
    Swal.fire({
        title: 'สร้างหมวดหมู่อัตโนมัติ',
        html: "<div style='text-align:left; font-size:14px;'><p>ระบบจะทำการประมวลผลดังนี้:</p><ul style='color:#6d28d9;'><li>จัดหนังสือเข้าหมวดเดิมที่มีอยู่ (วิเคราะห์จากชื่อ)</li><li><b>ดึงชื่อหนังสือมาสร้างเป็นหมวดหมู่ใหม่ให้เอง</b> สำหรับหนังสือที่ตกหล่น</li></ul></div>",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#8b5cf6',
        cancelButtonColor: '#e2e8f0',
        confirmButtonText: 'เริ่มจัดหมวดหมู่',
        cancelButtonText: '<span style="color:#1e293b">ยกเลิก</span>',
        customClass: { popup: 'rounded-4' }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังประมวลผล...',
                text: 'ระบบกำลังวิเคราะห์ข้อมูลหนังสือทั้งหมด...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading() }
            });
            document.getElementById('autoCatForm').submit();
        }
    })
}
</script>
</body>
</html>