<?php
/**
 * Admin Messages Management
 * View and manage contact messages from customers
 */
require_once __DIR__ . '/auth.php';

$page_title = 'Messages';

// Filter
$status_filter = $_GET['status'] ?? 'all';
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    // Build query
    $where = '';
    $params = [];
    
    if ($status_filter !== 'all') {
        $where = 'WHERE status = :status';
        $params[':status'] = $status_filter;
    }
    
    // Count total
    $count_sql = "SELECT COUNT(*) as total FROM contact_messages $where";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = (int)$count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $pages = ceil($total / $per_page);
    
    // Fetch messages
    $messages_sql = "SELECT cm.*, u.username 
                     FROM contact_messages cm
                     LEFT JOIN users u ON cm.user_id = u.user_id
                     $where
                     ORDER BY cm.created_at DESC
                     LIMIT :limit OFFSET :offset";
    
    $messages_stmt = $pdo->prepare($messages_sql);
    $messages_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $messages_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $messages_stmt->bindValue($key, $value);
    }
    $messages_stmt->execute();
    $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get status counts
    $new_count = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
    $read_count = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'read'")->fetchColumn();
    $replied_count = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'replied'")->fetchColumn();
    $closed_count = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'closed'")->fetchColumn();
    
} catch (Exception $e) {
    error_log("Messages query error: " . $e->getMessage());
    $messages = [];
    $total = 0;
    $pages = 1;
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Customer Messages</h1>
        <p class="page-subtitle">View and respond to customer inquiries</p>
    </div>
</div>

<!-- Status Filter Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <a href="?status=new" class="text-decoration-none">
            <div class="card <?php echo $status_filter === 'new' ? 'border-primary' : ''; ?>">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-0"><?php echo $new_count; ?></h3>
                    <p class="text-muted mb-0">New Messages</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?status=read" class="text-decoration-none">
            <div class="card <?php echo $status_filter === 'read' ? 'border-info' : ''; ?>">
                <div class="card-body text-center">
                    <h3 class="text-info mb-0"><?php echo $read_count; ?></h3>
                    <p class="text-muted mb-0">Read</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?status=replied" class="text-decoration-none">
            <div class="card <?php echo $status_filter === 'replied' ? 'border-success' : ''; ?>">
                <div class="card-body text-center">
                    <h3 class="text-success mb-0"><?php echo $replied_count; ?></h3>
                    <p class="text-muted mb-0">Replied</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?status=closed" class="text-decoration-none">
            <div class="card <?php echo $status_filter === 'closed' ? 'border-secondary' : ''; ?>">
                <div class="card-body text-center">
                    <h3 class="text-secondary mb-0"><?php echo $closed_count; ?></h3>
                    <p class="text-muted mb-0">Closed</p>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Messages Table -->
<div class="admin-card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="card-title mb-0">Messages (<?php echo number_format($total); ?>)</h2>
            <a href="?status=all" class="btn btn-secondary btn-sm">View All</a>
        </div>
    </div>
    
    <?php if (!empty($messages)): ?>
        <div class="table-responsive">
            <table class="data-table" style="min-width: 900px;">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Message Preview</th>
                        <th>Received</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): 
                        $status_badges = [
                            'new' => '<span class="badge-status badge-primary">New</span>',
                            'read' => '<span class="badge-status badge-info">Read</span>',
                            'replied' => '<span class="badge-status badge-success">Replied</span>',
                            'closed' => '<span class="badge-status badge-secondary">Closed</span>'
                        ];
                    ?>
                        <tr>
                            <td><?php echo $status_badges[$msg['status']] ?? ''; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($msg['name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($msg['email']); ?></small>
                                <?php if ($msg['username']): ?>
                                    <br><span class="badge bg-light text-dark">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($msg['username']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                            <td>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars(substr($msg['message'], 0, 100)) . (strlen($msg['message']) > 100 ? '...' : ''); ?>
                                </small>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="message_detail.php?id=<?php echo urlencode($msg['message_id']); ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
            <div style="padding: 2rem 1.5rem; text-align: center; border-top: 1px solid var(--admin-border); display: flex; justify-content: center; gap: .5rem;">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="?status=<?php echo urlencode($status_filter); ?>&page=<?php echo $i; ?>" 
                       class="btn <?php echo $page === $i ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="padding: 3rem; text-align: center; color: var(--admin-text-muted);">
            <i class="fas fa-envelope-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <p>No messages found.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
