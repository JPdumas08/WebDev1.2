            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="includes/admin.js?v=<?php echo time(); ?>"></script>
    <script>
        // Mark all admin notifications as read
        async function markAllAdminNotifsRead() {
            try {
                const response = await fetch('api/mark_admin_notifications_read.php', {
                    method: 'POST'
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error marking notifications as read:', error);
            }
        }
    </script>
</body>
</html>
