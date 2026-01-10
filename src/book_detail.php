<?php
session_start();
require_once 'config.php';
require_once 'header.php'; // เรียก Header มาใช้เลย จะได้มีเมนูครบ

// รับค่า ID หนังสือที่ส่งมา
if (!isset($_GET['id'])) {
    echo "<script>window.location='index.php';</script>";
    exit();
}

$id = $_GET['id'];

// 1. ดึงข้อมูลรายละเอียดหนังสือ (Master)
$stmt = $pdo->prepare("SELECT * FROM book_masters WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    echo "<div class='container mt-5'><h3>ไม่พบข้อมูลหนังสือ</h3></div>";
    exit();
}

// 2. เช็คจำนวนสต็อก (ทั้งหมด / ว่าง / ถูกยืม)
$stmtStock = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed
    FROM book_items 
    WHERE book_master_id = ?
");
$stmtStock->execute([$id]);
$stock = $stmtStock->fetch();

// ป้องกันค่า NULL กรณีเพิ่งเพิ่มหนังสือแต่ยังไม่มีเล่มจริง
$total_items = $stock['total'] ?? 0;
$available_items = $stock['available'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $book['title']; ?></title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #000000ff;
            margin: 0;
            overflow: hidden;
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

    </style>
</head>
<body><?php require_once 'loader.php'; ?>
<div id="particles-js"></div>
<div class="container my-5">
    <div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="row">
            <div class="col-md-4 text-center">
                <?php if ($book['cover_image']): ?>
                    <img src="uploads/<?php echo $book['cover_image']; ?>" class="img-fluid rounded shadow" style="max-height: 400px;">
                <?php else: ?>
                    <img src="https://via.placeholder.com/300x450?text=No+Cover" class="img-fluid rounded shadow">
                <?php endif; ?>
            </div>

            <div class="col-md-8">
                <h2 class="fw-bold text-primary mb-3"><?php echo $book['title']; ?></h2>

                <table class="table table-borderless">
                    <tr>
                        <th width="30%">รหัสวิชา / ISBN:</th>
                        <td><span class="badge bg-dark fs-6"><?php echo $book['isbn']; ?></span></td>
                    </tr>
                    <tr>
                        <th>ผู้แต่ง:</th>
                        <td><?php echo $book['author']; ?></td>
                    </tr>
                    <tr>
                        <th>สำนักพิมพ์:</th>
                        <td><?php echo $book['publisher']; ?></td>
                    </tr>
                    <tr>
                        <th>ราคาต่อเล่ม:</th>
                        <td><?php echo number_format($book['price'], 2); ?> บาท</td>
                    </tr>
                    <div class="bg-light bg-opacity-50 p-3 rounded-3 border mb-4 mt-3">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-swatchbook me-2"></i> รายละเอียดรูปเล่มและการพิมพ์
                        </h6>

                        <div class="row g-3">
                            <div class="col-6 col-md-4">
                                <small class="text-muted d-block" style="font-size: 0.8rem;">ครั้งที่อนุมัติ</small>
                                <span class="fw-bold text-dark"><?php echo !empty($book['approval_no']) ? $book['approval_no'] : '-'; ?></span>
                            </div>
                            <div class="col-6 col-md-4">
                                <small class="text-muted d-block" style="font-size: 0.8rem;">ลำดับที่อนุมัติ</small>
                                <span class="fw-bold text-dark"><?php echo !empty($book['approval_order']) ? $book['approval_order'] : '-'; ?></span>
                            </div>
                            <div class="col-6 col-md-4">
                                <small class="text-muted d-block" style="font-size: 0.8rem;">จำนวนหน้า</small>
                                <span class="fw-bold text-dark"><?php echo !empty($book['page_count']) ? number_format($book['page_count']) . ' หน้า' : '-'; ?></span>
                            </div>

                            <div class="col-6 col-md-4">
                                <small class="text-muted d-block" style="font-size: 0.8rem;">รูปแบบกระดาษ</small>
                                <span class="badge bg-secondary bg-opacity-10 text-dark border">
                                    <?php echo !empty($book['paper_type']) ? $book['paper_type'] : '-'; ?>
                                </span>
                            </div>
                            <div class="col-6 col-md-4">
                                <small class="text-muted d-block" style="font-size: 0.8rem;">รูปแบบการพิมพ์</small>
                                <span class="badge bg-secondary bg-opacity-10 text-dark border">
                                    <?php echo !empty($book['print_type']) ? $book['print_type'] : '-'; ?>
                                </span>
                            </div>
                            <div class="col-6 col-md-4">
                                <small class="text-muted d-block" style="font-size: 0.8rem;">ขนาดรูปเล่ม</small>
                                <span class="badge bg-info bg-opacity-10 text-primary border border-info">
                                    <?php echo !empty($book['book_size']) ? $book['book_size'] : '-'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <tr>
                        <th>สถานะสต็อก:</th>
                        <td>
                            <span class="badge bg-info text-dark">ทั้งหมด <?php echo $total_items; ?> เล่ม</span>
                            <span class="badge bg-success">ว่าง <?php echo $available_items; ?> เล่ม</span>
                            <span class="badge bg-warning text-dark">ถูกยืม <?php echo $stock['borrowed'] ?? 0; ?> เล่ม</span>
                        </td>
                    </tr>
                </table>

                <hr>

                <div class="mt-4">
                    <?php if ($available_items > 0): ?>
                        <h4 class="text-success mb-3"><i class="fa-solid fa-check-circle"></i> มีหนังสือว่างพร้อมยืม</h4>
                    <?php else: ?>
                        <h4 class="text-danger mb-3"><i class="fa-solid fa-circle-xmark"></i> หนังสือหมดชั่วคราว</h4>
                    <?php endif; ?>
                    <div class="d-flex gap-2">

                        <?php if ($available_items > 0): ?>
                            <button onclick="confirmBorrowDetail(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')"
                                class="btn btn-lg btn-success px-4">
                                <i class="fa-solid fa-book-open"></i> ยืมเล่มนี้ทันที
                            </button>
                        <?php else: ?>
                            <button class="btn btn-lg btn-secondary px-4" disabled>ไม่สามารถยืมได้</button>
                        <?php endif; ?>

                        <a href="index.php" class="btn btn-lg btn-outline-dark px-4">
                            <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
                        </a>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-lg btn-warning px-4 ms-2">
                                <i class="fa-solid fa-edit"></i> แก้ไข
                            </a>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmBorrowDetail(id, title) {
        Swal.fire({
            title: 'ยืนยันการยืม?',
            text: "คุณต้องการยืมหนังสือ: " + title,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, ขอยืมเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'borrow_save.php?id=' + id;
            }
        })
    }
