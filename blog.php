<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
require_once __DIR__ . '/includes/blog.php';
$page_title = 'Blog — Painting & Drywall Tips for the Lehigh Valley';
$page_description = 'Painting and drywall tips, project stories, and advice for homeowners in Easton, PA and the Lehigh Valley, from Randy\'s Painting & Drywall Services.';
$page_keywords = 'painting tips Easton PA, drywall repair tips, Lehigh Valley painting blog, how to paint a room, house painting advice';
$posts = blog_published();
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Blog</nav>
            <span class="eyebrow">From our blog</span>
            <h1 style="margin-top:1rem">Painting &amp; drywall tips for the <span class="ul-brush">Lehigh Valley</span></h1>
            <p>Advice, project stories, and answers to the questions we hear most from homeowners in Easton, PA and across the Lehigh Valley.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <?php if ($posts): ?>
                <div class="blog-search">
                    <span class="blog-search__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
                    </span>
                    <input type="search" id="blogSearch" class="blog-search__input" placeholder="Search posts&hellip; (e.g. drywall, ceiling)" aria-label="Search blog posts" autocomplete="off">
                </div>
                <div class="blog-grid" id="blogGrid">
                    <?php foreach ($posts as $post): ?>
                        <a class="blog-card" href="<?= e(url('blog-post.php?id=' . (int) $post['id'])) ?>" data-search="<?= e(strtolower($post['title'] . ' ' . ($post['excerpt'] ?? '') . ' ' . strip_tags($post['body']))) ?>">
                            <div class="blog-card__img">
                                <?php if ($post['image']): ?>
                                    <img src="<?= e(blog_image_url($post['image'])) ?>" alt="">
                                <?php else: ?>
                                    <div class="ph ph--warm"><span class="ph__tag">Blog</span></div>
                                <?php endif; ?>
                            </div>
                            <div class="blog-card__body">
                                <div class="blog-card__date"><?= e(blog_date($post['created_at'])) ?></div>
                                <h2 class="blog-card__title"><?= e($post['title']) ?></h2>
                                <?php if ($post['excerpt']): ?><p class="blog-card__excerpt"><?= e($post['excerpt']) ?></p><?php endif; ?>
                                <span class="blog-card__more">Read more<?= svg_arrow() ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="blog-noresults" id="blogNoResults" hidden>
                    <p>No posts match <strong id="blogNoResultsTerm"></strong> — try another keyword.</p>
                </div>
            <?php else: ?>
                <div class="center" style="padding:3rem 1rem;color:var(--muted)">
                    <p>No posts yet — check back soon for painting and drywall tips!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php mkt_cta_band("Let's talk", 'Ready to start your project?', 'Free estimates in Easton, PA and across the Lehigh Valley, within 25 miles.'); ?>
</div>
<?php if ($posts): ?><script src="<?= e(url('assets/js/blog-search.js')) ?>"></script><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
