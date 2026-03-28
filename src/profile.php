<?php
session_start();
require_once 'config.php';

// เช็คว่าล็อกอินหรือยัง?
if (!isset($_SESSION['user_id'])) {
    header("Location: landing.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'];
$user_role = $_SESSION['role'];

// 1. ดึงข้อมูล User จากฐานข้อมูล
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$userData = $stmtUser->fetch();

// 2. ดึงสถิติส่วนตัว
// - กำลังยืมอยู่
$stmtBorrow = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'borrowed'");
$stmtBorrow->execute([$user_id]);
$my_borrowed = $stmtBorrow->fetchColumn();

// - ค้างส่ง (เกินกำหนด)
$stmtOverdue = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'borrowed' AND due_date < NOW()");
$stmtOverdue->execute([$user_id]);
$my_overdue = $stmtOverdue->fetchColumn();

// - ประวัติการยืมทั้งหมด
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
$stmtTotal->execute([$user_id]);
$my_total = $stmtTotal->fetchColumn();

// เช็คสถานะแจ้งเตือน (กรณีถ้ามี Block จากเกินกำหนดส่ง)
$is_blocked = ($my_overdue > 0);

// สมมติว่าเช็คการผูก LINE จากคอลัมน์ line_uid (ถ้าไม่มีคอลัมน์นี้ ระบบจะมองว่ายังไม่เชื่อมต่อ)
$has_line = !empty($userData['line_uid']); 

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลส่วนตัว - Library Hub</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* === นำ CSS หลักมาใช้ (แบบเดียวกับ index.php) === */
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

        /* Sidebar & Topbar (โครงสร้างเดิม) */
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

        /* Main Wrapper */
        .main-wrapper { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar { height: 90px; background: rgba(255, 255, 255, .85); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 32px; gap: 16px; position: sticky; top: 0; z-index: 50; justify-content: space-between; }
        .topbar-greeting { flex: 1; display: flex; align-items: center; gap: 16px; }
        .menu-toggle { display: none; width: 36px; height: 36px; align-items: center; justify-content: center; border: 1px solid var(--border); border-radius: 8px; background: white; cursor: pointer; font-size: 16px; color: var(--text-secondary); }

        /* === PROFILE PAGE STYLES === */
        .page-content { padding: 32px; flex: 1; }
        
        .card { background: var(--card-bg); border-radius: var(--card-radius); border: 1px solid var(--border); box-shadow: var(--shadow-sm); margin-bottom: 24px; padding: 24px; }
        
        /* 1. Header Profile */
        .profile-header { display: flex; align-items: center; gap: 24px; }
        .profile-avatar-large { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), #7c3aed); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 40px; flex-shrink: 0; box-shadow: 0 10px 25px rgba(37,99,235,0.2); }
        .profile-title { font-size: 24px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; }
        .profile-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; background: var(--accent-soft); color: var(--accent); border: 1px solid var(--accent-light); }

        /* 2. Mini Stats */
        .mini-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .mini-stat-card { background: var(--card-bg); border-radius: 12px; padding: 20px; border: 1px solid var(--border); display: flex; align-items: center; gap: 16px; transition: transform 0.2s; box-shadow: var(--shadow-sm); }
        .mini-stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .ms-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .ms-info h4 { font-size: 24px; font-weight: 700; margin: 0; color: var(--text-primary); line-height: 1; }
        .ms-info p { font-size: 13px; color: var(--text-muted); margin: 4px 0 0 0; font-weight: 500;}

        /* 3. Detail Grid */
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .detail-card-title { font-size: 16px; font-weight: 700; border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 20px; color: var(--text-primary); display: flex; align-items: center; gap: 10px;}
        
        .info-row { display: flex; flex-direction: column; margin-bottom: 16px; }
        .info-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 4px; }
        .info-value { font-size: 15px; color: var(--text-primary); font-weight: 500; background: var(--body-bg); padding: 10px 16px; border-radius: 8px; border: 1px solid var(--border);}
        
        /* Line Status Box */
        .line-status-box { padding: 16px; border-radius: 12px; display: flex; align-items: center; gap: 16px; margin-top: 10px;}
        .line-status-box.connected { background: #ecfdf5; border: 1px solid #a7f3d0; }
        .line-status-box.disconnected { background: #f8fafc; border: 1px solid var(--border); }
        .line-icon-large { font-size: 32px; color: #00B900; }
        
        .form-control-custom { width: 100%; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-family: var(--font-main); font-size: 14px; background: var(--body-bg); color: var(--text-primary); transition: 0.2s; }
        .form-control-custom:focus { border-color: var(--accent); background: white; outline: none; box-shadow: 0 0 0 3px var(--accent-light); }
        
        .btn-modern { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary-modern { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        .btn-primary-modern:hover { background: #1d4ed8; transform: translateY(-2px); }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeup { animation: fadeUp .5s ease both; }
        .delay-1 { animation-delay: .1s; } .delay-2 { animation-delay: .2s; }

        @media (max-width: 1024px) {
            .details-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); box-shadow: 0 0 50px rgba(0, 0, 0, 0.2); }
            .main-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; }
            .mini-stats-grid { grid-template-columns: 1fr; }
            .profile-header { flex-direction: column; text-align: center; }
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
            <a href="#security" class="dropdown-item"><i class="fa-solid fa-key"></i> เปลี่ยนรหัสผ่าน</a>
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
            <h2 class="mb-0" style="font-size: 18px; font-weight: 700;">ข้อมูลส่วนตัว (My Profile)</h2>
        </div>
    </header>

    <main class="page-content">

        <div class="card animate-fadeup" style="padding: 32px;">
            <div class="profile-header">
                <div class="profile-avatar-large"><?php echo mb_substr($user_name, 0, 1); ?></div>
                <div>
                    <h3 class="profile-title"><?php echo htmlspecialchars($userData['fullname']); ?></h3>
                    <div class="profile-badge">
                        <i class="fa-solid <?php echo $user_role == 'admin' ? 'fa-user-shield' : 'fa-user-graduate'; ?> me-2"></i>
                        <?php echo $user_role == 'admin' ? 'บัญชีผู้ดูแลระบบ (Admin)' : 'บัญชีนักเรียน (Student)'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mini-stats-grid animate-fadeup delay-1">
            <div class="mini-stat-card">
                <div class="ms-icon" style="background:#eff6ff;color:var(--accent);"><i class="fa-solid fa-book-open-reader"></i></div>
                <div class="ms-info">
                    <h4><?php echo $my_borrowed; ?></h4>
                    <p>หนังสือที่กำลังยืม</p>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="ms-icon" style="background:#fef2f2;color:var(--danger);"><i class="fa-solid fa-clock"></i></div>
                <div class="ms-info">
                    <h4 style="<?php echo $my_overdue > 0 ? 'color:var(--danger);' : ''; ?>"><?php echo $my_overdue; ?></h4>
                    <p>ค้างส่ง (เกินกำหนด)</p>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="ms-icon" style="background:#f0fdf4;color:var(--success);"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div class="ms-info">
                    <h4><?php echo $my_total; ?></h4>
                    <p>ประวัติการยืมทั้งหมด</p>
                </div>
            </div>
        </div>

<div class="details-grid animate-fadeup delay-2">
            
            <div class="card">
                <div class="detail-card-title"><i class="fa-regular fa-address-card"></i> ข้อมูลการติดต่อ</div>
                
                <div class="info-row">
                    <span class="info-label">รหัสประจำตัว / Student ID</span>
                    <div class="info-value"><?php echo htmlspecialchars($userData['student_id'] ?? 'ยังไม่ระบุรหัสประจำตัว'); ?></div>
                </div>

                <div class="info-row mt-2">
                    <span class="info-label">ประเภทผู้ใช้งาน</span>
                    <div class="info-value">
                        <?php echo $user_role == 'admin' ? 'ผู้ดูแลระบบ (Admin)' : 'นักเรียน (Student)'; ?>
                    </div>
                </div>

                <div class="info-row mt-4">
                    <span class="info-label">ระบบแจ้งเตือนผ่าน LINE</span>
                    <?php if ($has_line): ?>
                        <div class="line-status-box connected">
                            <i class="fab fa-line line-icon-large"></i>
                            <div>
                                <strong style="color:#065f46; display:block;">เชื่อมต่อบัญชีสำเร็จแล้ว</strong>
                                <span style="font-size:12px; color:#047857;">คุณจะได้รับการแจ้งเตือนกำหนดส่งหนังสืออัตโนมัติ</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="line-status-box disconnected">
                            <i class="fab fa-line line-icon-large" style="color:#cbd5e1;"></i>
                            <div>
                                <strong style="color:var(--text-primary); display:block;">ยังไม่ได้เชื่อมต่อบัญชี LINE</strong>
                                <span style="font-size:12px; color:var(--text-muted);">สแกน QR Code จากเมนูเพื่อเชื่อมต่อ</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card" id="activity">
                <div class="detail-card-title d-flex justify-content-between align-items-center">
                    <div><i class="fa-solid fa-list-ul"></i> ทำรายการล่าสุด</div>
                    <a href="my_history.php" class="btn btn-sm btn-light border text-primary fw-bold px-3 rounded-pill" style="font-size: 11px;">ดูทั้งหมด</a>
                </div>
                
                <div class="activity-list" style="max-height: 250px; overflow-y: auto;">
                    <?php
                    // ดึงประวัติ 5 รายการล่าสุด
                    $stmtRecent = $pdo->prepare("SELECT t.*, b.title 
                                                 FROM transactions t 
                                                 JOIN book_items bi ON t.book_item_id = bi.id 
                                                 JOIN book_masters b ON bi.book_master_id = b.id
                                                 WHERE t.user_id = ? 
                                                 ORDER BY t.borrow_date DESC 
                                                 LIMIT 5");
                    $stmtRecent->execute([$user_id]);
                    
                    if ($stmtRecent->rowCount() > 0):
                        while ($recent = $stmtRecent->fetch()):
                            $is_returned = ($recent['status'] == 'returned');
                            $icon = $is_returned ? '<i class="fa-solid fa-arrow-down" style="color:var(--success);"></i>' : '<i class="fa-solid fa-arrow-up" style="color:var(--warning);"></i>';
                            $action_text = $is_returned ? 'คืนหนังสือ' : 'ยืมหนังสือ';
                            $bg_color = $is_returned ? '#f0fdf4' : '#fffbeb';
                    ?>
                            <div class="d-flex align-items-start gap-3 mb-3 pb-3 border-bottom border-light">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $bg_color; ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px;">
                                    <?php echo $icon; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold" style="font-size: 13px; color: var(--text-primary);"><?php echo htmlspecialchars($recent['title']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                        ทำรายการ <?php echo $action_text; ?> เมื่อ <?php echo date('d/m/Y', strtotime($recent['borrow_date'])); ?>
                                    </div>
                                </div>
                            </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div class="text-center py-4" style="color: var(--text-muted);">
                            <i class="fa-solid fa-clock-rotate-left fa-2x mb-2 opacity-50"></i>
                            <p style="font-size: 13px;">ยังไม่มีประวัติการทำรายการ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function() {
    // Toggle Sidebar บนมือถือ
    $('#menuToggle').on('click', function(e) {
        e.stopPropagation();
        $('#sidebar').toggleClass('open');
    });

    // ระบบคลิกโปรไฟล์เปิด Dropdown Menu
    $('#userDropdownTrigger').on('click', function(e) {
        e.stopPropagation();
        $('#userDropdown').toggleClass('show');
        if ($('#userDropdown').hasClass('show')) {
            $('#userDropdownIcon').css('transform', 'rotate(180deg)');
        } else {
            $('#userDropdownIcon').css('transform', 'rotate(0deg)');
        }
    });

    // กดที่ว่างปิดเมนู
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
</script>
</body>
</html>