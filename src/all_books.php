<?php
session_start();
require_once 'config.php';

// เช็ค Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// กำหนดตัวแปร Session สำหรับ UI
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'] ?? 'Unknown';
$user_role = $_SESSION['role'] ?? 'student';
$current_page = basename($_SERVER['PHP_SELF']);

// 🔥 ระบบแปลงลิงก์ Google Drive ให้เป็นไฟล์ PDF ดิบ
if (isset($_GET['read_pdf'])) {
    $id = $_GET['read_pdf'];
    $stmt = $pdo->prepare("SELECT sample_pdf FROM book_masters WHERE id = ?");
    $stmt->execute([$id]);
    $pdf = $stmt->fetchColumn();

    if (!empty($pdf)) {
        $fileId = '';
        if (strpos($pdf, 'drive.google.com') !== false) {
            if (preg_match('/d\/(.*?)\//', $pdf, $matches)) {
                $fileId = $matches[1];
            } elseif (preg_match('/id=([^&]+)/', $pdf, $matches)) {
                $fileId = $matches[1];
            }
        }
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="book_preview_' . $id . '.pdf"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        if ($fileId) {
            $url = "https://drive.google.com/uc?export=download&id=" . $fileId;
            @readfile($url);
        } else {
            $path = (strpos($pdf, 'http') === 0) ? $pdf : "uploads/pdfs/" . $pdf;
            @readfile($path);
        }
        exit();
    }
}

// 🔥 BLOCKING LOGIC
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'borrowed' AND due_date < NOW()");
$stmtCheck->execute([$user_id]);
$overdue_count = $stmtCheck->fetchColumn();
$is_blocked = ($overdue_count > 0);

// --- ส่วนจัดการหมวดหมู่ ---
try {
    $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    $has_categories = true;
} catch (Exception $e) {
    $cats = [];
    $has_categories = false;
}

// รับค่าค้นหา และ หมวดหมู่
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_cat = isset($_GET['cat']) ? $_GET['cat'] : '';

