<?php
require_once __DIR__ . '/../includes/app.php';
$u = require_admin_page();
$b = business_info();

$page_title = 'Admin Dashboard';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-sidebar__brand" href="<?= e(url('admin/index.php')) ?>">
            <img src="<?= e(url('assets/img/logo.png')) ?>" alt="<?= e($b['name']) ?>">
            <span><?= e($b['name']) ?></span>
        </a>

        <div class="tabs" data-tabs role="tablist">
            <button class="tab is-active" data-tab="overview" role="tab">Overview</button>
            <button class="tab" data-tab="chat" role="tab">Live Chat</button>
            <button class="tab" data-tab="leads" role="tab">CRM / Leads</button>
            <button class="tab" data-tab="bookings" role="tab">Bookings</button>
            <button class="tab" data-tab="reports" role="tab">Reports</button>
            <button class="tab" data-tab="gallery" role="tab">Gallery</button>
            <button class="tab" data-tab="blog" role="tab">Blog</button>
            <button class="tab" data-tab="reviews" role="tab">Reviews</button>
            <button class="tab" data-tab="careers" role="tab">Careers</button>
        </div>

        <div class="admin-sidebar__foot">
            <span class="admin-sidebar__user"><?= e($u['email']) ?></span>
            <a href="<?= e(url('logout.php')) ?>">Log out</a>
        </div>
    </aside>

    <div class="admin-content">
        <h1 class="app-title">Admin Dashboard</h1>
        <p class="app-sub">Manage chats, bookings, the gallery, and the blog.</p>

        <div data-panel="overview"></div>
        <div data-panel="chat" hidden></div>
        <div data-panel="leads" hidden></div>
        <div data-panel="bookings" hidden></div>
        <div data-panel="reports" hidden></div>
        <div data-panel="gallery" hidden></div>
        <div data-panel="blog" hidden></div>
        <div data-panel="reviews" hidden></div>
        <div data-panel="careers" hidden></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= e(url('assets/js/admin.js')) ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/js/admin.js') ?>"></script>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
