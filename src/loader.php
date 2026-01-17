<style>
    /* 1. Wrapper สำหรับจัดกึ่งกลางและบังหน้าจอ (พื้นหลังโปร่งใส) */
    #global-loader-wrapper {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: transparent; /* ✅ เปลี่ยนเป็นโปร่งใส */
        z-index: 99999; /* อยู่บนสุด */
        display: flex;
        justify-content: center;
        align-items: center;
        transition: opacity 0.5s ease, visibility 0.5s ease;
        pointer-events: none; /* ✅ เพิ่มเพื่อให้คลิกทะลุได้ (ถ้าต้องการบล็อกคลิกให้ลบบรรทัดนี้) */
    }

    /* 2. Keyframes Animation */
    @-webkit-keyframes honeycomb {
        0%, 20%, 80%, 100% {
            opacity: 0;
            -webkit-transform: scale(0);
            transform: scale(0);
        }
        30%, 70% {
            opacity: 1;
            -webkit-transform: scale(1);
            transform: scale(1);
        }
    }

    @keyframes honeycomb {
        0%, 20%, 80%, 100% {
            opacity: 0;
            -webkit-transform: scale(0);
            transform: scale(0);
        }
        30%, 70% {
            opacity: 1;
            -webkit-transform: scale(1);
            transform: scale(1);
        }
    }

    /* 3. Honeycomb Styles */
    .honeycomb {
        height: 24px;
        position: relative;
        width: 24px;
        /* เพิ่มเงาหรือ Background เล็กๆ ให้ตัวโหลดเด่นขึ้น (ถ้าต้องการ) */
        /* filter: drop-shadow(0 0 5px rgba(255,255,255,0.8)); */
    }

    .honeycomb div {
        -webkit-animation: honeycomb 2.1s infinite backwards;
        animation: honeycomb 2.1s infinite backwards;
        background: #0d6efd; /* สีฟ้า */
        height: 12px;
        margin-top: 6px;
        position: absolute;
        width: 24px;
    }

    .honeycomb div:after,
    .honeycomb div:before {
        content: '';
        border-left: 12px solid transparent;
        border-right: 12px solid transparent;
        position: absolute;
        left: 0;
        right: 0;
    }

    .honeycomb div:after {
        top: -6px;
        border-bottom: 6px solid #0d6efd; /* ขอบล่างสีฟ้า */
    }

    .honeycomb div:before {
        bottom: -6px;
        border-top: 6px solid #0d6efd; /* ขอบบนสีฟ้า */
    }

    /* จัดวางตำแหน่งแต่ละชิ้น */
    .honeycomb div:nth-child(1) { -webkit-animation-delay: 0s; animation-delay: 0s; left: -28px; top: 0; }
    .honeycomb div:nth-child(2) { -webkit-animation-delay: 0.1s; animation-delay: 0.1s; left: -14px; top: 22px; }
    .honeycomb div:nth-child(3) { -webkit-animation-delay: 0.2s; animation-delay: 0.2s; left: 14px; top: 22px; }
    .honeycomb div:nth-child(4) { -webkit-animation-delay: 0.3s; animation-delay: 0.3s; left: 28px; top: 0; }
    .honeycomb div:nth-child(5) { -webkit-animation-delay: 0.4s; animation-delay: 0.4s; left: 14px; top: -22px; }
    .honeycomb div:nth-child(6) { -webkit-animation-delay: 0.5s; animation-delay: 0.5s; left: -14px; top: -22px; }
    .honeycomb div:nth-child(7) { -webkit-animation-delay: 0.6s; animation-delay: 0.6s; left: 0; top: 0; }
</style>

<div id="global-loader-wrapper">
    <div class="honeycomb">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    </div>
</div>

<script>
    // เมื่อโหลดหน้าเว็บเสร็จ
    window.addEventListener('load', function() {
        var loader = document.getElementById('global-loader-wrapper');
        
        if(loader){
            // หน่วงเวลา 1200ms
            setTimeout(function() {
                loader.style.opacity = '0';
                loader.style.visibility = 'hidden'; 
                
                setTimeout(function() {
                    loader.style.display = 'none';
                }, 500); 
            }, 1000); 
        }
    });

    // เมื่อมีการกด Submit Form
    document.addEventListener('submit', function(e) {
        if (e.target.checkValidity()) {
            var loader = document.getElementById('global-loader-wrapper');
            if(loader) {
                loader.style.display = 'flex';
                loader.style.visibility = 'visible';
                setTimeout(() => { 
                    loader.style.opacity = '1'; 
                }, 10);
            }
        }
    });
</script>