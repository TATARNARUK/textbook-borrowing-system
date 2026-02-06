<?php
session_start();
session_destroy(); // ทำลาย Session PHP
?>
<!DOCTYPE html>
<html>
<head>
    <script>
        // ทำลายตัวเช็คฝั่ง Browser ด้วย
        sessionStorage.removeItem('is_logged_in');
        // ดีดกลับไปหน้า Login
        window.location.href = 'login.php';
    </script>
</head>
<body></body>
</html>