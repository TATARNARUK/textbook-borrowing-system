<?php
session_start();
require_once 'config.php';

// 1. เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'];
$user_role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);

// -------------------------------------------------------------
// ฟังก์ชันสำหรับแปลงลิงก์ Google Drive ให้เป็นแบบ Embed/Preview
// -------------------------------------------------------------
function convertGoogleDriveLink($url) {
    if (empty($url)) return '';

    if (strpos($url, 'drive.google.com/file/d/') !== false) {
        preg_match('/d\/(.*?)\//', $url, $matches);
        if (isset($matches[1])) {
            $fileId = $matches[1];
            return "https://drive.google.com/file/d/{$fileId}/preview";
        }
    }
    return $url;
}

// 2. ฟังก์ชันดึงข้อมูล API (เวอร์ชันสำหรับ Server จริง แก้บั๊ก Cloudflare SSL)
function getBooksFromApi()
{
    $url = "https://itdev.bncc.ac.th/vbss/Education_system/api/v1.php?path=get_book";
    $apiKey = "76802395e80ea1ef8147f683e59f9c62";

    if (!function_exists('curl_init')) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'ข้อผิดพลาดร้ายแรง!',
                    text: 'เซิร์ฟเวอร์ของคุณยังไม่ได้เปิดใช้งาน cURL extension',
                    icon: 'error',
                    customClass: { popup: 'rounded-4' }
                });
            });
        </script>";
        return null;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-key: $apiKey"]);
    
    // ปิดการตรวจสอบ SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $error_msg = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!empty($error_msg)) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Network Error',
                    text: 'ไม่สามารถเชื่อมต่อ API ได้: " . addslashes($error_msg) . "',
                    icon: 'error',
                    customClass: { popup: 'rounded-4' }
                });
            });
        </script>";
        return null;
    }

    if ($httpCode !== 200) {
        return null;
    }

    return json_decode($response, true);
}

$apiResult = getBooksFromApi();
$books = isset($apiResult['data']) ? $apiResult['data'] : [];

