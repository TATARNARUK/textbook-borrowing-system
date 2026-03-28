<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// กำหนดตัวแปรสำหรับ UI
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'];
$user_role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);

// กำหนดค่าเริ่มต้นสำหรับค้นหาวันที่
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$transactions = [];
$db_error = null;

try {
    $sql = "SELECT t.*, u.fullname, u.student_id, b.title, b.book_code 
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            JOIN book_items bi ON t.book_item_id = bi.id
            JOIN book_masters b ON bi.book_master_id = b.id
            WHERE date(t.borrow_date) BETWEEN :start AND :end
            ORDER BY t.borrow_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['start' => $start_date, 'end' => $end_date]);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสรุปการยืม-คืน</title>
    <link rel="icon" type="image/png" href="images/books.png">

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* === นำ CSS หลักมาใช้ (InsightHub Style) === */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --sidebar-w: 260px;
            --sidebar-bg: #ffffff;
            --body-bg: #f8fafc;
            --accent: #2563eb;
            --accent-light: #dbeafe;
            --accent-soft: #eff6ff;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border: #f1f5f9;
            --card-bg: #ffffff;
            --card-radius: 16px;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --font-main: 'DM Sans', 'Noto Sans Thai', sans-serif;
            --shadow-sm: 0 4px 15px rgba(0, 0, 0, 0.02);
            --shadow-md: 0 10px 25px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: var(--font-main);
            background: var(--body-bg);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar & Topbar (โครงสร้างเดิมที่ให้ไป) */
        .sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: transform .3s ease;
        }

        .sidebar-logo {
            height: 90px;
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
        }

        .logo-text {
            line-height: 1.2;
            padding-right: 5px;
        }

        .logo-text strong {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-primary);
            display: block;
            word-wrap: break-word;
            line-height: 1.3;
        }

        .logo-text small {
            font-size: 11px;
            color: var(--text-muted);
            display: block;
            margin-top: 2px;
        }

        .sidebar-section {
            padding: 20px 16px 8px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 8px;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 10px;
            color: var(--text-secondary);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
            margin-bottom: 4px;
        }

        .nav-item:hover {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .nav-item.active {
            background: var(--accent);
            color: white;
        }

        .nav-item .nav-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            background: transparent;
            transition: all .2s;
            flex-shrink: 0;
        }

        .nav-item.active .nav-icon {
            background: rgba(255, 255, 255, .2);
        }

        .nav-item:hover:not(.active) .nav-icon {
            background: var(--accent-light);
            color: var(--accent);
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
            background-color: var(--sidebar-bg);
            position: relative;
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 12px;
            background: var(--accent-soft);
            cursor: pointer;
            transition: background .2s;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 13px;
            color: var(--text-muted);
        }

        .user-dropdown {
            position: absolute;
            bottom: calc(100% + 5px);
            left: 16px;
            right: 16px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            padding: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1050;
        }

        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background: var(--body-bg);
            color: var(--accent);
        }

        .dropdown-item i {
            font-size: 14px;
            color: var(--text-muted);
            width: 20px;
            text-align: center;
            transition: all 0.2s;
        }

        .dropdown-item:hover i {
            color: var(--accent);
        }

        .dropdown-divider {
            height: 1px;
            background: var(--border);
            margin: 6px 0;
        }

        .dropdown-item.text-danger {
            color: var(--danger);
        }

        .dropdown-item.text-danger i {
            color: var(--danger);
        }

        .dropdown-item.text-danger:hover {
            background: #fee2e2;
            color: #b91c1c;
        }

        .dropdown-item.text-danger:hover i {
            color: #b91c1c;
        }

        .main-wrapper {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            height: 90px;
            background: rgba(255, 255, 255, .85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            padding: 0 32px;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 50;
            justify-content: space-between;
        }

        .topbar-greeting {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .menu-toggle {
            display: none;
            width: 36px;
            height: 36px;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 16px;
            color: var(--text-secondary);
        }

        /* === Page Specific (Report) === */
        .page-content {
            padding: 32px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .insight-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            padding: 24px;
            margin-bottom: 24px;
        }

        .filter-section {
            display: flex;
            align-items: flex-end;
            gap: 16px;
        }

        .filter-group {
            flex: 1;
        }

        .filter-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
        }

        .form-control-custom {
            width: 100%;
            padding: 12px 16px;
            border-radius: 10px;
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
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-primary-modern {
            background: var(--accent);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-primary-modern:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .btn-outline-modern {
            background: white;
            color: var(--text-secondary);
            border: 1px solid #e2e8f0;
        }

        .btn-outline-modern:hover {
            background: var(--body-bg);
            color: var(--text-primary);
        }

        /* --- Clean Table --- */
        .table-container {
            overflow-x: auto;
        }

        .insight-table {
            width: 100%;
            border-collapse: collapse;
        }

        .insight-table thead th {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 12px 16px;
            border-bottom: 2px solid var(--border);
            text-align: left;
        }

        .insight-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.2s;
        }

        .insight-table tbody tr:hover {
            background-color: var(--body-bg);
        }

        .insight-table td {
            padding: 16px;
            vertical-align: middle;
            color: var(--text-primary);
            font-size: 14px;
        }

        .status-pill {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
        }

        .st-borrow {
            background-color: #fffbeb;
            color: #b45309;
        }

        .st-return {
            background-color: #ecfdf5;
            color: #047857;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        /* --- 🖨️ PRINT MODE (Clean White) --- */
        @media print {
            body {
                background-color: #fff !important;
                color: #000 !important;
                font-family: 'Sarabun', 'Noto Sans Thai', sans-serif !important;
                display: block !important;
            }

            .sidebar,
            .topbar,
            .no-print,
            .btn-modern {
                display: none !important;
            }

            .main-wrapper {
                margin: 0 !important;
            }

            .page-content {
                padding: 0 !important;
            }

            .insight-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .insight-table {
                width: 100%;
                border-collapse: collapse !important;
            }

            .insight-table th,
            .insight-table td {
                border: 1px solid #000 !important;
                color: #000 !important;
                padding: 8px !important;
                font-size: 12pt !important;
            }

            .insight-table tbody tr {
                background: none !important;
            }

            .status-pill {
                border: none !important;
                color: #000 !important;
                padding: 0 !important;
                font-weight: normal;
                background: none !important;
            }

            @page {
                size: A4;
                margin: 2cm;
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeup {
            animation: fadeUp .5s ease both;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
                box-shadow: 0 0 50px rgba(0, 0, 0, 0.2);
            }

            .main-wrapper {
                margin-left: 0;
            }

            .menu-toggle {
                display: flex;
            }

            .page-content {
                padding: 20px 16px;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .insight-table {
                min-width: 800px;
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar" id="sidebar" class="no-print">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="fa-solid fa-book-open"></i></div>
            <div class="logo-text">
                <strong>TEXTBOOK BORROWING</strong>
                <small>ระบบยืม-คืนหนังสือเรียนฟรี</small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section">เมนูหลัก</div>
            <a href="index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fa-solid fa-table-columns"></i></div><span>Dashboard</span>
            </a>
            <a href="all_books.php" class="nav-item <?php echo ($current_page == 'all_books.php') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fa-solid fa-book"></i></div><span>รายการหนังสือ</span>
            </a>
            <a href="my_history.php" class="nav-item <?php echo ($current_page == 'my_history.php') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fa-solid fa-clock-rotate-left"></i></div><span>ประวัติการยืม</span>
            </a>

            <?php if ($user_role == 'admin'): ?>
                <div class="sidebar-section" style="margin-top:8px;">ผู้ดูแลระบบ</div>
                <a href="report.php" class="nav-item <?php echo ($current_page == 'report.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-chart-pie"></i></div><span>รายงานสรุป</span>
                </a>
                <a href="add_book.php" class="nav-item <?php echo ($current_page == 'add_book.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-book-medical"></i></div><span>เพิ่มหนังสือใหม่</span>
                </a>
                <a href="book_stock_list.php" class="nav-item <?php echo ($current_page == 'book_stock_list.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-boxes-stacked"></i></div><span>จัดการสต็อก</span>
                </a>
                <a href="manage_categories.php" class="nav-item <?php echo ($current_page == 'manage_categories.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-layer-group"></i></div><span>จัดการหมวดหมู่</span>
                </a>
                <a href="admin_users.php" class="nav-item <?php echo ($current_page == 'admin_users.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-users-gear"></i></div><span>จัดการผู้ใช้</span>
                </a>
                <a href="import_api.php" class="nav-item <?php echo ($current_page == 'import_api.php') ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fa-solid fa-cloud-arrow-down"></i></div><span>นำเข้าจาก API</span>
                </a>
            <?php endif; ?>

            <div class="sidebar-section" style="margin-top:8px;">อื่นๆ</div>
            <a href="manual.php" class="nav-item <?php echo ($current_page == 'manual.php') ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fa-solid fa-circle-question"></i></div><span>คู่มือการใช้งาน</span>
            </a>
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

        <header class="topbar no-print">
            <div class="topbar-greeting">
                <button class="menu-toggle" id="menuToggle"><i class="fa-solid fa-bars"></i></button>
                <h2 class="mb-0" style="font-size: 18px; font-weight: 700;">📊 รายงานสรุปการยืม-คืน</h2>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn-modern btn-outline-modern d-none d-md-flex">
                    <i class="fa-solid fa-print"></i> พิมพ์รายงาน
                </button>
            </div>
        </header>

        <main class="page-content">

            <?php if (isset($db_error)): ?>
                <div class="alert-banner animate-fadeup no-print">
                    <div class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div>
                        <h6>เกิดข้อผิดพลาดในการดึงข้อมูล!</h6>
                        <p><?php echo htmlspecialchars($db_error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="insight-card animate-fadeup no-print">
                <form method="get" class="filter-section">
                    <div class="filter-group">
                        <span class="filter-label">ตั้งแต่วันที่</span>
                        <input type="date" name="start_date" class="form-control-custom" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">ถึงวันที่</span>
                        <input type="date" name="end_date" class="form-control-custom" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <button type="submit" class="btn-modern btn-primary-modern">
                        <i class="fa-solid fa-magnifying-glass"></i> ค้นหาข้อมูล
                    </button>
                    <button type="button" onclick="window.print()" class="btn-modern btn-outline-modern d-md-none">
                        <i class="fa-solid fa-print"></i> พิมพ์
                    </button>
                </form>
            </div>

            <div class="d-none d-print-block text-center mb-4" style="font-family: 'Sarabun', sans-serif;">
                <h2 style="font-weight: bold; margin-bottom: 8px;">รายงานสรุปการยืม-คืนหนังสือเรียน</h2>
                <p style="font-size: 16pt; margin-bottom: 4px;">แผนกเทคโนโลยีสารสนเทศ (IT Textbook System)</p>
                <p style="font-size: 14pt;">ข้อมูลระหว่างวันที่: <?php echo date('d/m/Y', strtotime($start_date)); ?> ถึง <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
            </div>

            <div class="insight-card animate-fadeup delay-1" style="padding: 0; overflow: hidden;">
                <div class="table-container">
                    <table class="insight-table">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">#</th>
                                <th width="15%">วันที่ยืม</th>
                                <th width="15%">รหัสนักเรียน</th>
                                <th width="25%">ชื่อ-สกุล / ชื่อหนังสือ</th>
                                <th width="15%" class="text-center">สถานะ</th>
                                <th width="15%" class="text-center">วันที่คืน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transactions) > 0): ?>
                                <?php $i = 1;
                                foreach ($transactions as $row):
                                    $is_borrowed = $row['status'] == 'borrowed';
                                ?>
                                    <tr>
                                        <td class="text-center" style="color: var(--text-muted); font-weight: 500;"><?php echo $i++; ?></td>

                                        <td>
                                            <div style="font-weight: 600;"><?php echo date('d/m/Y', strtotime($row['borrow_date'])); ?></div>
                                        </td>

                                        <td>
                                            <span style="font-family: monospace; background: var(--body-bg); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border);">
                                                <?php echo htmlspecialchars($row['student_id'] ?? '-'); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 2px;">
                                                <i class="fa-solid fa-user me-1" style="color:var(--text-muted); font-size:10px;"></i>
                                                <?php echo htmlspecialchars($row['fullname']); ?>
                                            </div>
                                            <div style="font-size: 12px; color: var(--accent); font-weight: 500;">
                                                <i class="fa-solid fa-book-open me-1" style="font-size:10px;"></i>
                                                <?php echo htmlspecialchars($row['title']); ?>
                                                <span style="color:var(--text-muted); font-size:11px;">(รหัส: <?php echo htmlspecialchars($row['book_code'] ?? '-'); ?>)</span>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <?php if ($is_borrowed): ?>
                                                <span class="status-pill st-borrow">กำลังยืม</span>
                                            <?php else: ?>
                                                <span class="status-pill st-return">คืนแล้ว</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <?php if ($row['return_date']): ?>
                                                <span style="font-weight: 600; color: var(--success);">
                                                    <?php echo date('d/m/Y', strtotime($row['return_date'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted);">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-folder-open empty-icon"></i>
                                            <div style="font-weight: 600; color: var(--text-primary);">ไม่พบข้อมูลการยืม-คืนในช่วงเวลานี้</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-none d-print-block mt-5 pt-5" style="font-family: 'Sarabun', sans-serif;">
                <div style="display: flex; justify-content: space-between; padding: 0 40px; margin-top: 50px;">
                    <div style="text-center">
                        <p style="margin-bottom: 20px;">ลงชื่อ ....................................................... ผู้จัดทำ</p>
                        <p class="text-center">(.......................................................)</p>
                    </div>
                    <div style="text-center">
                        <p style="margin-bottom: 20px;">ลงชื่อ ....................................................... ครูที่ปรึกษา</p>
                        <p class="text-center">(.......................................................)</p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        $(document).ready(function() {
            AOS.init({
                duration: 600,
                once: true
            });

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
    </script>
</body>

</html>