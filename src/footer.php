</div> <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function () {
            // ✅ แก้ไข: เช็คก่อนว่าในหน้านั้นมีตาราง id="bookTable" ไหม?
            if ($('#bookTable').length > 0) {
                $('#bookTable').DataTable({
                    "language": {
                        "search": "ค้นหา:",
                        "lengthMenu": "แสดง _MENU_ รายการ",
                        "info": "หน้าที่ _PAGE_ จาก _PAGES_",
                        "paginate": { "next": "ถัดไป", "previous": "ก่อนหน้า" },
                        "zeroRecords": "ไม่พบข้อมูล"
                    }
                });
            }
        });

        // ส่วนของกราฟ (เหมือนเดิม)
        const ctx = document.getElementById('myChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['ว่าง', 'ถูกยืม'],
                    datasets: [{
                        data: [
                            <?php echo isset($cnt_available) ? $cnt_available : 0; ?>, 
                            <?php echo isset($cnt_borrow) ? $cnt_borrow : 0; ?>
                        ],
                        backgroundColor: ['#1cc88a', '#f6c23e'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    cutout: '70%'
                }
            });
        }
    </script>
</body>
</html>