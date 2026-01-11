<style>
    /* --- แก้ไข: ฉากหลังเป็นกระจกโปร่งแสง (See-through) --- */
    #global-loader-wrapper {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        
        /* ✅ สีดำโปร่งแสง 80% (เห็นข้างหลังลางๆ) */
        background-color: rgba(0, 0, 0, 0.8); 
        
        /* ✅ เพิ่มความเบลอให้ฉากหลัง (Glass Effect) */
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        
        z-index: 99999;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: opacity 0.5s ease;
    }

    body.loading {
        overflow: hidden;
    }

    /* --- อนิเมชั่นตัวหนังสือ (เหมือนเดิม) --- */
    .loader {
        width: 80px;
        height: 50px;
        position: relative;
    }

    .loader-text {
        position: absolute;
        top: 0;
        padding: 0;
        margin: 0;
        color: #ffffff;
        animation: text_713 3.5s ease both infinite;
        font-size: .8rem;
        letter-spacing: 1px;
        font-family: sans-serif;
        font-weight: bold;
        text-transform: uppercase;
    }

    .load {
        background-color: #000000;
        border-radius: 50px;
        display: block;
        height: 16px;
        width: 16px;
        bottom: 0;
        position: absolute;
        transform: translateX(64px);
        animation: loading_713 3.5s ease both infinite;
    }

    .load::before {
        position: absolute;
        content: "";
        width: 100%;
        height: 100%;
        background-color: #ffffff;
        border-radius: inherit;
        animation: loading2_713 3.5s ease both infinite;
    }

    @keyframes text_713 {
        0% { letter-spacing: 1px; transform: translateX(0px); }
        40% { letter-spacing: 2px; transform: translateX(26px); }
        80% { letter-spacing: 1px; transform: translateX(32px); }
        90% { letter-spacing: 2px; transform: translateX(0px); }
        100% { letter-spacing: 1px; transform: translateX(0px); }
    }

    @keyframes loading_713 {
        0% { width: 16px; transform: translateX(0px); }
        40% { width: 100%; transform: translateX(0px); }
        80% { width: 16px; transform: translateX(64px); }
        90% { width: 100%; transform: translateX(0px); }
        100% { width: 16px; transform: translateX(0px); }
    }

    @keyframes loading2_713 {
        0% { transform: translateX(0px); width: 16px; }
        40% { transform: translateX(0%); width: 80%; }
        80% { width: 100%; transform: translateX(0px); }
        90% { width: 80%; transform: translateX(15px); }
        100% { transform: translateX(0px); width: 16px; }
    }
</style>

<div id="global-loader-wrapper">
    <div class="loader">
        <span class="loader-text">loading</span>
        <span class="load"></span>
    </div>
</div>

<script>
    document.body.classList.add('loading');

    window.addEventListener('load', function() {
        var loader = document.getElementById('global-loader-wrapper');
        
        if(loader){
            // สั่งให้รอ 3 วินาที (เพื่อให้เห็นอนิเมชั่นบนฉากหลังโปร่งแสง)
            setTimeout(function() {
                loader.style.opacity = '0';
                setTimeout(function() {
                    loader.style.display = 'none';
                    document.body.classList.remove('loading');
                }, 500);
            }, 200); // รอ 0.2 วินาทีก่อนซ่อน
        }
    });

    document.addEventListener('submit', function(e) {
        if (e.target.checkValidity()) {
            var loader = document.getElementById('global-loader-wrapper');
            if(loader) {
                loader.style.display = 'flex';
                setTimeout(() => { loader.style.opacity = '1'; }, 10);
            }
        }
    });
</script>