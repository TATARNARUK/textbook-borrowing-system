<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'];
$role = $_SESSION['role'];
$user_role = $role; // สำหรับใช้งานกับ Sidebar ใหม่
$current_page = basename($_SERVER['PHP_SELF']);

// SQL: Admin เห็นหมด / User เห็นแค่ของตัวเอง
if ($role == 'admin') {
    $sql = "SELECT t.*, b.title, b.cover_image, bi.book_code, u.fullname 
            FROM transactions t 
            JOIN book_items bi ON t.book_item_id = bi.id 
            JOIN book_masters b ON bi.book_master_id = b.id
            JOIN users u ON t.user_id = u.id
            ORDER BY t.id DESC";
    $stmt = $pdo->query($sql);
} else {
    $sql = "SELECT t.*, b.title, b.cover_image, bi.book_code 
            FROM transactions t 
            JOIN book_items bi ON t.book_item_id = bi.id 
            JOIN book_masters b ON bi.book_master_id = b.id
            WHERE t.user_id = ? 
            ORDER BY t.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการยืม-คืนหนังสือ</title>
    <link rel="icon" type="image/png" href="images/books.png">
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* === นำ CSS หลักมาใช้แบบ InsightHub === */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --sidebar-w: 260px; --sidebar-bg: #ffffff; --body-bg: #f8fafc; /* พื้นหลังสีสว่าง */
            --accent: #2563eb; --accent-light: #dbeafe; --accent-soft: #eff6ff;
            --text-primary: #0f172a; --text-secondary: #64748b; --text-muted: #94a3b8;
            --border: #f1f5f9; --card-bg: #ffffff; --card-radius: 16px;
            --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
            --font-main: 'DM Sans', 'Noto Sans Thai', sans-serif;
            --shadow-sm: 0 4px 15px rgba(0, 0, 0, 0.02);
            --shadow-md: 0 10px 25px rgba(0, 0, 0, 0.06);
        }
        body { font-family: var(--font-main); background: var(--body-bg); color: var(--text-primary); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* Sidebar & Topbar (โครงสร้าง InsightHub) */
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
        .topbar { height: 90px; background: rgba(255, 255, 255, .85); backdrop-filter: blur(12px); border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; padding: 0 32px; gap: 16px; position: sticky; top: 0; z-index: 50; justify-content: space-between;}
        .topbar-greeting { flex: 1; display: flex; align-items: center; gap: 16px; }
        .menu-toggle { display: none; width: 36px; height: 36px; align-items: center; justify-content: center; border: 1px solid #e2e8f0; border-radius: 8px; background: white; cursor: pointer; font-size: 16px; color: var(--text-secondary); }

        /* === Page Specific Styles === */
        .page-content { padding: 32px; flex: 1; display: flex; flex-direction: column;}
        
        .insight-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
        }

        .header-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
            font-weight: 500;
        }

        /* --- Clean Table Styling --- */
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
            background-color: transparent;
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

        .book-thumb {
            width: 45px;
            height: 65px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
        }

        .book-title-cell {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .book-code-badge {
            background-color: var(--body-bg);
            color: var(--text-secondary);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid var(--border);
        }

        /* Status Pills */
        .status-pill {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-borrowed { background-color: #fffbeb; color: #b45309; }
        .status-returned { background-color: #ecfdf5; color: #047857; }
        .status-overdue { background-color: #fef2f2; color: #b91c1c; }

        .dot { width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; }

        /* Action Button */
        .btn-return {
            background-color: var(--body-bg);
            color: var(--accent);
            border: 1px solid var(--accent-light);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-return:hover {
            background-color: var(--accent);
            color: white;
            box-shadow: 0 4px 12px rgba(37,99,235,0.2);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        
        .empty-icon {
            width: 64px; height: 64px;
            background: var(--body-bg);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: var(--text-muted);
            margin: 0 auto 16px;
        }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeup { animation: fadeUp .5s ease both; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); box-shadow: 0 0 50px rgba(0, 0, 0, 0.2); }
            .main-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; }
            .page-content { padding: 20px 16px; }
            .insight-table { min-width: 800px; /* บังคับเลื่อนแนวนอนมือถือ */ }
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
            <h2 class="mb-0" style="font-size: 18px; font-weight: 700;">🕒 ประวัติการยืม-คืนหนังสือ</h2>
        </div>
    </header>

    <main class="page-content">

        <div class="insight-card animate-fadeup">
            
            <div class="card-header-flex">
                <div>
                    <div class="header-title">รายการทำธุรกรรมทั้งหมด</div>
                    <div class="header-subtitle">ตรวจสอบประวัติยืม-คืน และสถานะของหนังสือ</div>
                </div>
            </div>

            <div class="table-container">
                <table class="insight-table">
                    <thead>
                        <tr>
                            <th width="70">ปก</th>
                            <th>รายละเอียดหนังสือ</th>
                            <?php if ($role == 'admin') echo "<th>ชื่อผู้ยืม</th>"; ?>
                            <th>วันที่ทำรายการยืม</th>
                            <th>กำหนดส่ง / วันที่คืน</th>
                            <th>สถานะ</th>
                            <?php if ($role == 'admin') echo "<th class='text-end'>การจัดการ</th>"; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($stmt->rowCount() == 0) {
                            echo '<tr><td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                                        <h6 class="fw-bold" style="color:var(--text-primary);">ไม่มีประวัติการยืม-คืน</h6>
                                        <p>คุณยังไม่เคยทำรายการยืมหนังสือในระบบ</p>
                                    </div>
                                  </td></tr>';
                        }

                        while ($row = $stmt->fetch()) {
                            // เช็คเกินกำหนด
                            $is_overdue = (strtotime($row['due_date']) < time()) && ($row['status'] == 'borrowed');

                            // โหลดรูปปก
                            $cover = $row['cover_image'];
                            if (strpos($cover, 'http') === 0) {
                                $showImg = $cover;
                            } else {
                                $showImg = !empty($cover) ? "uploads/" . $cover : "https://via.placeholder.com/50x70/eee/999?text=No+Img";
                            }
                        ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $showImg; ?>" class="book-thumb" alt="Cover">
                                </td>
                                <td>
                                    <div class="book-title-cell"><?php echo htmlspecialchars($row['title']); ?></div>
                                    <span class="book-code-badge">รหัส: <?php echo htmlspecialchars($row['book_code']); ?></span>
                                </td>

                                <?php if ($role == 'admin'): ?>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width:28px; height:28px; border-radius:50%; background:var(--accent-soft); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold;">
                                                <?php echo mb_substr($row['fullname'], 0, 1); ?>
                                            </div>
                                            <span style="font-weight: 500;"><?php echo htmlspecialchars($row['fullname']); ?></span>
                                        </div>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <div style="font-weight: 600;"><?php echo date('d M Y', strtotime($row['borrow_date'])); ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?php echo date('H:i', strtotime($row['borrow_date'])); ?> น.</div>
                                </td>

                                <td>
                                    <?php if ($row['status'] == 'borrowed'): ?>
                                        <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : 'text-primary fw-bold'; ?>">
                                            <i class="fa-regular fa-calendar me-1"></i>
                                            <?php echo date('d M Y', strtotime($row['due_date'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="font-weight: 600; color: var(--success);">
                                            <i class="fa-solid fa-check me-1"></i>
                                            <?php echo date('d M Y', strtotime($row['return_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($row['status'] == 'borrowed'): ?>
                                        <?php if ($is_overdue): ?>
                                            <span class="status-pill status-overdue"><span class="dot"></span> เกินกำหนด</span>
                                        <?php else: ?>
                                            <span class="status-pill status-borrowed"><span class="dot"></span> กำลังยืม</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="status-pill status-returned"><span class="dot"></span> คืนเรียบร้อย</span>
                                    <?php endif; ?>
                                </td>

                                <?php if ($role == 'admin'): ?>
                                    <td class="text-end">
                                        <?php if ($row['status'] == 'borrowed'): ?>
                                            <button onclick="confirmReturn(<?php echo $row['id']; ?>, <?php echo $row['book_item_id']; ?>)" class="btn-return">
                                                <i class="fa-solid fa-hand-holding-hand"></i> รับคืน
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted opacity-25 px-3">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

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

// ฟังก์ชันรับคืนหนังสือ (สำหรับแอดมิน)
function confirmReturn(transId, itemId) {
    Swal.fire({
        title: 'ยืนยันการรับคืน?',
        text: "คุณต้องการบันทึกการรับคืนหนังสือเล่มนี้ใช่หรือไม่?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb', // สี Accent
        cancelButtonColor: '#e2e8f0',
        cancelButtonText: '<span style="color:#1e293b">ยกเลิก</span>',
        confirmButtonText: 'ยืนยันรับคืน',
        customClass: { popup: 'rounded-4' }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `return_save.php?trans_id=${transId}&item_id=${itemId}`;
        }
    })
}

// เช็คสถานะการคืนจาก URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('status') === 'returned') {
    Swal.fire({
        title: 'สำเร็จ!',
        text: 'บันทึกการคืนหนังสือเรียบร้อยแล้ว',
        icon: 'success',
        confirmButtonColor: '#10b981',
        customClass: { popup: 'rounded-4' }
    }).then(() => window.history.replaceState(null, null, window.location.pathname));
}
</script>
</body>
</html>