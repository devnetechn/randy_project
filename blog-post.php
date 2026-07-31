<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
require_once __DIR__ . '/includes/blog.php';

$id = (int) ($_GET['id'] ?? 0);
$post = $id ? blog_find_published($id) : null;

if (!$post) {
    http_response_code(404);
    $page_title = 'Post not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="mkt">
        <section class="page-hero">
            <div class="page-hero__bg" aria-hidden="true"></div>
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> <a href="<?= e(url('blog.php')) ?>">Blog</a><span>/</span> Not found</nav>
                <h1 style="margin-top:1rem">We couldn&apos;t find that post</h1>
                <p>It may have been removed or isn&apos;t published yet.</p>
                <div style="margin-top:2rem"><a href="<?= e(url('blog.php')) ?>" class="btn btn--slate">Back to the blog<?= svg_arrow() ?></a></div>
            </div>
        </section>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $post['title'];
$page_description = $post['excerpt'] ?: 'Painting and drywall tips from Randy\'s Painting & Drywall Services, serving Easton, PA and the Lehigh Valley.';
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero page-hero--project">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> <a href="<?= e(url('blog.php')) ?>">Blog</a><span>/</span> <?= e($post['title']) ?></nav>
            <span class="eyebrow">From our blog</span>
            <h1 style="margin-top:1rem"><?= e($post['title']) ?></h1>
            <p><?= e(blog_date($post['created_at'])) ?></p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container blog-article">
            <?php if ($post['image']): ?>
                <div class="blog-article__img"><img src="<?= e(blog_image_url($post['image'])) ?>" alt="<?= e($post['title']) ?>"></div>
            <?php endif; ?>
            <div class="blog-article__body">
                <?= blog_render_body($post['body']) ?>
            </div>
            <div style="margin-top:2rem"><a href="<?= e(url('blog.php')) ?>" class="btn btn--slate">&larr; Back to the blog</a></div>
        </div>
    </section>

    <?php if (!empty($post['faqs'])): ?>
        <?php mkt_faq_custom(array_map(fn($pair) => [$pair['q'], $pair['a']], $post['faqs']), 'Frequently asked questions'); ?>
    <?php endif; ?>

    <?php mkt_cta_band("Let's talk", 'Ready to start your project?', 'Free estimates across the Lehigh Valley and Bucks County, PA.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
