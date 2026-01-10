<style>
    /* ฉากหลังสีขาวบังหน้าจอ */
    #global-loader {
        position: fixed;
        z-index: 99999; /* อยู่บนสุดของทุกอย่าง */
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.95); /* สีขาวจางๆ นิดหน่อย */
        display: flex;
        justify-content: center;
        align-items: center;
        transition: opacity 0.5s ease; /* เอฟเฟกต์ค่อยๆ จางหาย */
    }

    /* ซ่อนการเลื่อนหน้าจอตอนโหลด */
    body.loading {
        overflow: hidden;
    }
</style>

<div id="global-loader">
    <div class="text-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2 fw-bold text-secondary">กำลังโหลด...</div>
    </div>
</div>

<script>
    // 1. ทันทีที่เข้าเว็บ ให้ใส่ class loading เพื่อล็อคหน้าจอ
    document.body.classList.add('loading');

    // 2. เมื่อโหลดทุกอย่างเสร็จ (รูป, สคริปต์, ข้อมูล)
    window.addEventListener('load', function() {
        var loader = document.getElementById('global-loader');
        
        // ค่อยๆ จางหายไป
        loader.style.opacity = '0';
        
        // รอ 0.5 วินาที (ตามเวลา transition) แล้วลบทิ้งไปเลย
        setTimeout(function() {
            loader.style.display = 'none';
            document.body.classList.remove('loading'); // ปลดล็อคหน้าจอ
        }, 500);
    });

    // 3. (แถม) ถ้ามีการกดปุ่ม Submit หรือ Link ที่จะเปลี่ยนหน้า ให้โชว์ Loader อีกรอบ
    // เพื่อให้คนรู้ว่าระบบกำลังทำงานอยู่ (เช่น ตอนกดบันทึก หรือ ดึง API)
    document.addEventListener('submit', function(e) {
        // เช็คก่อนว่าฟอร์มกรอกครบไหม (ถ้าไม่ครบ ไม่ต้องโชว์ Loader)
        if (e.target.checkValidity()) {
            var loader = document.getElementById('global-loader');
            loader.style.display = 'flex';
            loader.style.opacity = '1';
        }
    });
</script>