<?php
session_start();
require_once 'config.php';

// เช็คสิทธิ์
if (!isset($_SESSION['user_id']) /* || $_SESSION['role'] !== 'admin' */) {
    // header("Location: login.php"); exit();
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher = $_POST['publisher'];
    $price = $_POST['price'];

    // ค่าใหม่
    $approval_no = $_POST['approval_no'];
    $approval_order = $_POST['approval_order'];
    $page_count = $_POST['page_count'];
    $paper_type = $_POST['paper_type'];
    $print_type = $_POST['print_type'];
    $book_size = $_POST['book_size'];

    // รูปภาพ
    $image_path = "";
    if (isset($_FILES['cover_img']) && $_FILES['cover_img']['error'] == 0) {
        $ext = pathinfo($_FILES['cover_img']['name'], PATHINFO_EXTENSION);
        $new_name = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['cover_img']['tmp_name'], "uploads/" . $new_name);
        $image_path = $new_name;
    }

    // SQL
    $sql = "INSERT INTO book_masters (isbn, title, author, publisher, price, approval_no, approval_order, page_count, paper_type, print_type, book_size, cover_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$isbn, $title, $author, $publisher, $price, $approval_no, $approval_order, $page_count, $paper_type, $print_type, $book_size, $image_path])) {
        $msg = "success";
    } else {
        $msg = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มหนังสือใหม่ - Admin</title>
    <link rel="icon" type="image/png" href="images/books.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* --- Monochrome Theme Setup --- */
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
            background: rgba(10, 10, 10, 0.85);
            /* เพิ่มความทึบนิดนึงเพื่อให้ฟอร์มเด่น */
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        /* --- Inputs Styling --- */
        .form-control,
        .form-select {
            background-color: #111 !important;
            border: 1px solid #333;
            color: #fff !important;
            border-radius: 4px;
            padding: 12px 15px;
            font-weight: 300;
            /* ป้องกันพื้นหลังเปลี่ยนสี */
            background-clip: padding-box;
        }



        /* --- Monochrome Theme Setup --- */
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: #000000;
            color: #e0e0e0;
            overflow-x: hidden;
        }

        /* --- Glass Card --- */
        .glass-card {
            background: rgba(10, 10, 10, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        /* 🔥 ไม้ตายแก้ช่องขาว (Autofill Override) 🔥 */
        /* ใช้ box-shadow สีดำ ถมทับพื้นหลังสีขาวที่ browser ยัดเยียดมา */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active,
        textarea:-webkit-autofill,
        textarea:-webkit-autofill:hover,
        textarea:-webkit-autofill:focus,
        select:-webkit-autofill,
        select:-webkit-autofill:hover,
        select:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #111 inset !important;
            /* ถมดำทับ */
            -webkit-text-fill-color: #fff !important;
            /* ตัวหนังสือขาว */
            transition: background-color 5000s ease-in-out 0s;
            /* หลอก browser ว่ายังไม่ได้เปลี่ยนสี */
            caret-color: #fff;
            /* สี cursor */
            border: 1px solid #555 !important;
            /* บังคับขอบสีเทา */
        }

        /* จัดการ Input ทั่วไป */
        .form-control,
        .form-select {
            background-color: #111 !important;
            border: 1px solid #333;
            color: #fff !important;
            border-radius: 4px;
            padding: 12px 15px;
            font-weight: 300;
            background-clip: padding-box;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #000 !important;
            border-color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .form-control::placeholder {
            color: #555;
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        }

        .form-select option {
            background-color: #000;
            color: #fff;
        }

        /* --- Floating Labels Settings --- */

        /* 🔥🔥🔥 [จุดที่เพิ่ม] แก้ก้อนสีขาวบังตัวหนังสือ 🔥🔥🔥 */
        /* Bootstrap สร้างฉากหลังให้ Label เราต้องสั่งลบออก */
        .form-floating>label::after {
            background-color: transparent !important;
        }

        .form-floating>label {
            color: #666;
            z-index: 10;
        }

        /* ตอน Label ลอยขึ้นไป */
        .form-floating>.form-control:focus~label,
        .form-floating>.form-control:not(:placeholder-shown)~label {
            color: #ffffff;
            font-weight: 600;
            background-color: transparent !important;
        }

        /* แก้บั๊ก Label มีพื้นหลังบังเส้นขอบกรณี Autofill ทำงาน */
        .form-floating>.form-control:-webkit-autofill~label {
            background-color: transparent !important;
        }

        /* --- Custom Upload Zone --- */
        .upload-zone {
            border: 1px dashed #555;
            background-color: rgba(255, 255, 255, 0.02);
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .upload-zone:hover {
            border-color: #fff;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .upload-zone input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        /* --- Typography & Elements --- */
        .section-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #888;
            margin-bottom: 25px;
            border-left: 2px solid #fff;
            padding-left: 12px;
        }

        /* --- Buttons --- */
        .btn-monochrome {
            background-color: #ffffff;
            color: #000000;
            border: 1px solid #ffffff;
            font-weight: 700;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .btn-monochrome:hover {
            background-color: #000000;
            color: #ffffff;
            border-color: #ffffff;
        }

        .btn-outline-monochrome {
            background-color: transparent;
            color: #888;
            border: 1px solid #555;
            transition: all 0.3s;
        }

        .btn-outline-monochrome:hover {
            color: #fff;
            border-color: #fff;
            background-color: transparent;
        }
    </style>
</head>

<body>
    <?php require_once 'loader.php'; ?>
    <div id="particles-js"></div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">

                <div class="glass-card p-4 p-md-5" data-aos="fade-up" data-aos-duration="1000">

                    <div class="d-flex align-items-center justify-content-between mb-5 border-bottom border-secondary pb-3">
                        <h3 class="fw-light mb-0 text-white" style="letter-spacing: 1px;">
                            <i class="fa-solid fa-plus me-2 text-secondary"></i>เพิ่มหนังสือใหม่
                        </h3>
                        <a href="index.php" class="btn btn-outline-monochrome btn-sm rounded-0 px-3">
                            กลับหน้าหลัก
                        </a>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data">

                        <div class="section-title text-white">ข้อมูลทั่วไป</div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="isbn" name="isbn" placeholder="ISBN" required autocomplete="off">
                                    <label for="isbn" class="text-white">รหัสวิชา / ISBN</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="title" name="title" placeholder="ชื่อหนังสือ" required autocomplete="off">
                                    <label for="title" class="text-white">ชื่อหนังสือ / ชื่อวิชา</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="author" name="author" placeholder="ผู้แต่ง" autocomplete="off">
                                    <label for="author" class="text-white">ชื่อผู้แต่ง</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="publisher" name="publisher" placeholder="สำนักพิมพ์" autocomplete="off">
                                    <label for="publisher" class="text-white">สำนักพิมพ์</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" placeholder="ราคา" autocomplete="off">
                                    <label for="price" class="text-white">ราคาหน้าปก (บาท)</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="upload-zone h-100 d-flex flex-column justify-content-center">
                                    <input type="file" name="cover_img" id="cover_img" accept="image/*" onchange="previewFile()">
                                    <div id="upload-label">
                                        <i class="fa-regular fa-image fs-4 text-secondary mb-2"></i><br>
                                        <small class="text-white" style="font-weight: 300;">อัปโหลดรูปหน้าปก</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-title mt-5 text-white">รายละเอียดหนังสือและอนุมัติ</div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="approval_no" name="approval_no" placeholder="ครั้งที่อนุมัติ" autocomplete="off">
                                    <label for="approval_no" class="text-white">ครั้งที่อนุมัติ</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="approval_order" name="approval_order" placeholder="ลำดับ" autocomplete="off">
                                    <label for="approval_order" class="text-white">ลำดับที่อนุมัติ</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="page_count" name="page_count" placeholder="หน้า" autocomplete="off">
                                    <label for="page_count" class="text-white">จำนวนหน้า</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-floating">
                                    <select class="form-select" id="paper_type" name="paper_type">
                                        <option value="">- เลือก -</option>
                                        <option value="ปอนด์">ปอนด์</option>
                                        <option value="ถนอมสายตา">ถนอมสายตา</option>
                                        <option value="อาร์ต">อาร์ต</option>
                                        <option value="บรู๊ฟ">บรู๊ฟ</option>
                                    </select>
                                    <label for="paper_type" class="text-white">กระดาษ</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-floating">
                                    <select class="form-select" id="print_type" name="print_type">
                                        <option value="">- เลือก -</option>
                                        <option value="1 สี">1 สี</option>
                                        <option value="2 สี">2 สี</option>
                                        <option value="4 สี">4 สี</option>
                                    </select>
                                    <label for="print_type" class="text-white">การพิมพ์</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-floating">
                                    <select class="form-select" id="book_size" name="book_size">
                                        <option value="">- เลือก -</option>
                                        <option value="8 หน้ายก">8 หน้ายก</option>
                                        <option value="A4">A4</option>
                                        <option value="อื่นๆ">อื่นๆ</option>
                                    </select>
                                    <label for="book_size" class="text-white">ขนาด</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" class="btn btn-monochrome btn-lg rounded-0 py-3 shadow-sm">
                                ยืนยันการเพิ่มหนังสือ
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
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
    <script>
        // Init Animation
        AOS.init({
            duration: 800,
            once: true
        });

        // Particles
        particlesJS("particles-js", {
            "particles": {
                "number": {
                    "value": 80,
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
                    "value": 0.5,
                    "random": true
                },
                "size": {
                    "value": 3,
                    "random": true
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#ffffff",
                    "opacity": 0.4,
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 2
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        "mode": "grab"
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push"
                    },
                    "resize": true
                }
            },
            "retina_detect": true
        });

        // Script Show Filename
        function previewFile() {
            const fileInput = document.getElementById('cover_img');
            const label = document.getElementById('upload-label');
            if (fileInput.files.length > 0) {
                label.innerHTML = '<i class="fa-solid fa-check text-white fs-4 mb-2"></i><br><span class="text-white">' + fileInput.files[0].name + '</span>';
                document.querySelector('.upload-zone').style.borderColor = '#fff';
            }
        }

        // SweetAlert Style B&W
        <?php if ($msg == 'success'): ?>
            Swal.fire({
                icon: 'success',
                title: 'SUCCESS',
                text: 'Book has been added.',
                background: '#000',
                color: '#fff',
                iconColor: '#fff',
                confirmButtonColor: '#fff',
                confirmButtonText: '<span style="color:#000; font-weight:bold;">OK</span>'
            }).then(() => {
                window.location = 'index.php';
            });
        <?php elseif ($msg == 'error'): ?>
            Swal.fire({
                icon: 'error',
                title: 'ERROR',
                text: 'Something went wrong.',
                background: '#000',
                color: '#fff',
                iconColor: '#fff',
                confirmButtonColor: '#333'
            });
        <?php endif; ?>
    </script>
</body>

</html>