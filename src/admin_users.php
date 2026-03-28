<?php
session_start();
require_once 'config.php';

// 1. เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id_session = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'];
$user_role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);

$message = '';
$msg_type = '';

// 2. บันทึกเปลี่ยนรหัสผ่าน
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $target_user_id = $_POST['user_id'];
    $new_password = $_POST['new_pass'];

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $target_user_id])) {
            $message = "เปลี่ยนรหัสผ่านให้ผู้ใช้เรียบร้อยแล้ว!";
            $msg_type = "success";
        } else {
            $message = "เกิดข้อผิดพลาดในการบันทึก";
            $msg_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - Library Hub</title>
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

        /* === Page Specific (Manage Users) === */
        .page-content { padding: 32px; flex: 1; display: flex; flex-direction: column;}
        
        .insight-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            padding: 24px;
            margin-bottom: 24px;
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .header-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-icon-box {
            width: 40px; height: 40px;
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }

        .btn-modern {
            padding: 12px 24px; border-radius: 12px; font-weight: 600; font-size: 14px;
            border: none; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;
        }
        .btn-primary-modern { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        .btn-primary-modern:hover { background: #1d4ed8; transform: translateY(-2px); color: white;}

        /* --- Clean Table --- */
        .table-container { overflow-x: auto; }
        .insight-table { width: 100%; border-collapse: collapse; }
        .insight-table thead th {
            font-size: 12px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
            color: var(--text-muted); padding: 12px 16px; border-bottom: 2px solid var(--border); text-align: left;
        }
        .insight-table tbody tr { border-bottom: 1px solid var(--border); transition: background 0.2s; cursor: default;}
        .insight-table tbody tr:hover { background-color: var(--body-bg); }
        .insight-table td { padding: 16px; vertical-align: middle; color: var(--text-primary); font-size: 14px; }

        .user-id-badge {
            background-color: var(--body-bg); color: var(--text-secondary); padding: 4px 10px; border-radius: 6px; font-size: 13px; font-family: monospace; border: 1px solid var(--border);
        }
        
        .user-name-link {
            font-weight: 700; color: var(--text-primary); cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; text-decoration: none;
        }
        .user-name-link:hover { color: var(--accent); }

        .role-pill {
            padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 4px;
        }
        .role-admin { background-color: #fef2f2; color: #ef4444; }
        .role-user { background-color: #ecfdf5; color: #10b981; }

        .btn-icon-manage {
            width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid #e2e8f0; background: white; color: var(--text-secondary); transition: 0.2s; cursor: pointer;
        }
        .btn-icon-manage:hover { background: var(--accent-soft); color: var(--accent); border-color: var(--accent-light); }

        /* DATATABLES OVERRIDES */
        div.dataTables_wrapper div.dataTables_filter input { border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 16px; font-size: 13px; outline: none; background: var(--body-bg); color: var(--text-primary);}
        div.dataTables_wrapper div.dataTables_filter input:focus { border-color: var(--accent); background: white; box-shadow: 0 0 0 3px var(--accent-light);}
        div.dataTables_wrapper div.dataTables_length select { border: 1px solid var(--border); border-radius: 8px; padding: 6px 12px; font-size: 13px; outline: none;}
        .dataTables_info, .dataTables_length, .dataTables_paginate { font-size: 12px !important; color: var(--text-muted) !important; margin-top: 16px; }
        .page-item.active .page-link { background: var(--accent); border-color: var(--accent); }
        .page-link { border-radius: 6px !important; margin: 0 2px;}

        /* MODAL STYLES */
        .modal-content { border: none; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .modal-header { border-bottom: 1px solid var(--border); padding: 20px 24px; }
        .modal-title { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;}
        .modal-body { padding: 24px; }
        .modal-footer { border-top: none; padding: 0 24px 24px; }

        .form-control-custom {
            width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0; background: var(--body-bg); font-family: var(--font-main); font-size: 14px; color: var(--text-primary); transition: 0.2s;
        }
        .form-control-custom:focus { border-color: var(--accent); background: white; outline: none; box-shadow: 0 0 0 3px var(--accent-light); }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeup { animation: fadeUp .5s ease both; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); box-shadow: 0 0 50px rgba(0, 0, 0, 0.2); }
            .main-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; }
            .page-content { padding: 20px 16px; }
            .header-flex { flex-direction: column; align-items: flex-start; gap: 16px; }
            .insight-table { min-width: 800px; }
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
            <h2 class="mb-0" style="font-size: 18px; font-weight: 700;">👥 จัดการผู้ใช้งาน</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="sync_students.php" class="btn-modern btn-primary-modern" onclick="return confirm('⚠️ ยืนยันการดึงข้อมูลจาก RMS ใช่หรือไม่?');">
                <i class="fa-solid fa-cloud-arrow-down"></i> <span class="d-none d-sm-inline">ดึงข้อมูลจาก RMS</span>
            </a>
        </div>
    </header>

    <main class="page-content">

        <div class="insight-card animate-fadeup">
            
            <div class="header-flex">
                <div class="header-title">
                    <div class="header-icon-box"><i class="fa-solid fa-users-gear"></i></div>
                    <div>ข้อมูลสมาชิกและรหัสผ่าน</div>
                </div>
            </div>

            <div class="table-container">
                <table id="userTable" class="insight-table">
                    <thead>
                        <tr>
                            <th width="20%">รหัสนักเรียน</th>
                            <th width="35%">ชื่อ-สกุล (คลิกเพื่อดูประวัติ)</th>
                            <th width="20%">เบอร์โทรศัพท์</th>
                            <th width="15%" class="text-center">สิทธิ์การใช้งาน</th>
                            <th width="10%" class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, id DESC");
                        while ($row = $stmt->fetch()) {
                        ?>
                            <tr>
                                <td>
                                    <span class="user-id-badge"><?php echo htmlspecialchars($row['student_id']); ?></span>
                                </td>
                                <td>
                                    <a class="user-name-link" onclick="openHistoryModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['fullname']); ?>')">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--accent-soft); color: var(--accent); display: flex; justify-content: center; align-items: center; font-size: 12px; font-weight: bold;">
                                            <?php echo mb_substr($row['fullname'], 0, 1); ?>
                                        </div>
                                        <?php echo htmlspecialchars($row['fullname']); ?> 
                                        <i class="fa-solid fa-clock-rotate-left" style="color:var(--text-muted); font-size: 10px; margin-left: 4px;"></i>
                                    </a>
                                </td>
                                <td>
                                    <?php echo $row['phone'] ? htmlspecialchars($row['phone']) : '<span style="color:var(--text-muted);">-</span>'; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['role'] == 'admin'): ?>
                                        <span class="role-pill role-admin"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                                    <?php else: ?>
                                        <span class="role-pill role-user"><i class="fa-solid fa-user"></i> Student</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn-icon-manage" onclick="openResetModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['fullname']); ?>')" title="เปลี่ยนรหัสผ่าน">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>

    </main>
</div>

<div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-lock text-primary"></i> เปลี่ยนรหัสผ่านให้ผู้ใช้งาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body text-center">
                    <input type="hidden" name="reset_password" value="1">
                    <input type="hidden" name="user_id" id="modal_user_id">
                    
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--accent-soft); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 16px;">
                        <i class="fa-solid fa-user-lock"></i>
                    </div>
                    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">กำลังตั้งรหัสผ่านใหม่ให้กับ</div>
                    <div id="modal_user_name" style="font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 24px;">...</div>
                    
                    <div class="text-start">
                        <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; display: block;">รหัสผ่านใหม่</label>
                        <input type="text" name="new_pass" class="form-control-custom" required placeholder="กรอกรหัสผ่านใหม่ที่นี่..." autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer" style="justify-content: center;">
                    <button type="button" class="btn-modern" style="background:#f1f5f9; color:var(--text-secondary);" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn-modern btn-primary-modern">บันทึกรหัสผ่าน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg"> 
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-clock-rotate-left text-primary"></i> ประวัติการยืม-คืน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding-top: 16px;">
                <div id="history_user_name" style="font-size: 14px; font-weight: 700; color: var(--accent); margin-bottom: 16px; padding: 10px 16px; background: var(--accent-soft); border-radius: 8px; display: inline-block;">...</div>
                
                <div id="history_content" class="text-center" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
            </div>
            <div class="modal-footer" style="padding-top: 0;">
                <button type="button" class="btn-modern" style="background:#f1f5f9; color:var(--text-secondary); width: 100%;" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
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

    // เริ่มต้น DataTables แบบคลีนๆ
    $('#userTable').DataTable({
        language: { 
            search: "", // ซ่อนคำว่า "ค้นหา:"
            searchPlaceholder: "ค้นหาชื่อ, รหัสนักเรียน...",
            lengthMenu: "แสดง _MENU_ รายการ", 
            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ คน", 
            paginate: { next: "ถัดไป", previous: "ก่อนหน้า" }, 
            zeroRecords: "ไม่พบข้อมูลผู้ใช้งาน" 
        },
        dom: "<'row mb-4'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 d-flex justify-content-end'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row mt-4'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
        columnDefs: [{ orderable: false, targets: [4] }]
    });
});

