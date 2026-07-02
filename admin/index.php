<?php
require_once __DIR__ . '/../includes/app.php';
require_admin_page();

$page_title = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin">
    <h1 class="app-title">Admin Dashboard</h1>
    <p class="app-sub">Manage chats, bookings, the gallery, and the blog.</p>

    <div class="tabs" data-tabs role="tablist">
        <button class="tab is-active" data-tab="overview" role="tab">Overview</button>
        <button class="tab" data-tab="chat" role="tab">Live Chat</button>
        <button class="tab" data-tab="leads" role="tab">CRM / Leads</button>
        <button class="tab" data-tab="bookings" role="tab">Bookings</button>
        <button class="tab" data-tab="reports" role="tab">Reports</button>
        <button class="tab" data-tab="gallery" role="tab">Gallery</button>
        <button class="tab" data-tab="blog" role="tab">Blog</button>
        <button class="tab" data-tab="reviews" role="tab">Reviews</button>
    </div>

    <div data-panel="overview"></div>
    <div data-panel="chat" hidden></div>
    <div data-panel="leads" hidden></div>
    <div data-panel="bookings" hidden></div>
    <div data-panel="reports" hidden></div>
    <div data-panel="gallery" hidden></div>
    <div data-panel="blog" hidden></div>
    <div data-panel="reviews" hidden></div>
</div>
<script src="<?= e(url('assets/js/admin.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