// --- ส่วนทำงานเมื่อมีการกดปุ่ม (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function saveBook($pdo, $data)
    {
        $title = $data['name'] ?? '-';
        
        // 🔥 แยกเก็บรหัสหนังสือ และ ISBN ออกจากกัน
        $book_code = (!empty($data['code']) && $data['code'] != '-') ? $data['code'] : '-';
        $isbn = (!empty($data['isbn']) && $data['isbn'] != '-') ? $data['isbn'] : '-';
        
        $author = $data['author'] ?? '-';
        $price = $data['price'] ?? 0;

        $cover = $data['image'] ?? '';
        $cover = str_replace(' ', '%20', $cover);
        if (!empty($cover) && strpos($cover, 'http') === false) {
            $cover = 'https://itdev.bncc.ac.th/vbss/Education_system/other/img/uploads/' . $cover;
        }

        $raw_pdf = $data['linkExp'] ?? '';
        $pdf = convertGoogleDriveLink($raw_pdf);

        $desc = $data['detail'] ?? '';
        $pages = $data['countPage'] ?? 0;
        $paper = $data['paperFormat'] ?? '-';
        $print = $data['color'] ?? '-';
        $size  = $data['size'] ?? '-';
        $app_no = $data['approval_time'] ?? '-';
        $app_order = $data['approval_number'] ?? '-';

        // 1. เช็คว่ามีรหัสหนังสือนี้ในระบบหรือยัง? (เช็คจาก book_code ก่อน)
        $stmtCheck = $pdo->prepare("SELECT id FROM book_masters WHERE book_code = ? AND book_code != '-'");
        $stmtCheck->execute([$book_code]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            // กรณีมีอยู่แล้ว: ให้อัปเดตไฟล์ PDF
            if (!empty($pdf)) {
                $sqlUpdate = "UPDATE book_masters SET sample_pdf = ? WHERE id = ?";
                $pdo->prepare($sqlUpdate)->execute([$pdf, $existingId]);
            }
            return true;
        } else {
            // 2. กรณียังไม่มี: เพิ่มใหม่ (เพิ่ม book_code เข้าไปด้วย)
            $sql = "INSERT INTO book_masters 
                    (book_code, isbn, title, author, publisher, price, cover_image, sample_pdf, description, 
                     page_count, paper_type, print_type, book_size, approval_no, approval_order) 
                    VALUES (?, ?, ?, ?, '-', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$book_code, $isbn, $title, $author, $price, $cover, $pdf, $desc, $pages, $paper, $print, $size, $app_no, $app_order]);
            return true;
        }
    }

    if (isset($_POST['import_book'])) {
        $bookData = [
            'name' => $_POST['title'],
            'author' => $_POST['author'],
            'code' => $_POST['book_code'],
            'isbn' => $_POST['isbn'],
            'price' => $_POST['price'],
            'image' => $_POST['cover'],
            'linkExp' => $_POST['pdf'], 
            'detail' => $_POST['description'],
            'countPage' => $_POST['pages'],
            'paperFormat' => $_POST['paper'],
            'color' => $_POST['print'],
            'size' => $_POST['size'],
            'approval_time' => $_POST['app_no'],
            'approval_number' => $_POST['app_order']
        ];

        if (saveBook($pdo, $bookData)) {
            echo "<script>setTimeout(function() { Swal.fire({title: 'สำเร็จ!', text: 'นำเข้า/อัปเดต ข้อมูลเรียบร้อย', icon: 'success', confirmButtonColor: '#2563eb', customClass: { popup: 'rounded-4' }}); }, 500);</script>";
        } else {
            echo "<script>setTimeout(function() { Swal.fire({title: 'แจ้งเตือน', text: 'เกิดข้อผิดพลาด', icon: 'warning', confirmButtonColor: '#f59e0b', customClass: { popup: 'rounded-4' }}); }, 500);</script>";
        }
    }

    if (isset($_POST['import_all'])) {
        $count = 0;
        foreach ($books as $book) {
            if (saveBook($pdo, $book)) {
                $count++;
            }
        }
        echo "<script>setTimeout(function() { Swal.fire({title: 'เสร็จสิ้น!', text: 'ประมวลผลหนังสือจำนวน $count เล่ม เรียบร้อย', icon: 'success', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-4' }}); }, 500);</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>นำเข้าหนังสือจาก API - Library Hub</title>
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

        /* === Page Specific (API Books) === */
        .page-content { padding: 32px; flex: 1; display: flex; flex-direction: column;}
        
        .insight-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            padding: 24px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-title { font-size: 18px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 12px; }
        .header-subtitle { font-size: 13px; color: var(--text-muted); font-weight: 500; margin-top: 4px; }
        
        .header-icon-box {
            width: 44px; height: 44px;
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }

        .btn-modern {
            padding: 12px 24px; border-radius: 12px; font-weight: 600; font-size: 14px;
            border: none; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;
        }
        .btn-success-modern { background: #10b981; color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
        .btn-success-modern:hover { background: #059669; transform: translateY(-2px); color: white;}

        /* --- API Book Cards --- */
        .card-book {
            background: var(--card-bg); border-radius: var(--card-radius); border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow: hidden; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); height: 100%; display: flex; flex-direction: column;
        }
        .card-book:hover { transform: translateY(-8px); box-shadow: var(--shadow-md); border-color: var(--accent-light); }
        
        .book-cover-container { position: relative; padding-top: 135%; background: var(--body-bg); overflow: hidden; }
        .book-cover { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .card-book:hover .book-cover { transform: scale(1.05); }
        
        .book-info { padding: 16px; flex-grow: 1; display: flex; flex-direction: column; }
        .book-title { font-size: 15px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .book-author { font-size: 12px; color: var(--text-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        .badges-wrapper { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 12px; }
        .badge-code { background: #f1f5f9; color: var(--text-secondary); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid var(--border); font-family: monospace;}
        .badge-price { background: #ecfdf5; color: #047857; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #a7f3d0;}

        .btn-pdf-modern { display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-size: 13px; color: #ef4444; background: #fef2f2; border: 1px solid #fecaca; padding: 8px; border-radius: 10px; font-weight: 700; transition: 0.2s; margin-bottom: 12px; width: 100%; cursor: pointer;}
        .btn-pdf-modern:hover { background: #ef4444; color: white; }
        
        .btn-disabled-modern { display: inline-flex; align-items: center; justify-content: center; font-size: 13px; color: var(--text-muted); background: var(--body-bg); border: 1px solid var(--border); padding: 8px; border-radius: 10px; font-weight: 600; margin-bottom: 12px; width: 100%; cursor: not-allowed;}

        .btn-import-single { width: 100%; padding: 10px; border-radius: 10px; font-weight: 700; font-size: 13px; border: none; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: auto; cursor: pointer;}
        .btn-import-new { background: var(--accent); color: white; }
        .btn-import-new:hover { background: #1d4ed8; transform: translateY(-1px); }
        .btn-import-update { background: #f59e0b; color: white; }
        .btn-import-update:hover { background: #d97706; transform: translateY(-1px); }

        /* Modal PDF */
        .modal-content { border: none; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); overflow: hidden;}
        .modal-header { border-bottom: 1px solid var(--border); padding: 16px 24px; background: #0f172a; color: white;}
        .modal-title { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;}
        .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeup { animation: fadeUp .5s ease both; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); box-shadow: 0 0 50px rgba(0, 0, 0, 0.2); }
            .main-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; }
            .page-content { padding: 20px 16px; }
            .insight-card { flex-direction: column; align-items: flex-start; gap: 16px; padding: 20px;}
            .insight-card .btn-modern { width: 100%; }
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
            <h2 class="mb-0" style="font-size: 18px; font-weight: 700;">☁️ นำเข้าหนังสือจากส่วนกลาง</h2>
        </div>
    </header>

    <main class="page-content">

        <div class="insight-card animate-fadeup">
            <div class="d-flex align-items-center gap-3">
                <div class="header-icon-box"><i class="fa-solid fa-cloud-arrow-down"></i></div>
                <div>
                    <div class="header-title" style="margin-bottom: 0; padding-bottom: 0; border: none;">ดึงข้อมูลหนังสือจาก API ส่วนกลาง</div>
                    <div class="header-subtitle">พบข้อมูลหนังสือที่พร้อมนำเข้าจำนวน <strong style="color:var(--accent);"><?php echo count($books); ?></strong> เล่ม</div>
                </div>
            </div>
            
            <form method="POST" onsubmit="return confirm('ยืนยันการนำเข้าข้อมูล?\n(ระบบจะเพิ่มเล่มใหม่ และอัปเดตไฟล์ PDF ให้เล่มเดิม)');">
                <button type="submit" name="import_all" class="btn-modern btn-success-modern">
                    <i class="fa-solid fa-layer-group"></i> นำเข้าและอัปเดตทั้งหมด
                </button>
            </form>
        </div>

        <?php if (!empty($books)): ?>
            <div class="row g-4">
                <?php foreach ($books as $index => $book):
                    $b_title = $book['name'] ?? '-';
                    $b_code = (!empty($book['code']) && $book['code'] != '-') ? $book['code'] : '-';
                    $b_isbn = (!empty($book['isbn']) && $book['isbn'] != '-') ? $book['isbn'] : '-';
                    $b_author = $book['author'] ?? '-';
                    $b_price = $book['price'] ?? 0;

                    $b_img_raw = $book['image'] ?? '';
                    $b_img = str_replace(' ', '%20', $b_img_raw);
                    if (!empty($b_img) && strpos($b_img, 'http') === false) {
                        $b_img = 'https://itdev.bncc.ac.th/vbss/Education_system/other/img/uploads/' . $b_img;
                    }

                    $raw_b_pdf = $book['linkExp'] ?? '';
                    $b_pdf = convertGoogleDriveLink($raw_b_pdf);

                    // ตรวจสอบว่าเล่มนี้มีในฐานข้อมูลของเราแล้วหรือยัง?
                    $chk = $pdo->prepare("SELECT id FROM book_masters WHERE book_code = ? AND book_code != '-'");
                    $chk->execute([$b_code]);
                    $is_exists = $chk->fetch();

                    // ทำ Animation Delay ให้โหลดทีละแถวสวยๆ
                    $delay = ($index % 6) * 100;
                ?>
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2 animate-fadeup" style="animation-delay: <?php echo $delay; ?>ms;">
                        <div class="card-book">
                            
                            <div class="book-cover-container">
                                <?php if ($b_img): ?>
                                    <img src="<?php echo $b_img; ?>" class="book-cover" onerror="this.src='https://via.placeholder.com/300x450?text=No+Cover'">
                                <?php else: ?>
                                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:12px; position:absolute; top:0; left:0;">ไม่มีรูปภาพปก</div>
                                <?php endif; ?>
                            </div>

                            <div class="book-info">
                                <div class="book-title" title="<?php echo htmlspecialchars($b_title); ?>"><?php echo htmlspecialchars($b_title); ?></div>
                                
                                <div class="badges-wrapper">
                                    <?php if ($b_code !== '-'): ?>
                                        <span class="badge-code" title="รหัสหนังสือ"><i class="fa-solid fa-hashtag me-1"></i><?php echo $b_code; ?></span>
                                    <?php endif; ?>
                                    <span class="badge-price"><?php echo $b_price; ?> บ.</span>
                                </div>

                                <div class="book-author text-truncate" title="<?php echo htmlspecialchars($b_author); ?>">
                                    <i class="fa-solid fa-user-pen"></i> <?php echo htmlspecialchars($b_author); ?>
                                </div>

                                <?php if ($b_pdf): ?>
                                    <button type="button" class="btn-pdf-modern" onclick="viewPDF('<?php echo htmlspecialchars($b_pdf); ?>', '<?php echo htmlspecialchars($b_title, ENT_QUOTES); ?>')">
                                        <i class="fa-regular fa-file-pdf"></i> ทดลองอ่าน E-Book
                                    </button>
                                <?php else: ?>
                                    <div class="btn-disabled-modern"><i class="fa-solid fa-ban me-1"></i> ไม่มีไฟล์ตัวอย่าง</div>
                                <?php endif; ?>

                                <form method="POST" style="margin-top: auto;">
                                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($b_title); ?>">
                                    <input type="hidden" name="author" value="<?php echo htmlspecialchars($b_author); ?>">
                                    <input type="hidden" name="book_code" value="<?php echo htmlspecialchars($b_code); ?>">
                                    <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($b_isbn); ?>">
                                    <input type="hidden" name="price" value="<?php echo htmlspecialchars($b_price); ?>">
                                    <input type="hidden" name="cover" value="<?php echo htmlspecialchars($b_img); ?>">
                                    <input type="hidden" name="pdf" value="<?php echo htmlspecialchars($raw_b_pdf); ?>"> 
                                    <input type="hidden" name="description" value="<?php echo htmlspecialchars($book['detail'] ?? ''); ?>">
                                    <input type="hidden" name="pages" value="<?php echo htmlspecialchars($book['countPage'] ?? 0); ?>">
                                    <input type="hidden" name="paper" value="<?php echo htmlspecialchars($book['paperFormat'] ?? '-'); ?>">
                                    <input type="hidden" name="print" value="<?php echo htmlspecialchars($book['color'] ?? '-'); ?>">
                                    <input type="hidden" name="size" value="<?php echo htmlspecialchars($book['size'] ?? '-'); ?>">
                                    <input type="hidden" name="app_no" value="<?php echo htmlspecialchars($book['approval_time'] ?? '-'); ?>">
                                    <input type="hidden" name="app_order" value="<?php echo htmlspecialchars($book['approval_number'] ?? '-'); ?>">

                                    <?php if ($is_exists): ?>
                                        <button type="submit" name="import_book" class="btn-import-single btn-import-update">
                                            <i class="fa-solid fa-rotate"></i> อัปเดตไฟล์
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="import_book" class="btn-import-single btn-import-new">
                                            <i class="fa-solid fa-download"></i> นำเข้าเล่มนี้
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 animate-fadeup" style="background:var(--card-bg); border-radius:var(--card-radius); border: 1px solid var(--border);">
                <div style="width: 80px; height: 80px; background: var(--body-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class="fa-solid fa-server fa-2x text-muted"></i>
                </div>
                <h5 class="fw-bold" style="color: var(--text-primary);">ไม่พบข้อมูลจาก API</h5>
                <p style="color: var(--text-muted); font-size: 14px;">อาจเกิดจากปัญหาการเชื่อมต่อเซิร์ฟเวอร์ หรือไม่มีหนังสือใหม่ในระบบส่วนกลาง</p>
            </div>
        <?php endif; ?>

    </main>
</div>

<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="height: 90vh;">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfModalTitle"><i class="fa-solid fa-book-open text-white"></i> อ่านตัวอย่างหนังสือ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closePDF()"></button>
            </div>
            <div class="modal-body p-0 position-relative" style="background: #e2e8f0;">
                <div id="pdfLoader" class="position-absolute top-50 start-50 translate-middle text-center" style="color: var(--text-secondary);">
                    <div class="spinner-border mb-2" role="status"></div>
                    <div style="font-weight: 600;">กำลังดาวน์โหลดเอกสาร...</div>
                </div>
                <iframe id="pdfIframe" src="" width="100%" height="100%" style="border:none; position: relative; z-index: 2;" allow="autoplay"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
});

// ฟังก์ชันเปิด PDF
function viewPDF(pdfUrl, title) {
    document.getElementById('pdfModalTitle').innerHTML = '<i class="fa-solid fa-book-open text-white me-2"></i>' + title;
    document.getElementById('pdfIframe').style.opacity = '0';
    document.getElementById('pdfIframe').src = pdfUrl;

    var myModal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
    myModal.show();

    document.getElementById('pdfIframe').onload = function() {
        document.getElementById('pdfIframe').style.opacity = '1';
        document.getElementById('pdfLoader').style.display = 'none';
    };
}

function closePDF() {
    document.getElementById('pdfIframe').src = "";
    document.getElementById('pdfLoader').style.display = 'block';
}
</script>
</body>
</html>