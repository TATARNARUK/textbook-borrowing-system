<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #000000;
            color: #e0e0e0;
            overflow-x: hidden;
        }

       #particles-js {
         position: fixed;
         /* ให้มันลอยอยู่กับที่ ไม่ต้องเลื่อนตาม Scroll bar */
         width: 100%;
         height: 100%;
         top: 0;
         left: 0;
         z-index: -1;
         /* ✅ สำคัญมาก! สั่งให้ไปอยู่ข้างหลังสุด */
         pointer-events: none;
         /* สั่งให้เม้าส์คลิกทะลุผ่านไปได้ (เผื่อไว้ก่อน) */
       }

        /* --- Glass Card --- */
        .glass-card {
            background: rgba(15, 15, 15, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8);
        }

        /* --- Table Styling --- */
        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px; /* เว้นระยะห่างระหว่างแถว */
        }
        
        .table-custom thead th {
            color: #777;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            padding-bottom: 15px;
            font-weight: 600;
        }

        .table-custom tbody tr {
            background-color: rgba(255, 255, 255, 0.03);
            transition: all 0.3s ease;
        }

        .table-custom tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .table-custom td {
            border: none;
            padding: 15px;
            vertical-align: middle;
            color: #ccc;
        }

        /* หัวท้ายมนๆ ของแต่ละแถว */
        .table-custom tbody tr td:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        .table-custom tbody tr td:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }

        /* --- Images --- */
        .book-thumb {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.5);
        }

        /* --- Status Badges (Minimal) --- */
        .status-badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-borrowed { background: rgba(255, 193, 7, 0.15); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }
        .status-returned { background: rgba(25, 135, 84, 0.15); color: #198754; border: 1px solid rgba(25, 135, 84, 0.3); }
        .status-overdue  { background: rgba(220, 53, 69, 0.15); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); }

        .dot { width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; }

        /* --- Buttons --- */
        .btn-outline-white {
            color: #fff; border: 1px solid rgba(255,255,255,0.3);
            background: transparent;
            transition: all 0.3s;
        }
        .btn-outline-white:hover { border-color: #fff; background: rgba(255,255,255,0.1); }

        .btn-action {
            background: #fff; color: #000;
            border: none; font-weight: 600;
            padding: 6px 15px; border-radius: 4px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        .btn-action:hover {
            background: #ccc; transform: scale(1.05);
        }
    </style>
</head>

<body>
    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>
    <div class="container py-5">
        
        <div class="glass-card p-4 p-md-5" data-aos="fade-up" data-aos-duration="1000">
            
            <div class="d-flex justify-content-between align-items-center mb-5 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <div>
                    <h3 class="fw-light text-white mb-0" style="letter-spacing: 1px;">
                        <i class="fa-solid fa-clock-rotate-left me-2 text-secondary"></i>HISTORY
                    </h3>
                    <small class="text-white">ประวัติการยืมและคืนหนังสือทั้งหมด</small>
                </div>
                <a href="index.php" class="btn btn-outline-white btn-sm rounded-0 px-4">
                    <i class="fa-solid fa-arrow-left me-2"></i> กลับหน้าหลัก
                </a>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th width="80" class="text-white">ปกหนังสือ</th>
                            <th class="text-white">ชื่อหนังสือ</th>
                            <th class="text-white">รหัสหนังสือ</th>
                            <?php if($role == 'admin') echo "<th class='text-white'>ชื่อผู้ยืม</th>"; ?>
                            <th class="text-white">วันที่ยืม</th>
                            <th class="text-white">วันที่คืน</th>
                            <th class="text-white">สถานะ</th>
                            <th class="text-white">ACTION</th>
                        </tr>
                    </thead>
                    <tbody> 
                        <?php 
                        // เช็คว่ามีข้อมูลไหม
                        if ($stmt->rowCount() == 0) {
                            echo '<tr><td colspan="8" class="text-center py-5 text-muted">ไม่พบประวัติการยืม-คืน</td></tr>';
                        }

                        while ($row = $stmt->fetch()) { 
                            $is_overdue = (strtotime($row['due_date']) < time()) && ($row['status'] == 'borrowed');
                        ?>
                        <tr>
                            <td>
                                <?php if($row['cover_image']): ?>
                                    <img src="uploads/<?php echo $row['cover_image']; ?>" class="book-thumb" alt="Cover">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/50x70/111/555?text=No+Img" class="book-thumb" alt="No Image">
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-white"><?php echo $row['title']; ?></div>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-25 text-light border border-secondary border-opacity-25 fw-normal">
                                    <?php echo $row['book_code']; ?>
                                </span>
                            </td>
                            
                            <?php if($role == 'admin'): ?>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-dark rounded-circle text-white d-flex justify-content-center align-items-center me-2" style="width:30px; height:30px; font-size: 0.8rem;">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                        <?php echo $row['fullname']; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                            
                            <td><?php echo date('d/m/Y', strtotime($row['borrow_date'])); ?></td>
                            
                            <td>
                                <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                    <?php echo date('d/m/Y', strtotime($row['due_date'])); ?>
                                </span>
                            </td>
                            
                            <td>
                                <?php if($row['status'] == 'borrowed'): ?>
                                    <?php if($is_overdue): ?>
                                        <span class="status-badge status-overdue"><span class="dot"></span> เกินกำหนด</span>
                                    <?php else: ?>
                                        <span class="status-badge status-borrowed"><span class="dot"></span> กำลังยืม</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="d-flex flex-column">
                                        <span class="status-badge status-returned mb-1"><span class="dot"></span> คืนแล้ว</span>
                                        <small style="font-size: 0.7rem; color: #555;">
                                            <?php echo date('d/m/y', strtotime($row['return_date'])); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if($role == 'admin' && $row['status'] == 'borrowed'): ?>
                                    <button onclick="confirmReturn(<?php echo $row['id']; ?>, <?php echo $row['book_item_id']; ?>)" 
                                            class="btn-action">
                                        รับหนังสือคืน
                                    </button>
                                <?php else: ?>
                                    <span class="text-secondary opacity-25">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        AOS.init({ duration: 800, once: true });

        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#ffffff" },
                "shape": { "type": "circle", "stroke": { "width": 0, "color": "#000000" } },
                "opacity": { "value": 0.3, "random": true },
                "size": { "value": 2, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#ffffff", "opacity": 0.15, "width": 1 },
                "move": { "enable": true, "speed": 0.5 }
            },
            "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": true, "mode": "grab" }, "resize": true } }
        });

        // Dark Theme Alert
        function confirmReturn(transId, itemId) {
            Swal.fire({
                title: 'CONFIRM RETURN',
                text: "ยืนยันการรับคืนหนังสือ?",
                icon: 'warning',
                showCancelButton: true,
                background: '#000',
                color: '#fff',
                confirmButtonColor: '#fff',
                cancelButtonColor: '#333',
                confirmButtonText: '<span style="color:#000; font-weight:bold;">YES, RETURN</span>',
                cancelButtonText: 'CANCEL'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `return_save.php?trans_id=${transId}&item_id=${itemId}`;
                }
            })
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'returned') {
            Swal.fire({
                title: 'COMPLETED',
                text: 'บันทึกการคืนเรียบร้อยแล้ว',
                icon: 'success',
                background: '#000',
                color: '#fff',
                confirmButtonColor: '#fff',
                confirmButtonText: '<span style="color:#000; font-weight:bold;">OK</span>'
            }).then(() => window.history.replaceState(null, null, window.location.pathname));
        }
    </script>
        <script>
        /* เรียกใช้ particles.js ที่กล่อง id="particles-js" */
        particlesJS("particles-js", {
            "particles": {
                "number": {
                    "value": 80,
                    /* จำนวนดาว (ยิ่งเยอะยิ่งรก) ลองปรับดูที่ 50-100 */
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": {
                    "value": "#ffffff" /* สีของดาว (สีขาว) */
                },
                "shape": {
                    "type": "circle",
                    /* รูปร่าง (วงกลม) */
                    "stroke": {
                        "width": 0,
                        "color": "#000000"
                    },
                    "polygon": {
                        "nb_sides": 5
                    }
                },
                "opacity": {
                    "value": 0.5,
                    /* ความจางของดาว (0.5 คือครึ่งๆ) */
                    "random": true,
                    /* ให้จางไม่เท่ากัน ดูมีมิติ */
                    "anim": {
                        "enable": false,
                        "speed": 1,
                        "opacity_min": 0.1,
                        "sync": false
                    }
                },
                "size": {
                    "value": 3,
                    /* ขนาดของดาว */
                    "random": true,
                    /* เล็กใหญ่ไม่เท่ากัน */
                    "anim": {
                        "enable": false,
                        "speed": 40,
                        "size_min": 0.1,
                        "sync": false
                    }
                },
                "line_linked": {
                    "enable": true,
                    /* ✅ ถ้าไม่อยากได้เส้นเชื่อม ให้แก้เป็น false */
                    "distance": 150,
                    /* ระยะห่างที่จะให้มีเส้นเชื่อม */
                    "color": "#ffffff",
                    /* สีของเส้น */
                    "opacity": 0.4,
                    /* ความจางของเส้น */
                    "width": 1
                },
                "move": {
                    "enable": true,
                    /* สั่งให้ขยับ */
                    "speed": 2,
                    /* ความเร็วในการวิ่ง (ยิ่งเยอะยิ่งเร็ว) */
                    "direction": "none",
                    /* ทิศทาง (none คือมั่ว) */
                    "random": false,
                    "straight": false,
                    "out_mode": "out",
                    "bounce": false,
                    "attract": {
                        "enable": false,
                        "rotateX": 600,
                        "rotateY": 1200
                    }
                }
            },
            "interactivity": {
                /* ส่วนนี้คือเวลาเอาเมาส์ไปโดน */
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        /* ถ้า true เวลาเอาเมาส์ไปชี้ ดาวจะวิ่งหนีหรือวิ่งเข้าหา */
                        "mode": "grab" /* grab = มีเส้นดูดเข้าหาเมาส์, repulse = วิ่งหนี */
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push" /* คลิกแล้วมีดาวเพิ่ม */
                    },
                    "resize": true
                },
                "modes": {
                    "grab": {
                        "distance": 140,
                        "line_linked": {
                            "opacity": 1
                        }
                    },
                    "bubble": {
                        "distance": 400,
                        "size": 40,
                        "duration": 2,
                        "opacity": 8,
                        "speed": 3
                    },
                    "repulse": {
                        "distance": 200,
                        "duration": 0.4
                    },
                    "push": {
                        "particles_nb": 4
                    },
                    "remove": {
                        "particles_nb": 2
                    }
                }
            },
            "retina_detect": true
        });
    </script>
</body>
</html>