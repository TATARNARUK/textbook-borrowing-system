<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ (สามารถเข้าถึงได้ทั้ง Student และ Admin)
if (!isset($_SESSION['user_id'])) {
    header("Location: landing.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'];
$user_role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คู่มือการใช้งาน</title>
    <link rel="icon" type="image/png" href="images/books.png">
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* === Page Specific (Premium Manual) === */
        .page-content { padding: 32px; flex: 1; display: flex; flex-direction: column; align-items: center; }
        
        .main-container {
            width: 100%;
            max-width: 1000px;
        }

        /* 🌟 Premium Hero Banner */
        .manual-hero {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6, #06b6d4);
            border-radius: 24px;
            padding: 50px 40px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.2);
        }
        .manual-hero::before {
            content: ''; position: absolute; top: -50%; left: -20%; width: 60%; height: 200%; background: rgba(255,255,255,0.05); transform: rotate(30deg);
        }
        .manual-hero::after {
            content: ''; position: absolute; bottom: -30px; right: -30px; width: 150px; height: 150px; border-radius: 50%; background: rgba(255,255,255,0.1); backdrop-filter: blur(5px);
        }

        .hero-icon-wrapper {
            width: 80px; height: 80px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
            position: relative; z-index: 2;
        }

        .manual-hero h2 { font-size: 28px; font-weight: 800; margin-bottom: 8px; position: relative; z-index: 2; text-shadow: 0 2px 4px rgba(0,0,0,0.2);}
        .manual-hero p { font-size: 15px; opacity: 0.9; margin: 0; position: relative; z-index: 2; font-weight: 400;}

        /* 🌟 Premium Card Tabs */
        .custom-tabs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 40px;
        }

        .tab-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            color: var(--text-secondary);
        }

        .tab-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-light);
            color: var(--accent);
        }

        .tab-card.active {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            transform: translateY(-5px);
        }

        .tab-icon { font-size: 24px; margin-bottom: 12px; transition: 0.3s;}
        .tab-card.active .tab-icon { transform: scale(1.1); }
        .tab-title { font-size: 15px; font-weight: 700; }

        /* 🌟 Beautiful Content Boxes */
        .manual-content-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #ffffff; /* ขอบขาวเพื่อให้ดูนูน */
            margin-bottom: 30px;
        }

        .step-row {
            display: flex;
            align-items: flex-start;
            gap: 32px;
            margin-bottom: 48px;
        }
        .step-row:last-child { margin-bottom: 0; }

        .step-number {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--accent), #6d28d9);
            color: white;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }

        .step-text { flex: 1; }
        .step-text h4 { font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; }
        .step-text p { font-size: 14px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 20px;}

        /* Premium Image Frame */
        .img-showcase {
            background: #f1f5f9;
            padding: 12px;
            border-radius: 16px;
            display: inline-block;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
            transition: 0.3s;
        }
        .img-showcase:hover {
            transform: scale(1.02);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02), 0 10px 25px rgba(0,0,0,0.08);
        }
        .img-showcase img {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            max-width: 100%;
        }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeup { animation: fadeUp .5s ease both; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); box-shadow: 0 0 50px rgba(0, 0, 0, 0.2); }
            .main-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; }
            .page-content { padding: 20px 16px; }
            .manual-hero { padding: 30px 20px; }
            .custom-tabs { grid-template-columns: 1fr; gap: 12px; }
            .step-row { flex-direction: column; gap: 16px; }
            .manual-content-card { padding: 24px; }
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
            <h2 class="mb-0" style="font-size: 18px; font-weight: 700;">ศูนย์ช่วยเหลือ (Help Center)</h2>
        </div>
    </header>

    <main class="page-content">

        <div class="main-container">

            <div class="manual-hero animate-fadeup">
                <div class="hero-icon-wrapper"><i class="fa-solid fa-book-open-reader"></i></div>
                <h2>คู่มือการใช้งานระบบยืม-คืนหนังสือ</h2>
                <p>เรียนรู้วิธีการใช้งานฟังก์ชันต่างๆ ภายในระบบได้อย่างง่ายดายผ่านขั้นตอนด้านล่างนี้</p>
            </div>

            <div class="custom-tabs animate-fadeup delay-1" id="manualTabs">
                <div class="tab-card active" data-target="step1">
                    <div class="tab-icon"><i class="fa-solid fa-right-to-bracket"></i></div>
                    <div class="tab-title">1. การเข้าสู่ระบบ</div>
                </div>
                <div class="tab-card" data-target="step2">
                    <div class="tab-icon"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
                    <div class="tab-title">2. การค้นหาและยืม</div>
                </div>
                <div class="tab-card" data-target="step3">
                    <div class="tab-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div class="tab-title">3. ตรวจสอบประวัติ</div>
                </div>
            </div>

            <div class="manual-content-card animate-fadeup delay-2 tab-content" id="step1">
                <div class="step-row">
                    <div class="step-number">1</div>
                    <div class="step-text">
                        <h4>เข้าสู่ระบบด้วยรหัสนักเรียน</h4>
                        <p>หากท่านยังไม่มีบัญชี กรุณาติดต่อคุณครูเพื่อขอรับรหัสนักเรียน จากนั้นกรอก <strong>รหัสนักเรียน</strong> และ <strong>รหัสผ่าน</strong> ลงในแบบฟอร์มเพื่อเข้าใช้งานระบบ</p>
                        <div class="img-showcase"><img src="images/login.png" alt="Login Form" style="max-width: 400px;"></div>
                    </div>
                </div>
                <div class="step-row">
                    <div class="step-number" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fa-solid fa-key"></i></div>
                    <div class="step-text">
                        <h4>กรณีลืมรหัสผ่าน</h4>
                        <p>หากคุณจำรหัสผ่านไม่ได้ หรือไม่สามารถเข้าสู่ระบบได้ <strong>กรุณาติดต่อแอดมินหรือเจ้าหน้าที่ห้องสมุด</strong> เพื่อให้เจ้าหน้าที่ทำการรีเซ็ตรหัสผ่านใหม่ให้กับคุณผ่านระบบจัดการผู้ใช้</p>
                        <div class="img-showcase"><img src="images/login2.png" alt="Forgot Password" style="max-width: 400px;"></div>
                    </div>
                </div>
            </div>

            <div class="manual-content-card tab-content" id="step2" style="display: none;">
                <div class="step-row">
                    <div class="step-number">1</div>
                    <div class="step-text">
                        <h4>ค้นหาหนังสือที่ต้องการ</h4>
                        <p>กดไปที่เมนู <strong>"รายการหนังสือ"</strong> และใช้ช่องค้นหาด้านบน เพื่อพิมพ์ชื่อหนังสือ, รหัสวิชา หรือชื่อผู้แต่ง ระบบจะคัดกรองหนังสือที่ตรงกับคำค้นหาของคุณขึ้นมาทันที</p>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="img-showcase"><img src="images/s.png" alt="Search Box" style="max-width: 350px;"></div>
                            <div class="img-showcase"><img src="images/ss.png" alt="Search Result" style="max-width: 350px;"></div>
                        </div>
                    </div>
                </div>
                <div class="step-row">
                    <div class="step-number">2</div>
                    <div class="step-text">
                        <h4>วิธีกดขอยืมหนังสือ</h4>
                        <p>ให้สังเกตที่ป้ายสถานะบนหน้าปกหนังสือ หากแสดงคำว่า <span class="badge bg-success" style="font-size: 11px;">ว่าง</span> คุณสามารถกดปุ่มยืมรูปหัวใจด้านล่างได้เลย จากนั้นกดยืนยันในหน้าต่างที่เด้งขึ้นมา</p>
                        <div class="img-showcase"><img src="images/sss.png" alt="Borrow Button" style="max-width: 350px;"></div>
                    </div>
                </div>
            </div>

            <div class="manual-content-card tab-content" id="step3" style="display: none;">
                <div class="step-row">
                    <div class="step-number">1</div>
                    <div class="step-text">
                        <h4>ตรวจสอบรายการที่ยืม</h4>
                        <p>ไปที่เมนู <strong>"ประวัติการยืม"</strong> เพื่อดูรายการหนังสือทั้งหมดที่คุณได้ทำการยืมไป รวมถึงสถานะการคืนด้วย ระบบจะแสดงประวัติทั้งหมดอย่างเป็นระเบียบ</p>
                        <div class="img-showcase"><img src="images/history1.png" alt="History Menu" style="max-width: 300px;"></div>
                    </div>
                </div>
                <div class="step-row">
                    <div class="step-number" style="background: linear-gradient(135deg, #ef4444, #b91c1c);"><i class="fa-regular fa-calendar-xmark"></i></div>
                    <div class="step-text">
                        <h4>วันกำหนดส่งคืน</h4>
                        <p>ในตารางประวัติจะระบุ <strong>"วันกำหนดส่งคืน"</strong> อย่างชัดเจน หากเลยกำหนด ระบบจะขึ้นตัวอักษรสีแดงเตือน และถ้ามีการผูก LINE ไว้ ระบบจะส่งข้อความแจ้งเตือนอัตโนมัติด้วย</p>
                        <div class="img-showcase"><img src="images/history.png" alt="Due Date" style="max-width: 500px;"></div>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
$(document).ready(function() {
    AOS.init({ duration: 600, once: true });

    // Toggle Sidebar
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

    // Custom Tab Logic
    $('.tab-card').on('click', function() {
        // เปลี่ยนสถานะ Tab
        $('.tab-card').removeClass('active');
        $(this).addClass('active');

        // ซ่อนเนื้อหาทั้งหมด แล้วแสดงเฉพาะอันที่ถูกเลือกพร้อมเอฟเฟกต์เฟด
        const target = $(this).data('target');
        $('.tab-content').hide();
        $('#' + target).fadeIn(300);
    });
});
</script>
</body>
</html>