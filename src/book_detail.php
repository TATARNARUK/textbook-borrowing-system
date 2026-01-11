<?php
session_start();
require_once 'config.php';

// รับค่า ID
if (!isset($_GET['id'])) {
    echo "<script>window.location='index.php';</script>";
    exit();
}

$id = $_GET['id'];

// 1. ดึงข้อมูลหนังสือ
$stmt = $pdo->prepare("SELECT * FROM book_masters WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    echo "<div class='container mt-5 text-white'><h3>ไม่พบข้อมูลหนังสือ</h3></div>";
    exit();
}

// 2. เช็คสต็อก
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

$total_items = $stock['total'] ?? 0;
$available_items = $stock['available'] ?? 0;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $book['title']; ?></title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* --- Monochrome Base --- */
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
            /* เหลี่ยมเท่ๆ */
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8);
        }

        /* --- Image Styling --- */
        .book-cover-container {
            position: relative;
            overflow: hidden;
            border-radius: 4px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease;
        }

        .book-cover-container:hover {
            transform: translateY(-5px);
        }

        .book-cover {
            width: 100%;
            height: auto;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* --- Typography --- */
        .text-label {
            color: #777;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .text-value {
            color: #fff;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .price-tag {
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -1px;
        }

        .isbn-badge {
            background: rgba(255, 255, 255, 0.1);
            color: #aaa;
            padding: 4px 10px;
            font-size: 0.8rem;
            letter-spacing: 1px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* --- Spec Grid --- */
        .spec-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
        }

        .spec-box:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* --- Buttons --- */
        .btn-monochrome {
            background-color: #ffffff;
            color: #000000;
            border: 1px solid #ffffff;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 12px 30px;
            border-radius: 0;
            transition: all 0.3s;
        }

        .btn-monochrome:hover {
            background-color: #000000;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.1);
        }

        .btn-monochrome:disabled {
            background-color: #333;
            border-color: #333;
            color: #666;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-outline-monochrome {
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px 20px;
            border-radius: 0;
            transition: all 0.3s;
        }

        .btn-outline-monochrome:hover {
            border-color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        /* --- Status Indicator --- */
        .status-dot {
            height: 10px;
            width: 10px;
            background-color: #333;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .status-dot.active {
            background-color: #00ff88;
            box-shadow: 0 0 10px #00ff88;
        }

        .status-dot.inactive {
            background-color: #ff3333;
            box-shadow: 0 0 10px #ff3333;
        }
    </style>
</head>

<body>
    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <div class="glass-card p-4 p-lg-5" data-aos="fade-up" data-aos-duration="1000">
                    <div class="row g-5">

                        <div class="col-md-4">
                            <div class="book-cover-container">
                                <?php if ($book['cover_image']): ?>
                                    <img src="uploads/<?php echo $book['cover_image']; ?>" class="book-cover" alt="Cover">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/400x600/111/555?text=No+Cover" class="book-cover" alt="No Cover">
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 text-center p-3" style="border: 1px solid rgb(255, 255, 255); background: rgba(0,0,0,0.3);">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-label" style="font-size: 0.7rem;">STATUS</span>
                                    <div>
                                        <?php if ($available_items > 0): ?>
                                            <span class="status-dot active"></span> <span class="text-white small">พร้อมยืม</span>
                                        <?php else: ?>
                                            <span class="status-dot inactive"></span> <span class="text-white small">หมดชั่วคราว</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="progress" style="height: 4px; background: #333;">
                                    <?php
                                    $percent = ($total_items > 0) ? ($available_items / $total_items) * 100 : 0;
                                    $color = ($available_items > 0) ? '#fff' : '#333';
                                    ?>
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percent; ?>%; background-color: <?php echo $color; ?>;"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-2 small text-white">
                                    <span>ว่าง: <?php echo $available_items; ?></span>
                                    <span>ทั้งหมด: <?php echo $total_items; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">

                            <div class="mb-4 border-bottom border-secondary pb-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="isbn-badge mb-2 d-inline-block">ISBN: <?php echo $book['isbn']; ?></span>
                                        <h1 class="fw-bold text-white mb-2" style="letter-spacing: -0.5px;"><?php echo $book['title']; ?></h1>
                                        <div class="d-flex gap-3 text-white small">
                                            <span><i class="fa-regular fa-user me-1"></i> <?php echo $book['author']; ?></span>
                                            <span><i class="fa-regular fa-building me-1"></i> <?php echo $book['publisher']; ?></span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="price-tag"><?php echo number_format($book['price'], 0); ?>.-</div>
                                        <div class="text-label text-end" style="font-size: 0.7rem;">THB</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-5">
                                <div class="text-white mb-3"><i class="fa-solid fa-layer-group me-2"></i>SPECIFICATIONS</div>
                                <div class="row g-0">
                                    <div class="col-6 col-md-3">
                                        <div class="spec-box">
                                            <div class="text-label" style="font-size: 0.7rem;">จำนวนหน้า</div>
                                            <div class="text-white"><?php echo !empty($book['page_count']) ? $book['page_count'] : '-'; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="spec-box" style="border-left: 0;">
                                            <div class="text-label" style="font-size: 0.7rem;">รูปแบบกระดาษ</div>
                                            <div class="text-white"><?php echo !empty($book['paper_type']) ? $book['paper_type'] : '-'; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="spec-box" style="border-left: 0;">
                                            <div class="text-label" style="font-size: 0.7rem;">ประเภทการพิมพ์</div>
                                            <div class="text-white"><?php echo !empty($book['print_type']) ? $book['print_type'] : '-'; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="spec-box" style="border-left: 0;">
                                            <div class="text-label" style="font-size: 0.7rem;">ขนาดหนังสือ</div>
                                            <div class="text-white"><?php echo !empty($book['book_size']) ? $book['book_size'] : '-'; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-0 mt-2">
                                    <div class="col-12">
                                        <div class="spec-box d-flex justify-content-between">
                                            <span class="text-label">APPROVAL NO.</span>
                                            <span class="text-white"><?php echo !empty($book['approval_no']) ? $book['approval_no'] : '-'; ?> (ลำดับที่ <?php echo !empty($book['approval_order']) ? $book['approval_order'] : '-'; ?>)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-3 mt-auto">
                                <?php if ($available_items > 0): ?>
                                    <button onclick="confirmBorrowDetail(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')"
                                        class="btn btn-monochrome flex-grow-1">
                                        <i class="fa-solid fa-book-open me-2"></i> ยิมหนังสือ
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-monochrome flex-grow-1" disabled>
                                        <i class="fa-solid fa-lock me-2"></i> OUT OF STOCK
                                    </button>
                                <?php endif; ?>

                                <a href="index.php" class="btn btn-outline-monochrome">
                                    <i class="fa-solid fa-arrow-left"></i>
                                </a>

                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                    <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-outline-monochrome text-warning border-warning">
                                        <i class="fa-solid fa-pen"></i>
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
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        AOS.init({
            duration: 800,
            once: true
        });

        /* Particles Config (White Dots) */
        particlesJS("particles-js", {
            "particles": {
                "number": {
                    "value": 60,
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": {
                    "value": "#ffffff"
                },
                "shape": {
                    "type": "circle",
                    "stroke": {
                        "width": 0,
                        "color": "#000000"
                    }
                },
                "opacity": {
                    "value": 0.3,
                    "random": true
                },
                "size": {
                    "value": 2,
                    "random": true
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#ffffff",
                    "opacity": 0.15,
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 0.5
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        "mode": "grab"
                    },
                    "resize": true
                }
            }
        });

        /* Dark Theme SweetAlert */
        function confirmBorrowDetail(id, title) {
            Swal.fire({
                title: 'CONFIRM BORROW',
                text: "ต้องการยืมหนังสือ: " + title,
                icon: 'question',
                showCancelButton: true,
                background: '#000',
                color: '#fff',
                iconColor: '#fff',
                confirmButtonColor: '#fff',
                cancelButtonColor: '#333',
                confirmButtonText: '<span style="color:#000; font-weight:bold;">YES, BORROW</span>',
                cancelButtonText: 'CANCEL'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'borrow_save.php?id=' + id;
                }
            })
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