<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
$page_title = 'Privacy Policy';
$page_description = 'Privacy policy for Randy\'s Painting & Drywall Services — how we collect, use, and protect your personal information.';
require __DIR__ . '/includes/header.php';
$b = business_info();
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Privacy Policy</nav>
            <span class="eyebrow">Legal</span>
            <h1 style="margin-top:1rem">Privacy Policy</h1>
            <p>Your privacy matters to us. This policy explains how we collect, use, and protect your personal information when you contact us or use our website.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container" style="max-width:780px">

            <p style="color:var(--text-muted);margin-bottom:2rem">Last updated: June 2026</p>

            <h2>Our Commitment to Your Privacy</h2>
            <p>Randy's Painting &amp; Drywall Services is committed to protecting your privacy. We have developed this policy so you understand how we collect, use, and handle personal information provided to us through this website or through direct contact.</p>

            <h2 style="margin-top:2.5rem">Information We Collect</h2>
            <p>We collect personal information only when you voluntarily provide it — for example, when you request a quote, send us a message, book an appointment, or register an account. This may include:</p>
            <ul style="margin:1rem 0 1rem 1.5rem;line-height:2">
                <li>Your name and contact details (phone number, email address)</li>
                <li>Your property address (for estimating and scheduling purposes)</li>
                <li>Details about your project that you share with us</li>
                <li>Messages or correspondence you send through our website or chat</li>
            </ul>

            <h2 style="margin-top:2.5rem">How We Use Your Information</h2>
            <p>We use the information you provide solely to respond to your inquiry, prepare estimates, schedule work, and follow up on completed projects. We do not sell, rent, or share your personal information with third parties for marketing purposes. Specifically:</p>
            <ul style="margin:1rem 0 1rem 1.5rem;line-height:2">
                <li>We identify the purpose for collecting information before or at the time of collection.</li>
                <li>We collect and use personal information only to fulfill the purposes you agreed to.</li>
                <li>We retain your information only as long as necessary to complete and support your project.</li>
                <li>We collect information by lawful and fair means, with your knowledge or consent.</li>
                <li>We keep your data accurate, complete, and up to date.</li>
                <li>We protect your information against unauthorized access, disclosure, copying, or modification using reasonable security measures.</li>
            </ul>

            <h2 style="margin-top:2.5rem">SMS &amp; Communication</h2>
            <p>If you provide your phone number, we may use it to send appointment reminders, follow-up messages, or estimates via SMS. Message frequency varies. Standard message and data rates may apply. You can opt out at any time by replying STOP to any message or by contacting us directly.</p>

            <h2 style="margin-top:2.5rem">Cookies &amp; Website Analytics</h2>
            <p>Our website may use basic analytics to understand how visitors find and use the site. This information is aggregated and does not identify individual users. We do not use tracking cookies for advertising purposes.</p>

            <h2 style="margin-top:2.5rem">Your Rights</h2>
            <p>You have the right to request access to the personal information we hold about you, to ask us to correct inaccurate information, or to request that we delete your information. To make any of these requests, contact us at the details below.</p>

            <h2 style="margin-top:2.5rem">Contact Us</h2>
            <p>If you have questions about this privacy policy or how we handle your information, please reach out:</p>
            <ul style="margin:1rem 0 1rem 1.5rem;line-height:2.2">
                <li><strong>Business:</strong> <?= e($b['name']) ?></li>
                <li><strong>Phone:</strong> <a href="tel:<?= e($b['phoneTel']) ?>"><?= e($b['phone']) ?></a></li>
                <li><strong>Email:</strong> <a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></li>
                <li><strong>Location:</strong> Easton, PA — serving the Lehigh Valley &amp; Bucks County, PA</li>
            </ul>

        </div>
    </section>

    <?php mkt_cta_band('Questions about your data?', 'We\'re happy to help.', 'Reach out any time — by phone, email, or the contact form.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
