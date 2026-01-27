<?php
session_start();
require_once 'config.php';

// 1. เช็ค Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่าส่ง ID มาไหม
if (isset($_GET['id'])) {
    $book_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    try {
        // 2. เช็คสต็อก (ใช้รหัส book_master_id)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM book_items WHERE book_master_id = ? AND status = 'available'");
        $stmt->execute([$book_id]);
        $stock = $stmt->fetchColumn();

        if ($stock > 0) {
            
            // 3. ป้องกันการยืมซ้ำ (เล่มเดิมยังไม่คืน)
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM transactions 
                                        WHERE user_id = ? AND book_master_id = ? AND status = 'borrowed'");
            $stmtCheck->execute([$user_id, $book_id]);
            $alreadyBorrowed = $stmtCheck->fetchColumn();

            if ($alreadyBorrowed > 0) {
                header("Location: index.php?status=duplicate");
                exit();
            }

            // 4. สุ่มหยิบเล่มว่าง 1 เล่ม
            $stmtItem = $pdo->prepare("SELECT id FROM book_items WHERE book_master_id = ? AND status = 'available' LIMIT 1");
            $stmtItem->execute([$book_id]);
            $item = $stmtItem->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                $item_id = $item['id'];

                // 5. บันทึก Transaction
                $pdo->beginTransaction();

                $sqlInsert = "INSERT INTO transactions (user_id, book_item_id, book_master_id, borrow_date, due_date, status) 
                              VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'borrowed')";
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([$user_id, $item_id, $book_id]);

                // อัปเดตสถานะหนังสือ
                $sqlUpdate = "UPDATE book_items SET status = 'borrowed' WHERE id = ?";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([$item_id]);

                $pdo->commit();

                header("Location: index.php?status=success");
                exit();
            }
        } 
        
        header("Location: index.php?status=error");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        header("Location: index.php?status=error");
    }
} else {
    header("Location: index.php");
    exit();
}
?>