// สร้าง SQL Query
$sql = "SELECT * FROM book_masters WHERE 1=1";
$params = [];
if ($search) {
    $sql .= " AND (title LIKE :q OR author LIKE :q OR isbn LIKE :q OR book_code LIKE :q)";
    $params[':q'] = "%$search%";
}
if ($filter_cat && $has_categories) {
    $sql .= " AND category_id = :cat";
    $params[':cat'] = $filter_cat;
}
$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>หนังสือทั้งหมด</title>
    <link rel="icon" type="image/png" href="images/books.png">
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* === นำ CSS หลักมาใช้ === */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --sidebar-w: 260px; --sidebar-bg: #ffffff; --body-bg: #f0f4fb;
            --accent: #2563eb; --accent-light: #dbeafe; --accent-soft: #eff6ff;
            --text-primary: #0f172a; --text-secondary: #64748b; --text-muted: #94a3b8;
            --border: #e2e8f0; --card-bg: #ffffff; --card-radius: 16px;
            --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
            --font-main: 'DM Sans', 'Noto Sans Thai', sans-serif;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
        }
        body { font-family: var(--font-main); background: var(--body-bg); color: var(--text-primary); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* Sidebar */
        .sidebar { width: var(--sidebar-w); height: 100vh; background: var(--sidebar-bg); border-right: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; z-index: 1000; transition: transform .3s ease; }
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
        
        /* Dropdown Profile */
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

        /* Main Wrapper & Topbar */
        .main-wrapper { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar { height: 90px; background: rgba(255, 255, 255, .85); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 32px; gap: 16px; position: sticky; top: 0; z-index: 50; justify-content: space-between; }
        .topbar-greeting { flex: 1; display: flex; align-items: center; gap: 16px; }
        .menu-toggle { display: none; width: 36px; height: 36px; align-items: center; justify-content: center; border: 1px solid var(--border); border-radius: 8px; background: white; cursor: pointer; font-size: 16px; color: var(--text-secondary); }

        /* Page Content & Alert */
        .page-content { padding: 32px; flex: 1; }
        .alert-banner { background: linear-gradient(135deg, #fef2f2, #fee2e2); border: 1px solid #fecaca; border-radius: var(--card-radius); padding: 16px 20px; display: flex; align-items: center; gap: 14px; margin-bottom: 24px; }
        .alert-banner .alert-icon { width: 40px; height: 40px; border-radius: 10px; background: var(--danger); display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; flex-shrink: 0; }
        .alert-banner h6 { font-size: 14px; font-weight: 600; color: #991b1b; margin-bottom: 3px; }
        .alert-banner p { font-size: 12px; color: #b91c1c; margin: 0; }

        /* === Search Box Card === */
        .search-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-bottom: 30px;
        }

        .search-input-group { position: relative; flex-grow: 1; }
        .search-input-group i.fa-magnifying-glass { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-input-group .btn-clear { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 14px; }
        .search-input-group .btn-clear:hover { color: var(--danger); }
        .search-input-custom {
            width: 100%;
            padding: 12px 40px 12px 42px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--body-bg);
            font-family: var(--font-main);
            color: var(--text-primary);
            transition: 0.2s;
        }
        .search-input-custom:focus { border-color: var(--accent); background: white; outline: none; box-shadow: 0 0 0 3px var(--accent-light); }
        .search-select-custom { padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--body-bg); font-family: var(--font-main); color: var(--text-primary); cursor: pointer; transition: 0.2s;}
        .search-select-custom:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-light); outline: none; }
        .btn-search { background: var(--accent); color: white; border: none; border-radius: 12px; padding: 0 24px; font-weight: 600; transition: 0.2s; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        .btn-search:hover { background: #1d4ed8; transform: translateY(-2px); color: white; }

        /* === Book Cards Grid === */
        .book-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        .book-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-md); border-color: var(--accent-light); }
        
        .book-cover-container { position: relative; padding-top: 135%; background: var(--body-bg); overflow: hidden; }
        .book-cover { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .book-card:hover .book-cover { transform: scale(1.05); }
        
        .badge-status { position: absolute; top: 12px; right: 12px; z-index: 2; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; box-shadow: 0 4px 10px rgba(0,0,0,0.1); backdrop-filter: blur(4px); }
        .bg-available { background: rgba(16, 185, 129, 0.9); color: white; border: 1px solid #34d399; }
        .bg-out { background: rgba(239, 68, 68, 0.9); color: white; border: 1px solid #f87171; }
        
        .book-info { padding: 16px; flex-grow: 1; display: flex; flex-direction: column; }
        .book-title { font-size: 15px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .book-author { font-size: 12px; color: var(--text-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 4px; }

        .btn-pdf-mini { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #ef4444; background: #fef2f2; border: 1px solid #fecaca; padding: 3px 10px; border-radius: 20px; text-decoration: none; font-weight: 600; transition: 0.2s; margin-bottom: 12px; align-self: flex-start;}
        .btn-pdf-mini:hover { background: #ef4444; color: white; }

        .btn-modern-full { width: 100%; padding: 10px; border-radius: 10px; font-weight: 600; font-size: 13px; border: none; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: auto; }
        .btn-borrow { background: var(--accent-soft); color: var(--accent); }
        .btn-borrow:hover { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        .btn-disabled { background: var(--body-bg); color: var(--text-muted); cursor: not-allowed; }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeup { animation: fadeUp .5s ease both; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); box-shadow: 0 0 50px rgba(0, 0, 0, 0.2); }
            .main-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; }
            .page-content { padding: 20px 16px; }
            .search-form-row { flex-direction: column; }
        }
    </style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fa-solid fa-book-open"></i></div>
        <div class="logo-text">
            <strong>TEXTBOOK BORROWING SYSTEM</strong>
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
            <h2 class="mb-0" style="font-size: 18px; font-weight: 700;">📚 คลังหนังสือทั้งหมด</h2>
        </div>
    </header>

    <main class="page-content">

        <?php if ($is_blocked): ?>
            <div class="alert-banner animate-fadeup">
                <div class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <h6>สิทธิ์การยืมถูกระงับชั่วคราว!</h6>
                    <p>คุณมีหนังสือที่เกินกำหนดส่งคืน <strong><?php echo $overdue_count; ?> เล่ม</strong> กรุณาติดต่อคืนหนังสือที่ห้องสมุดก่อน</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="search-card animate-fadeup">
            <form action="" method="GET" class="d-flex gap-3 search-form-row">
                
                <?php if ($has_categories): ?>
                <select name="cat" class="search-select-custom" style="min-width: 200px;" onchange="this.form.submit()">
                    <option value="">✨ ทุกหมวดหมู่</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($filter_cat == $c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <div class="search-input-group">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="q" class="search-input-custom" placeholder="ค้นหาชื่อหนังสือ, ผู้แต่ง, รหัสวิชา..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if ($search || $filter_cat): ?>
                        <a href="all_books.php" class="btn-clear"><i class="fa-solid fa-circle-xmark"></i> ล้างการค้นหา</a>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn-search">ค้นหา</button>
            </form>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 animate-fadeup">
            <h5 class="fw-bold m-0" style="font-size: 16px; color: var(--text-primary);">
                <?php 
                    $cat_name = "แสดงหนังสือทั้งหมด";
                    if($filter_cat && $has_categories) {
                        foreach($cats as $c) { if($c['id'] == $filter_cat) $cat_name = "หมวดหมู่: " . $c['name']; }
                    }
                    echo $search ? 'ผลการค้นหา: "' . htmlspecialchars($search) . '"' : $cat_name; 
                ?>
            </h5>
            <span style="font-size: 13px; font-weight: 600; color: var(--text-muted); background: var(--card-bg); padding: 4px 12px; border-radius: 20px; border: 1px solid var(--border);">
                พบ <?php echo count($books); ?> รายการ
            </span>
        </div>

        <div class="row g-4 mb-5">
            <?php if (count($books) > 0): ?>
                <?php foreach ($books as $book):
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ? AND status = 'available'");
                    $stmtCount->execute([$book['id']]);
                    $available = $stmtCount->fetchColumn();
                    
                    // Logic รูปภาพ
                    $cover = $book['cover_image'];
                    $cover = str_replace(' ', '%20', $cover);
                    if (strpos($cover, 'http') === 0) { $img = $cover; } 
                    elseif (!empty($cover)) {
                        if (file_exists("uploads/" . urldecode($cover))) { $img = "uploads/" . $cover; } 
                        else { $img = "https://itdev.bncc.ac.th/vbss/Education_system/other/img/uploads/" . $cover; }
                    } else { $img = "https://via.placeholder.com/300x450?text=No+Cover"; }

                    $pdf = $book['sample_pdf'];
                ?>
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2 animate-fadeup">
                        <div class="book-card" data-id="<?php echo $book['id']; ?>">
                            
                            <div class="book-cover-container">
                                <?php if ($available > 0): ?>
                                    <span class="badge-status bg-available">ว่าง <?php echo $available; ?></span>
                                <?php else: ?>
                                    <span class="badge-status bg-out">หมด</span>
                                <?php endif; ?>
                                <img src="<?php echo $img; ?>" class="book-cover" alt="Cover">
                            </div>

                            <div class="book-info">
                                <div class="book-title" title="<?php echo htmlspecialchars($book['title']); ?>"><?php echo htmlspecialchars($book['title']); ?></div>
                                <div class="book-author"><i class="fa-solid fa-pen-nib"></i> <?php echo htmlspecialchars($book['author']); ?></div>
                                
                                <?php if (!empty($pdf)): ?>
                                    <a href="all_books.php?read_pdf=<?php echo $book['id']; ?>#toolbar=0&navpanes=0" target="_blank" class="btn-pdf-mini" onclick="event.stopPropagation();">
                                        <i class="fa-solid fa-file-pdf"></i> ตัวอย่าง
                                    </a>
                                <?php else: ?>
                                    <div style="height: 31px; margin-bottom: 12px;"></div> <?php endif; ?>

                                <?php if ($is_blocked): ?>
                                    <button class="btn-modern-full btn-disabled" disabled><i class="fa-solid fa-ban"></i> ระงับสิทธิ์</button>
                                <?php elseif ($available > 0): ?>
                                    <button type="button" class="btn-modern-full btn-borrow btn-borrow-click" data-id="<?php echo $book['id']; ?>" data-title="<?php echo htmlspecialchars($book['title']); ?>">
                                        <i class="fa-solid fa-hand-holding-heart"></i> ยืมหนังสือ
                                    </button>
                                <?php else: ?>
                                    <button class="btn-modern-full btn-disabled" disabled><i class="fa-solid fa-box-open"></i> หนังสือหมด</button>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 animate-fadeup">
                    <div style="width: 80px; height: 80px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
                        <i class="fa-solid fa-box-open fa-2x text-muted"></i>
                    </div>
                    <h5 class="fw-bold" style="color: var(--text-primary);">ไม่พบหนังสือที่คุณค้นหา</h5>
                    <p style="color: var(--text-muted); font-size: 14px;">ลองเปลี่ยนคำค้นหา หรือเลือกหมวดหมู่ใหม่อีกครั้ง</p>
                    <a href="all_books.php" class="btn btn-primary rounded-pill px-4 mt-2 shadow-sm" style="font-weight: 600;">ดูหนังสือทั้งหมด</a>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
$(document).ready(function() {
    // โหลด Animation
    AOS.init({ duration: 600, once: true });

    // Toggle Sidebar Mobile
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

    // คลิกลงบนการ์ดหนังสือให้ไปหน้า Detail
    $('.book-card').on('click', function(e) {
        // ดักไม่ให้คลิกโดนปุ่มแล้วเปลี่ยนหน้าซ้อนกัน
        if ($(e.target).closest('.btn-borrow-click, .btn-pdf-mini').length) return;
        const id = $(this).data('id');
        if(id) window.location.href = 'book_detail.php?id=' + id;
    });

    // ป๊อปอัพยืนยันการยืม
    $('.btn-borrow-click').on('click', function(e) {
        e.stopPropagation();
        const id = $(this).data('id');
        const title = $(this).data('title');
        
        Swal.fire({
            title: 'ยืนยันการยืม?',
            text: title,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb', // สีน้ำเงินใหม่ (Accent)
            cancelButtonColor: '#e2e8f0',
            cancelButtonText: '<span style="color:#1e293b">ยกเลิก</span>',
            confirmButtonText: 'ยืนยันการยืม',
            customClass: { popup: 'rounded-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'borrow_save.php?id=' + id;
            }
        });
    });

    // แจ้งเตือน URL Status
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    if (status === 'success') {
        Swal.fire({ title: 'ยืมสำเร็จ!', text: 'อย่าลืมคืนหนังสือภายใน 7 วันนะครับ', icon: 'success', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-4' } })
            .then(() => { window.history.replaceState(null, null, window.location.pathname); });
    } else if (status === 'duplicate') {
        Swal.fire({ title: 'ยืมไม่ได้!', text: 'คุณมีหนังสือเล่มนี้อยู่แล้ว', icon: 'warning', confirmButtonColor: '#f59e0b', customClass: { popup: 'rounded-4' } })
            .then(() => { window.history.replaceState(null, null, window.location.pathname); });
    } else if (status === 'error') {
        Swal.fire({ title: 'ขออภัย', text: 'หนังสือเล่มนี้หมดพอดี', icon: 'error', confirmButtonColor: '#ef4444', customClass: { popup: 'rounded-4' } })
            .then(() => { window.history.replaceState(null, null, window.location.pathname); });
    }
});
</script>
</body>
</html>