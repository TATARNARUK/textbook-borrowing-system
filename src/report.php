<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); exit();
}

// กำหนดค่าเริ่มต้น
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Query
$sql = "SELECT t.*, u.fullname, u.student_id, b.title, bi.book_code 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        JOIN book_items bi ON t.book_item_id = bi.id
        JOIN book_masters b ON bi.book_master_id = b.id
        WHERE date(t.borrow_date) BETWEEN :start AND :end
        ORDER BY t.borrow_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['start' => $start_date, 'end' => $end_date]);
$transactions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานสรุปการยืม-คืน</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* --- 🌑 SCREEN MODE (Dark Luxury) --- */
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

        /* Glass Panel */
        .glass-panel {
            background: rgba(15, 15, 15, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0px; /* เหลี่ยมเท่ๆ */
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            padding: 30px;
            margin-bottom: 30px;
        }

        /* Inputs (Dark) */
        .form-control {
            background-color: #111;
            border: 1px solid #333;
            color: #fff;
            border-radius: 4px;
        }
        .form-control:focus {
            background-color: #000;
            border-color: #fff;
            color: #fff;
            box-shadow: none;
        }
        /* Custom Date Picker Icon fix */
        ::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }

        /* Buttons */
        .btn-monochrome {
            background: #fff; color: #000; border: 1px solid #fff;
            font-weight: 600; border-radius: 4px; padding: 7px 20px;
            transition: all 0.3s;
        }
        .btn-monochrome:hover {
            background: #000; color: #fff;
        }
        .btn-outline-white {
            background: transparent; color: #aaa; border: 1px solid #333;
            border-radius: 4px;
        }
        .btn-outline-white:hover {
            border-color: #fff; color: #fff;
        }

        /* Modern Table */
        .table-custom {
            width: 100%; border-collapse: separate; border-spacing: 0 10px;
        }
        .table-custom thead th {
            color: #666; font-size: 0.9rem; /* ปรับขนาดให้พอดีภาษาไทย */
            letter-spacing: 0.5px; border: none; padding-bottom: 15px;
        }
        .table-custom tbody tr {
            background-color: rgba(255, 255, 255, 0.03);
            transition: all 0.2s;
        }
        .table-custom tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08);
            transform: scale(1.01);
        }
        .table-custom td {
            border: none; padding: 15px; vertical-align: middle; color: #ccc;
        }
        .table-custom td:first-child { border-top-left-radius: 6px; border-bottom-left-radius: 6px; }
        .table-custom td:last-child { border-top-right-radius: 6px; border-bottom-right-radius: 6px; }

        /* Status Badges */
        .status-pill {
            padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 500;
        }
        .st-borrow { background: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }
        .st-return { background: rgba(25, 135, 84, 0.1); color: #198754; border: 1px solid rgba(25, 135, 84, 0.3); }


        /* --- 🖨️ PRINT MODE (Clean White) --- */
        @media print {
            body { background-color: #fff !important; color: #000 !important; }
            #particles-js, .no-print, .btn, header, nav { display: none !important; }
            
            .glass-panel {
                background: none !important; border: none !important;
                box-shadow: none !important; padding: 0 !important; margin: 0 !important;
                backdrop-filter: none !important;
            }

            .table-custom { border-collapse: collapse !important; border-spacing: 0 !important; }
            .table-custom th, .table-custom td {
                border: 1px solid #000 !important; color: #000 !important; padding: 8px !important;
            }
            .table-custom tbody tr { background: none !important; }
            
            /* Hide Badges Background for print clarity */
            .status-pill { border: none !important; color: #000 !important; padding: 0 !important; }
            
            /* Page Setup */
            @page { size: auto; margin: 10mm; }
            .container { max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body>

    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>

    <div class="container py-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4 no-print" data-aos="fade-down">
            <div>
                <h3 class="text-white fw-light mb-0" style="letter-spacing: 1px;">
                    <i class="fa-solid fa-file-invoice me-2 text-secondary"></i>รายงานสรุป
                </h3>
                <small class="text-secondary">ระบบรายงานข้อมูลการยืม-คืนหนังสือ</small>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-white me-2">
                    <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
                </a>
                <button onclick="window.print()" class="btn btn-monochrome">
                    <i class="fa-solid fa-print me-2"></i> พิมพ์รายงาน
                </button>
            </div>
        </div>

        <div class="glass-panel no-print" data-aos="fade-up">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="text-white small mb-2">ตั้งแต่วันที่</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="text-white small mb-2">ถึงวันที่</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-monochrome w-100">
                        <i class="fa-solid fa-magnifying-glass me-2"></i> ค้นหาข้อมูล
                    </button>
                </div>
            </form>
        </div>

        <div class="glass-panel" data-aos="fade-up" data-aos-delay="100">
            
            <div class="d-none d-print-block text-center mb-4">
                <h2 class="fw-bold">รายงานสรุปการยืม-คืนหนังสือเรียน</h2>
                <p>แผนกเทคโนโลยีสารสนเทศ (IT Textbook System)</p>
                <p class="small">ข้อมูลระหว่างวันที่: <?php echo date('d/m/Y', strtotime($start_date)); ?> ถึง <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center text-white">#</th>
                            <th width="15%" class="text-white">วันที่ยืม</th>
                            <th width="15%" class="text-white">รหัสนักเรียน</th>
                            <th width="20%" class="text-white">ชื่อ-สกุล</th>
                            <th width="25%" class="text-white">ชื่อหนังสือ</th>
                            <th width="10%" class="text-center text-white">สถานะ</th>
                            <th width="10%" class="text-center text-white">วันที่คืน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php $i = 1; foreach ($transactions as $row): 
                                $is_borrowed = $row['status'] == 'borrowed';
                            ?>
                            <tr>
                                <td class="text-center text-white"><?php echo $i++; ?></td>
                                <td>
                                    <span class="text-white"><?php echo date('d/m/Y', strtotime($row['borrow_date'])); ?></span>
                                </td>
                                <td><span class="text-white font-monospace"><?php echo $row['student_id']; ?></span></td>
                                <td class="text-white"><?php echo $row['fullname']; ?></td>
                                <td>
                                    <div class="text-white"><?php echo $row['title']; ?></div>
                                    <small class="text-white" style="font-size: 0.8rem;">รหัสเล่ม: <?php echo $row['book_code']; ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if($is_borrowed): ?>
                                        <span class="status-pill st-borrow">กำลังยืม</span>
                                    <?php else: ?>
                                        <span class="status-pill st-return">คืนแล้ว</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo ($row['return_date']) ? date('d/m/y', strtotime($row['return_date'])) : '-'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <i class="fa-solid fa-box-open fs-1 mb-3 opacity-25"></i><br>
                                    ไม่พบข้อมูลในช่วงเวลานี้
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-none d-print-block mt-5 pt-5">
                <div class="d-flex justify-content-between px-5">
                    <div class="text-center">
                        <p>ลงชื่อ ....................................................... ผู้จัดทำ</p>
                        <p>(.......................................................)</p>
                    </div>
                    <div class="text-center">
                        <p>ลงชื่อ ....................................................... ครูที่ปรึกษา</p>
                        <p>(.......................................................)</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
        
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 40 },
                "color": { "value": "#ffffff" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.2, "random": true },
                "size": { "value": 2, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#ffffff", "opacity": 0.1, "width": 1 },
                "move": { "enable": true, "speed": 0.5 }
            },
            "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": false } } }
        });
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