// เปิด Modal เปลี่ยนรหัสผ่าน
function openResetModal(id, name) {
    document.getElementById('modal_user_id').value = id;
    document.getElementById('modal_user_name').innerText = name;
    var myModal = new bootstrap.Modal(document.getElementById('resetModal'));
    myModal.show();
}

// เปิด Modal ประวัติการยืม (AJAX)
function openHistoryModal(id, name) {
    document.getElementById('history_user_name').innerHTML = '<i class="fa-solid fa-user me-2"></i>' + name;
    document.getElementById('history_content').innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    
    var myModal = new bootstrap.Modal(document.getElementById('historyModal'));
    myModal.show();

    // ส่ง AJAX ไปขอข้อมูล
    $.ajax({
        url: 'get_user_history.php',
        type: 'POST',
        data: { user_id: id },
        success: function(response) {
            $('#history_content').html(response);
        },
        error: function() {
            $('#history_content').html('<p class="text-danger fw-bold mt-3"><i class="fa-solid fa-triangle-exclamation me-2"></i>เกิดข้อผิดพลาดในการโหลดข้อมูล</p>');
        }
    });
}

// แจ้งเตือน Alert หลังเปลี่ยนรหัส
<?php if ($message): ?>
    Swal.fire({
        icon: '<?php echo $msg_type; ?>',
        title: '<?php echo $msg_type == "success" ? "สำเร็จ!" : "เกิดข้อผิดพลาด"; ?>',
        text: '<?php echo $message; ?>',
        confirmButtonColor: '#2563eb',
        customClass: { popup: 'rounded-4' }
    });
<?php endif; ?>
</script>
</body>
</html>