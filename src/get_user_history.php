<?php
require_once 'config.php';

if (isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    // ดึงข้อมูลการยืม พร้อมชื่อหนังสือ
    $sql = "SELECT t.*, b.book_code, m.title, m.cover_image 
            FROM transactions t
            JOIN book_items b ON t.book_item_id = b.id
            JOIN book_masters m ON b.book_master_id = m.id
            WHERE t.user_id = ? 
            ORDER BY t.borrow_date DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $history = $stmt->fetchAll();

    if (count($history) > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered table-striped text-center small">';
        echo '<thead class="table-primary"><tr>
                <th>วันที่ยืม</th>
                <th>กำหนดส่ง</th>
                <th>ชื่อหนังสือ</th>
                <th>สถานะ</th>
                <th>วันที่คืน</th>
              </tr></thead>';
        echo '<tbody>';

        foreach ($history as $row) {
            $status_badge = '';
            if ($row['status'] == 'borrowed') {
                $status_badge = '<span class="badge bg-warning text-dark">กำลังยืม</span>';
            } elseif ($row['status'] == 'returned') {
                $status_badge = '<span class="badge bg-success">คืนแล้ว</span>';
            } elseif ($row['status'] == 'overdue') { // สมมติถ้ามีสถานะนี้
                $status_badge = '<span class="badge bg-danger">เกินกำหนด</span>';
            }

            $return_date = ($row['return_date']) ? date('d/m/Y', strtotime($row['return_date'])) : '-';

            echo '<tr>';
            echo '<td>' . date('d/m/Y', strtotime($row['borrow_date'])) . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($row['due_date'])) . '</td>';
            echo '<td class="text-start text-truncate" style="max-width: 150px;" title="'.$row['title'].'">' . $row['title'] . '</td>';
            echo '<td>' . $status_badge . '</td>';
            echo '<td>' . $return_date . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    } else {
        echo '<div class="text-center text-muted py-4"><i class="fa-solid fa-box-open fa-3x mb-2"></i><br>ยังไม่มีประวัติการยืมหนังสือ</div>';
    }
}
?>