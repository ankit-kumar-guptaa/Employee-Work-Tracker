</div> <!-- End container-fluid -->
    </div> <!-- End content-wrapper -->

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery (for some additional functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Chart.js (for reports) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar toggle
            const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');

            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    sidebarBackdrop.classList.toggle('show');
                });
            }

            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    sidebarBackdrop.classList.remove('show');
                });
            }

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Initialize popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl)
            });

            // Auto-close alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);

            // File input show filename
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });

            // Date range picker initialization
            if (typeof daterangepicker !== 'undefined') {
                $('input[name="daterange"]').daterangepicker({
                    opens: 'left'
                });
            }
        });
    </script>
</body>
</html>
