            </div>
        </div>
    </div>
    
    <script>
        // نمایش/مخفی کردن پیام‌های موقت
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
                    setTimeout(function() {
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            alert.remove();
                        }, 300);
                    }, 5000);
                }
            });
        });
        
        // تأیید عملیات‌های خطرناک
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-danger') || e.target.closest('.btn-danger')) {
                if (!confirm('آیا از انجام این عملیات مطمئن هستید؟')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>
</html>