</script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 1000, // ความเร็วในการขยับ (1000ms = 1 วินาที) ยิ่งเลขเยอะยิ่งช้าและนุ่ม
    once: true      // ให้เล่นแค่ครั้งเดียวตอนเลื่อนลงมา (ไม่ต้องเล่นซ้ำตอนเลื่อนขึ้น)
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
    /* เรียกใช้ particles.js ที่กล่อง id="particles-js" */
    particlesJS("particles-js", {
      "particles": {
        "number": {
          "value": 80, /* จำนวนดาว (ยิ่งเยอะยิ่งรก) ลองปรับดูที่ 50-100 */
          "density": {
            "enable": true,
            "value_area": 800
          }
        },
        "color": {
          "value": "#ffffff" /* สีของดาว (สีขาว) */
        },
        "shape": {
          "type": "circle", /* รูปร่าง (วงกลม) */
          "stroke": {
            "width": 0,
            "color": "#000000"
          },
          "polygon": {
            "nb_sides": 5
          }
        },
        "opacity": {
          "value": 0.5, /* ความจางของดาว (0.5 คือครึ่งๆ) */
          "random": true, /* ให้จางไม่เท่ากัน ดูมีมิติ */
          "anim": {
            "enable": false,
            "speed": 1,
            "opacity_min": 0.1,
            "sync": false
          }
        },
        "size": {
          "value": 3, /* ขนาดของดาว */
          "random": true, /* เล็กใหญ่ไม่เท่ากัน */
          "anim": {
            "enable": false,
            "speed": 40,
            "size_min": 0.1,
            "sync": false
          }
        },
        "line_linked": {
          "enable": true, /* ✅ ถ้าไม่อยากได้เส้นเชื่อม ให้แก้เป็น false */
          "distance": 150, /* ระยะห่างที่จะให้มีเส้นเชื่อม */
          "color": "#ffffff", /* สีของเส้น */
          "opacity": 0.4, /* ความจางของเส้น */
          "width": 1
        },
        "move": {
          "enable": true, /* สั่งให้ขยับ */
          "speed": 2, /* ความเร็วในการวิ่ง (ยิ่งเยอะยิ่งเร็ว) */
          "direction": "none", /* ทิศทาง (none คือมั่ว) */
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
      "interactivity": { /* ส่วนนี้คือเวลาเอาเมาส์ไปโดน */
        "detect_on": "canvas",
        "events": {
          "onhover": {
            "enable": true, /* ถ้า true เวลาเอาเมาส์ไปชี้ ดาวจะวิ่งหนีหรือวิ่งเข้าหา */
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


