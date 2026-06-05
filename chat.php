<?php
require_once __DIR__ . '/includes/app.php';

// Admins use the dashboard, not the customer chat.
if (is_admin()) {
    redirect('admin/index.php');
}

$page_title = 'Chat';
require __DIR__ . '/includes/header.php';
?>
<div class="chat-page" data-chat-page>
    <div class="chat-shell">
        <div class="chat-body" data-chat-body></div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
