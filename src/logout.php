<?php
session_start();
session_destroy(); // ทำลาย Session PHP
?>
<!DOCTYPE html>
<html>

<head>
    <script>
// 2. ทำลาย Session ฝั่ง Browser (ตัวแปรกันเหนียว)
        sessionStorage.removeItem('is_logged_in');
        
        // 3. ดีดกลับไปหน้า Landing
        window.location.href = 'landing.php';
    </script>
</head>

<body></body>

</html>